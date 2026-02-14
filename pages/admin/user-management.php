<?php
$pageTitle = 'User Management | Admin';
$activePage = 'user-management.php';
$breadcrumbs = ['User Management'];

ob_start();
?>

<div class="mb-6">
	<div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 text-white">
		<p class="text-xs uppercase tracking-wide text-emerald-300">Admin</p>
		<h1 class="text-2xl font-bold mt-1">User Management</h1>
		<p class="text-sm text-slate-300 mt-2">Manage user accounts, assign access roles, and control login credentials.</p>
	</div>
</div>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
	<header class="px-6 py-4 border-b border-slate-200">
		<h2 class="text-lg font-semibold text-slate-800">Add / Archive User Accounts</h2>
		<p class="text-sm text-slate-500 mt-1">Create new platform accounts and archive inactive or separated users.</p>
	</header>

	<form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
		<div>
			<label class="text-slate-600">Full Name</label>
			<input type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter full name">
		</div>
		<div>
			<label class="text-slate-600">Action</label>
			<select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
				<option>Add User Account</option>
				<option>Archive User Account</option>
			</select>
		</div>
		<div>
			<label class="text-slate-600">Email Address</label>
			<input type="email" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter official email">
		</div>
		<div>
			<label class="text-slate-600">Department</label>
			<select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
				<option>Human Resource Division</option>
				<option>Management Information Systems</option>
				<option>Training Division</option>
			</select>
		</div>
		<div class="md:col-span-2">
			<label class="text-slate-600">Account Notes</label>
			<textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add onboarding details or archival reason"></textarea>
		</div>
		<div class="md:col-span-2 flex justify-end gap-3 mt-2">
			<button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
			<button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Account</button>
		</div>
	</form>

	<div class="px-6 pb-6 overflow-x-auto">
		<table class="w-full text-sm">
			<thead class="bg-slate-50 text-slate-600">
				<tr>
					<th class="text-left px-4 py-3">User</th>
					<th class="text-left px-4 py-3">Email</th>
					<th class="text-left px-4 py-3">Account Status</th>
					<th class="text-left px-4 py-3">Created Date</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-slate-100">
				<tr>
					<td class="px-4 py-3">Ana Dela Cruz</td>
					<td class="px-4 py-3">ana.delacruz@da.gov.ph</td>
					<td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">Active</span></td>
					<td class="px-4 py-3">Feb 08, 2026</td>
				</tr>
				<tr>
					<td class="px-4 py-3">Lea Ramos</td>
					<td class="px-4 py-3">lea.ramos@da.gov.ph</td>
					<td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-slate-200 text-slate-700">Archived</span></td>
					<td class="px-4 py-3">Jan 15, 2026</td>
				</tr>
			</tbody>
		</table>
	</div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
	<header class="px-6 py-4 border-b border-slate-200">
		<h2 class="text-lg font-semibold text-slate-800">Assign Roles (Admin, HR Staff, Employee)</h2>
		<p class="text-sm text-slate-500 mt-1">Set permission roles based on job function and system access scope.</p>
	</header>

	<form class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
		<div>
			<label class="text-slate-600">User</label>
			<select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
				<option>Ana Dela Cruz</option>
				<option>Mark Villanueva</option>
				<option>John Reyes</option>
			</select>
		</div>
		<div>
			<label class="text-slate-600">Role</label>
			<select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
				<option>Admin</option>
				<option>HR Staff</option>
				<option>Employee</option>
			</select>
		</div>
		<div>
			<label class="text-slate-600">Effectivity Date</label>
			<input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
		</div>
		<div class="md:col-span-3 flex justify-end mt-1">
			<button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Assign Role</button>
		</div>
	</form>

	<div class="px-6 pb-6 overflow-x-auto">
		<table class="w-full text-sm">
			<thead class="bg-slate-50 text-slate-600">
				<tr>
					<th class="text-left px-4 py-3">User</th>
					<th class="text-left px-4 py-3">Current Role</th>
					<th class="text-left px-4 py-3">Assigned By</th>
					<th class="text-left px-4 py-3">Last Updated</th>
				</tr>
			</thead>
			<tbody class="divide-y divide-slate-100">
				<tr>
					<td class="px-4 py-3">Ana Dela Cruz</td>
					<td class="px-4 py-3">HR Staff</td>
					<td class="px-4 py-3">Admin User</td>
					<td class="px-4 py-3">Feb 11, 2026</td>
				</tr>
				<tr>
					<td class="px-4 py-3">Mark Villanueva</td>
					<td class="px-4 py-3">Employee</td>
					<td class="px-4 py-3">Admin User</td>
					<td class="px-4 py-3">Feb 10, 2026</td>
				</tr>
			</tbody>
		</table>
	</div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
	<header class="px-6 py-4 border-b border-slate-200">
		<h2 class="text-lg font-semibold text-slate-800">Manage Login Credentials</h2>
		<p class="text-sm text-slate-500 mt-1">Update usernames, reset passwords, and lock/unlock account access when required.</p>
	</header>

	<form class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
		<div>
			<label class="text-slate-600">User</label>
			<select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
				<option>Ana Dela Cruz</option>
				<option>Mark Villanueva</option>
				<option>John Reyes</option>
			</select>
		</div>
		<div>
			<label class="text-slate-600">Credential Action</label>
			<select class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
				<option>Reset Password</option>
				<option>Unlock Account</option>
				<option>Disable Login Access</option>
			</select>
		</div>
		<div>
			<label class="text-slate-600">Temporary Password (if reset)</label>
			<input type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Enter temporary password">
		</div>
		<div>
			<label class="text-slate-600">Effective Until</label>
			<input type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
		</div>
		<div class="md:col-span-2">
			<label class="text-slate-600">Credential Notes</label>
			<textarea rows="3" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add reason for credential update"></textarea>
		</div>
		<div class="md:col-span-2 flex justify-end gap-3 mt-2">
			<button type="button" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Reset</button>
			<button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Apply Changes</button>
		</div>
	</form>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
