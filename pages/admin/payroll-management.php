<?php
$pageTitle = 'Payroll Management | Admin';
$activePage = 'payroll-management.php';
$breadcrumbs = ['Payroll Management'];

ob_start();
?>

<div class="mb-6">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
        <p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
        <h1 class="text-2xl font-bold mt-1">Payroll Management</h1>
        <p class="text-sm text-slate-300 mt-2">Configure salary components, generate payroll outputs, and manage payslip release workflows.</p>
    </div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Manage Salary Setup (Base Pay, Deductions, Allowance)</h2>
        <p class="text-sm text-slate-500 mt-1">Define salary components and deduction rules per employee or salary group.</p>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Employee</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Ana Dela Cruz</option>
                <option>Mark Villanueva</option>
                <option>Lea Ramos</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Base Pay</label>
            <input type="number" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="25000">
        </div>
        <div>
            <label class="text-slate-600">Total Allowance</label>
            <input type="number" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="3500">
        </div>
        <div>
            <label class="text-slate-600">Tax Deduction</label>
            <input type="number" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="2200">
        </div>
        <div>
            <label class="text-slate-600">Government Deductions</label>
            <input type="number" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="1200">
        </div>
        <div>
            <label class="text-slate-600">Other Deductions</label>
            <input type="number" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" value="500">
        </div>
        <div class="md:col-span-3 flex justify-end gap-3 mt-2">
            <button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
            <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Salary Setup</button>
        </div>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Generate Payroll Reports</h2>
            <p class="text-sm text-slate-500 mt-1">Generate payroll summaries and detailed breakdowns by cutoff period.</p>
        </div>
        <button type="button" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Generate Report</button>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-500">Current Cutoff</p>
            <p class="font-semibold text-slate-800 mt-2">Feb 01 - Feb 15, 2026</p>
            <p class="text-xs text-slate-500 mt-1">248 employees included</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700">Estimated Gross</p>
            <p class="font-semibold text-slate-800 mt-2">₱6,548,000</p>
            <p class="text-xs text-slate-500 mt-1">Before deductions</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase text-amber-700">Estimated Net</p>
            <p class="font-semibold text-slate-800 mt-2">₱5,882,300</p>
            <p class="text-xs text-slate-500 mt-1">After all deductions</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Approve Payroll Batches</h2>
        <p class="text-sm text-slate-500 mt-1">Review computed payroll batches and approve for payslip release processing.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Batch ID</th>
                    <th class="text-left px-4 py-3">Cutoff Period</th>
                    <th class="text-left px-4 py-3">Employee Count</th>
                    <th class="text-left px-4 py-3">Total Net Pay</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">PR-2026-02A</td>
                    <td class="px-4 py-3">Feb 01 - Feb 15, 2026</td>
                    <td class="px-4 py-3">248</td>
                    <td class="px-4 py-3">₱5,882,300</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2">
                            <button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">Review</button>
                            <button type="button" class="px-3 py-1.5 rounded-md bg-emerald-700 text-white hover:bg-emerald-800">Approve</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">View Employee Payslips</h2>
        <p class="text-sm text-slate-500 mt-1">Check generated payslips before release and verify employee-level payroll details.</p>
    </header>

    <div class="p-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Cutoff</th>
                    <th class="text-left px-4 py-3">Gross Pay</th>
                    <th class="text-left px-4 py-3">Deductions</th>
                    <th class="text-left px-4 py-3">Net Pay</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr>
                    <td class="px-4 py-3">Ana Dela Cruz</td>
                    <td class="px-4 py-3">Feb 01 - Feb 15, 2026</td>
                    <td class="px-4 py-3">₱28,500</td>
                    <td class="px-4 py-3">₱3,900</td>
                    <td class="px-4 py-3">₱24,600</td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">View Payslip</button></td>
                </tr>
                <tr>
                    <td class="px-4 py-3">Mark Villanueva</td>
                    <td class="px-4 py-3">Feb 01 - Feb 15, 2026</td>
                    <td class="px-4 py-3">₱31,200</td>
                    <td class="px-4 py-3">₱4,350</td>
                    <td class="px-4 py-3">₱26,850</td>
                    <td class="px-4 py-3"><button type="button" class="px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50">View Payslip</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Send Payslip to Employees via Email</h2>
            <p class="text-sm text-slate-500 mt-1">Release approved payslips securely through employee email notifications.</p>
        </div>
        <button type="button" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Send Payslips</button>
    </header>

    <form class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div>
            <label class="text-slate-600">Cutoff Period</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Feb 01 - Feb 15, 2026</option>
                <option>Jan 16 - Jan 31, 2026</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Recipient Group</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>All Active Employees</option>
                <option>Selected Department</option>
                <option>Selected Employees</option>
            </select>
        </div>
        <div>
            <label class="text-slate-600">Delivery Mode</label>
            <select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option>Send Immediately</option>
                <option>Schedule Send</option>
            </select>
        </div>
    </form>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
