<?php

$page_title = 'My Profile | CampusXchange';
$page_heading = 'My Profile';
$page_description = 'View and manage your administrator profile.';

$active_page = '';
$page_css = 'profile.css';


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


/* =====================================================
   FETCH ADMIN PROFILE
===================================================== */

$admin = null;

$sql = "
    SELECT
        a.admin_id,
        a.full_name,
        a.email,
        a.admin_role,
        a.status,
        a.created_at,

        ap.profile_picture,
        ap.phone,
        ap.designation,
        ap.department,
        ap.address,
        ap.city,
        ap.state,
        ap.postal_code,
        ap.bio

    FROM Admins a

    LEFT JOIN Admin_Profiles ap
        ON a.admin_id = ap.admin_id

    WHERE a.admin_id = ?
      AND a.deleted_at IS NULL

    LIMIT 1
";


$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $admin_id);

    $stmt->execute();

    $result = $stmt->get_result();

    $admin = $result->fetch_assoc();

    $stmt->close();
}


/* =====================================================
   SAFETY FALLBACK
===================================================== */

if (!$admin) {

    $admin = [

        'admin_id'        => $admin_id,

        'full_name'       => 'Admin',
        'email'           => '',
        'admin_role'      => 'Admin',
        'status'          => 'Active',
        'created_at'      => null,

        'profile_picture' => null,

        'phone'           => '',
        'designation'     => '',
        'department'      => '',
        'address'         => '',
        'city'            => '',
        'state'           => '',
        'postal_code'     => '',
        'bio'             => ''

    ];

}


/* =====================================================
   BREADCRUMB
===================================================== */

$breadcrumbs = [
    [
        'label' => 'Profile'
    ]
];


include 'includes/admin_header.php';

?>


<div class="profile-page">


    <!-- =================================================
         PROFILE OVERVIEW
    ================================================== -->

    <div class="profile-card profile-overview">


        <!-- PROFILE AVATAR -->

        <div class="profile-avatar-wrapper">

            <?php

            $profilePicture =
                trim($admin['profile_picture'] ?? '');

            ?>

            <?php if (!empty($profilePicture)): ?>

                <img
                    src="../uploads/admin_profiles/<?= htmlspecialchars($profilePicture) ?>"
                    alt="Profile Picture"
                    class="profile-avatar-image"
                >

            <?php else: ?>

                <div class="profile-avatar-large">

                    <?= htmlspecialchars(
                        strtoupper(
                            substr(
                                trim($admin['full_name'] ?? 'A'),
                                0,
                                1
                            )
                        )
                    ) ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- PROFILE INFORMATION -->

        <div class="profile-overview-info">

            <h2>
                <?= htmlspecialchars(
                    $admin['full_name']
                ) ?>
            </h2>


            <p class="profile-designation">

                <?= htmlspecialchars(
                    $admin['designation']
                    ?: $admin['admin_role']
                ) ?>

            </p>


            <p class="profile-email">

                <i class="fa-solid fa-envelope"></i>

                <?= htmlspecialchars(
                    $admin['email']
                ) ?>

            </p>

        </div>


        <!-- ACCOUNT STATUS -->

        <div class="profile-status">

            <span
                class="status-badge
                <?= strtolower($admin['status']) === 'active'
                    ? 'status-active'
                    : 'status-inactive'
                ?>"
            >

                <i class="fa-solid fa-circle"></i>

                <?= htmlspecialchars(
                    $admin['status']
                ) ?>

            </span>

        </div>

    </div>



    <!-- =================================================
         PERSONAL INFORMATION
    ================================================== -->

    <div class="profile-card">


        <div class="profile-card-header">

            <div>

                <h2>
                    Personal Information
                </h2>

                <p>
                    Your administrator profile details.
                </p>

            </div>


            <a
                href="profile_edit"
                class="profile-edit-btn"
            >

                <i class="fa-solid fa-pen"></i>

                Edit Profile

            </a>

        </div>



        <div class="profile-info-grid">


            <!-- FULL NAME -->

            <div class="profile-info-item">

                <span class="profile-info-label">
                    Full Name
                </span>

                <strong>

                    <?= htmlspecialchars(
                        $admin['full_name']
                    ) ?>

                </strong>

            </div>



            <!-- PHONE -->

            <div class="profile-info-item">

                <span class="profile-info-label">
                    Phone
                </span>

                <strong>

                    <?= htmlspecialchars(
                        $admin['phone']
                        ?: 'Not provided'
                    ) ?>

                </strong>

            </div>



            <!-- DESIGNATION -->

            <div class="profile-info-item">

                <span class="profile-info-label">
                    Designation
                </span>

                <strong>

                    <?= htmlspecialchars(
                        $admin['designation']
                        ?: 'Not provided'
                    ) ?>

                </strong>

            </div>



            <!-- DEPARTMENT -->

            <div class="profile-info-item">

                <span class="profile-info-label">
                    Department
                </span>

                <strong>

                    <?= htmlspecialchars(
                        $admin['department']
                        ?: 'Not provided'
                    ) ?>

                </strong>

            </div>



            <!-- ADDRESS -->

            <div class="profile-info-item profile-info-full">

                <span class="profile-info-label">
                    Address
                </span>

                <strong>

                    <?php

                    $addressParts = array_filter([
                        $admin['address'],
                        $admin['city'],
                        $admin['state'],
                        $admin['postal_code']
                    ]);

                    echo htmlspecialchars(
                        !empty($addressParts)
                            ? implode(', ', $addressParts)
                            : 'Not provided'
                    );

                    ?>

                </strong>

            </div>



            <!-- BIO -->

            <div class="profile-info-item profile-info-full">

                <span class="profile-info-label">
                    Bio
                </span>

                <p>

                    <?= !empty($admin['bio'])

                        ? nl2br(
                            htmlspecialchars(
                                $admin['bio']
                            )
                        )

                        : 'No bio added.'
                    ?>

                </p>

            </div>


        </div>

    </div>



    <!-- =================================================
         ACCOUNT & SECURITY
    ================================================== -->

    <div class="profile-card">


        <div class="profile-card-header">

            <div>

                <h2>
                    Account & Security
                </h2>

                <p>
                    Manage your email, password and account security.
                </p>

            </div>


            <a
                href="account_security"
                class="profile-edit-btn"
            >

                <i class="fa-solid fa-shield-halved"></i>

                Manage Account

            </a>

        </div>



        <div class="profile-info-grid">


            <!-- CURRENT EMAIL -->

            <div class="profile-info-item">

                <span class="profile-info-label">
                    Current Email
                </span>

                <strong>

                    <?= htmlspecialchars(
                        $admin['email']
                    ) ?>

                </strong>

            </div>



            <!-- ADMIN ROLE -->

            <div class="profile-info-item">

                <span class="profile-info-label">
                    Admin Role
                </span>

                <strong>

                    <?= htmlspecialchars(
                        $admin['admin_role']
                    ) ?>

                </strong>

            </div>



            <!-- ACCOUNT STATUS -->

            <div class="profile-info-item">

                <span class="profile-info-label">
                    Account Status
                </span>

                <strong>

                    <?= htmlspecialchars(
                        $admin['status']
                    ) ?>

                </strong>

            </div>



            <!-- JOINED DATE -->

            <div class="profile-info-item">

                <span class="profile-info-label">
                    Joined On
                </span>

                <strong>

                    <?= !empty($admin['created_at'])

                        ? date(
                            'd M Y',
                            strtotime(
                                $admin['created_at']
                            )
                        )

                        : '—'
                    ?>

                </strong>

            </div>



            <!-- PASSWORD -->

            <!-- <div class="profile-info-item profile-info-full">

                <span class="profile-info-label">
                    Password
                </span>

                <strong class="password-hidden">

                    ••••••••••••••••

                </strong>

            </div> -->


        </div>

    </div>



</div>


<?php include 'includes/admin_footer.php'; ?>