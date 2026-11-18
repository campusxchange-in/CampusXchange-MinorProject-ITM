<?php
declare(strict_types=1);
/* Presence + message-delivery helpers (CX/backend/user/_presence_helpers.php)
 *
 * ONLINE  = the user's last_seen_at (User_Presence) is not older than
 *           CX_ONLINE_WINDOW_SECONDS. last_seen_at is refreshed
 *             - by user_auth_check.php on any logged-in API call (throttled), and
 *             - by presence_ping.php, which the Navbar calls every ~20 s while the
 *               tab is visible.
 *           Logging out pushes last_seen_at back so the user is shown offline at once.
 * All ages are computed by MySQL (TIMESTAMPDIFF ... NOW()), so PHP / DB timezone
 * differences can never make someone look online forever.
 */

const CX_ONLINE_WINDOW_SECONDS = 50;
const CX_PRESENCE_THROTTLE_SECONDS = 15;

function cx_presence_touch(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare(
        "INSERT INTO User_Presence (user_id, last_seen_at) VALUES (?, NOW())
         ON DUPLICATE KEY UPDATE last_seen_at = NOW()"
    );
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
}

/* Same as touch, but at most once per CX_PRESENCE_THROTTLE_SECONDS per session. */
function cx_presence_touch_throttled(mysqli $conn, int $userId): void
{
    $last = (int)($_SESSION['presence_touched_at'] ?? 0);
    if (time() - $last < CX_PRESENCE_THROTTLE_SECONDS) {
        return;
    }
    $_SESSION['presence_touched_at'] = time();
    cx_presence_touch($conn, $userId);
}

/* Called on logout: keep a believable "last seen" but make the user offline now. */
function cx_presence_set_offline(mysqli $conn, int $userId): void
{
    $window = CX_ONLINE_WINDOW_SECONDS + 10;
    $stmt = $conn->prepare(
        "UPDATE User_Presence
         SET last_seen_at = LEAST(COALESCE(last_seen_at, NOW()), NOW() - INTERVAL $window SECOND)
         WHERE user_id = ?"
    );
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
}

/* Delivery ack: this user's device is online, so every message sent TO them
 * (in any of their chats) counts as delivered. */
function cx_mark_all_delivered(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare(
        "UPDATE Messages m
         INNER JOIN Chats c ON c.chat_id = m.chat_id
         SET m.delivered_at = NOW()
         WHERE (c.buyer_id = ? OR c.seller_id = ?)
           AND m.sender_id <> ?
           AND m.delivered_at IS NULL
           AND m.deleted_at IS NULL"
    );
    if ($stmt) {
        $stmt->bind_param('iii', $userId, $userId, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

/* 'sent' (stored on the server) -> 'delivered' (receiver's app fetched it) -> 'read' */
function cx_message_status(?string $deliveredAt, ?string $readAt): string
{
    if ($readAt !== null) {
        return 'read';
    }
    return $deliveredAt !== null ? 'delivered' : 'sent';
}
