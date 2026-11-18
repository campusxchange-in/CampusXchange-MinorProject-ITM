<?php

/* =====================================================
   AUTHENTICATION
===================================================== */

include 'includes/auth_check.php';


/* =====================================================
   PAGE VARIABLES
===================================================== */

$page_title = 'Dashboard | CampusXchange';
$page_heading = 'Dashboard';
$active_page = 'dashboard';
$page_css = 'dashboard.css';


/* =====================================================
   DATABASE CONNECTION
===================================================== */

include '../server/database/db_connect.php';


/* =====================================================
   GET LOGGED-IN ADMIN
===================================================== */

$admin_id = $_SESSION["admin_id"] ?? 0;

$stmt = $conn->prepare("
    SELECT
        admin_id,
        full_name,
        email,
        admin_role,
        status,
        created_at
    FROM Admins
    WHERE admin_id = ?
      AND status = 'Active'
      AND deleted_at IS NULL
    LIMIT 1
");

$stmt->bind_param("i", $admin_id);
$stmt->execute();

$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   ADMIN NO LONGER EXISTS / ACTIVE
===================================================== */

if (!$admin) {

    session_unset();
    session_destroy();

    include '../config/app_config.php';

    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}


/* =====================================================
   DATE HELPERS
===================================================== */

$today = new DateTime();

$today_date = $today->format('Y-m-d');

$seven_days_ago = (clone $today)
    ->modify('-6 days')
    ->format('Y-m-d');

$previous_seven_start = (clone $today)
    ->modify('-13 days')
    ->format('Y-m-d');

$previous_seven_end = (clone $today)
    ->modify('-7 days')
    ->format('Y-m-d');


/* =====================================================
   HELPER FUNCTIONS
===================================================== */

function dashboard_count($conn, $sql, $types = '', $params = [])
{
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int)($row['total'] ?? 0);
}


function dashboard_format_time($datetime)
{
    if (empty($datetime)) {
        return '';
    }

    $time = strtotime($datetime);

    if (!$time) {
        return '';
    }

    $diff = time() - $time;

    if ($diff < 60) {
        return 'Just now';
    }

    if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';
    }

    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
    }

    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }

    return date('d M Y, h:i A', $time);
}


function dashboard_status_class($status)
{
    $status = strtolower(trim($status));

    $status = str_replace(
        [' ', '_'],
        '-',
        $status
    );

    return preg_replace(
        '/[^a-z0-9\-]/',
        '',
        $status
    );
}


/* =====================================================
   DASHBOARD STATISTICS
===================================================== */


/* Active Listings */

$active_listings = dashboard_count(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM Products
        WHERE status = 'Active'
          AND deleted_at IS NULL
    "
);


/* Verified Users */

$verified_users = dashboard_count(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM User_Verification
        WHERE verification_status = 'Approved'
          AND deleted_at IS NULL
    "
);


/* Completed Deals */

$completed_deals = dashboard_count(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM Transactions
        WHERE transaction_status = 'Completed'
          AND deleted_at IS NULL
    "
);


/* Open Reports */

$open_reports = dashboard_count(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM Reports
        WHERE status IN ('Pending', 'Under Review')
          AND deleted_at IS NULL
    "
);


/* =====================================================
   CURRENT PERIOD ACTIVITY
===================================================== */


/* Listings posted in last 7 days */

$new_listings_7_days = dashboard_count(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM Products
        WHERE created_at >= ?
          AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
          AND deleted_at IS NULL
    ",
    "ss",
    [
        $seven_days_ago,
        $today_date
    ]
);


/* Users verified in last 7 days */

$new_verified_7_days = dashboard_count(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM User_Verification
        WHERE verification_status = 'Approved'
          AND verified_at >= ?
          AND verified_at < DATE_ADD(?, INTERVAL 1 DAY)
          AND deleted_at IS NULL
    ",
    "ss",
    [
        $seven_days_ago,
        $today_date
    ]
);


/* Deals completed in last 7 days */

$new_deals_7_days = dashboard_count(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM Transactions
        WHERE transaction_status = 'Completed'
          AND transaction_date >= ?
          AND transaction_date < DATE_ADD(?, INTERVAL 1 DAY)
          AND deleted_at IS NULL
    ",
    "ss",
    [
        $seven_days_ago,
        $today_date
    ]
);


/* Reports created in last 7 days */

$new_reports_7_days = dashboard_count(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM Reports
        WHERE created_at >= ?
          AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
          AND deleted_at IS NULL
    ",
    "ss",
    [
        $seven_days_ago,
        $today_date
    ]
);


/* =====================================================
   DASHBOARD STAT CARDS
===================================================== */

$dashboard_stats = [

    [
        'title' => 'Active Listings',
        'value' => number_format($active_listings),
        'change' => '+' . number_format($new_listings_7_days) . ' this week',
        'change_type' => 'positive',
        'icon' => 'fa-box'
    ],

    [
        'title' => 'Verified Users',
        'value' => number_format($verified_users),
        'change' => '+' . number_format($new_verified_7_days) . ' this week',
        'change_type' => 'positive',
        'icon' => 'fa-users'
    ],

    [
        'title' => 'Deals Closed',
        'value' => number_format($completed_deals),
        'change' => '+' . number_format($new_deals_7_days) . ' this week',
        'change_type' => 'positive',
        'icon' => 'fa-handshake'
    ],

    [
        'title' => 'Open Reports',
        'value' => number_format($open_reports),
        'change' => '+' . number_format($new_reports_7_days) . ' new',
        'change_type' => 'negative',
        'icon' => 'fa-flag'
    ]

];


/* =====================================================
   WEEKLY LISTING DATA
   ALWAYS SHOW 7 DAYS
===================================================== */

$weekly_listings = [];


/* Initialize all seven days with zero */

for ($i = 6; $i >= 0; $i--) {

    $date = (clone $today)
        ->modify("-{$i} days");

    $key = $date->format('Y-m-d');

    $weekly_listings[$key] = [
        'label' => $date->format('D'),
        'value' => 0
    ];
}


/* Get actual listing counts */

$stmt = $conn->prepare("
    SELECT
        DATE(created_at) AS listing_date,
        COUNT(*) AS total
    FROM Products
    WHERE created_at >= ?
      AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
      AND deleted_at IS NULL
    GROUP BY DATE(created_at)
    ORDER BY listing_date ASC
");

$stmt->bind_param(
    "ss",
    $seven_days_ago,
    $today_date
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $listing_date = $row['listing_date'];

    if (isset($weekly_listings[$listing_date])) {

        $weekly_listings[$listing_date]['value'] =
            (int)$row['total'];

    }
}

$stmt->close();


$max_listing = 0;

foreach ($weekly_listings as $day) {

    if ($day['value'] > $max_listing) {
        $max_listing = $day['value'];
    }
}


/*
   Keep zero-data charts usable.
   The bar itself has a minimum height in CSS.
*/

$chart_max = max(
    $max_listing,
    1
);


/* =====================================================
   RECENT PLATFORM ACTIVITY
===================================================== */

$recent_activity = [];


/*
   Combine the most useful platform events:
   - New listings
   - Completed transactions
   - New reports
   - User verification
*/

$activity_sql = "

    SELECT
        'listing' AS activity_type,
        p.title AS activity_title,
        p.created_at AS activity_date,
        u.full_name AS user_name
    FROM Products p
    LEFT JOIN Users u
        ON u.user_id = p.seller_id
    WHERE p.deleted_at IS NULL

    UNION ALL

    SELECT
        'transaction' AS activity_type,
        CONCAT(
            'Transaction completed for \"',
            p.title,
            '\"'
        ) AS activity_title,
        t.transaction_date AS activity_date,
        u.full_name AS user_name
    FROM Transactions t
    INNER JOIN Products p
        ON p.product_id = t.product_id
    LEFT JOIN Users u
        ON u.user_id = t.buyer_id
    WHERE t.transaction_status = 'Completed'
      AND t.deleted_at IS NULL

    UNION ALL

    SELECT
        'report' AS activity_type,
        CONCAT(
            'A ',
            LOWER(r.report_type),
            ' report was submitted'
        ) AS activity_title,
        r.created_at AS activity_date,
        u.full_name AS user_name
    FROM Reports r
    LEFT JOIN Users u
        ON u.user_id = r.reporter_id
    WHERE r.deleted_at IS NULL

    UNION ALL

    SELECT
        'verification' AS activity_type,
        CONCAT(
            'User verification was ',
            LOWER(uv.verification_status)
        ) AS activity_title,
        COALESCE(
            uv.verified_at,
            uv.submitted_at,
            uv.created_at
        ) AS activity_date,
        u.full_name AS user_name
    FROM User_Verification uv
    LEFT JOIN Users u
        ON u.user_id = uv.user_id
    WHERE uv.deleted_at IS NULL

    ORDER BY activity_date DESC
    LIMIT 5
";


$result = $conn->query($activity_sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $activity_type = $row['activity_type'];

        $user_name = trim(
            $row['user_name'] ?? ''
        );

        $activity_title =
            $row['activity_title'] ?? 'Platform activity';


        /*
           Make activity descriptions more useful.
        */

        if ($activity_type === 'listing') {

            if ($user_name !== '') {

                $title =
                    htmlspecialchars($user_name)
                    . ' listed "'
                    . htmlspecialchars($activity_title)
                    . '"';

            } else {

                $title =
                    'A new listing was posted: "'
                    . htmlspecialchars($activity_title)
                    . '"';
            }

        } else {

            if ($user_name !== '') {

                $title =
                    htmlspecialchars($user_name)
                    . ' — '
                    . htmlspecialchars($activity_title);

            } else {

                $title =
                    htmlspecialchars($activity_title);
            }
        }


        $recent_activity[] = [

            'title' => $title,

            'time' =>
                dashboard_format_time(
                    $row['activity_date']
                )

        ];
    }
}


/* =====================================================
   RECENT LISTINGS
   FIRST PRODUCT IMAGE ONLY
===================================================== */

$recent_listings = [];


$stmt = $conn->prepare("
    SELECT
        p.product_id,
        p.title,
        p.price,
        p.status,
        p.created_at,

        u.full_name AS seller_name,

        c.category_name,

        (
            SELECT pi.image_path
            FROM Product_Images pi
            WHERE pi.product_id = p.product_id
              AND pi.deleted_at IS NULL
            ORDER BY pi.image_id ASC
            LIMIT 1
        ) AS first_image

    FROM Products p

    LEFT JOIN Users u
        ON u.user_id = p.seller_id

    LEFT JOIN Categories c
        ON c.category_id = p.category_id

    WHERE p.deleted_at IS NULL

    ORDER BY p.created_at DESC

    LIMIT 5
");

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $recent_listings[] = [

        'product_id' =>
            (int)$row['product_id'],

        'item' =>
            $row['title'] ?? 'Untitled Product',

        'seller' =>
            $row['seller_name'] ?? 'Unknown Seller',

        'category' =>
            $row['category_name'] ?? 'Uncategorized',

        'price' =>
            '₹' . number_format(
                (float)$row['price'],
                2
            ),

        'status' =>
            $row['status'] ?? 'Inactive',

        'image' =>
            $row['first_image'] ?? '',

        'created_at' =>
            $row['created_at']

    ];
}

$stmt->close();


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [

    [
        'label' => 'Dashboard'
    ]

];


/* =====================================================
   LOAD ADMIN LAYOUT
===================================================== */

include 'includes/admin_header.php';

?>


<!-- =====================================================
     DASHBOARD CONTENT
===================================================== -->


<!-- =================================================
        PAGE HEADER
================================================== -->

<div class="dashboard-header">

    <div>

        <h1>
            Dashboard
        </h1>

        <p>
            Here's what's happening on CampusXchange today.
        </p>

    </div>


    <div class="dashboard-date">

        <i class="fa-regular fa-calendar"></i>

        <span id="dashboardDate">
            Loading...
        </span>

    </div>

</div>



<!-- =================================================
        STATISTICS
================================================== -->

<section class="dashboard-stats">

    <?php foreach ($dashboard_stats as $stat): ?>

        <div class="stat-card">

            <div class="stat-card-top">

                <div class="stat-icon">

                    <i
                        class="fa-solid <?= htmlspecialchars($stat['icon']) ?>"
                    ></i>

                </div>


                <span
                    class="stat-change <?= htmlspecialchars($stat['change_type']) ?>"
                >
                    <?= htmlspecialchars($stat['change']) ?>
                </span>

            </div>


            <div class="stat-value">

                <?= htmlspecialchars($stat['value']) ?>

            </div>


            <div class="stat-title">

                <?= htmlspecialchars($stat['title']) ?>

            </div>

        </div>

    <?php endforeach; ?>

</section>



<!-- =================================================
        ANALYTICS ROW
================================================== -->

<section class="dashboard-grid">


    <!-- =============================================
            WEEKLY LISTINGS
    ============================================== -->

    <div class="dashboard-card weekly-card">

        <div class="card-header">

            <div>

                <h2>
                    Listings Posted This Week
                </h2>

                <p>
                    Daily listing activity
                </p>

            </div>


            <span class="card-badge">

                Last 7 days

            </span>

        </div>


        <div class="weekly-chart">

            <?php foreach ($weekly_listings as $day): ?>

                <?php

                $value = $day['value'];

                $height =
                    ($value / $chart_max) * 100;

                ?>

                <div class="chart-column">

                    <div class="chart-value">

                        <?= number_format($value) ?>

                    </div>


                    <div class="chart-bar-wrapper">

                        <div
                            class="chart-bar <?= $value === 0 ? 'zero-bar' : '' ?>"
                            style="height: <?= $height ?>%;"
                        ></div>

                    </div>


                    <span class="chart-day">

                        <?= htmlspecialchars($day['label']) ?>

                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    </div>



    <!-- =============================================
            RECENT ACTIVITY
    ============================================== -->

    <div class="dashboard-card activity-card">

        <div class="card-header">

            <div>

                <h2>
                    Recent Activity
                </h2>

                <p>
                    Latest platform activity
                </p>

            </div>


            <a
                href="activity_logs"
                class="card-link"
            >
                View all
            </a>

        </div>


        <div class="activity-list">

            <?php if (!empty($recent_activity)): ?>

                <?php foreach ($recent_activity as $activity): ?>

                    <div class="activity-item">

                        <span class="activity-dot"></span>


                        <div class="activity-content">

                            <p>
                                <?= $activity['title'] ?>
                            </p>

                            <span>
                                <?= htmlspecialchars($activity['time']) ?>
                            </span>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="dashboard-empty">

                    <i class="fa-regular fa-clock"></i>

                    <span>
                        No recent activity.
                    </span>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>



<!-- =================================================
        RECENT LISTINGS
================================================== -->

<section class="dashboard-card recent-listings-card">

    <div class="card-header">

        <div>

            <h2>
                Recent Listings
            </h2>

            <p>
                Latest products posted on CampusXchange
            </p>

        </div>


        <a
            href="products"
            class="card-link"
        >

            View all

            <i class="fa-solid fa-arrow-right"></i>

        </a>

    </div>


    <div class="table-wrapper">

        <table class="dashboard-table">

            <thead>

                <tr>

                    <th>
                        ITEM
                    </th>

                    <th>
                        SELLER
                    </th>

                    <th>
                        CATEGORY
                    </th>

                    <th>
                        PRICE
                    </th>

                    <th>
                        STATUS
                    </th>

                    <th>
                        ACTION
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if (!empty($recent_listings)): ?>

                    <?php foreach ($recent_listings as $listing): ?>

                        <?php

                        $status_class =
                            dashboard_status_class(
                                $listing['status']
                            );

                        ?>

                        <tr>

                            <td>

                                <div class="listing-name">

                                    <div class="listing-image">

                                        <?php if (!empty($listing['image'])): ?>

                                            <img
                                                src="../uploads/products/<?= htmlspecialchars($listing['image']) ?>"
                                                alt="<?= htmlspecialchars($listing['item']) ?>"
                                            >

                                        <?php else: ?>

                                            <i class="fa-solid fa-box"></i>

                                        <?php endif; ?>

                                    </div>


                                    <span>

                                        <?= htmlspecialchars(
                                            $listing['item']
                                        ) ?>

                                    </span>

                                </div>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $listing['seller']
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $listing['category']
                                ) ?>

                            </td>


                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $listing['price']
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <span
                                    class="status-badge <?= htmlspecialchars($status_class) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $listing['status']
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <a
                                    href="product-details.php?id=<?= $listing['product_id'] ?>"
                                    class="table-action"
                                >

                                    View

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="6"
                            class="table-empty"
                        >

                            <i class="fa-solid fa-box-open"></i>

                            <span>
                                No listings found.
                            </span>

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>



<!-- =================================================
        QUICK ACTIONS
================================================== -->

<section class="quick-actions">


    <a
        href="products"
        class="quick-action"
    >

        <span class="quick-action-icon">

            <i class="fa-solid fa-box"></i>

        </span>


        <span>

            Manage Products

        </span>


        <i class="fa-solid fa-arrow-right"></i>

    </a>



    <a
        href="users"
        class="quick-action"
    >

        <span class="quick-action-icon">

            <i class="fa-solid fa-users"></i>

        </span>


        <span>

            Manage Users

        </span>


        <i class="fa-solid fa-arrow-right"></i>

    </a>



    <a
        href="reports"
        class="quick-action"
    >

        <span class="quick-action-icon">

            <i class="fa-solid fa-flag"></i>

        </span>


        <span>

            Review Reports

        </span>


        <i class="fa-solid fa-arrow-right"></i>

    </a>


</section>



<script src="assets/js/dashboard.js"></script>

<?php

include 'includes/admin_footer.php';

?>