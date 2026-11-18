
<?php

/* =====================================================
   CAMPUSXCHANGE
   SHARED SYSTEM SETTINGS HELPER
===================================================== */


/* =====================================================
   GET ONE SYSTEM SETTING
===================================================== */

if (!function_exists('get_system_setting')) {

    function get_system_setting(
        mysqli $conn,
        string $setting_key,
        $default_value = null
    ) {

        $stmt = $conn->prepare("
            SELECT setting_value
            FROM System_Settings
            WHERE setting_key = ?
              AND deleted_at IS NULL
            LIMIT 1
        ");

        if (!$stmt) {
            return $default_value;
        }

        $stmt->bind_param('s', $setting_key);
        $stmt->execute();

        $result = $stmt->get_result();
        $setting = $result->fetch_assoc();

        $stmt->close();

        if (!$setting) {
            return $default_value;
        }

        return $setting['setting_value'];
    }

}


/* =====================================================
   GET ALL ACTIVE SYSTEM SETTINGS
===================================================== */

if (!function_exists('get_all_system_settings')) {

    function get_all_system_settings(
        mysqli $conn
    ): array {

        $settings = [];

        $result = $conn->query("
            SELECT setting_key, setting_value
            FROM System_Settings
            WHERE deleted_at IS NULL
        ");

        if (!$result) {
            return $settings;
        }

        while ($row = $result->fetch_assoc()) {

            $settings[$row['setting_key']] =
                $row['setting_value'];

        }

        return $settings;
    }

}


/* =====================================================
   CHECK BOOLEAN SYSTEM SETTING
===================================================== */

if (!function_exists('system_setting_enabled')) {

    function system_setting_enabled(
        mysqli $conn,
        string $setting_key,
        bool $default_value = false
    ): bool {

        $default = $default_value ? '1' : '0';

        $value = get_system_setting(
            $conn,
            $setting_key,
            $default
        );

        return in_array(
            strtolower(trim((string)$value)),
            ['1', 'true', 'yes', 'on'],
            true
        );
    }

}


/* =====================================================
   END SETTINGS HELPER
===================================================== */
