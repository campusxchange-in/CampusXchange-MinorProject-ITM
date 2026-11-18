<?php

/* =====================================================
   PAGE CONFIGURATION
===================================================== */

$page_title = 'Notifications | CampusXchange';
$page_heading = 'Notifications';
$page_description = 'View platform alerts and administrator notifications.';
$active_page = 'notifications';
$page_css = 'notifications.css';


/* =====================================================
   AUTHENTICATION
===================================================== */

include 'includes/auth_check.php';


/* =====================================================
   DATABASE CONNECTION
===================================================== */

include '../server/database/db_connect.php';


/* =====================================================
   NOTIFICATION HELPER
===================================================== */

include_once __DIR__ . '/../server/helpers/notification_helper.php';


/* =====================================================
   CURRENT ADMIN FOR NOTIFICATIONS
===================================================== */

$notification_admin_id = (int)(
    $_SESSION['admin_id'] ?? 0
);


if ($notification_admin_id <= 0) {

    header(
        "Location: " . FRONTEND_URL . "/admin-login"
    );

    exit;
}


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [

    [
        'label' => 'Notifications'
    ]

];


/* =====================================================
   FILTERS
===================================================== */

$search = trim(
    $_GET['search'] ?? ''
);

$type = $_GET['type'] ?? 'All';

$status = $_GET['status'] ?? 'All';


$allowed_types = admin_notification_types();


$allowed_statuses = [

    'All',
    'Read',
    'Unread'

];


if (
    $type !== 'All' &&
    !in_array(
        $type,
        $allowed_types,
        true
    )
) {

    $type = 'All';

}


if (!in_array(
    $status,
    $allowed_statuses,
    true
)) {

    $status = 'All';

}


/* =====================================================
   MARK ALL AS READ
===================================================== */

if (
    isset($_GET['mark_all_read']) &&
    $_GET['mark_all_read'] === '1'
) {

    $placeholders = implode(
        ',',
        array_fill(
            0,
            count($allowed_types),
            '?'
        )
    );


    $type_string = str_repeat(
        's',
        count($allowed_types)
    );


    $sql = "
        UPDATE Notifications

        SET
            is_read = TRUE,
            read_at = NOW()

        WHERE admin_id = ?

        AND user_id IS NULL

        AND notification_type IN ($placeholders)

        AND is_read = FALSE

        AND deleted_at IS NULL
    ";


    $stmt = $conn->prepare($sql);


    if ($stmt) {

        $bind_types = 'i' . $type_string;


        $params = [
            $bind_types,
            $notification_admin_id
        ];


        foreach ($allowed_types as $notification_type) {

            $params[] = $notification_type;

        }


        $bind_names = [];


        foreach ($params as $key => $value) {

            $bind_names[$key] = &$params[$key];

        }


        call_user_func_array(
            [$stmt, 'bind_param'],
            $bind_names
        );


        $stmt->execute();

        $stmt->close();

    }


    header("Location: notifications");
    exit;

}


/* =====================================================
   STATISTICS
===================================================== */

$total_notifications = 0;
$unread_notifications = 0;
$today_notifications = 0;
$security_notifications = 0;


/* =====================================================
   STATISTICS TYPES
===================================================== */

$admin_types = admin_notification_types();


$type_placeholders = implode(
    ',',
    array_fill(
        0,
        count($admin_types),
        '?'
    )
);


$type_bind_string = str_repeat(
    's',
    count($admin_types)
);


/* =====================================================
   TOTAL NOTIFICATIONS
===================================================== */

$sql = "
    SELECT COUNT(*)

    FROM Notifications

    WHERE admin_id = ?

    AND user_id IS NULL

    AND notification_type IN ($type_placeholders)

    AND deleted_at IS NULL
";


$stmt = $conn->prepare($sql);


if ($stmt) {

    $bind_types = 'i' . $type_bind_string;


    $params = [
        $bind_types,
        $notification_admin_id
    ];


    foreach ($admin_types as $notification_type) {

        $params[] = $notification_type;

    }


    $bind_names = [];


    foreach ($params as $key => $value) {

        $bind_names[$key] = &$params[$key];

    }


    call_user_func_array(
        [$stmt, 'bind_param'],
        $bind_names
    );


    $stmt->execute();


    $stmt->bind_result(
        $total_notifications
    );


    $stmt->fetch();

    $stmt->close();

}


/* =====================================================
   UNREAD NOTIFICATIONS
===================================================== */

$sql = "
    SELECT COUNT(*)

    FROM Notifications

    WHERE admin_id = ?

    AND user_id IS NULL

    AND notification_type IN ($type_placeholders)

    AND is_read = FALSE

    AND deleted_at IS NULL
";


$stmt = $conn->prepare($sql);


if ($stmt) {

    $bind_types = 'i' . $type_bind_string;


    $params = [
        $bind_types,
        $notification_admin_id
    ];


    foreach ($admin_types as $notification_type) {

        $params[] = $notification_type;

    }


    $bind_names = [];


    foreach ($params as $key => $value) {

        $bind_names[$key] = &$params[$key];

    }


    call_user_func_array(
        [$stmt, 'bind_param'],
        $bind_names
    );


    $stmt->execute();


    $stmt->bind_result(
        $unread_notifications
    );


    $stmt->fetch();

    $stmt->close();

}


/* =====================================================
   TODAY NOTIFICATIONS
===================================================== */

$sql = "
    SELECT COUNT(*)

    FROM Notifications

    WHERE admin_id = ?

    AND user_id IS NULL

    AND notification_type IN ($type_placeholders)

    AND DATE(created_at) = CURDATE()

    AND deleted_at IS NULL
";


$stmt = $conn->prepare($sql);


if ($stmt) {

    $bind_types = 'i' . $type_bind_string;


    $params = [
        $bind_types,
        $notification_admin_id
    ];


    foreach ($admin_types as $notification_type) {

        $params[] = $notification_type;

    }


    $bind_names = [];


    foreach ($params as $key => $value) {

        $bind_names[$key] = &$params[$key];

    }


    call_user_func_array(
        [$stmt, 'bind_param'],
        $bind_names
    );


    $stmt->execute();


    $stmt->bind_result(
        $today_notifications
    );


    $stmt->fetch();

    $stmt->close();

}


/* =====================================================
   SECURITY NOTIFICATIONS
===================================================== */

$stmt = $conn->prepare("
    SELECT COUNT(*)

    FROM Notifications

    WHERE admin_id = ?

    AND user_id IS NULL

    AND notification_type = 'Security'

    AND deleted_at IS NULL
");


if ($stmt) {

    $stmt->bind_param(
        "i",
        $notification_admin_id
    );


    $stmt->execute();


    $stmt->bind_result(
        $security_notifications
    );


    $stmt->fetch();

    $stmt->close();

}


/* =====================================================
   PAGINATION
===================================================== */

$per_page = 10;


$page = max(
    1,
    (int)($_GET['page'] ?? 1)
);


/* =====================================================
   WHERE CLAUSE
===================================================== */

$where = [

    "admin_id = ?",

    "user_id IS NULL",

    "deleted_at IS NULL",

    "notification_type IN ($type_placeholders)"

];


$types = "i" . $type_bind_string;


$params = [

    $notification_admin_id

];


foreach ($admin_types as $notification_type) {

    $params[] = $notification_type;

}


/* =====================================================
   SEARCH
===================================================== */

if ($search !== '') {

    $where[] = "
        (
            title LIKE ?
            OR message LIKE ?
        )
    ";


    $search_value = "%" . $search . "%";


    $types .= "ss";

    $params[] = $search_value;
    $params[] = $search_value;

}


/* =====================================================
   TYPE FILTER
===================================================== */

if ($type !== 'All') {

    $where[] = "notification_type = ?";

    $types .= "s";

    $params[] = $type;

}


/* =====================================================
   STATUS FILTER
===================================================== */

if ($status === 'Read') {

    $where[] = "is_read = TRUE";

}


if ($status === 'Unread') {

    $where[] = "is_read = FALSE";

}


$where_sql = implode(
    " AND ",
    $where
);


/* =====================================================
   TOTAL FILTERED RECORDS
===================================================== */

$count_sql = "
    SELECT COUNT(*)

    FROM Notifications

    WHERE $where_sql
";


$stmt = $conn->prepare(
    $count_sql
);


$bind_params = [$types];


foreach ($params as $key => $value) {

    $bind_params[] = &$params[$key];

}


call_user_func_array(
    [$stmt, 'bind_param'],
    $bind_params
);


$stmt->execute();


$stmt->bind_result(
    $filtered_total
);


$stmt->fetch();

$stmt->close();


/* =====================================================
   TOTAL PAGES
===================================================== */

$total_pages = max(
    1,
    (int)ceil(
        $filtered_total / $per_page
    )
);


if ($page > $total_pages) {

    $page = $total_pages;

}


$offset = ($page - 1) * $per_page;


/* =====================================================
   FETCH NOTIFICATIONS
===================================================== */

$sql = "
    SELECT

        notification_id,
        notification_type,
        title,
        message,
        reference_type,
        reference_id,
        is_read,
        created_at,
        read_at

    FROM Notifications

    WHERE $where_sql

    ORDER BY
        is_read ASC,
        created_at DESC

    LIMIT ? OFFSET ?
";


$stmt = $conn->prepare($sql);


$final_types = $types . "ii";


$final_params = $params;

$final_params[] = $per_page;
$final_params[] = $offset;


$bind_params = [$final_types];


foreach ($final_params as $key => $value) {

    $bind_params[] = &$final_params[$key];

}


call_user_func_array(
    [$stmt, 'bind_param'],
    $bind_params
);


$stmt->execute();


$result = $stmt->get_result();


$notifications = [];


while ($row = $result->fetch_assoc()) {

    $notifications[] = $row;

}


$stmt->close();


/* =====================================================
   PAGE URL HELPER
===================================================== */

function build_page_url($page_number)
{

    $query = $_GET;

    $query['page'] = $page_number;

    return '?' . http_build_query($query);

}


/* =====================================================
   NOTIFICATION ICON
===================================================== */

function notification_icon($type)
{

    return match ($type) {

        'Verification' => 'fa-circle-check',
        'Product'      => 'fa-box',
        'Report'       => 'fa-flag',
        'Ban'          => 'fa-user-slash',
        'Security'     => 'fa-shield-halved',
        'System'       => 'fa-gear',

        default        => 'fa-bell'

    };

}


/* =====================================================
   HEADER
===================================================== */

include 'includes/admin_header.php';

?>


<div class="admin-page notifications-page">


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-bell"></i>

            </div>

            <div>

                <span class="stat-label">
                    Total
                </span>

                <strong>
                    <?= number_format($total_notifications) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-envelope"></i>

            </div>

            <div>

                <span class="stat-label">
                    Unread
                </span>

                <strong>
                    <?= number_format($unread_notifications) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-calendar-day"></i>

            </div>

            <div>

                <span class="stat-label">
                    Today
                </span>

                <strong>
                    <?= number_format($today_notifications) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">

                <i class="fa-solid fa-shield-halved"></i>

            </div>

            <div>

                <span class="stat-label">
                    Security
                </span>

                <strong>
                    <?= number_format($security_notifications) ?>
                </strong>

            </div>

        </div>


    </div>


    <!-- =================================================
         FILTERS
    ================================================== -->

    <div class="filter-card">

        <form
            method="GET"
            class="filter-form"
        >


            <div class="filter-group search-group">

                <label for="search">
                    Search
                </label>

                <div class="input-with-icon">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search notifications..."
                    >

                </div>

            </div>


            <div class="filter-group">

                <label for="type">
                    Type
                </label>

                <select
                    id="type"
                    name="type"
                >

                    <option value="All">
                        All Types
                    </option>


                    <?php foreach ($allowed_types as $notification_type): ?>

                        <option
                            value="<?= htmlspecialchars($notification_type) ?>"
                            <?= ($type === $notification_type) ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars($notification_type) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="filter-group">

                <label for="status">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                >

                    <option value="All">
                        All
                    </option>


                    <option
                        value="Unread"
                        <?= ($status === 'Unread') ? 'selected' : '' ?>
                    >
                        Unread
                    </option>


                    <option
                        value="Read"
                        <?= ($status === 'Read') ? 'selected' : '' ?>
                    >
                        Read
                    </option>

                </select>

            </div>


            <div class="filter-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="fa-solid fa-filter"></i>

                    Filter

                </button>


                <a
                    href="notifications"
                    class="btn btn-secondary"
                >
                    Reset
                </a>

            </div>


        </form>

    </div>


    <!-- =================================================
         NOTIFICATIONS CARD
    ================================================== -->

    <div class="notifications-card">


        <div class="card-header">

            <div>

                <h2>
                    Notifications
                </h2>

                <p>

                    <?= number_format($filtered_total) ?>

                    notification<?= ($filtered_total == 1) ? '' : 's' ?>

                </p>

            </div>


            <?php if ($unread_notifications > 0): ?>

                <a
                    href="notifications?mark_all_read=1"
                    class="btn btn-primary"
                >

                    <i class="fa-solid fa-check-double"></i>

                    Mark All as Read

                </a>

            <?php endif; ?>

        </div>


        <?php if (empty($notifications)): ?>


            <div class="empty-state">

                <div class="empty-icon">

                    <i class="fa-regular fa-bell-slash"></i>

                </div>


                <h3>
                    No notifications found
                </h3>


                <p>
                    There are no notifications matching your current filters.
                </p>

            </div>


        <?php else: ?>


            <div class="notification-list">


                <?php foreach ($notifications as $notification): ?>


                    <div
                        class="notification-item <?= $notification['is_read'] ? 'read' : 'unread' ?>"
                    >


                        <div class="notification-icon">

                            <i class="fa-solid <?= notification_icon($notification['notification_type']) ?>"></i>

                        </div>


                        <div class="notification-content">


                            <div class="notification-top">

                                <div>

                                    <span class="notification-type">

                                        <?= htmlspecialchars(
                                            $notification['notification_type']
                                        ) ?>

                                    </span>


                                    <?php if (!$notification['is_read']): ?>

                                        <span class="unread-badge">
                                            Unread
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <span class="notification-time">

                                    <?= htmlspecialchars(
                                        date(
                                            'd M Y, h:i A',
                                            strtotime(
                                                $notification['created_at']
                                            )
                                        )
                                    ) ?>

                                </span>

                            </div>


                            <!-- =========================================
                                 NOTIFICATION CLICK
                            ========================================== -->

                            <form
                                method="POST"
                                action="notification_action.php"
                                class="notification-click-form"
                            >

                                <input
                                    type="hidden"
                                    name="notification_id"
                                    value="<?= (int)$notification['notification_id'] ?>"
                                >


                                <input
                                    type="hidden"
                                    name="action"
                                    value="notification_click"
                                >


                                <button
                                    type="submit"
                                    class="notification-main-button"
                                >

                                    <h3>

                                        <?= htmlspecialchars(
                                            $notification['title']
                                        ) ?>

                                    </h3>


                                    <p>

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $notification['message']
                                            )
                                        ) ?>

                                    </p>

                                </button>

                            </form>


                            <!-- =========================================
                                 MARK READ
                            ========================================== -->

                            <?php if (!$notification['is_read']): ?>

                                <div class="notification-actions">

                                    <form
                                        method="POST"
                                        action="notification_action.php"
                                        class="notification-action-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="notification_id"
                                            value="<?= (int)$notification['notification_id'] ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="mark_read"
                                        >


                                        <button
                                            type="submit"
                                            class="text-button"
                                        >

                                            <i class="fa-solid fa-check"></i>

                                            Mark Read

                                        </button>

                                    </form>

                                </div>

                            <?php endif; ?>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


            <!-- =================================================
                 PAGINATION
            ================================================== -->

            <?php if ($total_pages > 1): ?>

                <div class="pagination">


                    <?php if ($page > 1): ?>

                        <a
                            href="<?= htmlspecialchars(
                                build_page_url($page - 1)
                            ) ?>"
                            class="pagination-link"
                        >

                            <i class="fa-solid fa-chevron-left"></i>

                        </a>

                    <?php endif; ?>


                    <?php for (
                        $i = 1;
                        $i <= $total_pages;
                        $i++
                    ): ?>


                        <?php if (
                            $i === 1 ||
                            $i === $total_pages ||
                            abs($i - $page) <= 2
                        ): ?>

                            <a
                                href="<?= htmlspecialchars(
                                    build_page_url($i)
                                ) ?>"
                                class="pagination-link <?= ($i === $page) ? 'active' : '' ?>"
                            >

                                <?= $i ?>

                            </a>


                        <?php elseif (
                            $i === 2 &&
                            $page > 4
                        ): ?>

                            <span class="pagination-dots">
                                ...
                            </span>


                        <?php elseif (
                            $i === $total_pages - 1 &&
                            $page < $total_pages - 3
                        ): ?>

                            <span class="pagination-dots">
                                ...
                            </span>

                        <?php endif; ?>


                    <?php endfor; ?>


                    <?php if ($page < $total_pages): ?>

                        <a
                            href="<?= htmlspecialchars(
                                build_page_url($page + 1)
                            ) ?>"
                            class="pagination-link"
                        >

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    <?php endif; ?>


                </div>

            <?php endif; ?>


        <?php endif; ?>


    </div>


</div>


<?php include 'includes/admin_footer.php'; ?>