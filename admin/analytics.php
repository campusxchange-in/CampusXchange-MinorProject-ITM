<?php

/* =====================================================
   AUTHENTICATION
===================================================== */

include 'includes/auth_check.php';


/* =====================================================
   PAGE VARIABLES
===================================================== */

$page_title = 'Analytics | CampusXchange';
$page_heading = 'Analytics';
$page_description = 'Platform performance and activity insights.';
$active_page = 'analytics';
$page_css = 'analytics.css';


/* =====================================================
   DATABASE CONNECTION
===================================================== */

include '../server/database/db_connect.php';


/* =====================================================
   CURRENT ADMIN
===================================================== */

$admin_id = $_SESSION['admin_id'] ?? 0;

if (!$admin_id) {

    include '../config/app_config.php';

    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}


/* =====================================================
   VERIFY ACTIVE ADMIN
===================================================== */

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


if (!$admin) {

    session_unset();
    session_destroy();

    include '../config/app_config.php';

    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}


/* =====================================================
   DATE RANGE
===================================================== */

$allowed_ranges = [
    '7'   => 'Last 7 Days',
    '30'  => 'Last 30 Days',
    '90'  => 'Last 90 Days',
    '365' => 'This Year',
    'all' => 'All Time'
];

/*
 * Always read the selected range from the URL.
 *
 * Examples:
 * analytics.php?range=7
 * analytics.php?range=30
 * analytics.php?range=90
 * analytics.php?range=365
 * analytics.php?range=all
 */

$range = isset($_GET['range'])
    ? (string) $_GET['range']
    : '30';

if (!isset($allowed_ranges[$range])) {
    $range = '30';
}


/* =====================================================
   DATE CONDITIONS
===================================================== */

/*
 * Users / Products use created_at.
 */

$date_condition = "";

if ($range !== 'all') {

    if ($range === '365') {

        $date_condition = "
            AND created_at >= DATE_FORMAT(
                CURDATE(),
                '%Y-01-01'
            )
        ";

    } else {

        $days = (int) $range - 1;

        $date_condition = "
            AND created_at >= DATE_SUB(
                CURDATE(),
                INTERVAL {$days} DAY
            )
        ";
    }
}


/*
 * Transactions use transaction_date.
 */

$transaction_date_condition = "";

if ($range !== 'all') {

    if ($range === '365') {

        $transaction_date_condition = "
            AND transaction_date >= DATE_FORMAT(
                CURDATE(),
                '%Y-01-01'
            )
        ";

    } else {

        $days = (int) $range - 1;

        $transaction_date_condition = "
            AND transaction_date >= DATE_SUB(
                CURDATE(),
                INTERVAL {$days} DAY
            )
        ";
    }
}


/* =====================================================
   HELPER
===================================================== */

function get_count($conn, $sql)
{
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row['total'] ?? 0);
}


function get_sum($conn, $sql)
{
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (float) ($row['total'] ?? 0);
}


/* =====================================================
   ANALYTICS CHART HELPERS
===================================================== */

/*
 * Build the complete timeline for the selected range.
 *
 * 7 Days    -> 7 daily points
 * 30 Days   -> 30 daily points
 * 90 Days   -> 13 weekly points
 * This Year -> January to current month
 * All Time  -> earliest month to current month
 *
 * Missing periods are automatically created with 0.
 */

function build_chart_periods(
    $conn,
    $table,
    $date_column,
    $range
) {

    $periods = [];

    $today = new DateTime('today');


    /* =================================================
       LAST 7 / 30 DAYS
    ================================================= */

    if ($range === '7' || $range === '30') {

        $days = (int) $range;

        /*
         * Find the first day.
         *
         * Example:
         * 7 days = today - 6
         * 30 days = today - 29
         */

        $start = clone $today;

        $start->modify(
            '-' . ($days - 1) . ' days'
        );


        /*
         * Create every day even if database
         * contains no records for that day.
         */

        for (
            $date = clone $start;
            $date <= $today;
            $date->modify('+1 day')
        ) {

            $key = $date->format('Y-m-d');

            $periods[$key] = [
                'period' => $key,
                'total'  => 0
            ];
        }


        /*
         * Get actual database values.
         */

        $days_minus_one = $days - 1;

        $sql = "
            SELECT
                DATE(`{$date_column}`) AS period,
                COUNT(*) AS total
            FROM `{$table}`
            WHERE deleted_at IS NULL
              AND `{$date_column}` >= DATE_SUB(
                    CURDATE(),
                    INTERVAL {$days_minus_one} DAY
              )
              AND `{$date_column}` < DATE_ADD(
                    CURDATE(),
                    INTERVAL 1 DAY
              )
            GROUP BY DATE(`{$date_column}`)
            ORDER BY period ASC
        ";

        $result = $conn->query($sql);

        if ($result) {

            while ($row = $result->fetch_assoc()) {

                $period = $row['period'];

                if (isset($periods[$period])) {

                    $periods[$period]['total'] =
                        (int) $row['total'];
                }
            }
        }


        return $periods;
    }


    /* =================================================
       LAST 90 DAYS
    ================================================= */

    if ($range === '90') {

        /*
         * Start with 90 days of data.
         */

        $range_start = clone $today;

        $range_start->modify('-89 days');


        /*
         * Align the first point to Monday.
         *
         * This matches MySQL WEEKDAY().
         */

        $days_from_monday =
            (int) $range_start->format('N') - 1;

        if ($days_from_monday > 0) {

            $range_start->modify(
                '-' . $days_from_monday . ' days'
            );
        }


        /*
         * Find current week's Monday.
         */

        $current_week = clone $today;

        $days_from_monday =
            (int) $current_week->format('N') - 1;

        if ($days_from_monday > 0) {

            $current_week->modify(
                '-' . $days_from_monday . ' days'
            );
        }


        /*
         * Generate every week.
         */

        for (
            $date = clone $range_start;
            $date <= $current_week;
            $date->modify('+7 days')
        ) {

            $key = $date->format('Y-m-d');

            $periods[$key] = [
                'period' => $key,
                'total'  => 0
            ];
        }


        /*
         * Get actual database values.
         */

        $result = $conn->query("
            SELECT
                DATE_SUB(
                    DATE(`{$date_column}`),
                    INTERVAL WEEKDAY(`{$date_column}`) DAY
                ) AS period,
                COUNT(*) AS total
            FROM `{$table}`
            WHERE deleted_at IS NULL
              AND `{$date_column}` >= DATE_SUB(
                    CURDATE(),
                    INTERVAL 89 DAY
              )
              AND `{$date_column}` < DATE_ADD(
                    CURDATE(),
                    INTERVAL 1 DAY
              )
            GROUP BY
                DATE_SUB(
                    DATE(`{$date_column}`),
                    INTERVAL WEEKDAY(`{$date_column}`) DAY
                )
            ORDER BY period ASC
        ");

        if ($result) {

            while ($row = $result->fetch_assoc()) {

                $period = $row['period'];

                if (isset($periods[$period])) {

                    $periods[$period]['total'] =
                        (int) $row['total'];
                }
            }
        }


        return $periods;
    }


    /* =================================================
       THIS YEAR
    ================================================= */

    if ($range === '365') {

        $start = new DateTime(
            date('Y') . '-01-01'
        );

        /*
         * Generate January through current month.
         */

        for (
            $date = clone $start;
            $date <= $today;
            $date->modify('+1 month')
        ) {

            $key = $date->format('Y-m');

            $periods[$key] = [
                'period' => $key,
                'total'  => 0
            ];
        }


        /*
         * Get actual monthly values.
         */

        $result = $conn->query("
            SELECT
                DATE_FORMAT(
                    `{$date_column}`,
                    '%Y-%m'
                ) AS period,
                COUNT(*) AS total
            FROM `{$table}`
            WHERE deleted_at IS NULL
              AND `{$date_column}` >= DATE_FORMAT(
                    CURDATE(),
                    '%Y-01-01'
              )
              AND `{$date_column}` < DATE_ADD(
                    CURDATE(),
                    INTERVAL 1 DAY
              )
            GROUP BY
                DATE_FORMAT(
                    `{$date_column}`,
                    '%Y-%m'
                )
            ORDER BY period ASC
        ");

        if ($result) {

            while ($row = $result->fetch_assoc()) {

                $period = $row['period'];

                if (isset($periods[$period])) {

                    $periods[$period]['total'] =
                        (int) $row['total'];
                }
            }
        }


        return $periods;
    }


    /* =================================================
       ALL TIME
    ================================================= */

    /*
     * Find the earliest available record.
     */

    $first_result = $conn->query("
        SELECT
            MIN(`{$date_column}`) AS first_date
        FROM `{$table}`
        WHERE deleted_at IS NULL
    ");

    $first_date = null;

    if ($first_result) {

        $first_row =
            $first_result->fetch_assoc();

        $first_date =
            $first_row['first_date'] ?? null;
    }


    /*
     * If there is no data at all, still show
     * the current month as a zero-data period.
     */

    if (!$first_date) {

        $current_month = new DateTime(
            $today->format('Y-m-01')
        );

        $key = $current_month->format('Y-m');

        $periods[$key] = [
            'period' => $key,
            'total'  => 0
        ];

        return $periods;
    }


    /*
     * Start at the first month containing data.
     */

    $start = new DateTime(
        date('Y-m-01', strtotime($first_date))
    );

    $current_month = new DateTime(
        $today->format('Y-m-01')
    );


    /*
     * Generate every month, including empty months.
     */

    for (
        $date = clone $start;
        $date <= $current_month;
        $date->modify('+1 month')
    ) {

        $key = $date->format('Y-m');

        $periods[$key] = [
            'period' => $key,
            'total'  => 0
        ];
    }


    /*
     * Get actual monthly values.
     */

    $result = $conn->query("
        SELECT
            DATE_FORMAT(
                `{$date_column}`,
                '%Y-%m'
            ) AS period,
            COUNT(*) AS total
        FROM `{$table}`
        WHERE deleted_at IS NULL
        GROUP BY
            DATE_FORMAT(
                `{$date_column}`,
                '%Y-%m'
            )
        ORDER BY period ASC
    ");

    if ($result) {

        while ($row = $result->fetch_assoc()) {

            $period = $row['period'];

            if (isset($periods[$period])) {

                $periods[$period]['total'] =
                    (int) $row['total'];
            }
        }
    }


    return $periods;
}


/* =====================================================
   FORMAT CHART LABEL
===================================================== */

function format_chart_label($period, $range)
{

    /*
     * Daily
     */

    if ($range === '7' || $range === '30') {

        $date = DateTime::createFromFormat(
            'Y-m-d',
            $period
        );

        if ($date) {

            return $date->format('d M');
        }
    }


    /*
     * Weekly
     */

    if ($range === '90') {

        $date = DateTime::createFromFormat(
            'Y-m-d',
            $period
        );

        if ($date) {

            return $date->format('d M');
        }
    }


    /*
     * Monthly
     */

    $date = DateTime::createFromFormat(
        'Y-m',
        $period
    );

    if ($date) {

        return $date->format('M Y');
    }


    return $period;
}


/* =====================================================
   PLATFORM OVERVIEW
===================================================== */


/* TOTAL USERS */

$total_users = get_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM Users
     WHERE deleted_at IS NULL"
);


/* ACTIVE USERS */

$active_users = get_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM Users
     WHERE status = 'Active'
       AND deleted_at IS NULL"
);


/* TOTAL PRODUCTS */

$total_products = get_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM Products
     WHERE deleted_at IS NULL"
);


/* ACTIVE PRODUCTS */

$active_products = get_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM Products
     WHERE status = 'Active'
       AND deleted_at IS NULL"
);


/* COMPLETED TRANSACTIONS */

$completed_transactions = get_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM Transactions
     WHERE transaction_status = 'Completed'
       AND deleted_at IS NULL"
);


/* TOTAL TRANSACTION VALUE */

$total_transaction_value = get_sum(
    $conn,
    "SELECT COALESCE(SUM(selling_price), 0) AS total
     FROM Transactions
     WHERE transaction_status = 'Completed'
       AND deleted_at IS NULL"
);


/* PENDING VERIFICATION */

$pending_verification = get_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM User_Verification
     WHERE verification_status = 'Pending'
       AND deleted_at IS NULL"
);


/* OPEN REPORTS */

$open_reports = get_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM Reports
     WHERE status IN ('Pending', 'Under Review')
       AND deleted_at IS NULL"
);


/* =====================================================
   USER ANALYTICS
===================================================== */


/* USER STATUS */

$user_status = [
    'Active'    => 0,
    'Pending'   => 0,
    'Suspended' => 0,
    'Banned'    => 0
];

$result = $conn->query("
    SELECT
        status,
        COUNT(*) AS total
    FROM Users
    WHERE deleted_at IS NULL
    GROUP BY status
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        if (isset($user_status[$row['status']])) {

            $user_status[$row['status']] =
                (int) $row['total'];
        }
    }
}


/* USER ROLE */

$user_roles = [
    'Student' => 0,
    'Faculty' => 0
];

$result = $conn->query("
    SELECT
        role,
        COUNT(*) AS total
    FROM Users
    WHERE deleted_at IS NULL
    GROUP BY role
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        if (isset($user_roles[$row['role']])) {

            $user_roles[$row['role']] =
                (int) $row['total'];
        }
    }
}


/* =====================================================
   USER REGISTRATION TREND
===================================================== */

$user_trend = build_chart_periods(
    $conn,
    'Users',
    'created_at',
    $range
);

$user_trend = array_values($user_trend);


/* =====================================================
   PRODUCT ANALYTICS
===================================================== */


/* PRODUCT STATUS */

$product_status = [
    'Draft'    => 0,
    'Active'   => 0,
    'Sold'     => 0,
    'Removed'  => 0,
    'Inactive' => 0
];

$result = $conn->query("
    SELECT
        status,
        COUNT(*) AS total
    FROM Products
    WHERE deleted_at IS NULL
    GROUP BY status
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        if (isset($product_status[$row['status']])) {

            $product_status[$row['status']] =
                (int) $row['total'];
        }
    }
}


/* =====================================================
   PRODUCTS BY CATEGORY
===================================================== */

$products_by_category = [];

$result = $conn->query("
    SELECT
        c.category_name,
        COUNT(p.product_id) AS total
    FROM Categories c

    LEFT JOIN Products p
        ON p.category_id = c.category_id
       AND p.deleted_at IS NULL

    WHERE c.deleted_at IS NULL

    GROUP BY
        c.category_id,
        c.category_name

    HAVING total > 0

    ORDER BY total DESC
    LIMIT 10
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $products_by_category[] = [
            'category' => $row['category_name'],
            'total'    => (int) $row['total']
        ];
    }
}


/* =====================================================
   PRODUCT TREND
===================================================== */

$product_trend = build_chart_periods(
    $conn,
    'Products',
    'created_at',
    $range
);

$product_trend = array_values($product_trend);


/* =====================================================
   TRANSACTION ANALYTICS
===================================================== */


/* TRANSACTION STATUS */

$transaction_status = [
    'Completed' => 0,
    'Cancelled' => 0,
    'Disputed'  => 0
];

$result = $conn->query("
    SELECT
        transaction_status,
        COUNT(*) AS total
    FROM Transactions
    WHERE deleted_at IS NULL
    GROUP BY transaction_status
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        if (
            isset(
                $transaction_status[
                    $row['transaction_status']
                ]
            )
        ) {

            $transaction_status[
                $row['transaction_status']
            ] = (int) $row['total'];
        }
    }
}


/* =====================================================
   TRANSACTION TREND
===================================================== */

$transaction_trend = [];


/*
 * Build the same timeline used by the
 * selected range.
 */

$transaction_periods = build_chart_periods(
    $conn,
    'Transactions',
    'transaction_date',
    $range
);


/*
 * Re-query Transactions because we need
 * both count AND transaction value.
 */

if ($range === '7' || $range === '30') {

    $days = (int) $range - 1;

    $transaction_result = $conn->query("
        SELECT
            DATE(transaction_date) AS period,
            COUNT(*) AS total,
            COALESCE(
                SUM(selling_price),
                0
            ) AS value
        FROM Transactions
        WHERE deleted_at IS NULL
          AND transaction_date >= DATE_SUB(
                CURDATE(),
                INTERVAL {$days} DAY
          )
          AND transaction_date < DATE_ADD(
                CURDATE(),
                INTERVAL 1 DAY
          )
        GROUP BY DATE(transaction_date)
        ORDER BY period ASC
    ");

}
elseif ($range === '90') {

    $transaction_result = $conn->query("
        SELECT
            DATE_SUB(
                DATE(transaction_date),
                INTERVAL WEEKDAY(transaction_date) DAY
            ) AS period,
            COUNT(*) AS total,
            COALESCE(
                SUM(selling_price),
                0
            ) AS value
        FROM Transactions
        WHERE deleted_at IS NULL
          AND transaction_date >= DATE_SUB(
                CURDATE(),
                INTERVAL 89 DAY
          )
          AND transaction_date < DATE_ADD(
                CURDATE(),
                INTERVAL 1 DAY
          )
        GROUP BY
            DATE_SUB(
                DATE(transaction_date),
                INTERVAL WEEKDAY(transaction_date) DAY
            )
        ORDER BY period ASC
    ");

}
elseif ($range === '365') {

    $transaction_result = $conn->query("
        SELECT
            DATE_FORMAT(
                transaction_date,
                '%Y-%m'
            ) AS period,
            COUNT(*) AS total,
            COALESCE(
                SUM(selling_price),
                0
            ) AS value
        FROM Transactions
        WHERE deleted_at IS NULL
          AND transaction_date >= DATE_FORMAT(
                CURDATE(),
                '%Y-01-01'
          )
          AND transaction_date < DATE_ADD(
                CURDATE(),
                INTERVAL 1 DAY
          )
        GROUP BY
            DATE_FORMAT(
                transaction_date,
                '%Y-%m'
            )
        ORDER BY period ASC
    ");

}
else {

    $transaction_result = $conn->query("
        SELECT
            DATE_FORMAT(
                transaction_date,
                '%Y-%m'
            ) AS period,
            COUNT(*) AS total,
            COALESCE(
                SUM(selling_price),
                0
            ) AS value
        FROM Transactions
        WHERE deleted_at IS NULL
        GROUP BY
            DATE_FORMAT(
                transaction_date,
                '%Y-%m'
            )
        ORDER BY period ASC
    ");
}


/*
 * Create lookup map from actual transaction data.
 */

$transaction_map = [];

if ($transaction_result) {

    while ($row = $transaction_result->fetch_assoc()) {

        $transaction_map[$row['period']] = [
            'total' =>
                (int) $row['total'],

            'value' =>
                (float) $row['value']
        ];
    }
}


/*
 * Merge actual values into the complete
 * timeline.
 *
 * Missing periods remain:
 *
 * total = 0
 * value = 0
 */

foreach ($transaction_periods as $period => $item) {

    $transaction_trend[] = [
        'period' => $period,
        'total' =>
            $transaction_map[$period]['total'] ?? 0,
        'value' =>
            $transaction_map[$period]['value'] ?? 0
    ];
}


/* =====================================================
   VERIFICATION ANALYTICS
===================================================== */

$verification_status = [
    'Not Submitted' => 0,
    'Pending'       => 0,
    'Approved'      => 0,
    'Rejected'      => 0
];

$result = $conn->query("
    SELECT
        verification_status,
        COUNT(*) AS total
    FROM User_Verification
    WHERE deleted_at IS NULL
    GROUP BY verification_status
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        if (
            isset(
                $verification_status[
                    $row['verification_status']
                ]
            )
        ) {

            $verification_status[
                $row['verification_status']
            ] = (int) $row['total'];
        }
    }
}


/* =====================================================
   REPORT ANALYTICS
===================================================== */

$report_status = [
    'Pending'     => 0,
    'Under Review'=> 0,
    'Resolved'    => 0,
    'Rejected'    => 0
];

$result = $conn->query("
    SELECT
        status,
        COUNT(*) AS total
    FROM Reports
    WHERE deleted_at IS NULL
    GROUP BY status
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        if (isset($report_status[$row['status']])) {

            $report_status[$row['status']] =
                (int) $row['total'];
        }
    }
}


/* =====================================================
   REPORT CATEGORIES
===================================================== */

$report_categories = [];

$result = $conn->query("
    SELECT
        report_category,
        COUNT(*) AS total
    FROM Reports
    WHERE deleted_at IS NULL
    GROUP BY report_category
    ORDER BY total DESC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $report_categories[] = [
            'category' => $row['report_category'],
            'total'    => (int) $row['total']
        ];
    }
}


/* =====================================================
   REVIEW ANALYTICS
===================================================== */


/* PRODUCT REVIEWS */

$product_reviews = get_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM Product_Reviews
     WHERE deleted_at IS NULL"
);


/* SELLER REVIEWS */

$seller_reviews = get_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM Seller_Reviews
     WHERE deleted_at IS NULL"
);


/* PRODUCT AVERAGE RATING */

$product_average_rating = get_sum(
    $conn,
    "SELECT COALESCE(AVG(rating), 0) AS total
     FROM Product_Reviews
     WHERE deleted_at IS NULL
       AND status = 'Visible'"
);

$product_average_rating =
    round($product_average_rating, 2);


/* SELLER AVERAGE RATING */

$seller_average_rating = get_sum(
    $conn,
    "SELECT COALESCE(AVG(rating), 0) AS total
     FROM Seller_Reviews
     WHERE deleted_at IS NULL
       AND status = 'Visible'"
);

$seller_average_rating =
    round($seller_average_rating, 2);


/* =====================================================
   RATING DISTRIBUTION
===================================================== */

$rating_distribution = [
    5 => 0,
    4 => 0,
    3 => 0,
    2 => 0,
    1 => 0
];

$result = $conn->query("
    SELECT
        rating,
        COUNT(*) AS total
    FROM Product_Reviews
    WHERE deleted_at IS NULL
      AND status = 'Visible'
    GROUP BY rating
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $rating =
            (int) $row['rating'];

        if (isset($rating_distribution[$rating])) {

            $rating_distribution[$rating] =
                (int) $row['total'];
        }
    }
}


/* =====================================================
   BAN ANALYTICS
===================================================== */

$ban_types = [
    'Warning'   => 0,
    'Temporary' => 0,
    'Permanent' => 0
];

$result = $conn->query("
    SELECT
        ban_type,
        COUNT(*) AS total
    FROM User_Bans
    WHERE deleted_at IS NULL
    GROUP BY ban_type
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        if (isset($ban_types[$row['ban_type']])) {

            $ban_types[$row['ban_type']] =
                (int) $row['total'];
        }
    }
}


/* =====================================================
   ADMIN ACTIVITY
===================================================== */

$admin_activity = [];

$result = $conn->query("
    SELECT
        action_type,
        COUNT(*) AS total
    FROM Admin_Logs
    WHERE deleted_at IS NULL
    GROUP BY action_type
    ORDER BY total DESC
    LIMIT 10
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $admin_activity[] = [
            'action' => $row['action_type'],
            'total'  => (int) $row['total']
        ];
    }
}


/* =====================================================
   CALCULATED METRICS
===================================================== */


/* SELL-THROUGH RATE */

$sell_through_rate = 0;

if ($total_products > 0) {

    $sell_through_rate =
        ($product_status['Sold'] / $total_products) * 100;
}


/* VERIFICATION APPROVAL RATE */

$verification_approval_rate = 0;

$verification_decided =
    $verification_status['Approved'] +
    $verification_status['Rejected'];

if ($verification_decided > 0) {

    $verification_approval_rate =
        (
            $verification_status['Approved']
            /
            $verification_decided
        ) * 100;
}


/* REPORT RESOLUTION RATE */

$reports_decided =
    $report_status['Resolved'] +
    $report_status['Rejected'];

$report_resolution_rate = 0;

if ($reports_decided > 0) {

    $report_resolution_rate =
        (
            $report_status['Resolved']
            /
            $reports_decided
        ) * 100;
}


/* AVERAGE TRANSACTION VALUE */

$average_transaction_value = 0;

if ($completed_transactions > 0) {

    $average_transaction_value =
        $total_transaction_value
        /
        $completed_transactions;
}


/* =====================================================
   FORMAT HELPERS
===================================================== */

function format_number($number)
{
    return number_format(
        $number,
        0,
        '.',
        ','
    );
}


function format_currency($number)
{
    return '₹' . number_format(
        $number,
        2,
        '.',
        ','
    );
}


/* =====================================================
   BREADCRUMBS
===================================================== */

$breadcrumbs = [
    [
        'label' => 'Analytics'
    ]
];


/* =====================================================
   LOAD ADMIN HEADER
===================================================== */

include 'includes/admin_header.php';

?>


<!-- =====================================================
     ANALYTICS PAGE
===================================================== -->

<div class="analytics-page">


    


    <!-- =================================================
         PLATFORM OVERVIEW
    ================================================== -->

    <section class="analytics-section">

        <div class="section-heading analytics-header">

            <div>

                <h2>
                    Platform Overview
                </h2>

                <p>
                    Current platform-wide statistics.
                </p>

            </div>

            <form
                method="GET"
                class="analytics-filter"
            >

                <label for="range">
                    Date Range
                </label>

                <select
                    name="range"
                    id="range"
                    onchange="this.form.submit()"
                >

                    <?php foreach ($allowed_ranges as $key => $label): ?>

                        <option
                            value="<?= htmlspecialchars((string) $key) ?>"
                            <?= $range === (string) $key ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($label) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </form>

        </div>


        <div class="analytics-stats">


            <div class="analytics-stat-card">

                <div class="analytics-stat-icon users">
                    <i class="fa-solid fa-users"></i>
                </div>

                <div class="analytics-stat-content">

                    <span>
                        Total Users
                    </span>

                    <strong>
                        <?= format_number($total_users) ?>
                    </strong>

                </div>

            </div>


            <div class="analytics-stat-card">

                <div class="analytics-stat-icon products">
                    <i class="fa-solid fa-box"></i>
                </div>

                <div class="analytics-stat-content">

                    <span>
                        Total Products
                    </span>

                    <strong>
                        <?= format_number($total_products) ?>
                    </strong>

                </div>

            </div>


            <div class="analytics-stat-card">

                <div class="analytics-stat-icon transactions">
                    <i class="fa-solid fa-handshake"></i>
                </div>

                <div class="analytics-stat-content">

                    <span>
                        Completed Transactions
                    </span>

                    <strong>
                        <?= format_number($completed_transactions) ?>
                    </strong>

                </div>

            </div>


            <div class="analytics-stat-card">

                <div class="analytics-stat-icon revenue">
                    <i class="fa-solid fa-indian-rupee-sign"></i>
                </div>

                <div class="analytics-stat-content">

                    <span>
                        Transaction Value
                    </span>

                    <strong>
                        <?= format_currency($total_transaction_value) ?>
                    </strong>

                </div>

            </div>


            <div class="analytics-stat-card">

                <div class="analytics-stat-icon active">
                    <i class="fa-solid fa-user-check"></i>
                </div>

                <div class="analytics-stat-content">

                    <span>
                        Active Users
                    </span>

                    <strong>
                        <?= format_number($active_users) ?>
                    </strong>

                </div>

            </div>


            <div class="analytics-stat-card">

                <div class="analytics-stat-icon listings">
                    <i class="fa-solid fa-box-open"></i>
                </div>

                <div class="analytics-stat-content">

                    <span>
                        Active Listings
                    </span>

                    <strong>
                        <?= format_number($active_products) ?>
                    </strong>

                </div>

            </div>


            <div class="analytics-stat-card">

                <div class="analytics-stat-icon verification">
                    <i class="fa-solid fa-id-card"></i>
                </div>

                <div class="analytics-stat-content">

                    <span>
                        Pending Verification
                    </span>

                    <strong>
                        <?= format_number($pending_verification) ?>
                    </strong>

                </div>

            </div>


            <div class="analytics-stat-card">

                <div class="analytics-stat-icon reports">
                    <i class="fa-solid fa-flag"></i>
                </div>

                <div class="analytics-stat-content">

                    <span>
                        Open Reports
                    </span>

                    <strong>
                        <?= format_number($open_reports) ?>
                    </strong>

                </div>

            </div>


        </div>

    </section>


    <!-- =================================================
         KEY METRICS
    ================================================== -->

    <section class="analytics-section">

        <div class="section-heading">

            <div>

                <h2>
                    Key Metrics
                </h2>

                <p>
                    Calculated platform performance indicators.
                </p>

            </div>

        </div>


        <div class="metric-grid">


            <div class="metric-card">

                <span>
                    Sell-through Rate
                </span>

                <strong>
                    <?= number_format($sell_through_rate, 1) ?>%
                </strong>

                <small>
                    Sold products compared with total products
                </small>

            </div>


            <div class="metric-card">

                <span>
                    Verification Approval Rate
                </span>

                <strong>
                    <?= number_format($verification_approval_rate, 1) ?>%
                </strong>

                <small>
                    Approved compared with decided requests
                </small>

            </div>


            <div class="metric-card">

                <span>
                    Report Resolution Rate
                </span>

                <strong>
                    <?= number_format($report_resolution_rate, 1) ?>%
                </strong>

                <small>
                    Resolved compared with decided reports
                </small>

            </div>


            <?php /* <div class="metric-card">

                <span>
                    Average Transaction Value
                </span>

                <strong>
                    <?= format_currency($average_transaction_value) ?>
                </strong>

                <small>
                    Based on completed transactions
                </small>

            </div> */ ?>


        </div>

    </section>


    <!-- =================================================
         USER ANALYTICS
    ================================================== -->

    <section class="analytics-section">

        <div class="section-heading">

            <div>

                <h2>
                    User Analytics
                </h2>

                <p>
                    User growth, account status and user type.
                </p>

            </div>

        </div>


        <div class="analytics-grid">


            <!-- USER TREND -->

            <div class="analytics-card large-card">

                <div class="card-header">

                    <div>

                        <h3>
                            User Registration Trend
                        </h3>

                        <p>
                            New users during the selected period.
                        </p>

                    </div>

                </div>


                <div class="trend-chart">

                    <?php if (!empty($user_trend)): ?>

                        <?php

                        $max_user_value = 0;

                        foreach ($user_trend as $item) {

                            if ($item['total'] > $max_user_value) {

                                $max_user_value = $item['total'];
                            }
                        }

                        /*
                        * Keep a minimum scale so zero-data
                        * charts still have visible structure.
                        */

                        $chart_max =
                            $max_user_value > 0
                                ? $max_user_value
                                : 1;

                        ?>

                        <div class="trend-y-axis">

                            <span>
                                <?= format_number($chart_max) ?>
                            </span>

                            <span>
                                <?= format_number(
                                    round($chart_max / 2)
                                ) ?>
                            </span>

                            <span>
                                0
                            </span>

                        </div>


                        <div class="trend-chart-body">

                            <div class="trend-grid-lines">

                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>

                            </div>


                            <div class="trend-columns">

                                <?php foreach ($user_trend as $item): ?>

                                    <?php

                                    $height =
                                        $item['total'] > 0
                                            ? (
                                                $item['total']
                                                /
                                                $chart_max
                                            ) * 100
                                            : 0;

                                    ?>

                                    <div
                                        class="trend-column"
                                        title="<?= htmlspecialchars(
                                            format_chart_label(
                                                $item['period'],
                                                $range
                                            )
                                            . ': '
                                            . $item['total']
                                            . ' users'
                                        ) ?>"
                                    >

                                        <span class="trend-value">

                                            <?= format_number(
                                                $item['total']
                                            ) ?>

                                        </span>


                                        <div class="trend-bar-area">

                                            <div
                                                class="trend-bar <?= $item['total'] === 0
                                                    ? 'zero-bar'
                                                    : '' ?>"
                                                style="height: <?= $height ?>%;"
                                            ></div>

                                        </div>


                                        <span class="trend-label">

                                            <?= htmlspecialchars(
                                                format_chart_label(
                                                    $item['period'],
                                                    $range
                                                )
                                            ) ?>

                                        </span>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="analytics-empty">
                            No user registration data available.
                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- USER STATUS -->

            <div class="analytics-card">

                <div class="card-header">

                    <div>

                        <h3>
                            User Status
                        </h3>

                        <p>
                            Current account status distribution.
                        </p>

                    </div>

                </div>


                <div class="distribution-list">

                    <?php foreach ($user_status as $status => $total): ?>

                        <div class="distribution-item">

                            <div class="distribution-label">

                                <span>
                                    <?= htmlspecialchars($status) ?>
                                </span>

                                <strong>
                                    <?= format_number($total) ?>
                                </strong>

                            </div>


                            <div class="distribution-track">

                                <?php

                                $percentage =
                                    $total_users > 0
                                        ? ($total / $total_users) * 100
                                        : 0;

                                ?>

                                <div
                                    class="distribution-fill"
                                    style="width: <?= $percentage ?>%;"
                                ></div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- USER ROLES -->

            <div class="analytics-card">

                <div class="card-header">

                    <div>

                        <h3>
                            User Type
                        </h3>

                        <p>
                            Student and faculty distribution.
                        </p>

                    </div>

                </div>


                <div class="role-list">

                    <?php foreach ($user_roles as $role => $total): ?>

                        <div class="role-item">

                            <div class="role-icon">

                                <i
                                    class="fa-solid <?= $role === 'Student'
                                        ? 'fa-user-graduate'
                                        : 'fa-chalkboard-user' ?>"
                                ></i>

                            </div>

                            <div>

                                <strong>
                                    <?= format_number($total) ?>
                                </strong>

                                <span>
                                    <?= htmlspecialchars($role) ?>
                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         PRODUCT ANALYTICS
    ================================================== -->

    <section class="analytics-section">

        <div class="section-heading">

            <div>

                <h2>
                    Product Analytics
                </h2>

                <p>
                    Listing activity and product distribution.
                </p>

            </div>

        </div>


        <div class="analytics-grid">


            <!-- PRODUCT TREND -->

            <div class="analytics-card large-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Listing Trend
                        </h3>

                        <p>
                            Products created during the selected period.
                        </p>

                    </div>

                </div>


                <div class="trend-chart">

                    <?php if (!empty($product_trend)): ?>

                        <?php

                        $max_product_value = 0;

                        foreach ($product_trend as $item) {

                            if ($item['total'] > $max_product_value) {

                                $max_product_value = $item['total'];
                            }
                        }

                        $chart_max =
                            $max_product_value > 0
                                ? $max_product_value
                                : 1;

                        ?>

                        <div class="trend-y-axis">

                            <span>
                                <?= format_number($chart_max) ?>
                            </span>

                            <span>
                                <?= format_number(
                                    round($chart_max / 2)
                                ) ?>
                            </span>

                            <span>
                                0
                            </span>

                        </div>


                        <div class="trend-chart-body">

                            <div class="trend-grid-lines">

                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>

                            </div>


                            <div class="trend-columns">

                                <?php foreach ($product_trend as $item): ?>

                                    <?php

                                    $height =
                                        $item['total'] > 0
                                            ? (
                                                $item['total']
                                                /
                                                $chart_max
                                            ) * 100
                                            : 0;

                                    ?>

                                    <div
                                        class="trend-column"
                                        title="<?= htmlspecialchars(
                                            format_chart_label(
                                                $item['period'],
                                                $range
                                            )
                                            . ': '
                                            . $item['total']
                                            . ' listings'
                                        ) ?>"
                                    >

                                        <span class="trend-value">

                                            <?= format_number(
                                                $item['total']
                                            ) ?>

                                        </span>


                                        <div class="trend-bar-area">

                                            <div
                                                class="trend-bar product-bar <?= $item['total'] === 0
                                                    ? 'zero-bar'
                                                    : '' ?>"
                                                style="height: <?= $height ?>%;"
                                            ></div>

                                        </div>


                                        <span class="trend-label">

                                            <?= htmlspecialchars(
                                                format_chart_label(
                                                    $item['period'],
                                                    $range
                                                )
                                            ) ?>

                                        </span>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="analytics-empty">
                            No product listing data available.
                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- PRODUCT STATUS -->

            <div class="analytics-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Product Status
                        </h3>

                        <p>
                            Current listing status distribution.
                        </p>

                    </div>

                </div>


                <div class="distribution-list">

                    <?php foreach ($product_status as $status => $total): ?>

                        <div class="distribution-item">

                            <div class="distribution-label">

                                <span>
                                    <?= htmlspecialchars($status) ?>
                                </span>

                                <strong>
                                    <?= format_number($total) ?>
                                </strong>

                            </div>


                            <div class="distribution-track">

                                <?php

                                $percentage =
                                    $total_products > 0
                                        ? ($total / $total_products) * 100
                                        : 0;

                                ?>

                                <div
                                    class="distribution-fill"
                                    style="width: <?= $percentage ?>%;"
                                ></div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- CATEGORY -->

            <div class="analytics-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Products by Category
                        </h3>

                        <p>
                            Top categories by listing count.
                        </p>

                    </div>

                </div>


                <div class="category-list">

                    <?php if (!empty($products_by_category)): ?>

                        <?php

                        $max_category_value = max(
                            array_column(
                                $products_by_category,
                                'total'
                            )
                        );

                        if ($max_category_value <= 0) {
                            $max_category_value = 1;
                        }

                        ?>


                        <?php foreach ($products_by_category as $item): ?>

                            <?php

                            $percentage =
                                (
                                    $item['total']
                                    /
                                    $max_category_value
                                ) * 100;

                            ?>


                            <div class="category-item">

                                <div class="category-header">

                                    <span>
                                        <?= htmlspecialchars(
                                            $item['category']
                                        ) ?>
                                    </span>

                                    <strong>
                                        <?= format_number(
                                            $item['total']
                                        ) ?>
                                    </strong>

                                </div>

                                <div class="distribution-track">

                                    <div
                                        class="distribution-fill"
                                        style="width: <?= $percentage ?>%;"
                                    ></div>

                                </div>

                            </div>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <div class="analytics-empty">
                            No category data available.
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         TRANSACTION ANALYTICS
    ================================================== -->

    <section class="analytics-section">

        <div class="section-heading">

            <div>

                <h2>
                    Transaction Analytics
                </h2>

                <p>
                    Transaction volume and value.
                </p>

            </div>

        </div>


        <div class="analytics-card transaction-card">

            <div class="card-header">

                <div>

                    <h3>
                        Transaction Trend
                    </h3>

                    <p>
                        Completed, cancelled and disputed transactions.
                    </p>

                </div>

            </div>


            <div class="transaction-table-wrapper">

                <table class="analytics-table">

                    <thead>

                        <tr>

                            <th>
                                PERIOD
                            </th>

                            <th>
                                TRANSACTIONS
                            </th>

                            <th>
                                TRANSACTION VALUE
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!empty($transaction_trend)): ?>

                            <?php foreach ($transaction_trend as $item): ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars(
                                            $item['period']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= format_number(
                                            $item['total']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= format_currency(
                                            $item['value']
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="3"
                                    class="empty-cell"
                                >
                                    No transaction data available.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <div class="status-stat-grid">


            <?php foreach ($transaction_status as $status => $total): ?>

                <div class="small-stat-card">

                    <span>
                        <?= htmlspecialchars($status) ?>
                    </span>

                    <strong>
                        <?= format_number($total) ?>
                    </strong>

                </div>

            <?php endforeach; ?>


        </div>

    </section>


    <!-- =================================================
         MODERATION ANALYTICS
    ================================================== -->

    <section class="analytics-section">

        <div class="section-heading">

            <div>

                <h2>
                    Moderation Analytics
                </h2>

                <p>
                    Verification, reports and account moderation.
                </p>

            </div>

        </div>


        <div class="analytics-grid">


            <!-- VERIFICATION -->

            <div class="analytics-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Verification Status
                        </h3>

                        <p>
                            User verification distribution.
                        </p>

                    </div>

                </div>


                <div class="distribution-list">

                    <?php foreach ($verification_status as $status => $total): ?>

                        <div class="distribution-item">

                            <div class="distribution-label">

                                <span>
                                    <?= htmlspecialchars($status) ?>
                                </span>

                                <strong>
                                    <?= format_number($total) ?>
                                </strong>

                            </div>

                            <div class="distribution-track">

                                <?php

                                $verification_total =
                                    array_sum(
                                        $verification_status
                                    );

                                $percentage =
                                    $verification_total > 0
                                        ? (
                                            $total
                                            /
                                            $verification_total
                                        ) * 100
                                        : 0;

                                ?>

                                <div
                                    class="distribution-fill"
                                    style="width: <?= $percentage ?>%;"
                                ></div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- REPORT STATUS -->

            <div class="analytics-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Report Status
                        </h3>

                        <p>
                            Current moderation workload.
                        </p>

                    </div>

                </div>


                <div class="distribution-list">

                    <?php foreach ($report_status as $status => $total): ?>

                        <div class="distribution-item">

                            <div class="distribution-label">

                                <span>
                                    <?= htmlspecialchars($status) ?>
                                </span>

                                <strong>
                                    <?= format_number($total) ?>
                                </strong>

                            </div>

                            <div class="distribution-track">

                                <?php

                                $report_total =
                                    array_sum(
                                        $report_status
                                    );

                                $percentage =
                                    $report_total > 0
                                        ? (
                                            $total
                                            /
                                            $report_total
                                        ) * 100
                                        : 0;

                                ?>

                                <div
                                    class="distribution-fill"
                                    style="width: <?= $percentage ?>%;"
                                ></div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- REPORT CATEGORIES -->

            <div class="analytics-card large-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Reports by Category
                        </h3>

                        <p>
                            Most frequently reported issues.
                        </p>

                    </div>

                </div>


                <div class="category-list">

                    <?php if (!empty($report_categories)): ?>

                        <?php

                        $max_report_category =
                            max(
                                array_column(
                                    $report_categories,
                                    'total'
                                )
                            );

                        if ($max_report_category <= 0) {
                            $max_report_category = 1;
                        }

                        ?>


                        <?php foreach ($report_categories as $item): ?>

                            <?php

                            $percentage =
                                (
                                    $item['total']
                                    /
                                    $max_report_category
                                ) * 100;

                            ?>


                            <div class="category-item">

                                <div class="category-header">

                                    <span>
                                        <?= htmlspecialchars(
                                            $item['category']
                                        ) ?>
                                    </span>

                                    <strong>
                                        <?= format_number(
                                            $item['total']
                                        ) ?>
                                    </strong>

                                </div>


                                <div class="distribution-track">

                                    <div
                                        class="distribution-fill"
                                        style="width: <?= $percentage ?>%;"
                                    ></div>

                                </div>

                            </div>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <div class="analytics-empty">
                            No report data available.
                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- BANS -->

            <div class="analytics-card">

                <div class="card-header">

                    <div>

                        <h3>
                            User Bans
                        </h3>

                        <p>
                            Moderation action distribution.
                        </p>

                    </div>

                </div>


                <div class="role-list">

                    <?php foreach ($ban_types as $type => $total): ?>

                        <div class="role-item">

                            <div class="role-icon">

                                <i class="fa-solid fa-ban"></i>

                            </div>

                            <div>

                                <strong>
                                    <?= format_number($total) ?>
                                </strong>

                                <span>
                                    <?= htmlspecialchars($type) ?>
                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         REVIEW ANALYTICS
    ================================================== -->

    <section class="analytics-section">

        <div class="section-heading">

            <div>

                <h2>
                    Review Analytics
                </h2>

                <p>
                    Product and seller review performance.
                </p>

            </div>

        </div>


        <div class="analytics-grid">


            <div class="analytics-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Product Reviews
                        </h3>

                    </div>

                </div>

                <div class="review-number">

                    <?= format_number($product_reviews) ?>

                </div>

                <span class="review-label">
                    Total product reviews
                </span>

                <div class="rating-value">

                    <?= number_format(
                        $product_average_rating,
                        2
                    ) ?>

                    <i class="fa-solid fa-star"></i>

                </div>

                <span class="review-label">
                    Average rating
                </span>

            </div>


            <div class="analytics-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Seller Reviews
                        </h3>

                    </div>

                </div>

                <div class="review-number">

                    <?= format_number($seller_reviews) ?>

                </div>

                <span class="review-label">
                    Total seller reviews
                </span>

                <div class="rating-value">

                    <?= number_format(
                        $seller_average_rating,
                        2
                    ) ?>

                    <i class="fa-solid fa-star"></i>

                </div>

                <span class="review-label">
                    Average rating
                </span>

            </div>


            <div class="analytics-card large-card">

                <div class="card-header">

                    <div>

                        <h3>
                            Product Rating Distribution
                        </h3>

                        <p>
                            Visible product reviews by rating.
                        </p>

                    </div>

                </div>


                <div class="rating-list">

                    <?php foreach ($rating_distribution as $rating => $total): ?>

                        <div class="rating-item">

                            <span class="rating-stars">

                                <?php for ($i = 1; $i <= 5; $i++): ?>

                                    <i
                                        class="fa-solid fa-star <?= $i <= $rating
                                            ? 'filled'
                                            : '' ?>"
                                    ></i>

                                <?php endfor; ?>

                            </span>

                            <strong>
                                <?= format_number($total) ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         ADMIN ACTIVITY
    ================================================== -->

    <?php if ($admin['admin_role'] === 'Main Admin'): ?>

        <section class="analytics-section">

            <div class="section-heading">

                <div>

                    <h2>
                        Admin Activity
                    </h2>

                    <p>
                        Administrative actions recorded by the system.
                    </p>

                </div>

                <a
                    href="activity-logs"
                    class="section-link"
                >
                    View Activity Logs
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

            </div>


            <div class="analytics-card">

                <div class="admin-activity-list">

                    <?php if (!empty($admin_activity)): ?>

                        <?php

                        $max_admin_activity =
                            max(
                                array_column(
                                    $admin_activity,
                                    'total'
                                )
                            );

                        if ($max_admin_activity <= 0) {
                            $max_admin_activity = 1;
                        }

                        ?>


                        <?php foreach ($admin_activity as $item): ?>

                            <?php

                            $percentage =
                                (
                                    $item['total']
                                    /
                                    $max_admin_activity
                                ) * 100;

                            ?>


                            <div class="category-item">

                                <div class="category-header">

                                    <span>
                                        <?= htmlspecialchars(
                                            $item['action']
                                        ) ?>
                                    </span>

                                    <strong>
                                        <?= format_number(
                                            $item['total']
                                        ) ?>
                                    </strong>

                                </div>


                                <div class="distribution-track">

                                    <div
                                        class="distribution-fill"
                                        style="width: <?= $percentage ?>%;"
                                    ></div>

                                </div>

                            </div>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <div class="analytics-empty">
                            No admin activity available.
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </section>

    <?php endif; ?>


</div>


<?php

include 'includes/admin_footer.php';

?>