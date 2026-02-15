<?php

if (!function_exists('adminMailEnsureAutoload')) {
    function adminMailEnsureAutoload(): void
    {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return;
        }

        $autoloadPath = dirname(__DIR__, 4) . '/vendor/autoload.php';
        if (is_file($autoloadPath)) {
            require_once $autoloadPath;
        }
    }
}

if (!function_exists('smtpConfigIsReady')) {
    function smtpConfigIsReady(array $smtpConfig, string $fromEmail): bool
    {
        $host = trim((string)($smtpConfig['host'] ?? ''));
        $port = (int)($smtpConfig['port'] ?? 0);
        $username = trim((string)($smtpConfig['username'] ?? ''));
        $password = (string)($smtpConfig['password'] ?? '');

        return $host !== ''
            && $port > 0
            && $username !== ''
            && $password !== ''
            && trim($fromEmail) !== '';
    }
}

if (!function_exists('resolveSmtpMailConfig')) {
    function resolveSmtpMailConfig(string $supabaseUrl, array $headers, array $smtpConfig, string $mailFrom, string $mailFromName): array
    {
        if (!function_exists('apiRequest') || trim($supabaseUrl) === '') {
            return [
                'smtp' => $smtpConfig,
                'from' => $mailFrom,
                'from_name' => $mailFromName,
            ];
        }

        $keys = [
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_password',
            'smtp_encryption',
            'smtp_auth',
            'smtp_from_email',
            'smtp_from_name',
        ];

        $query = implode(',', $keys);
        $response = apiRequest(
            'GET',
            rtrim($supabaseUrl, '/') . '/rest/v1/system_settings?select=setting_key,setting_value&setting_key=in.(' . $query . ')&limit=50',
            $headers
        );

        if (!is_array($response) || (int)($response['status'] ?? 0) < 200 || (int)($response['status'] ?? 0) >= 300) {
            return [
                'smtp' => $smtpConfig,
                'from' => $mailFrom,
                'from_name' => $mailFromName,
            ];
        }

        $rows = is_array($response['data'] ?? null) ? $response['data'] : [];
        $values = [];
        foreach ($rows as $row) {
            $key = trim((string)($row['setting_key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $storedValue = $row['setting_value'] ?? null;
            $normalized = '';

            if (is_array($storedValue) && array_key_exists('value', $storedValue)) {
                $value = $storedValue['value'];
                if (is_bool($value)) {
                    $normalized = $value ? '1' : '0';
                } else {
                    $normalized = trim((string)$value);
                }
            } elseif (is_bool($storedValue)) {
                $normalized = $storedValue ? '1' : '0';
            } elseif (is_scalar($storedValue)) {
                $normalized = trim((string)$storedValue);
            }

            $values[$key] = $normalized;
        }

        if (isset($values['smtp_host']) && $values['smtp_host'] !== '') {
            $smtpConfig['host'] = $values['smtp_host'];
        }
        if (isset($values['smtp_port']) && is_numeric($values['smtp_port'])) {
            $smtpConfig['port'] = (int)$values['smtp_port'];
        }
        if (isset($values['smtp_username']) && $values['smtp_username'] !== '') {
            $smtpConfig['username'] = $values['smtp_username'];
        }
        if (isset($values['smtp_password']) && $values['smtp_password'] !== '') {
            $smtpConfig['password'] = $values['smtp_password'];
        }
        if (isset($values['smtp_encryption']) && $values['smtp_encryption'] !== '') {
            $smtpConfig['encryption'] = strtolower($values['smtp_encryption']);
        }
        if (isset($values['smtp_auth'])) {
            $smtpConfig['auth'] = in_array(strtolower($values['smtp_auth']), ['0', 'false', 'disabled', 'no'], true) ? '0' : '1';
        }
        if (isset($values['smtp_from_email']) && $values['smtp_from_email'] !== '') {
            $mailFrom = $values['smtp_from_email'];
        }
        if (isset($values['smtp_from_name']) && $values['smtp_from_name'] !== '') {
            $mailFromName = $values['smtp_from_name'];
        }

        return [
            'smtp' => $smtpConfig,
            'from' => $mailFrom,
            'from_name' => $mailFromName,
        ];
    }
}

if (!function_exists('smtpSendTransactionalEmail')) {
    function smtpSendTransactionalEmail(array $smtpConfig, string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, string $htmlContent): array
    {
        adminMailEnsureAutoload();

        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return [
                'status' => 500,
                'data' => [],
                'raw' => 'PHPMailer dependency is not available. Run composer install.',
            ];
        }

        try {
            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = (string)($smtpConfig['host'] ?? '');
            $mailer->Port = (int)($smtpConfig['port'] ?? 587);
            $mailer->SMTPAuth = ((string)($smtpConfig['auth'] ?? '1')) !== '0';
            $mailer->Username = (string)($smtpConfig['username'] ?? '');
            $mailer->Password = (string)($smtpConfig['password'] ?? '');

            $encryption = strtolower(trim((string)($smtpConfig['encryption'] ?? 'tls')));
            if ($encryption === 'ssl') {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls' || $encryption === 'starttls') {
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mailer->SMTPSecure = '';
                $mailer->SMTPAutoTLS = false;
            }

            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom($fromEmail, $fromName !== '' ? $fromName : $fromEmail);
            $mailer->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $htmlContent;
            $mailer->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlContent)));
            $mailer->send();

            return [
                'status' => 200,
                'data' => ['provider' => 'smtp'],
                'raw' => 'SMTP send success',
            ];
        } catch (\Throwable $error) {
            return [
                'status' => 500,
                'data' => [],
                'raw' => $error->getMessage(),
            ];
        }
    }
}

if (!function_exists('brevoSendTransactionalEmail')) {
    function brevoSendTransactionalEmail(string $apiKey, string $fromEmail, string $fromName, string $toEmail, string $toName, string $subject, string $htmlContent): array
    {
        return [
            'status' => 501,
            'data' => [],
            'raw' => 'Brevo helper is deprecated. Use smtpSendTransactionalEmail instead.',
        ];
    }
}
