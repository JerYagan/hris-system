<?php

require_once dirname(__DIR__, 3) . '/shared/lib/system-helpers.php';

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
        $authEnabled = ((string)($smtpConfig['auth'] ?? '1')) !== '0';

        return $host !== ''
            && $port > 0
            && (!$authEnabled || ($username !== '' && $password !== ''))
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
            $renderedHtmlContent = hrisEmailDecorateHtml($subject, $htmlContent, $fromName);
            $plainTextContent = hrisEmailBuildPlainText($renderedHtmlContent);

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
            $mailer->Body = $renderedHtmlContent;
            $mailer->AltBody = $plainTextContent;
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

if (!function_exists('hrisEmailFormatPhilippinesDateTime')) {
    function hrisEmailFormatPhilippinesDateTime(?string $dateTime, string $format = 'M d, Y h:i A'): string
    {
        $formatted = function_exists('formatDateTimeForPhilippines')
            ? formatDateTimeForPhilippines($dateTime, $format)
            : '-';

        return $formatted !== '-' ? ($formatted . ' PST') : '-';
    }
}

if (!function_exists('hrisEmailFormatPhilippinesTimestamp')) {
    function hrisEmailFormatPhilippinesTimestamp(int|string|null $timestamp, string $format = 'M d, Y h:i A'): string
    {
        $formatted = function_exists('formatUnixTimestampForPhilippines')
            ? formatUnixTimestampForPhilippines($timestamp, $format)
            : '-';

        return $formatted !== '-' ? ($formatted . ' PST') : '-';
    }
}

if (!function_exists('hrisEmailFormatPhilippinesLocalDateTime')) {
    function hrisEmailFormatPhilippinesLocalDateTime(?string $date, ?string $time, string $format = 'M d, Y h:i A'): string
    {
        $dateValue = trim((string)$date);
        $timeValue = trim((string)$time);
        if ($dateValue === '' || $timeValue === '') {
            return '-';
        }

        try {
            $dateTime = new DateTimeImmutable($dateValue . ' ' . $timeValue, new DateTimeZone('Asia/Manila'));
            return $dateTime->format($format) . ' PST';
        } catch (Throwable) {
            return '-';
        }
    }
}

if (!function_exists('hrisEmailDecorateHtml')) {
    function hrisEmailDecorateHtml(string $subject, string $htmlContent, string $fromName = ''): string
    {
        if (stripos($htmlContent, '<html') !== false || stripos($htmlContent, 'data-hris-email-wrapper') !== false) {
            return $htmlContent;
        }

        $safeSubject = htmlspecialchars(trim($subject) !== '' ? $subject : 'DA-ATI HRIS Notification', ENT_QUOTES, 'UTF-8');
        $safeFromName = htmlspecialchars(trim($fromName) !== '' ? $fromName : 'DA-ATI HRIS', ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html>'
            . '<html lang="en">'
            . '<head>'
            . '<meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>' . $safeSubject . '</title>'
            . '</head>'
            . '<body style="margin:0;padding:24px;background:#f8fafc;color:#0f172a;font-family:Segoe UI,Arial,sans-serif;">'
            . '<div data-hris-email-wrapper="1" style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 12px 32px rgba(15,23,42,0.08);">'
            . '<div style="padding:20px 28px;background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);color:#ffffff;">'
            . '<div style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.78;">DA-ATI HRIS</div>'
            . '<h1 style="margin:10px 0 0;font-size:24px;line-height:1.3;font-weight:700;">' . $safeSubject . '</h1>'
            . '</div>'
            . '<div style="padding:28px;font-size:14px;line-height:1.7;">' . $htmlContent . '</div>'
            . '<div style="padding:18px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;color:#475569;font-size:12px;line-height:1.6;">'
            . '<p style="margin:0 0 6px;"><strong>' . $safeFromName . '</strong></p>'
            . '<p style="margin:0;">This is an automated message from the DA-ATI HRIS. All dates and times in this email are in Philippine Standard Time (PST, UTC+8) unless otherwise stated.</p>'
            . '</div>'
            . '</div>'
            . '</body>'
            . '</html>';
    }
}

if (!function_exists('hrisEmailBuildPlainText')) {
    function hrisEmailBuildPlainText(string $htmlContent): string
    {
        $normalized = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>', '</tr>', '</h1>', '</h2>', '</h3>'], "\n", $htmlContent);
        $normalized = preg_replace('/<li[^>]*>/i', '- ', $normalized) ?? $normalized;
        $normalized = strip_tags($normalized);
        $normalized = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace("/\r\n|\r/", "\n", $normalized) ?? $normalized;
        $normalized = preg_replace("/\n{3,}/", "\n\n", $normalized) ?? $normalized;

        return trim($normalized);
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
