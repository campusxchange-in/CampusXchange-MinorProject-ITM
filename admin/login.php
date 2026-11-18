
<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

require_once "../config/app_config.php";

// =====================================================
// CORS - REACT / VITE
// =====================================================

header("Access-Control-Allow-Origin: " . FRONTEND_URL);
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// =====================================================
// PREFLIGHT
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// =====================================================
// ONLY POST REQUESTS
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

// =====================================================
// JSON RESPONSE HELPER
// =====================================================

function admin_login_response($success, $message, $status_code = 200)
{
    http_response_code($status_code);

    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);

    exit;
}

// =====================================================
// DATABASE CONNECTION
// =====================================================

require_once "../server/database/db_connect.php";

// =====================================================
// READ JSON REQUEST
// =====================================================

$data = json_decode(file_get_contents("php://input"), true);

$email = strtolower(trim($data["email"] ?? ""));
$password = $data["password"] ?? "";

// =====================================================
// BASIC VALIDATION
// =====================================================

if ($email === "" || $password === "") {
    admin_login_response(
        false,
        "Invalid email or password.",
        401
    );
}

// =====================================================
// LOGIN PROCESS
// =====================================================

try {

    // Fetch the account regardless of status.
    // This allows us to distinguish incorrect credentials
    // from suspended or banned accounts after password verification.

    $stmt = $conn->prepare("
        SELECT
            admin_id,
            full_name,
            email,
            email_verified_at,
            password,
            admin_role,
            status,
            created_at
        FROM Admins
        WHERE email = ?
          AND deleted_at IS NULL
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException("Unable to prepare login query.");
    }

    $stmt->bind_param("s", $email);

    $stmt->execute();

    $result = $stmt->get_result();

    $admin = $result->fetch_assoc();

    $stmt->close();

    // =================================================
    // VERIFY EMAIL + PASSWORD
    // =================================================

    if (
        !$admin ||
        !password_verify($password, $admin["password"])
    ) {
        admin_login_response(
            false,
            "Invalid email or password. Please try again.",
            401
        );
    }

    // =================================================
    // ACCOUNT STATUS
    // =================================================

    if ($admin["status"] === "Suspended") {
        admin_login_response(
            false,
            "Your administrator account has been suspended. Please contact the Main Admin.",
            403
        );
    }

    if ($admin["status"] === "Banned") {
        admin_login_response(
            false,
            "Your administrator account has been banned. Please contact the Main Admin.",
            403
        );
    }

    if ($admin["status"] !== "Active") {
        admin_login_response(
            false,
            "Your administrator account is currently unavailable.",
            403
        );
    }

    // =================================================
    // EMAIL VERIFICATION
    // =================================================

    if (empty($admin["email_verified_at"])) {
        admin_login_response(
            false,
            "Please verify your administrator email address before logging in.",
            403
        );
    }

    // =================================================
    // CREATE SESSION
    // =================================================

    session_regenerate_id(true);

    $_SESSION["admin_id"] = $admin["admin_id"];
    $_SESSION["admin_name"] = $admin["full_name"];
    $_SESSION["admin_email"] = $admin["email"];
    $_SESSION["admin_role"] = $admin["admin_role"];

    // =================================================
    // ACTIVITY LOG
    // =================================================

    $log_description =
        'Admin "' .
        $admin["full_name"] .
        '" logged in successfully.';

    $ip_address = $_SERVER["REMOTE_ADDR"] ?? null;

    $user_agent = substr(
        $_SERVER["HTTP_USER_AGENT"] ?? "",
        0,
        500
    );

    $log = $conn->prepare("
        INSERT INTO Admin_Logs (
            admin_id,
            action_type,
            action_description,
            target_type,
            target_id,
            ip_address,
            user_agent,
            created_at
        )
        VALUES (
            ?,
            'Login',
            ?,
            NULL,
            NULL,
            ?,
            ?,
            CURRENT_TIMESTAMP
        )
    ");

    if ($log) {
        $log->bind_param(
            "isss",
            $admin["admin_id"],
            $log_description,
            $ip_address,
            $user_agent
        );

        $log->execute();
        $log->close();
    }

    // =================================================
    // SUCCESS
    // =================================================

    admin_login_response(
        true,
        "Login successful.",
        200
    );

} catch (Throwable $e) {

    error_log("Admin Login Error: " . $e->getMessage());

    admin_login_response(
        false,
        "Unable to process your login request. Please try again later.",
        500
    );
}

// Close connection if execution reaches here.
$conn->close();

?>
