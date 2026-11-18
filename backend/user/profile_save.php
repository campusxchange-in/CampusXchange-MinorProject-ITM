<?php
/* POST CX/backend/user/profile_save.php   (multipart/form-data)
 * fields: role, department, course, year_of_study, semester, location, bio,
 *         phone, id_number
 * files:  profile_picture, id_front, id_back
 *
 * While verification is Pending or Approved the identity details are locked;
 * only bio and phone may still change (scope: phone/email/password stay editable). */
require_once __DIR__ . '/_user_common.php';
require_once __DIR__ . '/_profile_helpers.php';
cx_require_method('POST');

$cur = cx_profile_load($conn, $userId);
$vs = $cur['verification_status'] ?? 'Not Submitted';
$locked = in_array($vs, ['Pending', 'Approved'], true);

$txt = static fn(string $k, int $max): string => mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);

$phone = preg_replace('/[^0-9+]/', '', (string)($_POST['phone'] ?? ''));
if ($phone !== '' && !preg_match('/^\+?[0-9]{10,14}$/', $phone)) {
    cx_json_response(false, 'Enter a valid phone number.', [], 422);
}
if ($phone !== '') {
    $dup = cx_fetch_one($conn, "SELECT user_id FROM Users WHERE phone = ? AND user_id <> ? LIMIT 1", 'si', [mb_substr($phone, 0, 15), $userId]);
    if ($dup) cx_json_response(false, 'This phone number is already used by another account.', [], 409);
}

$bio = $txt('bio', 500);

if ($locked) {
    /* only phone + bio */
    cx_exec($conn,
        "INSERT INTO User_Profiles (user_id, bio, phone) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE bio = VALUES(bio), phone = VALUES(phone)",
        'iss', [$userId, $bio, $phone !== '' ? mb_substr($phone, 0, 20) : null]);
    if ($phone !== '') {
        cx_exec($conn, "UPDATE Users SET phone = ? WHERE user_id = ?", 'si', [mb_substr($phone, 0, 15), $userId]);
    }
    $c = cx_profile_refresh($conn, $userId);
    cx_json_response(true, 'Saved. Verified/pending identity details cannot be changed.',
        cx_profile_payload(cx_profile_load($conn, $userId), $c));
}

$role = (string)($_POST['role'] ?? '');
if (!in_array($role, ['Student', 'Faculty', ''], true)) cx_json_response(false, 'Invalid role.', [], 422);
$idNumber = $txt('id_number', 50);
$year = $txt('year_of_study', 20);
$sem = $txt('semester', 20);

$imgTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$picture = $front = $back = null;
try {
    if ($f = cx_files_list('profile_picture')) $picture = cx_save_upload($f[0], 'user_profiles', $imgTypes);
    if ($f = cx_files_list('id_front')) $front = cx_save_upload($f[0], 'verification', $imgTypes);
    if ($f = cx_files_list('id_back')) $back = cx_save_upload($f[0], 'verification', $imgTypes);
} catch (RuntimeException $e) {
    cx_json_response(false, $e->getMessage(), [], 422);
}

$conn->begin_transaction();
try {
    if ($role !== '') {
        cx_exec($conn, "UPDATE Users SET role = ? WHERE user_id = ?", 'si', [$role, $userId]);
    }
    if ($phone !== '') {
        cx_exec($conn, "UPDATE Users SET phone = ? WHERE user_id = ?", 'si', [mb_substr($phone, 0, 15), $userId]);
    }

    cx_exec($conn,
        "INSERT INTO User_Profiles (user_id, phone, department, course, year_of_study, semester, bio, location)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE phone = VALUES(phone), department = VALUES(department),
            course = VALUES(course), year_of_study = VALUES(year_of_study),
            semester = VALUES(semester), bio = VALUES(bio), location = VALUES(location)",
        'isssssss',
        [$userId, $phone !== '' ? mb_substr($phone, 0, 20) : null, $txt('department', 100), $txt('course', 100),
         $year, $sem, $bio, $txt('location', 150)]);

    if ($picture) {
        cx_exec($conn, "UPDATE User_Profiles SET profile_picture = ? WHERE user_id = ?", 'si', [$picture, $userId]);
    }

    $effRole = $role !== '' ? $role : ($cur['role'] ?? '');
    $idType = $effRole === 'Faculty' ? 'Faculty ID' : ($effRole === 'Student' ? 'Student ID' : null);

    cx_exec($conn,
        "INSERT INTO User_Verification (user_id, id_type, id_number)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE id_type = VALUES(id_type), id_number = VALUES(id_number), deleted_at = NULL",
        'iss', [$userId, $idType, $idNumber !== '' ? $idNumber : null]);
    if ($front) cx_exec($conn, "UPDATE User_Verification SET college_id_front_image = ? WHERE user_id = ?", 'si', [$front, $userId]);
    if ($back)  cx_exec($conn, "UPDATE User_Verification SET college_id_back_image = ? WHERE user_id = ?", 'si', [$back, $userId]);

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('profile_save failed: ' . $e->getMessage());
    cx_json_response(false, 'Could not save your profile. Please try again.', [], 500);
}

$c = cx_profile_refresh($conn, $userId);
cx_json_response(true, 'Profile saved.', cx_profile_payload(cx_profile_load($conn, $userId), $c));
