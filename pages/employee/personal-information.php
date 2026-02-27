<?php
/**
 * Employee Personal Information
 * DA-ATI HRIS
 */

require_once __DIR__ . '/includes/personal-information/bootstrap.php';
require_once __DIR__ . '/includes/personal-information/actions.php';
require_once __DIR__ . '/includes/personal-information/data.php';

$pageTitle = 'Personal Information | DA HRIS';
$activePage = 'personal-information.php';
$breadcrumbs = ['Personal Information'];
$pageScripts = $pageScripts ?? [];
$pageScripts[] = '/hris-system/assets/js/employee/personal-information/index.js';

ob_start();

$escape = static function (mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '-';
    }

    $ts = strtotime($value);
    return $ts === false ? '-' : date('M j, Y', $ts);
};

  $profileInitials = strtoupper(substr((string)($employeeProfile['first_name'] ?? 'E'), 0, 1) . substr((string)($employeeProfile['last_name'] ?? 'M'), 0, 1));
?>

<div class="mb-6">
  <h1 class="text-2xl font-bold">Personal Information</h1>
  <p class="text-sm text-gray-500">Manage your personal information</p>
</div>

<?php if (!empty($message)): ?>
  <?php
    $alertIsSuccess = ($state ?? '') === 'success';
    $alertClass = $alertIsSuccess
      ? 'border-green-200 bg-green-50 text-green-800'
      : 'border-red-200 bg-red-50 text-red-800';
  ?>
  <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $escape($alertClass) ?>" aria-live="polite">
    <?= $escape($message) ?>
  </div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
  <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" aria-live="polite">
    <?= $escape($dataLoadError) ?>
  </div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6 overflow-hidden">
  <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
    <div>
      <h2 class="text-lg font-semibold text-slate-800">Personal <span class="text-daGreen">Profile</span></h2>
      <p class="text-sm text-slate-500 mt-1">View your profile summary and update editable details.</p>
    </div>
    <button data-open-profile class="bg-daGreen text-white px-5 py-2 rounded-lg text-sm font-medium hover:opacity-90">Edit Profile</button>
  </header>

  <div class="p-6 space-y-5">
    <div class="flex flex-col lg:flex-row gap-4 lg:items-end lg:justify-between rounded-xl border border-slate-200 p-4 bg-slate-50">
      <div class="flex items-center gap-4">
        <?php if (!empty($employeeProfile['profile_photo_public_url'])): ?>
          <img src="<?= $escape((string)($employeeProfile['profile_photo_public_url'])) ?>" alt="Profile Photo" class="w-16 h-16 rounded-full object-cover border border-slate-200">
        <?php else: ?>
          <div class="w-16 h-16 rounded-full bg-daGreen text-white flex items-center justify-center text-lg font-semibold">
            <?= $escape($profileInitials !== '' ? $profileInitials : 'EM') ?>
          </div>
        <?php endif; ?>
        <div>
          <p class="text-sm font-semibold text-slate-800"><?= $escape(trim((string)($employeeProfile['first_name'] ?? '') . ' ' . (string)($employeeProfile['last_name'] ?? ''))) ?></p>
          <p class="text-xs text-slate-500">Profile photo appears in the top navigation.</p>
        </div>
      </div>

      <form id="profilePhotoForm" method="post" action="personal-information.php" enctype="multipart/form-data" class="flex flex-col lg:items-end gap-2">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
        <input type="hidden" name="action" value="upload_profile_photo">
        <div class="flex items-center gap-2">
          <input id="profilePhotoInput" type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp" class="hidden" required>
          <button id="profilePhotoActionBtn" type="button" class="border border-slate-300 bg-white text-slate-700 px-3 py-2 rounded-lg text-sm inline-flex items-center gap-1.5"><span id="profilePhotoActionIcon" class="material-symbols-outlined text-sm">upload</span><span id="profilePhotoActionText">Choose Photo</span></button>
          <span id="profilePhotoFileName" class="text-xs text-slate-500 max-w-[220px] truncate">No file selected</span>
        </div>
        <div id="profilePhotoPreviewWrap" class="hidden w-full lg:w-auto flex items-center gap-3 rounded-lg border border-slate-200 p-2 bg-white">
          <img id="profilePhotoPreview" src="" alt="Profile preview" class="w-14 h-14 rounded-full object-cover border">
          <p class="text-xs text-slate-600">Preview before saving</p>
        </div>
      </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
      <article class="rounded-xl border border-slate-200 p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">First Name</p>
        <p class="mt-2 font-medium text-slate-800"><?= $escape($employeeProfile['first_name'] ?? '-') ?></p>
      </article>
      <article class="rounded-xl border border-slate-200 p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Last Name</p>
        <p class="mt-2 font-medium text-slate-800"><?= $escape($employeeProfile['last_name'] ?? '-') ?></p>
      </article>
      <article class="rounded-xl border border-slate-200 p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Email Address</p>
        <p class="mt-2 font-medium text-slate-800 break-all"><?= $escape($employeeProfile['personal_email'] ?? '-') ?></p>
      </article>
      <article class="rounded-xl border border-slate-200 p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Mobile Number</p>
        <p class="mt-2 font-medium text-slate-800"><?= $escape($employeeProfile['mobile_no'] ?? '-') ?></p>
      </article>
      <article class="rounded-xl border border-slate-200 p-4 md:col-span-2 xl:col-span-2">
        <p class="text-xs uppercase tracking-wide text-slate-500">Address</p>
        <p class="mt-2 font-medium text-slate-800"><?= $escape($employeeProfile['address_line'] ?? '-') ?></p>
      </article>
      <article class="rounded-xl border border-slate-200 p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Date of Birth</p>
        <p class="mt-2 font-medium text-slate-800"><?= $escape($employeeProfile['date_of_birth'] ?? '-') ?></p>
      </article>
      <article class="rounded-xl border border-slate-200 p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Civil Status</p>
        <p class="mt-2 font-medium text-slate-800"><?= $escape($employeeProfile['civil_status'] ?? '-') ?></p>
      </article>
    </div>
  </div>
</section>

<section class="bg-white rounded-xl shadow p-6">
  <h2 class="text-lg font-bold mb-6">Employment <span class="text-daGreen">Details</span></h2>
  <div class="grid md:grid-cols-3 gap-4 text-sm">
    <div><label class="text-gray-500">Position</label><input disabled value="<?= $escape($employeeProfile['employment_position_title'] ?? '') ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
    <div><label class="text-gray-500">Office</label><input disabled value="<?= $escape($employeeProfile['employment_office_name'] ?? '') ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
    <div><label class="text-gray-500">Status</label><input disabled value="<?= $escape($employeeProfile['employment_status'] ?? '') ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
  </div>
</section>

<section class="bg-white rounded-xl shadow p-6 mt-6">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-bold">Spouse <span class="text-daGreen">Entry Requests</span></h2>
    <button type="button" data-open-spouse-request class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm font-medium">Request Additional Spouse Entry</button>
  </div>

  <?php if (!empty($spouseRequestHistory)): ?>
    <div class="space-y-3 text-sm">
      <?php foreach ($spouseRequestHistory as $request): ?>
        <?php
          $requestStatus = strtolower((string)($request['status'] ?? 'pending_admin_approval'));
          $requestStatusLabel = match ($requestStatus) {
              'approved' => 'Approved',
              'rejected' => 'Rejected',
              default => 'Pending Admin Approval',
          };
          $requestStatusClass = match ($requestStatus) {
              'approved' => 'bg-green-100 text-green-800',
              'rejected' => 'bg-red-100 text-red-800',
              default => 'bg-amber-100 text-amber-800',
          };
        ?>
        <article class="border rounded-lg p-4">
          <div class="flex flex-wrap justify-between items-start gap-2">
            <div>
              <p class="font-semibold text-slate-800"><?= $escape((string)($request['spouse_name'] !== '' ? $request['spouse_name'] : 'Spouse request')) ?></p>
              <p class="text-xs text-slate-500 mt-1">Submitted <?= $escape($formatDate($request['created_at'] ?? null)) ?></p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs <?= $escape($requestStatusClass) ?>"><?= $escape($requestStatusLabel) ?></span>
          </div>
          <?php if (!empty($request['attachment_name'])): ?>
            <p class="mt-2 text-xs text-slate-600">Attachment: <?= $escape((string)$request['attachment_name']) ?></p>
          <?php endif; ?>
          <?php if (!empty($request['notes'])): ?>
            <p class="mt-2 text-xs text-slate-600">Notes: <?= $escape((string)$request['notes']) ?></p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500">No spouse entry requests yet.</div>
  <?php endif; ?>
</section>

<div id="profileModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
  <div class="absolute inset-0 bg-black/40" data-close-profile></div>
  <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-6xl max-h-[92vh] overflow-hidden flex flex-col">
      <div class="px-6 py-4 border-b">
        <div class="flex justify-between items-center mb-3">
          <h2 class="text-lg font-semibold">Edit Profile</h2>
          <button type="button" data-close-profile><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="grid grid-cols-3 text-sm border rounded-lg overflow-hidden">
          <button type="button" data-profile-tab-target="personal" class="profile-tab px-3 py-2 bg-gray-50 border-b-2 border-daGreen text-daGreen font-medium">I. Personal Information</button>
          <button type="button" data-profile-tab-target="family" class="profile-tab px-3 py-2 bg-white border-b-2 border-transparent text-gray-600">II. Family Background</button>
          <button type="button" data-profile-tab-target="education" class="profile-tab px-3 py-2 bg-white border-b-2 border-transparent text-gray-600">III. Educational Background</button>
        </div>
      </div>

      <form method="post" action="personal-information.php" class="flex-1 min-h-0 flex flex-col">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
        <input type="hidden" name="action" value="update_profile">
        <input type="hidden" name="address_id" value="<?= $escape($employeeProfile['address_id'] ?? '') ?>">
        <input type="hidden" name="permanent_address_id" value="<?= $escape($employeeProfile['permanent_address_id'] ?? '') ?>">
        <input type="hidden" name="father_id" value="<?= $escape($employeeFather['id'] ?? '') ?>">
        <input type="hidden" name="mother_id" value="<?= $escape($employeeMother['id'] ?? '') ?>">

        <div class="flex-1 overflow-y-auto p-6 text-sm">
          <section data-profile-section="personal" class="space-y-6">
            <section class="space-y-3">
              <h4 class="text-sm font-semibold text-slate-700">Basic Identity</h4>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                  <label class="text-slate-600">First Name</label>
                  <input id="profileFirstName" name="first_name" type="text" value="<?= $escape($employeeProfile['first_name'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                  <label class="text-slate-600">Middle Name</label>
                  <input id="profileMiddleName" name="middle_name" type="text" value="<?= $escape($employeeProfile['middle_name'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-100" readonly>
                </div>
                <div>
                  <label class="text-slate-600">Last Name</label>
                  <input id="profileSurname" name="surname" type="text" value="<?= $escape($employeeProfile['last_name'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                  <label class="text-slate-600">Name Extension</label>
                  <input id="profileNameExtension" name="name_extension" type="text" value="<?= $escape($employeeProfile['name_extension'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Jr., Sr., III">
                </div>
              </div>
            </section>

            <section class="space-y-3 border-t border-slate-200 pt-4">
              <h4 class="text-sm font-semibold text-slate-700">Demographics</h4>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                  <label class="text-slate-600">Date of Birth</label>
                  <input id="profileDateOfBirth" name="date_of_birth" type="date" value="<?= $escape($employeeProfile['date_of_birth'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-100" readonly>
                </div>
                <div class="md:col-span-2">
                  <label class="text-slate-600">Place of Birth</label>
                  <div class="relative mt-1">
                    <input id="profilePlaceOfBirth" name="place_of_birth" type="text" list="profilePlaceOfBirthList" autocomplete="off" data-modern-search="place_of_birth" value="<?= $escape($employeeProfile['place_of_birth'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-slate-100" required readonly>
                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                      <span class="material-symbols-outlined text-[18px]">expand_more</span>
                    </span>
                  </div>
                </div>
                <div>
                  <label class="text-slate-600">Sex at Birth</label>
                  <select id="profileSexAtBirth" name="sex_at_birth" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    <option value="">Select sex</option>
                    <option value="male" <?= ($employeeProfile['sex_at_birth'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= ($employeeProfile['sex_at_birth'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                  </select>
                </div>
                <div>
                  <label class="text-slate-600">Civil Status</label>
                  <div class="relative mt-1">
                    <input id="profileCivilStatus" name="civil_status" type="text" list="profileCivilStatusList" autocomplete="off" data-modern-search="civil_status" value="<?= $escape($employeeProfile['civil_status'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                      <span class="material-symbols-outlined text-[18px]">expand_more</span>
                    </span>
                  </div>
                </div>
                <div>
                  <label class="text-slate-600">Height (m)</label>
                  <input id="profileHeightM" name="height_m" type="number" min="0" step="0.01" value="<?= $escape($employeeProfile['height_m'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                </div>
                <div>
                  <label class="text-slate-600">Weight (kg)</label>
                  <input id="profileWeightKg" name="weight_kg" type="number" min="0" step="0.01" value="<?= $escape($employeeProfile['weight_kg'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                </div>
                <div>
                  <label class="text-slate-600">Blood Type</label>
                  <div class="relative mt-1">
                    <input id="profileBloodType" name="blood_type" type="text" list="profileBloodTypeList" autocomplete="off" data-modern-search="blood_type" value="<?= $escape($employeeProfile['blood_type'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400">
                      <span class="material-symbols-outlined text-[18px]">expand_more</span>
                    </span>
                  </div>
                </div>
              </div>
            </section>

            <section class="space-y-3 border-t border-slate-200 pt-4">
              <h4 class="text-sm font-semibold text-slate-700">Citizenship</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="text-slate-600">Citizenship</label>
                  <input id="profileCitizenship" name="citizenship" type="text" value="<?= $escape($employeeProfile['citizenship'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                </div>
                <div>
                  <label class="text-slate-600">Dual Citizenship</label>
                  <select name="dual_citizenship" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    <option value="0" <?= ($employeeProfile['dual_citizenship'] ?? '0') === '0' ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= ($employeeProfile['dual_citizenship'] ?? '0') === '1' ? 'selected' : '' ?>>Yes</option>
                  </select>
                </div>
                <div>
                  <label class="text-slate-600">Dual Citizenship Country</label>
                  <input id="profileDualCitizenshipCountry" name="dual_citizenship_country" type="text" value="<?= $escape($employeeProfile['dual_citizenship_country'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                </div>
              </div>
            </section>

            <section class="space-y-3 border-t border-slate-200 pt-4">
              <h4 class="text-sm font-semibold text-slate-700">Residential Address</h4>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div><label class="text-slate-600">House No.</label><input id="profileResidentialHouseNo" name="residential_house_no" type="text" value="<?= $escape($employeeProfile['house_no'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div class="md:col-span-2"><label class="text-slate-600">Street</label><input id="profileResidentialStreet" name="residential_street" type="text" value="<?= $escape($employeeProfile['street'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div>
                  <label class="text-slate-600">Barangay</label>
                  <div class="relative mt-1">
                    <input id="profileResidentialBarangay" name="residential_barangay" type="text" autocomplete="off" data-address-role="barangay" data-address-group="residential" data-modern-search="residential_barangay" value="<?= $escape($employeeProfile['barangay'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                  </div>
                </div>
                <div><label class="text-slate-600">Subdivision</label><input id="profileResidentialSubdivision" name="residential_subdivision" type="text" value="<?= $escape($employeeProfile['subdivision'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div>
                  <label class="text-slate-600">City/Municipality</label>
                  <div class="relative mt-1">
                    <input id="profileResidentialCity" name="residential_city_municipality" type="text" list="profileCityList" autocomplete="off" data-address-role="city" data-address-group="residential" data-modern-search="residential_city" value="<?= $escape($employeeProfile['city_municipality'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                  </div>
                </div>
                <div>
                  <label class="text-slate-600">Province</label>
                  <div class="relative mt-1">
                    <input id="profileResidentialProvince" name="residential_province" type="text" list="profileProvinceList" autocomplete="off" data-modern-search="residential_province" value="<?= $escape($employeeProfile['province'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                  </div>
                </div>
                <div><label class="text-slate-600">ZIP Code</label><input id="profileResidentialZipCode" name="residential_zip_code" type="text" autocomplete="off" inputmode="numeric" pattern="^\d{4}$" data-address-role="zip" data-address-group="residential" data-modern-search="residential_zip" value="<?= $escape($employeeProfile['zip_code'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
              </div>
            </section>

            <section class="space-y-3 border-t border-slate-200 pt-4">
              <h4 class="text-sm font-semibold text-slate-700">Permanent Address</h4>
              <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input id="profileSameAsPermanentAddress" type="checkbox" name="permanent_same_as_residential" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" <?= !empty($employeeProfile['permanent_same_as_residential']) ? 'checked' : '' ?>>
                <span>Same as residential address</span>
              </label>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4" data-permanent-address-container>
                <div><label class="text-slate-600">House No.</label><input id="profilePermanentHouseNo" name="permanent_house_no" type="text" value="<?= $escape($employeeProfile['permanent_house_no'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div class="md:col-span-2"><label class="text-slate-600">Street</label><input id="profilePermanentStreet" name="permanent_street" type="text" value="<?= $escape($employeeProfile['permanent_street'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div>
                  <label class="text-slate-600">Barangay</label>
                  <div class="relative mt-1">
                    <input id="profilePermanentBarangay" name="permanent_barangay" type="text" autocomplete="off" data-address-role="barangay" data-address-group="permanent" data-modern-search="permanent_barangay" value="<?= $escape($employeeProfile['permanent_barangay'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                  </div>
                </div>
                <div><label class="text-slate-600">Subdivision</label><input id="profilePermanentSubdivision" name="permanent_subdivision" type="text" value="<?= $escape($employeeProfile['permanent_subdivision'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div>
                  <label class="text-slate-600">City/Municipality</label>
                  <div class="relative mt-1">
                    <input id="profilePermanentCity" name="permanent_city_municipality" type="text" list="profileCityList" autocomplete="off" data-address-role="city" data-address-group="permanent" data-modern-search="permanent_city" value="<?= $escape($employeeProfile['permanent_city_municipality'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                  </div>
                </div>
                <div>
                  <label class="text-slate-600">Province</label>
                  <div class="relative mt-1">
                    <input id="profilePermanentProvince" name="permanent_province" type="text" list="profileProvinceList" autocomplete="off" data-modern-search="permanent_province" value="<?= $escape($employeeProfile['permanent_province'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 pr-10 bg-white" required>
                    <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-slate-400"><span class="material-symbols-outlined text-[18px]">expand_more</span></span>
                  </div>
                </div>
                <div><label class="text-slate-600">ZIP Code</label><input id="profilePermanentZipCode" name="permanent_zip_code" type="text" autocomplete="off" inputmode="numeric" pattern="^\d{4}$" data-address-role="zip" data-address-group="permanent" data-modern-search="permanent_zip" value="<?= $escape($employeeProfile['permanent_zip_code'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required></div>
              </div>
            </section>

            <section class="space-y-3 border-t border-slate-200 pt-4">
              <h4 class="text-sm font-semibold text-slate-700">Government IDs</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="text-slate-600">UMID ID No.</label><input id="profileUmidNo" name="umid_no" type="text" value="<?= $escape($employeeProfile['umid_no'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div><label class="text-slate-600">PAG-IBIG ID No.</label><input id="profilePagibigNo" name="pagibig_no" type="text" value="<?= $escape($employeeProfile['pagibig_no'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div><label class="text-slate-600">PHILHEALTH No.</label><input id="profilePhilhealthNo" name="philhealth_no" type="text" value="<?= $escape($employeeProfile['philhealth_no'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div><label class="text-slate-600">PhilSys Number (PSN)</label><input id="profilePsnNo" name="psn_no" type="text" value="<?= $escape($employeeProfile['psn_no'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div><label class="text-slate-600">TIN No.</label><input id="profileTinNo" name="tin_no" type="text" value="<?= $escape($employeeProfile['tin_no'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div><label class="text-slate-600">Agency Employee No.</label><input id="profileAgencyEmployeeNo" name="agency_employee_no" type="text" value="<?= $escape($employeeProfile['agency_employee_no'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
              </div>
            </section>

            <section class="space-y-3 border-t border-slate-200 pt-4">
              <h4 class="text-sm font-semibold text-slate-700">Contact Details</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="text-slate-600">Telephone Number</label><input id="profileTelephoneNo" name="telephone_no" type="text" value="<?= $escape($employeeProfile['telephone_no'] ?? '') ?>" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div><label class="text-slate-600">Mobile Number</label><input id="profileMobile" name="mobile_no" type="text" pattern="^\+?[0-9][0-9\s-]{6,19}$" value="<?= $escape($employeeProfile['mobile_no'] ?? '') ?>" required class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
                <div><label class="text-slate-600">Email Address</label><input id="profileEmail" name="email" type="email" value="<?= $escape($employeeProfile['personal_email'] ?? '') ?>" required class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"></div>
              </div>
            </section>
          </section>

          <section data-profile-section="family" class="space-y-4 hidden">
            <h3 class="font-semibold text-base">II. Family Background</h3>
            <h4 class="font-semibold">Spouse</h4>
            <div class="rounded-lg border border-dashed border-slate-300 p-4">
              <p class="text-sm text-slate-600">Spouse details are controlled by request. Submit a spouse entry request with supporting documents for admin approval.</p>
              <div class="mt-3 text-sm">
                <p class="font-medium text-slate-700"><?= $escape(trim((string)($employeeSpouse['first_name'] ?? '') . ' ' . (string)($employeeSpouse['surname'] ?? ''))) !== '' ? $escape(trim((string)($employeeSpouse['first_name'] ?? '') . ' ' . (string)($employeeSpouse['surname'] ?? ''))) : 'No approved spouse record yet.' ?></p>
                <?php if (!empty($employeeSpouse['occupation']) || !empty($employeeSpouse['employer_business_name'])): ?>
                  <p class="text-xs text-slate-500 mt-1"><?= $escape((string)($employeeSpouse['occupation'] ?? '')) ?> <?= !empty($employeeSpouse['employer_business_name']) ? '· ' . $escape((string)$employeeSpouse['employer_business_name']) : '' ?></p>
                <?php endif; ?>
              </div>
              <button type="button" data-open-spouse-request class="mt-3 border border-slate-300 px-3 py-1.5 rounded-lg text-sm text-slate-700 hover:bg-slate-50">Submit Spouse Entry Request</button>
            </div>

            <div class="flex items-center justify-between pt-2">
              <h4 class="font-semibold">Children</h4>
              <button type="button" data-add-child-row class="border px-3 py-1 rounded-lg text-sm">Add Child</button>
            </div>
            <div id="childrenRows" class="space-y-2">
              <?php if (!empty($employeeChildren)): ?>
                <?php foreach ($employeeChildren as $child): ?>
                  <div class="grid grid-cols-1 md:grid-cols-12 gap-2 child-row">
                    <div class="md:col-span-8"><input name="children_full_name[]" value="<?= $escape($child['full_name'] ?? '') ?>" placeholder="Child Full Name" class="border rounded-lg p-2 w-full"></div>
                    <div class="md:col-span-3"><input type="date" name="children_birth_date[]" value="<?= $escape($child['birth_date'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
                    <div class="md:col-span-1"><button type="button" data-remove-child-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 child-row">
                  <div class="md:col-span-8"><input name="children_full_name[]" placeholder="Child Full Name" class="border rounded-lg p-2 w-full"></div>
                  <div class="md:col-span-3"><input type="date" name="children_birth_date[]" class="border rounded-lg p-2 w-full"></div>
                  <div class="md:col-span-1"><button type="button" data-remove-child-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
                </div>
              <?php endif; ?>
            </div>

            <h4 class="font-semibold pt-2">Father</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div><input name="father_surname" value="<?= $escape($employeeFather['surname'] ?? '') ?>" placeholder="Surname" class="border rounded-lg p-2 w-full"></div>
              <div><input name="father_first_name" value="<?= $escape($employeeFather['first_name'] ?? '') ?>" placeholder="First Name" class="border rounded-lg p-2 w-full"></div>
              <div><input name="father_name_extension" value="<?= $escape($employeeFather['extension_name'] ?? '') ?>" placeholder="Name Extension" class="border rounded-lg p-2 w-full"></div>
              <div><input name="father_middle_name" value="<?= $escape($employeeFather['middle_name'] ?? '') ?>" placeholder="Middle Name" class="border rounded-lg p-2 w-full"></div>
            </div>

            <h4 class="font-semibold">Mother</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div><input name="mother_surname" value="<?= $escape($employeeMother['surname'] ?? '') ?>" placeholder="Maiden Surname" class="border rounded-lg p-2 w-full"></div>
              <div><input name="mother_first_name" value="<?= $escape($employeeMother['first_name'] ?? '') ?>" placeholder="First Name" class="border rounded-lg p-2 w-full"></div>
              <div><input name="mother_middle_name" value="<?= $escape($employeeMother['middle_name'] ?? '') ?>" placeholder="Middle Name" class="border rounded-lg p-2 w-full"></div>
            </div>
          </section>

          <section data-profile-section="education" class="space-y-4 hidden">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-base">III. Educational Background</h3>
              <button type="button" data-add-education-row class="border px-3 py-1 rounded-lg text-sm">Add Education Row</button>
            </div>
            <div id="educationRows" class="space-y-3">
              <?php foreach ($employeeEducationRows as $educationRow): ?>
                <div class="border rounded-lg p-3 education-row">
                  <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                    <div class="md:col-span-2">
                      <select name="education_level[]" class="border rounded-lg p-2 w-full">
                        <?php foreach ($pdsEducationLevels as $level): ?>
                          <option value="<?= $escape($level) ?>" <?= ($educationRow['education_level'] ?? '') === $level ? 'selected' : '' ?>><?= $escape(ucfirst($level)) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="md:col-span-4"><input name="education_school_name[]" value="<?= $escape($educationRow['school_name'] ?? '') ?>" placeholder="Name of School" class="border rounded-lg p-2 w-full"></div>
                    <div class="md:col-span-3"><input name="education_course_degree[]" value="<?= $escape($educationRow['course_degree'] ?? '') ?>" placeholder="Basic Education / Degree / Course" class="border rounded-lg p-2 w-full"></div>
                    <div class="md:col-span-1"><input name="education_period_from[]" value="<?= $escape($educationRow['period_from'] ?? '') ?>" placeholder="From" class="border rounded-lg p-2 w-full"></div>
                    <div class="md:col-span-1"><input name="education_period_to[]" value="<?= $escape($educationRow['period_to'] ?? '') ?>" placeholder="To" class="border rounded-lg p-2 w-full"></div>
                    <div class="md:col-span-1"><button type="button" data-remove-education-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
                    <div class="md:col-span-4"><input name="education_highest_level_units[]" value="<?= $escape($educationRow['highest_level_units'] ?? '') ?>" placeholder="Highest Level / Units Earned" class="border rounded-lg p-2 w-full"></div>
                    <div class="md:col-span-3"><input name="education_year_graduated[]" value="<?= $escape($educationRow['year_graduated'] ?? '') ?>" placeholder="Year Graduated" class="border rounded-lg p-2 w-full"></div>
                    <div class="md:col-span-5"><input name="education_honors_received[]" value="<?= $escape($educationRow['honors_received'] ?? '') ?>" placeholder="Scholarship / Academic Honors Received" class="border rounded-lg p-2 w-full"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        </div>

        <div class="px-6 py-4 border-t flex items-center justify-between gap-3">
          <div class="flex gap-2">
            <button type="button" data-profile-prev class="border px-4 py-2 rounded-lg text-sm">Previous</button>
            <button type="button" data-profile-next class="border px-4 py-2 rounded-lg text-sm">Next</button>
          </div>
          <div class="flex gap-2">
            <button type="button" data-close-profile class="border px-4 py-2 rounded-lg text-sm">Cancel</button>
            <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<template id="childRowTemplate">
  <div class="grid grid-cols-1 md:grid-cols-12 gap-2 child-row">
    <div class="md:col-span-8"><input name="children_full_name[]" placeholder="Child Full Name" class="border rounded-lg p-2 w-full"></div>
    <div class="md:col-span-3"><input type="date" name="children_birth_date[]" class="border rounded-lg p-2 w-full"></div>
    <div class="md:col-span-1"><button type="button" data-remove-child-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
  </div>
</template>

<template id="educationRowTemplate">
  <div class="border rounded-lg p-3 education-row">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
      <div class="md:col-span-2">
        <select name="education_level[]" class="border rounded-lg p-2 w-full">
          <?php foreach ($pdsEducationLevels as $level): ?>
            <option value="<?= $escape($level) ?>"><?= $escape(ucfirst($level)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="md:col-span-4"><input name="education_school_name[]" placeholder="Name of School" class="border rounded-lg p-2 w-full"></div>
      <div class="md:col-span-3"><input name="education_course_degree[]" placeholder="Basic Education / Degree / Course" class="border rounded-lg p-2 w-full"></div>
      <div class="md:col-span-1"><input name="education_period_from[]" placeholder="From" class="border rounded-lg p-2 w-full"></div>
      <div class="md:col-span-1"><input name="education_period_to[]" placeholder="To" class="border rounded-lg p-2 w-full"></div>
      <div class="md:col-span-1"><button type="button" data-remove-education-row class="border rounded-lg px-2 py-2 w-full">×</button></div>
      <div class="md:col-span-4"><input name="education_highest_level_units[]" placeholder="Highest Level / Units Earned" class="border rounded-lg p-2 w-full"></div>
      <div class="md:col-span-3"><input name="education_year_graduated[]" placeholder="Year Graduated" class="border rounded-lg p-2 w-full"></div>
      <div class="md:col-span-5"><input name="education_honors_received[]" placeholder="Scholarship / Academic Honors Received" class="border rounded-lg p-2 w-full"></div>
    </div>
  </div>
</template>

<datalist id="profilePlaceOfBirthList">
  <?php foreach ($placeOfBirthOptions as $option): ?>
    <option value="<?= $escape($option) ?>"></option>
  <?php endforeach; ?>
</datalist>
<datalist id="profileCivilStatusList">
  <?php foreach ($civilStatusOptions as $option): ?>
    <option value="<?= $escape($option) ?>"></option>
  <?php endforeach; ?>
</datalist>
<datalist id="profileBloodTypeList">
  <?php foreach ($bloodTypeOptions as $option): ?>
    <option value="<?= $escape($option) ?>"></option>
  <?php endforeach; ?>
</datalist>
<datalist id="profileCityList">
  <?php foreach ($cityMunicipalityOptions as $option): ?>
    <option value="<?= $escape($option) ?>"></option>
  <?php endforeach; ?>
</datalist>
<datalist id="profileProvinceList">
  <?php foreach ($provinceOptions as $option): ?>
    <option value="<?= $escape($option) ?>"></option>
  <?php endforeach; ?>
</datalist>
<datalist id="profileResidentialBarangayList">
  <?php foreach ($barangayOptions as $option): ?>
    <option value="<?= $escape($option) ?>"></option>
  <?php endforeach; ?>
</datalist>
<datalist id="profilePermanentBarangayList">
  <?php foreach ($barangayOptions as $option): ?>
    <option value="<?= $escape($option) ?>"></option>
  <?php endforeach; ?>
</datalist>

<script id="employeeAddressLookupData" type="application/json"><?= (string)json_encode([
  'barangayByCity' => $barangayByCityLookup,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<div id="spouseRequestModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
  <div class="absolute inset-0 bg-black/40" data-close-spouse-request></div>
  <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
      <div class="px-6 py-4 border-b flex items-center justify-between">
        <h2 class="text-lg font-semibold">Request Additional Spouse Entry</h2>
        <button type="button" data-close-spouse-request><span class="material-symbols-outlined">close</span></button>
      </div>
      <form method="post" action="personal-information.php" enctype="multipart/form-data" class="p-6 overflow-y-auto space-y-4 text-sm">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
        <input type="hidden" name="action" value="submit_spouse_request">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div><label class="text-gray-600">Surname</label><input name="spouse_surname" required class="border rounded-lg p-2 w-full"></div>
          <div><label class="text-gray-600">First Name</label><input name="spouse_first_name" required class="border rounded-lg p-2 w-full"></div>
          <div><label class="text-gray-600">Middle Name</label><input name="spouse_middle_name" class="border rounded-lg p-2 w-full"></div>
          <div><label class="text-gray-600">Name Extension</label><input name="spouse_name_extension" class="border rounded-lg p-2 w-full"></div>
          <div><label class="text-gray-600">Occupation</label><input name="spouse_occupation" class="border rounded-lg p-2 w-full"></div>
          <div><label class="text-gray-600">Employer/Business Name</label><input name="spouse_employer_business_name" class="border rounded-lg p-2 w-full"></div>
          <div class="md:col-span-2"><label class="text-gray-600">Business Address</label><input name="spouse_business_address" class="border rounded-lg p-2 w-full"></div>
          <div><label class="text-gray-600">Telephone No.</label><input name="spouse_telephone_no" class="border rounded-lg p-2 w-full"></div>
        </div>

        <div>
          <label class="text-gray-600">Supporting Document</label>
          <input type="file" name="spouse_supporting_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="mt-1 w-full text-sm" required>
          <p class="text-xs text-slate-500 mt-1">Accepted: PDF, JPG, PNG, DOC, DOCX (max 10 MB)</p>
        </div>

        <div>
          <label class="text-gray-600">Request Notes</label>
          <textarea name="request_notes" rows="3" class="border rounded-lg p-2 w-full" placeholder="Reason/notes for admin review"></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" data-close-spouse-request class="border px-4 py-2 rounded-lg">Cancel</button>
          <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg">Submit for Approval</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include './includes/layout.php';
