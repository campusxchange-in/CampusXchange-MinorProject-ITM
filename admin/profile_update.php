<?php

/* =====================================================
   DEBUG MODE
===================================================== */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


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

    die('ERROR: Admin session not found.');
}


/* =====================================================
   REQUEST METHOD
===================================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    die('ERROR: This page only accepts POST requests.');
}


/* =====================================================
   DEBUG HELPER
===================================================== */

function debug_error($message, $stmt = null)
{
    echo '<div style="
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 40px auto;
        padding: 25px;
        background: #fff5f5;
        border: 1px solid #ffb3b3;
        border-radius: 10px;
        color: #8a0000;
    ">';

    echo '<h2 style="margin-top:0;">Profile Update Error</h2>';

    echo '<p><strong>' .
        htmlspecialchars($message) .
        '</strong></p>';

    if ($stmt && !empty($stmt->error)) {

        echo '<hr>';

        echo '<p><strong>MySQL Error:</strong></p>';

        echo '<pre style="
            white-space: pre-wrap;
            background:#fff;
            padding:15px;
            border:1px solid #ddd;
            color:#333;
        ">' .
            htmlspecialchars($stmt->error) .
            '</pre>';
    }

    echo '</div>';

    exit;
}


/* =====================================================
   CHECK DATABASE CONNECTION
===================================================== */

if (!$conn) {

    die('ERROR: Database connection failed.');
}


/* =====================================================
   GET FORM DATA
===================================================== */

$full_name   = trim($_POST['full_name'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$designation = trim($_POST['designation'] ?? '');
$department  = trim($_POST['department'] ?? '');
$address     = trim($_POST['address'] ?? '');
$city        = trim($_POST['city'] ?? '');
$state       = trim($_POST['state'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$bio         = trim($_POST['bio'] ?? '');


/* =====================================================
   DISPLAY RECEIVED DATA
   TEMPORARY DEBUG
===================================================== */

/*
echo '<pre>';
print_r($_POST);
print_r($_FILES);
echo '</pre>';
exit;
*/


/* =====================================================
   VALIDATION
===================================================== */

if ($full_name === '') {

    die('ERROR: Full name is required.');
}


if (mb_strlen($full_name) > 150) {

    die('ERROR: Full name exceeds 150 characters.');
}


if (mb_strlen($phone) > 20) {

    die('ERROR: Phone exceeds 20 characters.');
}


if (mb_strlen($designation) > 100) {

    die('ERROR: Designation exceeds 100 characters.');
}


if (mb_strlen($department) > 100) {

    die('ERROR: Department exceeds 100 characters.');
}


if (mb_strlen($address) > 255) {

    die('ERROR: Address exceeds 255 characters.');
}


if (mb_strlen($city) > 100) {

    die('ERROR: City exceeds 100 characters.');
}


if (mb_strlen($state) > 100) {

    die('ERROR: State exceeds 100 characters.');
}


if (mb_strlen($postal_code) > 20) {

    die('ERROR: Postal code exceeds 20 characters.');
}


if (mb_strlen($bio) > 1000) {

    die('ERROR: Bio exceeds 1000 characters.');
}


/* =====================================================
   FETCH CURRENT ADMIN
===================================================== */

$sql = "
    SELECT
        a.admin_id,
        a.full_name,
        a.email,
        a.deleted_at,
        ap.profile_picture

    FROM Admins a

    LEFT JOIN Admin_Profiles ap
        ON a.admin_id = ap.admin_id

    WHERE a.admin_id = ?
      AND a.deleted_at IS NULL

    LIMIT 1
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    debug_error(
        'Failed to prepare FETCH ADMIN query.',
        $stmt
    );
}


$stmt->bind_param("i", $admin_id);


if (!$stmt->execute()) {

    debug_error(
        'Failed to execute FETCH ADMIN query.',
        $stmt
    );
}


$result = $stmt->get_result();

$admin = $result->fetch_assoc();

$stmt->close();


if (!$admin) {

    die(
        'ERROR: Admin record was not found. Admin ID: ' .
        htmlspecialchars($admin_id)
    );
}


/* =====================================================
   PROFILE PICTURE VARIABLES
===================================================== */

$new_profile_picture = null;
$upload_new_picture = false;


/* =====================================================
   HANDLE PROFILE PICTURE
===================================================== */

if (
    isset($_FILES['profile_picture']) &&
    $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE
) {

    if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {

        die(
            'ERROR: Profile picture upload failed. Upload error code: ' .
            $_FILES['profile_picture']['error']
        );
    }


    /* ---------------------------------------------
       MAXIMUM FILE SIZE
    --------------------------------------------- */

    $max_file_size = 5 * 1024 * 1024;


    if ($_FILES['profile_picture']['size'] > $max_file_size) {

        die('ERROR: Profile picture is larger than 5 MB.');
    }


    /* ---------------------------------------------
       VERIFY IMAGE
    --------------------------------------------- */

    $tmp_name = $_FILES['profile_picture']['tmp_name'];

    $image_info = @getimagesize($tmp_name);


    if ($image_info === false) {

        die('ERROR: Uploaded file is not a valid image.');
    }


    /* ---------------------------------------------
       ALLOWED MIME TYPES
    --------------------------------------------- */

    $allowed_mime_types = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];


    $mime_type = $image_info['mime'] ?? '';


    if (!isset($allowed_mime_types[$mime_type])) {

        die(
            'ERROR: Invalid image type: ' .
            htmlspecialchars($mime_type)
        );
    }


    /* ---------------------------------------------
       UPLOAD DIRECTORY
    --------------------------------------------- */

    $upload_directory =
        __DIR__ . '/../uploads/admin_profiles/';


    if (!is_dir($upload_directory)) {

        if (!mkdir($upload_directory, 0755, true)) {

            die(
                'ERROR: Could not create upload directory: ' .
                htmlspecialchars($upload_directory)
            );
        }
    }


    /* ---------------------------------------------
       CHECK DIRECTORY WRITABLE
    --------------------------------------------- */

    if (!is_writable($upload_directory)) {

        die(
            'ERROR: Upload directory is not writable: ' .
            htmlspecialchars($upload_directory)
        );
    }


    /* ---------------------------------------------
       GENERATE FILE NAME
    --------------------------------------------- */

    $extension = $allowed_mime_types[$mime_type];

    $file_name =
        'admin_' .
        $admin_id .
        '_' .
        bin2hex(random_bytes(8)) .
        '.' .
        $extension;


    $file_path =
        $upload_directory .
        $file_name;


    /* ---------------------------------------------
       MOVE FILE
    --------------------------------------------- */

    if (!move_uploaded_file($tmp_name, $file_path)) {

        die(
            'ERROR: move_uploaded_file() failed.'
        );
    }


    $new_profile_picture = $file_name;

    $upload_new_picture = true;
}


/* =====================================================
   START TRANSACTION
===================================================== */

if (!$conn->begin_transaction()) {

    die('ERROR: Could not start database transaction.');
}


/* =====================================================
   UPDATE ADMINS TABLE
===================================================== */

$sql = "
    UPDATE Admins

    SET
        full_name = ?

    WHERE admin_id = ?
      AND deleted_at IS NULL
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    $conn->rollback();

    debug_error(
        'Failed to prepare Admins UPDATE query.',
        $stmt
    );
}


$stmt->bind_param(
    "si",
    $full_name,
    $admin_id
);


if (!$stmt->execute()) {

    $conn->rollback();

    debug_error(
        'Failed to update Admins table.',
        $stmt
    );
}


$stmt->close();


/* =====================================================
   CHECK ADMIN PROFILE
===================================================== */

$sql = "
    SELECT
        admin_id,
        profile_picture

    FROM Admin_Profiles

    WHERE admin_id = ?

    LIMIT 1
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    $conn->rollback();

    debug_error(
        'Failed to prepare Admin_Profiles SELECT query.',
        $stmt
    );
}


$stmt->bind_param("i", $admin_id);


if (!$stmt->execute()) {

    $conn->rollback();

    debug_error(
        'Failed to execute Admin_Profiles SELECT query.',
        $stmt
    );
}


$result = $stmt->get_result();

$existing_profile = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   UPDATE EXISTING PROFILE
===================================================== */

if ($existing_profile) {


    if ($upload_new_picture) {

        $sql = "
            UPDATE Admin_Profiles

            SET
                profile_picture = ?,
                phone = ?,
                designation = ?,
                department = ?,
                address = ?,
                city = ?,
                state = ?,
                postal_code = ?,
                bio = ?

            WHERE admin_id = ?
        ";


        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            $conn->rollback();

            debug_error(
                'Failed to prepare Admin_Profiles UPDATE query.',
                $stmt
            );
        }


        $stmt->bind_param(
            "sssssssssi",
            $new_profile_picture,
            $phone,
            $designation,
            $department,
            $address,
            $city,
            $state,
            $postal_code,
            $bio,
            $admin_id
        );

    } else {

        $sql = "
            UPDATE Admin_Profiles

            SET
                phone = ?,
                designation = ?,
                department = ?,
                address = ?,
                city = ?,
                state = ?,
                postal_code = ?,
                bio = ?

            WHERE admin_id = ?
        ";


        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            $conn->rollback();

            debug_error(
                'Failed to prepare Admin_Profiles UPDATE query.',
                $stmt
            );
        }


        $stmt->bind_param(
            "ssssssssi",
            $phone,
            $designation,
            $department,
            $address,
            $city,
            $state,
            $postal_code,
            $bio,
            $admin_id
        );
    }


    if (!$stmt->execute()) {

        $conn->rollback();

        debug_error(
            'Failed to update Admin_Profiles table.',
            $stmt
        );
    }


    $stmt->close();


/* =====================================================
   CREATE PROFILE
===================================================== */

} else {


    if ($upload_new_picture) {

        $sql = "
            INSERT INTO Admin_Profiles (
                admin_id,
                profile_picture,
                phone,
                designation,
                department,
                address,
                city,
                state,
                postal_code,
                bio
            )

            VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ";


        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            $conn->rollback();

            debug_error(
                'Failed to prepare Admin_Profiles INSERT query.',
                $stmt
            );
        }


        $stmt->bind_param(
            "isssssssss",
            $admin_id,
            $new_profile_picture,
            $phone,
            $designation,
            $department,
            $address,
            $city,
            $state,
            $postal_code,
            $bio
        );

    } else {

        $sql = "
            INSERT INTO Admin_Profiles (
                admin_id,
                phone,
                designation,
                department,
                address,
                city,
                state,
                postal_code,
                bio
            )

            VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ";


        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            $conn->rollback();

            debug_error(
                'Failed to prepare Admin_Profiles INSERT query.',
                $stmt
            );
        }


        $stmt->bind_param(
            "issssssss",
            $admin_id,
            $phone,
            $designation,
            $department,
            $address,
            $city,
            $state,
            $postal_code,
            $bio
        );
    }


    if (!$stmt->execute()) {

        $conn->rollback();

        debug_error(
            'Failed to insert Admin_Profiles record.',
            $stmt
        );
    }


    $stmt->close();
}


/* =====================================================
   COMMIT
===================================================== */

if (!$conn->commit()) {

    die('ERROR: Database commit failed.');
}


/* =====================================================
   DELETE OLD PROFILE PICTURE
===================================================== */

if (
    $upload_new_picture &&
    !empty($existing_profile['profile_picture'])
) {

    $old_picture =
        $existing_profile['profile_picture'];

    $old_picture_path =
        __DIR__ .
        '/../' .
        ltrim($old_picture, '/');


    if (
        file_exists($old_picture_path) &&
        is_file($old_picture_path)
    ) {

        @unlink($old_picture_path);
    }
}


/* =====================================================
   UPDATE SESSION
===================================================== */

$_SESSION['admin_name'] = $full_name;


/* =====================================================
   SUCCESS
===================================================== */

header("Location: profile?success=profile_updated");
exit;

?>