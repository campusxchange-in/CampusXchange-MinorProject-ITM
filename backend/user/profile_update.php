<?php
/* POST CX/backend/user/profile_update.php   (multipart/form-data)
 * Every card on the Profile page saves through this one endpoint:
 *   section = basic | phone | roll | id_cards | address | live_photo | avatar | bio
 * Identity sections are locked while verification is Pending or Approved
 * (email / phone / bio / avatar can still be changed). */
require_once __DIR__ . '/_user_common.php';
require_once __DIR__ . '/_profile_helpers.php';
cx_require_method('POST');

$cur = cx_profile_load($conn, $userId);
$vs = $cur['verification_status'] ?? 'Not Submitted';
$locked = in_array($vs, ['Pending', 'Approved'], true);

$section = (string)($_POST['section'] ?? '');
$txt = static fn(string $k, int $max): string => mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);
$imgTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

$identitySections = ['basic', 'roll', 'id_cards', 'address', 'live_photo'];
if ($locked && in_array($section, $identitySections, true)) {
    cx_json_response(false, 'Your details are locked while verification is pending or approved.', [], 403);
}

/* make sure the profile row exists */
cx_exec($conn, "INSERT IGNORE INTO User_Profiles (user_id) VALUES (?)", 'i', [$userId]);

switch ($section) {

    case 'basic':
        $name = $txt('full_name', 100);
        $college = $txt('college', 150);
        $course = $txt('course', 100);
        $year = $txt('graduation_year', 10);
        $location = $txt('location', 150);
        if ($name === '') cx_json_response(false, 'Name cannot be empty.', [], 422);
        if ($year !== '' && !preg_match('/^(19|20)[0-9]{2}$/', $year)) {
            cx_json_response(false, 'Enter a valid year, for example 2027.', [], 422);
        }
        cx_exec($conn, "UPDATE Users SET full_name = ? WHERE user_id = ?", 'si', [$name, $userId]);
        cx_exec($conn,
            "UPDATE User_Profiles SET college_name = ?, course = ?, graduation_year = ?, location = ? WHERE user_id = ?",
            'ssssi', [$college, $course, $year, $location, $userId]);
        break;

    case 'bio':
        cx_exec($conn, "UPDATE User_Profiles SET bio = ? WHERE user_id = ?", 'si', [$txt('bio', 500), $userId]);
        break;

    case 'phone':
        $phone = preg_replace('/\D/', '', (string)($_POST['phone'] ?? ''));
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            cx_json_response(false, 'Please enter a valid 10-digit phone number.', [], 422);
        }
        $dup = cx_fetch_one($conn, "SELECT user_id FROM Users WHERE phone = ? AND user_id <> ? LIMIT 1", 'si', [$phone, $userId]);
        if ($dup) cx_json_response(false, 'This phone number is already used by another account.', [], 409);
        cx_exec($conn, "UPDATE Users SET phone = ? WHERE user_id = ?", 'si', [$phone, $userId]);
        cx_exec($conn, "UPDATE User_Profiles SET phone = ? WHERE user_id = ?", 'si', [$phone, $userId]);
        break;

    case 'roll':
        $roll = strtoupper($txt('roll_number', 50));
        if (!preg_match('/^[A-Z0-9-]{3,50}$/', $roll)) {
            cx_json_response(false, 'Please enter a valid roll number.', [], 422);
        }
        $idType = ($cur['role'] ?? '') === 'Faculty' ? 'Faculty ID' : 'Student ID';
        cx_exec($conn,
            "INSERT INTO User_Verification (user_id, id_type, id_number) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE id_type = VALUES(id_type), id_number = VALUES(id_number), deleted_at = NULL",
            'iss', [$userId, $idType, $roll]);
        break;

    case 'id_cards':
        $front = $back = null;
        try {
            if ($f = cx_files_list('id_front')) $front = cx_save_upload($f[0], 'verification', $imgTypes);
            if ($f = cx_files_list('id_back'))  $back  = cx_save_upload($f[0], 'verification', $imgTypes);
        } catch (RuntimeException $e) {
            cx_json_response(false, $e->getMessage(), [], 422);
        }
        if (!$front && !$back) cx_json_response(false, 'Choose an ID card image to upload.', [], 422);
        $idType = ($cur['role'] ?? '') === 'Faculty' ? 'Faculty ID' : 'Student ID';
        cx_exec($conn,
            "INSERT INTO User_Verification (user_id, id_type) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE id_type = VALUES(id_type), deleted_at = NULL",
            'is', [$userId, $idType]);
        if ($front) cx_exec($conn, "UPDATE User_Verification SET college_id_front_image = ? WHERE user_id = ?", 'si', [$front, $userId]);
        if ($back)  cx_exec($conn, "UPDATE User_Verification SET college_id_back_image = ? WHERE user_id = ?", 'si', [$back, $userId]);
        break;

    case 'address':
        $addr = $txt('address_line', 255);
        $city = $txt('city', 100);
        $state = $txt('state', 100);
        $pin = preg_replace('/\D/', '', (string)($_POST['pincode'] ?? ''));
        if ($addr === '') cx_json_response(false, 'Address is required.', [], 422);
        if ($city === '') cx_json_response(false, 'City is required.', [], 422);
        if ($state === '') cx_json_response(false, 'State is required.', [], 422);
        if (!preg_match('/^[0-9]{6}$/', $pin)) cx_json_response(false, 'Enter a valid 6-digit PIN code.', [], 422);
        cx_exec($conn,
            "UPDATE User_Profiles SET address_line = ?, city = ?, state = ?, pincode = ?, location = ? WHERE user_id = ?",
            'sssssi', [$addr, $city, $state, $pin, mb_substr($city . ', ' . $state, 0, 150), $userId]);
        break;

    case 'live_photo':
        if (!empty($_POST['clear'])) {
            cx_exec($conn, "UPDATE User_Profiles SET live_photo = NULL WHERE user_id = ?", 'i', [$userId]);
            break;
        }
        try {
            $f = cx_files_list('live_photo');
            if (!$f) cx_json_response(false, 'No photo received.', [], 422);
            $name = cx_save_upload($f[0], 'verification', $imgTypes);
        } catch (RuntimeException $e) {
            cx_json_response(false, $e->getMessage(), [], 422);
        }
        cx_exec($conn, "UPDATE User_Profiles SET live_photo = ? WHERE user_id = ?", 'si', [$name, $userId]);
        break;

    case 'avatar':
        try {
            $f = cx_files_list('profile_picture');
            if (!$f) cx_json_response(false, 'Choose an image.', [], 422);
            $name = cx_save_upload($f[0], 'user_profiles', $imgTypes);
        } catch (RuntimeException $e) {
            cx_json_response(false, $e->getMessage(), [], 422);
        }
        cx_exec($conn, "UPDATE User_Profiles SET profile_picture = ? WHERE user_id = ?", 'si', [$name, $userId]);
        break;

    default:
        cx_json_response(false, 'Unknown section.', [], 422);
}

$c = cx_profile_refresh($conn, $userId);
cx_json_response(true, 'Saved.', cx_profile_payload(cx_profile_load($conn, $userId), $c));
