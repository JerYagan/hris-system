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

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold">Personal <span class="text-daGreen">Profile</span></h2>
    <button data-open-profile class="bg-daGreen text-white px-5 py-2 rounded-lg text-sm font-medium hover:opacity-90">Edit Profile</button>
  </div>

  <div class="mb-6 flex flex-col sm:flex-row gap-4 sm:items-end sm:justify-between border rounded-lg p-4 bg-gray-50">
    <div class="flex items-center gap-4">
      <?php if (!empty($employeeProfile['profile_photo_public_url'])): ?>
        <img src="<?= $escape((string)$employeeProfile['profile_photo_public_url']) ?>" alt="Profile Photo" class="w-16 h-16 rounded-full object-cover border">
      <?php else: ?>
        <div class="w-16 h-16 rounded-full bg-daGreen text-white flex items-center justify-center text-lg font-semibold">
          <?= $escape($profileInitials !== '' ? $profileInitials : 'EM') ?>
        </div>
      <?php endif; ?>
      <div>
        <p class="text-sm font-semibold"><?= $escape(trim((string)($employeeProfile['first_name'] ?? '') . ' ' . (string)($employeeProfile['last_name'] ?? ''))) ?></p>
        <p class="text-xs text-gray-500">Profile picture shown in top navigation.</p>
      </div>
    </div>

    <form method="post" action="personal-information.php" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2 sm:items-center">
      <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken ?? '') ?>">
      <input type="hidden" name="action" value="upload_profile_photo">
      <input type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp" class="text-sm" required>
      <button type="submit" class="bg-daGreen text-white px-4 py-2 rounded-lg text-sm inline-flex items-center gap-1.5"><span class="material-icons text-sm">photo_camera</span>Upload Photo</button>
    </form>
  </div>

  <div class="grid md:grid-cols-4 gap-4 text-sm">
    <div><label class="text-gray-500">First Name</label><input disabled value="<?= $escape($employeeProfile['first_name'] ?? '') ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
    <div><label class="text-gray-500">Last Name</label><input disabled value="<?= $escape($employeeProfile['last_name'] ?? '') ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
    <div><label class="text-gray-500">Email Address</label><input disabled value="<?= $escape($employeeProfile['personal_email'] ?? '') ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
    <div><label class="text-gray-500">Mobile Number</label><input disabled value="<?= $escape($employeeProfile['mobile_no'] ?? '') ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
    <div class="md:col-span-2"><label class="text-gray-500">Address</label><input disabled value="<?= $escape($employeeProfile['address_line'] ?? '-') ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
    <div><label class="text-gray-500">Date of Birth</label><input disabled value="<?= $escape($employeeProfile['date_of_birth'] ?? '') ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
    <div><label class="text-gray-500">Civil Status</label><input disabled value="<?= $escape($employeeProfile['civil_status'] ?? '') ?>" class="w-full mt-1 p-2 bg-gray-100 rounded-lg"></div>
  </div>
</section>

<section class="bg-white rounded-xl shadow p-6 mb-6">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold">Personal <span class="text-daGreen">Documents</span></h2>
    <a href="document-management.php" class="bg-daGreen text-white px-5 py-2 rounded-lg text-sm font-medium hover:opacity-90 inline-block">Upload / Manage Documents</a>
  </div>
  <p class="text-sm text-gray-500 mb-4">This section is view-only. Use Document Management for uploads, edits, removals, and version updates.</p>

  <div class="space-y-4 text-sm">
    <?php if (!empty($employeeDocuments)): ?>
      <?php foreach ($employeeDocuments as $document): ?>
        <?php
          $status = strtolower((string)($document['document_status'] ?? 'draft'));
          $statusLabel = match ($status) {
            'approved' => 'Approved',
            'submitted' => 'Submitted',
            'rejected' => 'Rejected',
            default => ucfirst($status),
          };
          $statusClass = match ($status) {
            'approved' => 'bg-approved text-green-800',
            'submitted' => 'bg-pending text-yellow-800',
            'rejected' => 'bg-rejected text-red-800',
            default => 'bg-gray-200 text-gray-700',
          };
        ?>
        <div class="flex items-center justify-between border rounded-lg px-4 py-3">
          <div class="flex items-center gap-3">
            <span class="material-icons text-gray-400">description</span>
            <div>
              <p class="font-medium"><?= $escape($document['title'] ?? 'Document') ?></p>
              <p class="text-xs text-gray-500"><?= $escape($document['category_name'] ?? 'Uncategorized') ?> · Updated <?= $escape($formatDate($document['updated_at'] ?? null)) ?></p>
            </div>
          </div>
          <span class="px-3 py-1 rounded-full <?= $escape($statusClass) ?>"><?= $escape($statusLabel) ?></span>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500">No uploaded documents yet.</div>
    <?php endif; ?>
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

<div id="profileModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
  <div class="absolute inset-0 bg-black/40" data-close-profile></div>
  <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-6xl max-h-[92vh] overflow-hidden flex flex-col">
      <div class="px-6 py-4 border-b">
        <div class="flex justify-between items-center mb-3">
          <h2 class="text-lg font-semibold">Edit Profile</h2>
          <button type="button" data-close-profile><span class="material-icons">close</span></button>
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
        <input type="hidden" name="spouse_id" value="<?= $escape($employeeSpouse['id'] ?? '') ?>">
        <input type="hidden" name="father_id" value="<?= $escape($employeeFather['id'] ?? '') ?>">
        <input type="hidden" name="mother_id" value="<?= $escape($employeeMother['id'] ?? '') ?>">

        <div class="flex-1 overflow-y-auto p-6 text-sm">
          <section data-profile-section="personal" class="space-y-4">
            <h3 class="font-semibold text-base">I. Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div><label class="text-gray-500">Surname</label><input value="<?= $escape($employeeProfile['last_name'] ?? '') ?>" class="border rounded-lg p-2 w-full bg-gray-100" readonly></div>
              <div><label class="text-gray-500">First Name</label><input value="<?= $escape($employeeProfile['first_name'] ?? '') ?>" class="border rounded-lg p-2 w-full bg-gray-100" readonly></div>
              <div><label class="text-gray-500">Name Extension</label><input name="name_extension" value="<?= $escape($employeeProfile['name_extension'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">Middle Name</label><input name="middle_name" value="<?= $escape($employeeProfile['middle_name'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">Date of Birth</label><input name="date_of_birth" type="date" value="<?= $escape($employeeProfile['date_of_birth'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div class="md:col-span-2"><label class="text-gray-500">Place of Birth</label><input name="place_of_birth" value="<?= $escape($employeeProfile['place_of_birth'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">Sex</label>
                <select name="sex_at_birth" class="border rounded-lg p-2 w-full">
                  <option value="">Select</option>
                  <option value="male" <?= ($employeeProfile['sex_at_birth'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                  <option value="female" <?= ($employeeProfile['sex_at_birth'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                </select>
              </div>
              <div><label class="text-gray-500">Civil Status</label><input name="civil_status" value="<?= $escape($employeeProfile['civil_status'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">Height (m)</label><input name="height_m" value="<?= $escape($employeeProfile['height_m'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">Weight (kg)</label><input name="weight_kg" value="<?= $escape($employeeProfile['weight_kg'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">Blood Type</label><input name="blood_type" value="<?= $escape($employeeProfile['blood_type'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">GSIS ID No.</label><input name="umid_no" value="<?= $escape($employeeProfile['umid_no'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">PAG-IBIG ID No.</label><input name="pagibig_no" value="<?= $escape($employeeProfile['pagibig_no'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">PhilHealth No.</label><input name="philhealth_no" value="<?= $escape($employeeProfile['philhealth_no'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">SSS/PSN No.</label><input name="psn_no" value="<?= $escape($employeeProfile['psn_no'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">TIN No.</label><input name="tin_no" value="<?= $escape($employeeProfile['tin_no'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">Agency Employee No.</label><input name="agency_employee_no" value="<?= $escape($employeeProfile['agency_employee_no'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div><label class="text-gray-500">Citizenship</label><input name="citizenship" value="<?= $escape($employeeProfile['citizenship'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
              <div>
                <label class="text-gray-500">Dual Citizenship</label>
                <select name="dual_citizenship" class="border rounded-lg p-2 w-full">
                  <option value="0" <?= ($employeeProfile['dual_citizenship'] ?? '0') === '0' ? 'selected' : '' ?>>No</option>
                  <option value="1" <?= ($employeeProfile['dual_citizenship'] ?? '0') === '1' ? 'selected' : '' ?>>Yes</option>
                </select>
              </div>
              <div><label class="text-gray-500">Dual Citizenship Country</label><input name="dual_citizenship_country" value="<?= $escape($employeeProfile['dual_citizenship_country'] ?? '') ?>" class="border rounded-lg p-2 w-full"></div>
            </div>

            <h4 class="font-semibold pt-2">Residential Address</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div><input name="house_no" value="<?= $escape($employeeProfile['house_no'] ?? '') ?>" placeholder="House/Block/Lot No." class="border rounded-lg p-2 w-full"></div>
              <div><input name="street" value="<?= $escape($employeeProfile['street'] ?? '') ?>" placeholder="Street" class="border rounded-lg p-2 w-full"></div>
              <div><input name="subdivision" value="<?= $escape($employeeProfile['subdivision'] ?? '') ?>" placeholder="Subdivision/Village" class="border rounded-lg p-2 w-full"></div>
              <div><input name="barangay" value="<?= $escape($employeeProfile['barangay'] ?? '') ?>" placeholder="Barangay" class="border rounded-lg p-2 w-full"></div>
              <div><input name="city_municipality" value="<?= $escape($employeeProfile['city_municipality'] ?? '') ?>" placeholder="City/Municipality" class="border rounded-lg p-2 w-full"></div>
              <div><input name="province" value="<?= $escape($employeeProfile['province'] ?? '') ?>" placeholder="Province" class="border rounded-lg p-2 w-full"></div>
              <div><input name="zip_code" value="<?= $escape($employeeProfile['zip_code'] ?? '') ?>" placeholder="ZIP Code" class="border rounded-lg p-2 w-full"></div>
            </div>

            <div class="pt-2 flex items-center gap-2">
              <input id="sameAddress" type="checkbox" name="permanent_same_as_residential" value="1" <?= !empty($employeeProfile['permanent_same_as_residential']) ? 'checked' : '' ?>>
              <label for="sameAddress" class="text-gray-600">Permanent address same as residential</label>
            </div>

            <h4 class="font-semibold">Permanent Address</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4" data-permanent-address-container>
              <div><input name="permanent_house_no" value="<?= $escape($employeeProfile['permanent_house_no'] ?? '') ?>" placeholder="House/Block/Lot No." class="border rounded-lg p-2 w-full"></div>
              <div><input name="permanent_street" value="<?= $escape($employeeProfile['permanent_street'] ?? '') ?>" placeholder="Street" class="border rounded-lg p-2 w-full"></div>
              <div><input name="permanent_subdivision" value="<?= $escape($employeeProfile['permanent_subdivision'] ?? '') ?>" placeholder="Subdivision/Village" class="border rounded-lg p-2 w-full"></div>
              <div><input name="permanent_barangay" value="<?= $escape($employeeProfile['permanent_barangay'] ?? '') ?>" placeholder="Barangay" class="border rounded-lg p-2 w-full"></div>
              <div><input name="permanent_city_municipality" value="<?= $escape($employeeProfile['permanent_city_municipality'] ?? '') ?>" placeholder="City/Municipality" class="border rounded-lg p-2 w-full"></div>
              <div><input name="permanent_province" value="<?= $escape($employeeProfile['permanent_province'] ?? '') ?>" placeholder="Province" class="border rounded-lg p-2 w-full"></div>
              <div><input name="permanent_zip_code" value="<?= $escape($employeeProfile['permanent_zip_code'] ?? '') ?>" placeholder="ZIP Code" class="border rounded-lg p-2 w-full"></div>
            </div>

            <h4 class="font-semibold">Contact</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div><input name="telephone_no" value="<?= $escape($employeeProfile['telephone_no'] ?? '') ?>" placeholder="Telephone No." class="border rounded-lg p-2 w-full"></div>
              <div><input name="mobile_no" value="<?= $escape($employeeProfile['mobile_no'] ?? '') ?>" placeholder="Mobile No." class="border rounded-lg p-2 w-full"></div>
              <div><input type="email" name="personal_email" value="<?= $escape($employeeProfile['personal_email'] ?? '') ?>" placeholder="Email Address" class="border rounded-lg p-2 w-full"></div>
            </div>
          </section>

          <section data-profile-section="family" class="space-y-4 hidden">
            <h3 class="font-semibold text-base">II. Family Background</h3>
            <h4 class="font-semibold">Spouse</h4>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div><input name="spouse_surname" value="<?= $escape($employeeSpouse['surname'] ?? '') ?>" placeholder="Surname" class="border rounded-lg p-2 w-full"></div>
              <div><input name="spouse_first_name" value="<?= $escape($employeeSpouse['first_name'] ?? '') ?>" placeholder="First Name" class="border rounded-lg p-2 w-full"></div>
              <div><input name="spouse_name_extension" value="<?= $escape($employeeSpouse['extension_name'] ?? '') ?>" placeholder="Name Extension" class="border rounded-lg p-2 w-full"></div>
              <div><input name="spouse_middle_name" value="<?= $escape($employeeSpouse['middle_name'] ?? '') ?>" placeholder="Middle Name" class="border rounded-lg p-2 w-full"></div>
              <div><input name="spouse_occupation" value="<?= $escape($employeeSpouse['occupation'] ?? '') ?>" placeholder="Occupation" class="border rounded-lg p-2 w-full"></div>
              <div><input name="spouse_employer_business_name" value="<?= $escape($employeeSpouse['employer_business_name'] ?? '') ?>" placeholder="Employer/Business Name" class="border rounded-lg p-2 w-full"></div>
              <div class="md:col-span-2"><input name="spouse_business_address" value="<?= $escape($employeeSpouse['business_address'] ?? '') ?>" placeholder="Business Address" class="border rounded-lg p-2 w-full"></div>
              <div><input name="spouse_telephone_no" value="<?= $escape($employeeSpouse['telephone_no'] ?? '') ?>" placeholder="Telephone No." class="border rounded-lg p-2 w-full"></div>
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

<?php
$content = ob_get_clean();
include './includes/layout.php';
