<?php
/* GET  CX/backend/public/public_stats.php
 * Live numbers for the landing page, login, signup and admin login screens.
 *
 * Returns:
 *   students         active, e-mail verified users with role Student
 *   members          active, e-mail verified users (students + faculty)
 *   verified_users   active users whose identity verification is Approved
 *   verified_percent verified_users / members, rounded (0-100)
 *   products_listed  products ever listed and still on the site (Active + Sold)
 *   active_listings  products currently for sale (same rule as Browse)
 *   deals            completed transactions
 */
require_once __DIR__ . '/_public_common.php';
cx_require_method('GET');

try {
    $conn = cx_db();

    $count = static function (mysqli $conn, string $sql): int {
        $res = $conn->query($sql);
        $row = $res ? $res->fetch_row() : null;
        return (int)($row[0] ?? 0);
    };

    $students = $count($conn,
        "SELECT COUNT(*) FROM Users
         WHERE status = 'Active' AND deleted_at IS NULL
           AND email_verified_at IS NOT NULL AND role = 'Student'");

    $members = $count($conn,
        "SELECT COUNT(*) FROM Users
         WHERE status = 'Active' AND deleted_at IS NULL
           AND email_verified_at IS NOT NULL");

    $verified = $count($conn,
        "SELECT COUNT(DISTINCT u.user_id)
         FROM Users u
         INNER JOIN User_Verification v ON v.user_id = u.user_id
         WHERE u.status = 'Active' AND u.deleted_at IS NULL
           AND u.email_verified_at IS NOT NULL
           AND v.verification_status = 'Approved' AND v.deleted_at IS NULL");

    $productsListed = $count($conn,
        "SELECT COUNT(*) FROM Products p
         INNER JOIN Users u ON u.user_id = p.seller_id
         WHERE p.status IN ('Active', 'Sold') AND p.deleted_at IS NULL
           AND u.deleted_at IS NULL");

    $activeListings = $count($conn,
        "SELECT COUNT(*) FROM Products p
         INNER JOIN Users u ON u.user_id = p.seller_id
         WHERE p.status = 'Active' AND p.deleted_at IS NULL
           AND u.status = 'Active' AND u.deleted_at IS NULL");

    $deals = $count($conn,
        "SELECT COUNT(*) FROM Transactions
         WHERE transaction_status = 'Completed' AND deleted_at IS NULL");

    cx_json_response(true, 'OK', [
        'stats' => [
            'students'         => $students,
            'members'          => $members,
            'verified_users'   => $verified,
            'verified_percent' => $members > 0 ? (int)round($verified / $members * 100) : 0,
            'products_listed'  => $productsListed,
            'active_listings'  => $activeListings,
            'deals'            => $deals,
        ],
    ]);
} catch (Throwable $e) {
    error_log('public_stats failed: ' . $e->getMessage());
    cx_json_response(false, 'Unable to load statistics.', [], 500);
}
