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
   ONLY ACCEPT POST REQUEST
===================================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: account_security"
    );

    exit;

}


/* =====================================================
   GET FORM DATA
===================================================== */

$current_password =
    trim($_POST['current_password'] ?? '');

$new_password =
    trim($_POST['new_password'] ?? '');

$confirm_password =
    trim($_POST['confirm_password'] ?? '');


/* =====================================================
   VALIDATION
===================================================== */

if ($current_password === '') {

    header(
        "Location: account_security?error=" .
        urlencode("Please enter your current password.")
    );

    exit;
}


if ($new_password === '') {

    header(
        "Location: account_security?error=" .
        urlencode("Please enter a new password.")
    );

    exit;
}


if (strlen($new_password) < 8) {

    header(
        "Location: account_security?error=" .
        urlencode(
            "New password must contain at least 8 characters."
        )
    );

    exit;
}


if ($confirm_password === '') {

    header(
        "Location: account_security?error=" .
        urlencode(
            "Please confirm your new password."
        )
    );

    exit;
}


if ($new_password !== $confirm_password) {

    header(
        "Location: account_security?error=" .
        urlencode(
            "New password and confirmation password do not match."
        )
    );

    exit;
}


/* =====================================================
   FETCH CURRENT PASSWORD
===================================================== */

$sql = "
    SELECT password
    FROM Admins
    WHERE admin_id = ?
      AND deleted_at IS NULL
    LIMIT 1
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    header(
        "Location: account_security?error=" .
        urlencode(
            "Unable to process your request."
        )
    );

    exit;
}


$stmt->bind_param("i", $admin_id);

$stmt->execute();

$result = $stmt->get_result();

$admin = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   CHECK ADMIN
===================================================== */

if (!$admin) {

    header(
        "Location: account_security?error=" .
        urlencode(
            "Administrator account could not be found."
        )
    );

    exit;
}


/* =====================================================
   CHECK CURRENT PASSWORD
===================================================== */

/*
   Passwords are stored as secure hashes.

   The entered password is verified against
   the stored hash using password_verify().
*/

if (!password_verify(
    $current_password,
    $admin['password']
)) {

    header(
        "Location: account_security?error=" .
        urlencode(
            "Current password is incorrect."
        )
    );

    exit;
}


/* =====================================================
   CHECK NEW PASSWORD IS DIFFERENT
===================================================== */

/*
   password_verify() is used here as well because
   the database contains a hash, not the plaintext
   current password.
*/

if (password_verify(
    $new_password,
    $admin['password']
)) {

    header(
        "Location: account_security?error=" .
        urlencode(
            "New password must be different from your current password."
        )
    );

    exit;
}


/* =====================================================
   HASH NEW PASSWORD
===================================================== */

/*
   NEVER store the new password directly.

   password_hash() automatically generates a secure
   salt and creates the password hash.
*/

$new_password_hash = password_hash(
    $new_password,
    PASSWORD_DEFAULT
);


/* =====================================================
   CHECK PASSWORD HASH CREATION
===================================================== */

if ($new_password_hash === false) {

    header(
        "Location: account_security?error=" .
        urlencode(
            "Unable to secure your new password."
        )
    );

    exit;
}


/* =====================================================
   UPDATE PASSWORD
===================================================== */

$sql = "
    UPDATE Admins
    SET password = ?
    WHERE admin_id = ?
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    header(
        "Location: account_security?error=" .
        urlencode(
            "Unable to update your password."
        )
    );

    exit;
}


$stmt->bind_param(
    "si",
    $new_password_hash,
    $admin_id
);


/* =====================================================
   EXECUTE PASSWORD UPDATE
===================================================== */

if ($stmt->execute()) {

    $stmt->close();

    header(
        "Location: account_security?success=" .
        urlencode(
            "Your password has been updated successfully."
        )
    );

    exit;

}


/* =====================================================
   UPDATE FAILED
===================================================== */

$stmt->close();


header(
    "Location: account_security?error=" .
    urlencode(
        "Unable to update your password."
    )
);

exit;

?>