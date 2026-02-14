<?php
$pageTitle = 'Settings | Admin';
$activePage = 'settings.php';
$breadcrumbs = ['Settings'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">System Settings</h1>
        <p class="text-sm text-slate-300 mt-2">Configure backups, security, notifications, and audit controls for the platform.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Backup & Restore Data</h2>
        <p class="text-sm text-slate-500 mt-1">Create scheduled backups and restore system data from selected backup files.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Backup Scope</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Full System Backup</option>
                <option>HR Records Only</option>
                <option>Payroll Data Only</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Schedule</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Daily</option>
                <option>Weekly</option>
                <option>Monthly</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="button" class="w-full px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Run Backup Now</button>
        </div>
        <div class="md:col-span-2">
            <label class="text-slate-600">Restore File</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>backup_2026-02-13_full.zip</option>
                <option>backup_2026-02-12_full.zip</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="button" class="w-full px-5 py-2 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50">Restore Selected Backup</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Security Configurations</h2>
        <p class="text-sm text-slate-500 mt-1">Manage authentication, password policy, and session security settings.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Minimum Password Length</label>
            <input type="number" min="8" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="10">
        </div>
        <div>
            <label class="text-slate-600">Failed Login Lockout Threshold</label>
            <input type="number" min="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="5">
        </div>
        <div>
            <label class="text-slate-600">Session Timeout (minutes)</label>
            <input type="number" min="5" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="30">
        </div>
        <div>
            <label class="text-slate-600">Two-Factor Authentication</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Enabled for Admin</option>
                <option>Enabled for All Users</option>
                <option>Disabled</option>
            </select>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Security Settings</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Notification Management</h2>
        <p class="text-sm text-slate-500 mt-1">Control alert channels and recipient rules for key system events.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label class="text-slate-600">System Alerts</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Enabled</option>
                <option>Disabled</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Email Notifications</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Enabled</option>
                <option>Disabled</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Critical Alerts Recipient</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Admin Only</option>
                <option>Admin + HR Staff</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Reminder Frequency</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Real-time</option>
                <option>Daily Digest</option>
                <option>Weekly Digest</option>
            </select>
        </div>
        <div class="md:col-span-2 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
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
                    <th class="text-left px-4 py-3">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Feb 14, 2026 09:12 AM</td>
                    <td class="px-4 py-3">admin@da.gov.ph</td>
                    <td class="px-4 py-3">Approved Payroll Batch PR-2026-02A</td>
                    <td class="px-4 py-3">Payroll Management</td>
                    <td class="px-4 py-3">192.168.1.24</td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Feb 14, 2026 08:45 AM</td>
                    <td class="px-4 py-3">admin@da.gov.ph</td>
                    <td class="px-4 py-3">Updated Security Configuration</td>
                    <td class="px-4 py-3">Settings</td>
                    <td class="px-4 py-3">192.168.1.24</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
