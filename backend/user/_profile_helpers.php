<?php
declare(strict_types=1);
/* Profile completion + verification helpers (CX/backend/user/_profile_helpers.php)
 * Needs sql/userprofile_migration.sql to be run once. */

/* The 6 rows shown in the "Profile Verification" card. Each is 1/6 of 100%. */
function cx_profile_steps(array $r): array
{
    $has = static fn($v): bool => trim((string)($v ?? '')) !== '';

    return [
        ['key' => 'basic',   'label' => 'Basic Info',
         'done' => $has($r['full_name'] ?? '') && $has($r['college_name'] ?? '')
                   && $has($r['course'] ?? '') && $has($r['graduation_year'] ?? '')],
        ['key' => 'email',   'label' => 'Email Verification',
         'done' => $has($r['email_verified_at'] ?? '')],
        ['key' => 'phone',   'label' => 'Phone Verification',
         'done' => $has(($r['profile_phone'] ?? '') ?: ($r['user_phone'] ?? ''))],
        ['key' => 'student', 'label' => 'Student Verification',
         'done' => $has($r['id_number'] ?? '') && $has($r['college_id_front_image'] ?? '')
                   && $has($r['college_id_back_image'] ?? '')],
        ['key' => 'address', 'label' => 'Address Verification',
         'done' => $has($r['address_line'] ?? '') && $has($r['city'] ?? '')
                   && $has($r['state'] ?? '') && $has($r['pincode'] ?? '')],
        ['key' => 'live',    'label' => 'Live Photo Verification',
         'done' => $has($r['live_photo'] ?? '')],
    ];
}

function cx_profile_load(mysqli $conn, int $userId): array
{
    $row = cx_fetch_one(
        $conn,
        "SELECT u.user_id, u.full_name, u.email, u.pending_email, u.email_verified_at,
                u.phone AS user_phone, u.role,
                p.profile_picture, p.phone AS profile_phone, p.department, p.course,
                p.year_of_study, p.semester, p.bio, p.location,
                p.college_name, p.graduation_year, p.address_line, p.city, p.state,
                p.pincode, p.live_photo,
                v.id_type, v.id_number, v.college_id_front_image, v.college_id_back_image,
                v.verification_status, v.submitted_at, v.verified_at, v.rejection_reason
         FROM Users u
         LEFT JOIN User_Profiles p ON p.user_id = u.user_id
         LEFT JOIN User_Verification v ON v.user_id = u.user_id AND v.deleted_at IS NULL
         WHERE u.user_id = ? LIMIT 1",
        'i',
        [$userId]
    );
    return $row ?? [];
}

/* ['percent' => int, 'missing' => [labels], 'complete' => bool, 'steps' => [...]] */
function cx_profile_completion(array $r): array
{
    $steps = cx_profile_steps($r);
    $done = count(array_filter($steps, static fn(array $s): bool => $s['done']));
    $missing = array_values(array_map(
        static fn(array $s): string => $s['label'],
        array_filter($steps, static fn(array $s): bool => !$s['done'])
    ));
    $percent = (int)round(($done / count($steps)) * 100);
    return ['percent' => $percent, 'missing' => $missing, 'complete' => $percent === 100, 'steps' => $steps];
}

/* Stores the % in User_Profiles (creates the row if needed). Returns completion. */
function cx_profile_refresh(mysqli $conn, int $userId): array
{
    $c = cx_profile_completion(cx_profile_load($conn, $userId));
    cx_exec(
        $conn,
        "INSERT INTO User_Profiles (user_id, profile_completion_percentage, profile_status)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE profile_completion_percentage = VALUES(profile_completion_percentage),
                                 profile_status = VALUES(profile_status)",
        'iis',
        [$userId, $c['percent'], $c['complete'] ? 'Complete' : 'Incomplete']
    );
    return $c;
}

function cx_profile_payload(array $r, array $c): array
{
    $vs = $r['verification_status'] ?? 'Not Submitted';
    $locked = in_array($vs, ['Pending', 'Approved'], true);
    $live = !empty($r['live_photo']) ? cx_upload_url($r['live_photo'], 'verification') : null;
    $pic = !empty($r['profile_picture']) ? cx_upload_url($r['profile_picture'], 'user_profiles') : null;

    return [
        'user' => [
            'full_name' => $r['full_name'],
            'email' => $r['email'],
            'email_verified' => !empty($r['email_verified_at']),
            'pending_email' => $r['pending_email'] ?? null,
            'role' => $r['role'],
        ],
        'profile' => [
            'avatar' => $pic ?: $live,
            'phone' => ($r['profile_phone'] ?? '') ?: ($r['user_phone'] ?? ''),
            'college' => $r['college_name'] ?? '',
            'course' => $r['course'] ?? '',
            'graduation_year' => $r['graduation_year'] ?? '',
            'location' => $r['location'] ?? '',
            'bio' => $r['bio'] ?? '',
            'address_line' => $r['address_line'] ?? '',
            'city' => $r['city'] ?? '',
            'state' => $r['state'] ?? '',
            'pincode' => $r['pincode'] ?? '',
            'live_photo' => $live,
        ],
        'identity' => [
            'id_type' => $r['id_type'] ?? null,
            'roll_number' => $r['id_number'] ?? '',
            'front_image' => !empty($r['college_id_front_image'])
                ? cx_upload_url($r['college_id_front_image'], 'verification') : null,
            'back_image' => !empty($r['college_id_back_image'])
                ? cx_upload_url($r['college_id_back_image'], 'verification') : null,
        ],
        'verification' => [
            'status' => $vs,
            'submitted_at' => $r['submitted_at'] ?? null,
            'verified_at' => $r['verified_at'] ?? null,
            'rejection_reason' => $r['rejection_reason'] ?? null,
            /* Pending / Approved = identity details locked */
            'locked' => $locked,
        ],
        'completion' => $c,
        'can_submit' => $c['complete'] && in_array($vs, ['Not Submitted', 'Rejected'], true),
    ];
}
