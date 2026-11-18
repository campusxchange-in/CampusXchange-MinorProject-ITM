<?php

$page_title = 'Edit Profile | CampusXchange';
$page_heading = 'Edit Profile';
$page_description = 'Update your personal administrator profile information.';

$active_page = '';
$page_css = 'profile_edit.css';


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
   FETCH CURRENT PROFILE
===================================================== */

$admin = null;

$sql = "
    SELECT
        a.admin_id,
        a.full_name,
        a.email,
        a.admin_role,
        a.status,

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
   SAFETY CHECK
===================================================== */

if (!$admin) {

    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}


/* =====================================================
   FORM VALUES
===================================================== */

$full_name = $admin['full_name'] ?? '';
$phone = $admin['phone'] ?? '';
$designation = $admin['designation'] ?? '';
$department = $admin['department'] ?? '';
$address = $admin['address'] ?? '';
$city = $admin['city'] ?? '';
$state = $admin['state'] ?? '';
$postal_code = $admin['postal_code'] ?? '';
$bio = $admin['bio'] ?? '';



/* =====================================================
   BREADCRUMB
===================================================== */

$breadcrumbs = [
    [
        'label' => 'Profile',
        'url' => 'profile'
    ],
    [
        'label' => 'Edit Profile'
    ]
];


include 'includes/admin_header.php';

?>


<div class="profile-edit-page">


    <!-- =================================================
         EDIT PROFILE CARD
    ================================================== -->

    <div class="profile-card">


        <div class="profile-card-header">

            <div>

                <h2>
                    Personal Information
                </h2>

                <p>
                    Update your administrator profile details.
                </p>

            </div>

        </div>


        <!-- =================================================
             PROFILE FORM
        ================================================== -->

        <form
            action="profile_update"
            method="POST"
            enctype="multipart/form-data"
            class="profile-edit-form"
        >


            <!-- =================================================
                 PROFILE PICTURE
            ================================================== -->

            <div class="profile-picture-section">


                <div class="profile-picture-preview">

                    <?php if (!empty($admin['profile_picture'])): ?>

                        <img
                            src="../uploads/admin_profiles/<?= htmlspecialchars(
                                $admin['profile_picture']
                            ) ?>"
                            alt="Profile Picture"
                            class="profile-edit-avatar"
                        >

                    <?php else: ?>

                        <div class="profile-edit-avatar-placeholder">

                            <?= strtoupper(
                                substr(
                                    $admin['full_name'] ?? 'A',
                                    0,
                                    1
                                )
                            ) ?>

                        </div>

                    <?php endif; ?>

                </div>


                <div class="profile-picture-controls">

                    <label
                        for="profile_picture"
                        class="profile-upload-btn"
                    >

                        <i class="fa-solid fa-camera"></i>

                        Change Picture

                    </label>

                    <input
                        type="file"
                        id="profile_picture"
                        name="profile_picture"
                        accept="image/*"
                        hidden
                    >

                    <small>
                        JPG, PNG or WEBP. Recommended square image.
                    </small>

                </div>

            </div>



            <!-- =================================================
                 BASIC INFORMATION
            ================================================== -->

            <div class="form-section">

                <div class="form-section-title">

                    <h3>
                        Basic Information
                    </h3>

                    <p>
                        Your basic administrator information.
                    </p>

                </div>


                <div class="form-grid">


                    <!-- FULL NAME -->

                    <div class="form-group">

                        <label for="full_name">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            value="<?= htmlspecialchars($full_name) ?>"
                            maxlength="150"
                            required
                        >

                    </div>



                    <!-- PHONE -->

                    <div class="form-group">

                        <label for="phone">
                            Phone
                        </label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="<?= htmlspecialchars($phone) ?>"
                            maxlength="20"
                            placeholder="Enter phone number"
                        >

                    </div>



                    <!-- DESIGNATION -->

                    <div class="form-group">

                        <label for="designation">
                            Designation
                        </label>

                        <input
                            type="text"
                            id="designation"
                            name="designation"
                            value="<?= htmlspecialchars($designation) ?>"
                            maxlength="100"
                            placeholder="e.g. System Administrator"
                        >

                    </div>



                    <!-- DEPARTMENT -->

                    <div class="form-group">

                        <label for="department">
                            Department
                        </label>

                        <input
                            type="text"
                            id="department"
                            name="department"
                            value="<?= htmlspecialchars($department) ?>"
                            maxlength="100"
                            placeholder="e.g. Administration"
                        >

                    </div>


                </div>

            </div>



            <!-- =================================================
                 ADDRESS
            ================================================== -->

            <div class="form-section">

                <div class="form-section-title">

                    <h3>
                        Address
                    </h3>

                    <p>
                        Your administrator contact location.
                    </p>

                </div>


                <div class="form-grid">


                    <!-- ADDRESS -->

                    <div class="form-group form-group-full">

                        <label for="address">
                            Address
                        </label>

                        <input
                            type="text"
                            id="address"
                            name="address"
                            value="<?= htmlspecialchars($address) ?>"
                            maxlength="255"
                            placeholder="Enter your address"
                        >

                    </div>



                    <!-- CITY -->

                    <div class="form-group">

                        <label for="city">
                            City
                        </label>

                        <input
                            type="text"
                            id="city"
                            name="city"
                            value="<?= htmlspecialchars($city) ?>"
                            maxlength="100"
                        >

                    </div>



                    <!-- STATE -->

                    <div class="form-group">

                        <label for="state">
                            State
                        </label>

                        <input
                            type="text"
                            id="state"
                            name="state"
                            value="<?= htmlspecialchars($state) ?>"
                            maxlength="100"
                        >

                    </div>



                    <!-- POSTAL CODE -->

                    <div class="form-group">

                        <label for="postal_code">
                            Postal Code
                        </label>

                        <input
                            type="text"
                            id="postal_code"
                            name="postal_code"
                            value="<?= htmlspecialchars($postal_code) ?>"
                            maxlength="20"
                        >

                    </div>


                </div>

            </div>



            <!-- =================================================
                 BIO
            ================================================== -->

            <div class="form-section">

                <div class="form-section-title">

                    <h3>
                        About
                    </h3>

                    <p>
                        Add a short description about yourself.
                    </p>

                </div>


                <div class="form-group">

                    <label for="bio">
                        Bio
                    </label>

                    <textarea
                        id="bio"
                        name="bio"
                        rows="5"
                        maxlength="1000"
                        placeholder="Write a short bio..."
                    ><?= htmlspecialchars($bio) ?></textarea>

                </div>

            </div>



            <!-- =================================================
                 ACCOUNT NOTICE
            ================================================== -->

            <div class="profile-edit-notice">

                <i class="fa-solid fa-shield-halved"></i>

                <div>

                    <strong>
                        Account & Security
                    </strong>

                    <p>
                        Email address, password, account status
                        and administrator role are managed separately
                        for security purposes.
                    </p>

                </div>

                <a href="account_security">

                    Manage Account

                    <i class="fa-solid fa-arrow-right"></i>

                </a>

            </div>



            <!-- =================================================
                 FORM ACTIONS
            ================================================== -->

            <div class="form-actions">


                <a
                    href="profile"
                    class="btn-secondary"
                >

                    <i class="fa-solid fa-xmark"></i>

                    Cancel

                </a>


                <button
                    type="submit"
                    class="btn-primary"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Save Changes

                </button>


            </div>


        </form>

    </div>


</div>


<?php include 'includes/admin_footer.php'; ?>