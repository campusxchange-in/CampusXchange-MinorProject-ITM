<?php

namespace PrivateMailer;

class License
{
    /**
     * Public license repository.
     */
    private const LICENSE_URL =
        'https://raw.githubusercontent.com/RNick007/PrivateMailer-Public-Licenses/main/licenses';

    private string $lastError = '';

    /**
     * Validate a PrivateMailer license.
     */
    public function isValid(string $licenseKey): bool
    {
        $this->lastError = '';

        if ($licenseKey === '') {
            $this->lastError = 'License key is missing.';
            return false;
        }

        $url =
            self::LICENSE_URL
            . '/'
            . rawurlencode($licenseKey)
            . '.json';

        $response = $this->request($url);

        if ($response === false) {
            $this->lastError =
                'License server is unavailable.';

            return false;
        }

        $license = json_decode(
            $response,
            true
        );

        if (!is_array($license)) {
            $this->lastError =
                'Invalid license response.';

            return false;
        }

        if (
            !isset($license['status']) ||
            $license['status'] !== 'active'
        ) {
            $this->lastError =
                'License is inactive.';

            return false;
        }

        if (!isset($license['expires'])) {
            $this->lastError =
                'License expiration date is missing.';

            return false;
        }

        $expiration = strtotime(
            $license['expires'] . ' 23:59:59 UTC'
        );

        if ($expiration === false) {
            $this->lastError =
                'Invalid license expiration date.';

            return false;
        }

        if ($expiration < time()) {
            $this->lastError =
                'License has expired.';

            return false;
        }

        return true;
    }

    /**
     * Return the last license error.
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Request a license record.
     */
    private function request(string $url): string|false
    {
        $ch = curl_init($url);

        if ($ch === false) {
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'PrivateMailer-License-Validator'
        ]);

        $response = curl_exec($ch);

        $httpCode = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        curl_close($ch);

        if (
            $response === false ||
            $httpCode !== 200
        ) {
            return false;
        }

        return $response;
    }
}

?>