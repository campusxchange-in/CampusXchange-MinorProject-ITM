<?php
/* POST CX/backend/user/presence_ping.php
 * Heartbeat. The Navbar calls it every ~20 s while the tab is visible, so
 * "online" really means "has the app open right now". Needs a valid session:
 * after logout the middleware answers 401 and nothing is recorded. */
require_once __DIR__ . '/_user_common.php';
require_once __DIR__ . '/_presence_helpers.php';
cx_require_method('POST');

cx_presence_touch($conn, $userId);
cx_mark_all_delivered($conn, $userId);

cx_json_response(true, 'OK', ['online_window' => CX_ONLINE_WINDOW_SECONDS]);
