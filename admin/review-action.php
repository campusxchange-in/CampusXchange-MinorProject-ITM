<?php

/* =====================================================
   AUTHENTICATION
===================================================== */

include 'includes/auth_check.php';


/* =====================================================
   DATABASE CONNECTION
===================================================== */

include '../server/database/db_connect.php';


/* =====================================================
   CURRENT ADMIN
===================================================== */

$admin_id = $_SESSION['admin_id'] ?? 0;

if (!$admin_id) {
    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}


/* =====================================================
   HELPER FUNCTIONS
===================================================== */

function redirectToReviews(string $type = '', string $status = '', string $message = ''): void
{
    $url = 'reviews.php';

    $params = [];

    if ($type !== '') {
        $params['type'] = $type;
    }

    if ($status !== '') {
        $params['status'] = $status;
    }

    if ($message !== '') {
        $params['message'] = $message;
    }

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    header("Location: $url");
    exit;
}


function logAdminAction(
    mysqli $conn,
    int $admin_id,
    string $action_type,
    string $action_description,
    ?string $target_type = null,
    ?int $target_id = null
): void {

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $sql = "
        INSERT INTO Admin_Logs (
            admin_id,
            action_type,
            action_description,
            target_type,
            target_id,
            ip_address,
            user_agent
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Failed to prepare admin log statement.');
    }

    $stmt->bind_param(
        "isssiss",
        $admin_id,
        $action_type,
        $action_description,
        $target_type,
        $target_id,
        $ip_address,
        $user_agent
    );

    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('Failed to record admin action.');
    }

    $stmt->close();
}


/* =====================================================
   REQUEST METHOD
===================================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectToReviews(
        '',
        'error',
        'Invalid request method.'
    );
}


/* =====================================================
   INPUT
===================================================== */

$action = trim($_POST['action'] ?? '');
$review_id = filter_input(
    INPUT_POST,
    'review_id',
    FILTER_VALIDATE_INT
);

$review_type = trim($_POST['review_type'] ?? '');


/* =====================================================
   BASIC VALIDATION
===================================================== */

$allowed_actions = [
    'hide',
    'restore',
    'remove'
];

$allowed_types = [
    'Product',
    'Seller'
];

if (!in_array($action, $allowed_actions, true)) {
    redirectToReviews(
        '',
        'error',
        'Invalid review action.'
    );
}

if (!$review_id || $review_id < 1) {
    redirectToReviews(
        '',
        'error',
        'Invalid review ID.'
    );
}

if (!in_array($review_type, $allowed_types, true)) {
    redirectToReviews(
        '',
        'error',
        'Invalid review type.'
    );
}


/* =====================================================
   DETERMINE REVIEW TABLE
===================================================== */

$table = $review_type === 'Product'
    ? 'Product_Reviews'
    : 'Seller_Reviews';


/* =====================================================
   FETCH REVIEW
===================================================== */

$sql = "
    SELECT
        rating,
        status,
        reviewer_id,
        transaction_id
    FROM {$table}
    WHERE " . ($review_type === 'Product'
        ? 'product_review_id'
        : 'seller_review_id') . " = ?
      AND deleted_at IS NULL
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    redirectToReviews(
        $review_type,
        'error',
        'Failed to prepare review lookup.'
    );
}

$stmt->bind_param("i", $review_id);

if (!$stmt->execute()) {
    $stmt->close();

    redirectToReviews(
        $review_type,
        'error',
        'Failed to load the review.'
    );
}

$result = $stmt->get_result();
$review = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   REVIEW EXISTS
===================================================== */

if (!$review) {
    redirectToReviews(
        $review_type,
        'error',
        'Review not found.'
    );
}


/* =====================================================
   CURRENT STATUS
===================================================== */

$current_status = $review['status'];


/* =====================================================
   DETERMINE NEW STATUS
===================================================== */

$new_status = null;
$action_label = '';

switch ($action) {

    case 'hide':

        if ($current_status !== 'Visible') {
            redirectToReviews(
                $review_type,
                'error',
                'Only visible reviews can be hidden.'
            );
        }

        $new_status = 'Hidden';
        $action_label = 'Hidden';

        break;


    case 'restore':

        if ($current_status !== 'Hidden') {
            redirectToReviews(
                $review_type,
                'error',
                'Only hidden reviews can be restored.'
            );
        }

        $new_status = 'Visible';
        $action_label = 'Restored';

        break;


    case 'remove':

        if (!in_array($current_status, ['Visible', 'Hidden'], true)) {
            redirectToReviews(
                $review_type,
                'error',
                'Only visible or hidden reviews can be removed.'
            );
        }

        $new_status = 'Removed';
        $action_label = 'Removed';

        break;
}


/* =====================================================
   UPDATE REVIEW
===================================================== */

$column = $review_type === 'Product'
    ? 'product_review_id'
    : 'seller_review_id';


$conn->begin_transaction();

try {

    /* -------------------------------------------------
       Update review status
    ------------------------------------------------- */

    $sql = "
        UPDATE {$table}
        SET
            status = ?
        WHERE {$column} = ?
          AND status = ?
          AND deleted_at IS NULL
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Failed to prepare review update.');
    }

    $stmt->bind_param(
        "sis",
        $new_status,
        $review_id,
        $current_status
    );

    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('Failed to update review status.');
    }

    if ($stmt->affected_rows !== 1) {
        $stmt->close();

        throw new Exception(
            'The review status has changed or the review is no longer available.'
        );
    }

    $stmt->close();


    /* -------------------------------------------------
       Admin log
    ------------------------------------------------- */

    $action_description =
        $action_label .
        ' ' .
        strtolower($review_type) .
        ' review #' .
        $review_id .
        ' from "' .
        $current_status .
        '" to "' .
        $new_status .
        '".';

    /*
     * Admin_Logs.target_type does not currently contain
     * "Review", so use "Other" and identify the review
     * type and ID in the description.
     */

    logAdminAction(
        $conn,
        $admin_id,
        'Other',
        $action_description,
        'Other',
        $review_id
    );


    /* -------------------------------------------------
       Commit
    ------------------------------------------------- */

    $conn->commit();


    /* -------------------------------------------------
       Success
    ------------------------------------------------- */

    redirectToReviews(
        $review_type,
        'success',
        'Review ' . strtolower($action_label) . ' successfully.'
    );


} catch (Throwable $e) {

    $conn->rollback();

    redirectToReviews(
        $review_type,
        'error',
        $e->getMessage()
    );
}
?>