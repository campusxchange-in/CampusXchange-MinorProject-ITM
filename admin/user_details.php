<?php

$page_title = 'User Details | CampusXchange';
$page_heading = 'User Details';
$page_description = 'View detailed information about this user.';
$active_page = 'users';

include 'includes/auth_check.php';


// ==========================================
// GET USER ID
// ==========================================

$user_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 1;


// ==========================================
// BREADCRUMB
// ==========================================

$breadcrumbs = [
    [
        'label' => 'Users',
        'url' => 'users'
    ],

    [
        'label' => 'User Details'
    ]
];


include 'includes/admin_header.php';


// ==========================================
// BREADCRUMB
// ==========================================

// include 'includes/breadcrumb.php';

?>

<!-- =========================================
     CONTENT CARD
========================================== -->

<div class="content-card">

    <h2>
        User Information
    </h2>


    <div class="dummy-user-details">

        <p>
            <strong>User ID:</strong>
            <?= htmlspecialchars($user_id) ?>
        </p>


        <p>
            <strong>Name:</strong>
            Amit Sharma
        </p>


        <p>
            <strong>Email:</strong>
            amit@example.com
        </p>


        <p>
            <strong>Role:</strong>
            Student
        </p>


        <p>
            <strong>Status:</strong>
            Active
        </p>


    </div>


    <br>


    <a
        href="users"
        class="btn-view"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Users

    </a>

</div>



<?php include 'includes/admin_footer.php'; ?>