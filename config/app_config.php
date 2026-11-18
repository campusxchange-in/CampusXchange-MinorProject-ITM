<?php

/* =====================================================
   APPLICATION TIMEZONE
===================================================== */

date_default_timezone_set('Asia/Kolkata');


/* =====================================================
   APPLICATION URLS
===================================================== */

if (!defined("FRONTEND_URL")) {
    define("FRONTEND_URL", "http://localhost:5173");
}

if (!defined("PHP_BASE_URL")) {
    define("PHP_BASE_URL", "http://localhost/CX");
}

?>