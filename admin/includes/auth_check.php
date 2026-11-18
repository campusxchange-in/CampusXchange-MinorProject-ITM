<?php

// include '../../config/app_config.php';
require_once __DIR__ . '/../../config/app_config.php';
session_start();

if (!isset($_SESSION["admin_id"])) {

    header("Location: " . FRONTEND_URL . "/admin-login");
    exit;
}
?>