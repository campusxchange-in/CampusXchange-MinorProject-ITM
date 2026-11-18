<?php
/* =====================================================
   SHARED SYSTEM SETTINGS
===================================================== */

include_once __DIR__ . '/settings_helper.php';
/* =====================================================
   CAMPUSXCHANGE
   ADMIN NOTIFICATION HELPER
===================================================== */


/* =====================================================
   ADMIN NOTIFICATION TYPES
===================================================== */

if (!function_exists('admin_notification_types')) {

    function admin_notification_types(): array
    {
        return [
            'Verification',
            'Product',
            'Report',
            'Ban',
            'Security',
            'System'
        ];
    }

}


/* =====================================================
   GET ADMIN NOTIFICATIONS
===================================================== */

if (!function_exists('get_admin_notifications')) {

    function get_admin_notifications(
        mysqli $conn,
        int $notification_admin_id,
        int $limit = 7
    ): array {

        if ($notification_admin_id <= 0) {
            return [];
        }

        $limit = max(1, min($limit, 100));

        $admin_types = admin_notification_types();

        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($admin_types),
                '?'
            )
        );

        $type_string = str_repeat(
            's',
            count($admin_types)
        );

        $sql = "
            SELECT
                notification_id,
                admin_id,
                user_id,
                notification_type,
                title,
                message,
                reference_type,
                reference_id,
                is_read,
                created_at,
                read_at

            FROM Notifications

            WHERE admin_id = ?

            AND user_id IS NULL

            AND notification_type IN ($placeholders)

            AND deleted_at IS NULL

            ORDER BY
                is_read ASC,
                created_at DESC

            LIMIT ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return [];
        }


        /*
         * Build bind parameters.
         */

        $bind_types =
            'i' .
            $type_string .
            'i';

        $params = [
            $bind_types,
            $notification_admin_id
        ];


        foreach ($admin_types as $notification_type) {
            $params[] = $notification_type;
        }


        $params[] = $limit;


        /*
         * bind_param requires references.
         */

        $bind_names = [];

        foreach ($params as $key => $value) {
            $bind_names[$key] = &$params[$key];
        }


        call_user_func_array(
            [$stmt, 'bind_param'],
            $bind_names
        );


        $stmt->execute();

        $result = $stmt->get_result();

        $notifications = [];


        while ($row = $result->fetch_assoc()) {

            $notifications[] = $row;

        }


        $stmt->close();


        return $notifications;

    }

}


/* =====================================================
   GET SINGLE ADMIN NOTIFICATION
===================================================== */

if (!function_exists('get_admin_notification')) {

    function get_admin_notification(
        mysqli $conn,
        int $notification_admin_id,
        int $notification_id
    ): ?array {

        if (
            $notification_admin_id <= 0 ||
            $notification_id <= 0
        ) {
            return null;
        }


        $admin_types = admin_notification_types();

        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($admin_types),
                '?'
            )
        );

        $type_string = str_repeat(
            's',
            count($admin_types)
        );


        $sql = "
            SELECT
                notification_id,
                admin_id,
                user_id,
                notification_type,
                title,
                message,
                reference_type,
                reference_id,
                is_read,
                created_at,
                read_at

            FROM Notifications

            WHERE notification_id = ?

            AND admin_id = ?

            AND user_id IS NULL

            AND notification_type IN ($placeholders)

            AND deleted_at IS NULL

            LIMIT 1
        ";


        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return null;
        }


        /*
         * notification_id
         * notification_admin_id
         * notification types
         */

        $bind_types =
            'ii' .
            $type_string;


        $params = [
            $bind_types,
            $notification_id,
            $notification_admin_id
        ];


        foreach ($admin_types as $notification_type) {
            $params[] = $notification_type;
        }


        $bind_names = [];

        foreach ($params as $key => $value) {
            $bind_names[$key] = &$params[$key];
        }


        call_user_func_array(
            [$stmt, 'bind_param'],
            $bind_names
        );


        $stmt->execute();

        $result = $stmt->get_result();

        $notification =
            $result->fetch_assoc();


        $stmt->close();


        return $notification ?: null;

    }

}


/* =====================================================
   GET ADMIN UNREAD COUNT
===================================================== */

if (!function_exists('get_admin_unread_count')) {

    function get_admin_unread_count(
        mysqli $conn,
        int $notification_admin_id
    ): int {

        if ($notification_admin_id <= 0) {
            return 0;
        }


        $admin_types = admin_notification_types();

        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($admin_types),
                '?'
            )
        );

        $type_string = str_repeat(
            's',
            count($admin_types)
        );


        $sql = "
            SELECT COUNT(*)

            FROM Notifications

            WHERE admin_id = ?

            AND user_id IS NULL

            AND notification_type IN ($placeholders)

            AND is_read = FALSE

            AND deleted_at IS NULL
        ";


        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return 0;
        }


        $bind_types =
            'i' .
            $type_string;


        $params = [
            $bind_types,
            $notification_admin_id
        ];


        foreach ($admin_types as $notification_type) {
            $params[] = $notification_type;
        }


        $bind_names = [];

        foreach ($params as $key => $value) {
            $bind_names[$key] = &$params[$key];
        }


        call_user_func_array(
            [$stmt, 'bind_param'],
            $bind_names
        );


        $stmt->execute();

        $stmt->bind_result(
            $unread_count
        );

        $stmt->fetch();

        $stmt->close();


        return (int)$unread_count;

    }

}


/* =====================================================
   GET ADMIN NOTIFICATION COUNT
===================================================== */

if (!function_exists('get_admin_notification_count')) {

    function get_admin_notification_count(
        mysqli $conn,
        int $notification_admin_id
    ): int {

        if ($notification_admin_id <= 0) {
            return 0;
        }


        $admin_types = admin_notification_types();

        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($admin_types),
                '?'
            )
        );

        $type_string = str_repeat(
            's',
            count($admin_types)
        );


        $sql = "
            SELECT COUNT(*)

            FROM Notifications

            WHERE admin_id = ?

            AND user_id IS NULL

            AND notification_type IN ($placeholders)

            AND deleted_at IS NULL
        ";


        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return 0;
        }


        $bind_types =
            'i' .
            $type_string;


        $params = [
            $bind_types,
            $notification_admin_id
        ];


        foreach ($admin_types as $notification_type) {
            $params[] = $notification_type;
        }


        $bind_names = [];

        foreach ($params as $key => $value) {
            $bind_names[$key] = &$params[$key];
        }


        call_user_func_array(
            [$stmt, 'bind_param'],
            $bind_names
        );


        $stmt->execute();

        $stmt->bind_result(
            $total_count
        );

        $stmt->fetch();

        $stmt->close();


        return (int)$total_count;

    }

}


/* =====================================================
   MARK ONE NOTIFICATION AS READ
===================================================== */

if (!function_exists('mark_admin_notification_read')) {

    function mark_admin_notification_read(
        mysqli $conn,
        int $notification_admin_id,
        int $notification_id
    ): bool {

        if (
            $notification_admin_id <= 0 ||
            $notification_id <= 0
        ) {
            return false;
        }


        /*
         * Important:
         *
         * The notification must belong to the
         * currently logged-in admin.
         */

        $sql = "
            UPDATE Notifications

            SET
                is_read = TRUE,
                read_at = NOW()

            WHERE notification_id = ?

            AND admin_id = ?

            AND user_id IS NULL

            AND is_read = FALSE

            AND deleted_at IS NULL
        ";


        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return false;
        }


        $stmt->bind_param(
            "ii",
            $notification_id,
            $notification_admin_id
        );


        $success = $stmt->execute();


        $stmt->close();


        return $success;

    }

}


/* =====================================================
   MARK ALL ADMIN NOTIFICATIONS AS READ
===================================================== */

if (!function_exists('mark_all_admin_notifications_read')) {

    function mark_all_admin_notifications_read(
        mysqli $conn,
        int $notification_admin_id
    ): bool {

        if ($notification_admin_id <= 0) {
            return false;
        }


        $admin_types = admin_notification_types();

        $placeholders = implode(
            ',',
            array_fill(
                0,
                count($admin_types),
                '?'
            )
        );

        $type_string = str_repeat(
            's',
            count($admin_types)
        );


        $sql = "
            UPDATE Notifications

            SET
                is_read = TRUE,
                read_at = NOW()

            WHERE admin_id = ?

            AND user_id IS NULL

            AND notification_type IN ($placeholders)

            AND is_read = FALSE

            AND deleted_at IS NULL
        ";


        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return false;
        }


        $bind_types =
            'i' .
            $type_string;


        $params = [
            $bind_types,
            $notification_admin_id
        ];


        foreach ($admin_types as $notification_type) {
            $params[] = $notification_type;
        }


        $bind_names = [];

        foreach ($params as $key => $value) {
            $bind_names[$key] = &$params[$key];
        }


        call_user_func_array(
            [$stmt, 'bind_param'],
            $bind_names
        );


        $success = $stmt->execute();


        $stmt->close();


        return $success;

    }

}


/* =====================================================
   NOTIFICATION ICON
===================================================== */

if (!function_exists('get_notification_icon')) {

    function get_notification_icon(
        string $notification_type
    ): string {

        return match ($notification_type) {

            'Verification' =>
                'fa-solid fa-circle-check',

            'Product' =>
                'fa-solid fa-box',

            'Report' =>
                'fa-solid fa-flag',

            'Ban' =>
                'fa-solid fa-user-slash',

            'Security' =>
                'fa-solid fa-shield-halved',

            'System' =>
                'fa-solid fa-gear',

            'Message' =>
                'fa-solid fa-message',

            'Transaction' =>
                'fa-solid fa-receipt',

            'Wishlist' =>
                'fa-solid fa-heart',

            'Review' =>
                'fa-solid fa-star',

            default =>
                'fa-solid fa-bell'
        };

    }

}


/* =====================================================
   NOTIFICATION REDIRECT URL
===================================================== */

if (!function_exists('get_notification_redirect_url')) {

    function get_notification_redirect_url(
        array $notification
    ): string {

        $reference_type =
            $notification['reference_type'] ?? '';

        $reference_id =
            (int)($notification['reference_id'] ?? 0);


        /*
         * No valid reference.
         */

        if ($reference_id <= 0) {
            return 'notifications';
        }


        switch ($reference_type) {

            case 'User':

                return
                    'user_view?id=' .
                    $reference_id;


            case 'Product':

                return
                    'product_view?id=' .
                    $reference_id;


            case 'Report':

                return
                    'report-details?id=' .
                    $reference_id;


            case 'Verification':

                return
                    'verification-details.php?id=' .
                    $reference_id;


            case 'Verification Request':

                return
                    'verification-details.php?id=' .
                    $reference_id;


            default:

                return 'notifications';

        }

    }

}


/* =====================================================
   CREATE ADMIN NOTIFICATION
===================================================== */

if (!function_exists('create_admin_notification')) {

    function create_admin_notification(
        mysqli $conn,
        int $notification_admin_id,
        string $notification_type,
        string $title,
        string $message,
        ?string $reference_type = null,
        ?int $reference_id = null
    ): bool {

        if ($notification_admin_id <= 0) {
            return false;
        }


        if (
            !in_array(
                $notification_type,
                admin_notification_types(),
                true
            )
        ) {
            return false;
        }

        /* ---------------------------------------------
        CHECK ADMIN NOTIFICATION PREFERENCES
        --------------------------------------------- */

        if (
            $notification_type === 'Verification' &&
            !system_setting_enabled(
                $conn,
                'notify_verification_requests',
                true
            )
        ) {
            return false;
        }

        if (
            $notification_type === 'Report' &&
            !system_setting_enabled(
                $conn,
                'notify_new_reports',
                true
            )
        ) {
            return false;
        }


        $sql = "
            INSERT INTO Notifications
            (
                admin_id,
                user_id,
                notification_type,
                title,
                message,
                reference_type,
                reference_id,
                is_read,
                created_at
            )

            VALUES
            (
                ?,
                NULL,
                ?,
                ?,
                ?,
                ?,
                ?,
                FALSE,
                NOW()
            )
        ";


        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return false;
        }


        $stmt->bind_param(
            "issssi",
            $notification_admin_id,
            $notification_type,
            $title,
            $message,
            $reference_type,
            $reference_id
        );


        $success = $stmt->execute();


        $stmt->close();


        return $success;

    }

}


/* =====================================================
   END NOTIFICATION HELPER
===================================================== */