<?php
/* GET CX/backend/user/profile_get.php
 * Profile + identity documents + verification status + completion %. */
require_once __DIR__ . '/_user_common.php';
require_once __DIR__ . '/_profile_helpers.php';
cx_require_method('GET');

$c = cx_profile_refresh($conn, $userId);
$r = cx_profile_load($conn, $userId);
cx_json_response(true, 'OK', cx_profile_payload($r, $c));
