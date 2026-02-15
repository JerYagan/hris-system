<?php if (($state ?? '') === 'success' && !empty($message)): ?>
    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?php echo cleanText($message); ?></div>
<?php elseif (($state ?? '') === 'error' && !empty($message)): ?>
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo cleanText($message); ?></div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Backup & Restore Data</h2>
        <p class="text-sm text-slate-500 mt-1">Create scheduled backups and restore system data from selected backup files.</p>
    </header>

    <form method="post" action="settings.php" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <input type="hidden" name="form_action" value="save_backup_settings">
        <div>
            <label class="text-slate-600">Backup Scope</label>
            <select name="backup_scope" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <?php $backupScope = settingValue('backup_scope'); ?>
                <option value="Full System Backup" <?php echo $backupScope === 'Full System Backup' ? 'selected' : ''; ?>>Full System Backup</option>
                <option value="HR Records Only" <?php echo $backupScope === 'HR Records Only' ? 'selected' : ''; ?>>HR Records Only</option>
                <option value="Payroll Data Only" <?php echo $backupScope === 'Payroll Data Only' ? 'selected' : ''; ?>>Payroll Data Only</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Schedule</label>
            <select name="backup_schedule" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <?php $backupSchedule = settingValue('backup_schedule'); ?>
                <option value="Daily" <?php echo $backupSchedule === 'Daily' ? 'selected' : ''; ?>>Daily</option>
                <option value="Weekly" <?php echo $backupSchedule === 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                <option value="Monthly" <?php echo $backupSchedule === 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Restore File</label>
            <input type="text" name="restore_file" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?php echo cleanText(settingValue('restore_file')); ?>" placeholder="backup_YYYY-MM-DD_full.zip">
        </div>
        <div class="md:col-span-3 flex justify-end gap-3 mt-2">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Backup Settings</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">SMTP Email Configuration</h2>
        <p class="text-sm text-slate-500 mt-1">Configure SMTP credentials used by admin email functions and send a test email.</p>
    </header>

    <form method="post" action="settings.php" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-2">
        <input type="hidden" name="form_action" value="save_smtp_settings">
        <div>
            <label class="text-slate-600">SMTP Host</label>
            <input type="text" name="smtp_host" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?php echo cleanText(settingValue('smtp_host')); ?>" placeholder="smtp.yourprovider.com">
        </div>
        <div>
            <label class="text-slate-600">SMTP Port</label>
            <input type="number" min="1" name="smtp_port" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?php echo cleanText(settingValue('smtp_port')); ?>" placeholder="587">
        </div>
        <div>
            <label class="text-slate-600">SMTP Username</label>
            <input type="text" name="smtp_username" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?php echo cleanText(settingValue('smtp_username')); ?>" placeholder="no-reply@yourdomain.com">
        </div>
        <div>
            <label class="text-slate-600">SMTP Password</label>
            <input type="password" name="smtp_password" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="" placeholder="Leave blank to keep existing password">
        </div>
        <div>
            <label class="text-slate-600">Encryption</label>
            <?php $smtpEncryption = strtolower((string) settingValue('smtp_encryption')); ?>
            <?php $smtpEncryption = $smtpEncryption !== '' ? $smtpEncryption : 'tls'; ?>
            <select name="smtp_encryption" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="tls" <?php echo $smtpEncryption === 'tls' ? 'selected' : ''; ?>>TLS</option>
                <option value="ssl" <?php echo $smtpEncryption === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                <option value="none" <?php echo $smtpEncryption === 'none' ? 'selected' : ''; ?>>None</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">SMTP Auth</label>
            <select name="smtp_auth" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="1" <?php echo isEnabled('smtp_auth') ? 'selected' : ''; ?>>Enabled</option>
                <option value="0" <?php echo !isEnabled('smtp_auth') ? 'selected' : ''; ?>>Disabled</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">From Email</label>
            <input type="email" name="smtp_from_email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?php echo cleanText(settingValue('smtp_from_email')); ?>" placeholder="no-reply@yourdomain.com">
        </div>
        <div>
            <label class="text-slate-600">From Name</label>
            <input type="text" name="smtp_from_name" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?php echo cleanText(settingValue('smtp_from_name')); ?>" placeholder="DA HRIS">
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save SMTP Settings</button>
        </div>
    </form>

    <form method="post" action="settings.php" class="px-6 pb-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <input type="hidden" name="form_action" value="send_smtp_test_email">
        <input type="hidden" name="smtp_host" value="<?php echo cleanText(settingValue('smtp_host')); ?>">
        <input type="hidden" name="smtp_port" value="<?php echo cleanText(settingValue('smtp_port')); ?>">
        <input type="hidden" name="smtp_username" value="<?php echo cleanText(settingValue('smtp_username')); ?>">
        <input type="hidden" name="smtp_encryption" value="<?php echo cleanText($smtpEncryption); ?>">
        <input type="hidden" name="smtp_auth" value="<?php echo isEnabled('smtp_auth') ? '1' : '0'; ?>">
        <input type="hidden" name="smtp_from_email" value="<?php echo cleanText(settingValue('smtp_from_email')); ?>">
        <input type="hidden" name="smtp_from_name" value="<?php echo cleanText(settingValue('smtp_from_name')); ?>">
        <div>
            <label class="text-slate-600">SMTP Test Recipient</label>
            <input type="email" name="smtp_test_recipient_email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="recipient@example.com" required>
            <p class="text-xs text-slate-500 mt-1">Uses currently saved SMTP settings. Save settings first before testing.</p>
        </div>
        <div>
            <label class="text-slate-600">SMTP Password (optional for test)</label>
            <input type="password" name="smtp_password" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="" placeholder="Leave blank to use saved password">
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="submit" class="px-5 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">Send SMTP Test Email</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Security Configurations</h2>
        <p class="text-sm text-slate-500 mt-1">Manage authentication, password policy, and session security settings.</p>
    </header>

    <form method="post" action="settings.php" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <input type="hidden" name="form_action" value="save_security_settings">
        <div>
            <label class="text-slate-600">Minimum Password Length</label>
            <input type="number" min="8" name="password_min_length" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?php echo cleanText(settingValue('password_min_length')); ?>">
        </div>
        <div>
            <label class="text-slate-600">Failed Login Lockout Threshold</label>
            <input type="number" min="3" name="login_lockout_threshold" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?php echo cleanText(settingValue('login_lockout_threshold')); ?>">
        </div>
        <div>
            <label class="text-slate-600">Session Timeout (minutes)</label>
            <input type="number" min="5" name="session_timeout_minutes" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="<?php echo cleanText(settingValue('session_timeout_minutes')); ?>">
        </div>
        <div>
            <label class="text-slate-600">Two-Factor Authentication</label>
            <select name="two_factor_mode" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <?php $twoFactorMode = settingValue('two_factor_mode'); ?>
                <option value="Enabled for Admin" <?php echo $twoFactorMode === 'Enabled for Admin' ? 'selected' : ''; ?>>Enabled for Admin</option>
                <option value="Enabled for All Users" <?php echo $twoFactorMode === 'Enabled for All Users' ? 'selected' : ''; ?>>Enabled for All Users</option>
                <option value="Disabled" <?php echo $twoFactorMode === 'Disabled' ? 'selected' : ''; ?>>Disabled</option>
            </select>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Security Settings</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Notification Management</h2>
        <p class="text-sm text-slate-500 mt-1">Control alert channels and recipient rules for key system events.</p>
    </header>

    <form method="post" action="settings.php" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <input type="hidden" name="form_action" value="save_notification_settings">
        <div>
            <label class="text-slate-600">System Alerts</label>
            <select name="alerts_enabled" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="1" <?php echo isEnabled('alerts_enabled') ? 'selected' : ''; ?>>Enabled</option>
                <option value="0" <?php echo !isEnabled('alerts_enabled') ? 'selected' : ''; ?>>Disabled</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Email Notifications</label>
            <select name="email_notifications_enabled" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="1" <?php echo isEnabled('email_notifications_enabled') ? 'selected' : ''; ?>>Enabled</option>
                <option value="0" <?php echo !isEnabled('email_notifications_enabled') ? 'selected' : ''; ?>>Disabled</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Critical Alerts Recipient</label>
            <select name="critical_alert_recipient" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <?php $criticalRecipient = settingValue('critical_alert_recipient'); ?>
                <option value="Admin Only" <?php echo $criticalRecipient === 'Admin Only' ? 'selected' : ''; ?>>Admin Only</option>
                <option value="Admin + HR Staff" <?php echo $criticalRecipient === 'Admin + HR Staff' ? 'selected' : ''; ?>>Admin + HR Staff</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Reminder Frequency</label>
            <select name="reminder_frequency" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <?php $reminderFrequency = settingValue('reminder_frequency'); ?>
                <option value="Real-time" <?php echo $reminderFrequency === 'Real-time' ? 'selected' : ''; ?>>Real-time</option>
                <option value="Daily Digest" <?php echo $reminderFrequency === 'Daily Digest' ? 'selected' : ''; ?>>Daily Digest</option>
                <option value="Weekly Digest" <?php echo $reminderFrequency === 'Weekly Digest' ? 'selected' : ''; ?>>Weekly Digest</option>
            </select>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Notification Settings</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Access Logs / Audit Trail</h2>
        <p class="text-sm text-slate-500 mt-1">Review user actions and system events for compliance and security auditing.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Timestamp</th>
                    <th class="text-left px-4 py-3">User</th>
                    <th class="text-left px-4 py-3">Action</th>
                    <th class="text-left px-4 py-3">Module</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($auditLogs)): ?>
                    <tr>
                        <td colspan="4" class="px-4 py-4 text-center text-slate-500">No audit logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($auditLogs as $log): ?>
                        <?php
                            $rawTimestamp = cleanText($log['created_at'] ?? '');
                            $displayTimestamp = $rawTimestamp !== '' ? date('M d, Y h:i A', strtotime($rawTimestamp)) : 'N/A';
                            $description = cleanText($log['description'] ?? 'No description');
                            $module = cleanText($log['module'] ?? 'Settings');
                            $user = cleanText((string) ($log['user'] ?? $adminEmail));
                         ?>
                        <tr>
                            <td class="px-4 py-3"><?php echo $displayTimestamp; ?></td>
                            <td class="px-4 py-3"><?php echo $user; ?></td>
                            <td class="px-4 py-3"><?php echo $description; ?></td>
                            <td class="px-4 py-3"><?php echo $module; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
