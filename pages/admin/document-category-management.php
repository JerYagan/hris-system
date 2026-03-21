<?php

require_once __DIR__ . '/includes/document-management/bootstrap.php';

$csrfToken = ensureCsrfToken();
$categoryManagementPath = 'document-category-management.php';
$invalidCategoryNames = ['haugafia'];

$normalizeCategoryLabel = static function (?string $value): string {
    return trim((string)preg_replace('/\s+/', ' ', (string)$value));
};

$buildCategoryKey = static function (string $label): string {
    return strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', $label), '_'));
};

$validateCategoryLabel = static function (string $label) use ($invalidCategoryNames): ?string {
    if ($label === '') {
        return 'Category name is required.';
    }

    if (mb_strlen($label) > 80) {
        return 'Category name must be 80 characters or less.';
    }

    if (in_array(strtolower($label), $invalidCategoryNames, true)) {
        return 'That category label is not allowed.';
    }

    if (preg_match('/^[A-Za-z0-9][A-Za-z0-9()\/,&\-\s]{1,79}$/', $label) !== 1) {
        return 'Use letters, numbers, spaces, and basic punctuation only for category names.';
    }

    return null;
};

$loadCategoryRow = static function (string $categoryId) use ($supabaseUrl, $headers): ?array {
    $response = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/document_categories?select=id,category_key,category_name,requires_approval,created_at&id=eq.' . rawurlencode($categoryId) . '&limit=1',
        $headers
    );

    return isSuccessful($response) && is_array($response['data'][0] ?? null)
        ? (array)$response['data'][0]
        : null;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cleanText($_POST['form_action'] ?? null) ?? '';
    $categoryId = cleanText($_POST['category_id'] ?? null) ?? '';
    $categoryName = $normalizeCategoryLabel($_POST['category_name'] ?? null);

    if (!isValidCsrfToken(cleanText($_POST['csrf_token'] ?? null))) {
        redirectWithState('error', 'Invalid request token. Please refresh the page and try again.', $categoryManagementPath);
    }

    if (!in_array($action, ['create_document_category', 'update_document_category', 'delete_document_category'], true)) {
        redirectWithState('error', 'Unknown category management action.', $categoryManagementPath);
    }

    if ($action === 'create_document_category') {
        $validationError = $validateCategoryLabel($categoryName);
        if ($validationError !== null) {
            redirectWithState('error', $validationError, $categoryManagementPath);
        }

        $categoryKey = $buildCategoryKey($categoryName);
        if ($categoryKey === '') {
            redirectWithState('error', 'Unable to generate a valid category key.', $categoryManagementPath);
        }

        $existingResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/document_categories?select=id,category_name'
            . '&or=(category_key.eq.' . rawurlencode($categoryKey) . ',category_name.eq.' . rawurlencode($categoryName) . ')'
            . '&limit=1',
            $headers
        );

        if (isSuccessful($existingResponse) && !empty((array)($existingResponse['data'] ?? []))) {
            redirectWithState('success', 'Document category already exists.', $categoryManagementPath);
        }

        $createResponse = apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/document_categories',
            array_merge($headers, ['Prefer: return=representation']),
            [[
                'category_key' => $categoryKey,
                'category_name' => $categoryName,
                'requires_approval' => true,
            ]]
        );

        if (!isSuccessful($createResponse)) {
            redirectWithState('error', 'Failed to create document category.', $categoryManagementPath);
        }

        $createdCategory = (array)($createResponse['data'][0] ?? []);
        $createdCategoryId = trim((string)($createdCategory['id'] ?? ''));
        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId,
                'module_name' => 'document_management',
                'entity_name' => 'document_categories',
                'entity_id' => $createdCategoryId !== '' ? $createdCategoryId : null,
                'action_name' => 'create_document_category',
                'old_data' => null,
                'new_data' => [
                    'category_name' => $categoryName,
                    'category_key' => $categoryKey,
                ],
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Document category created successfully.', $categoryManagementPath);
    }

    if (!isValidUuid($categoryId)) {
        redirectWithState('error', 'Select a valid document category.', $categoryManagementPath);
    }

    $currentCategory = $loadCategoryRow($categoryId);
    if (!is_array($currentCategory)) {
        redirectWithState('error', 'Document category record not found.', $categoryManagementPath);
    }

    if ($action === 'update_document_category') {
        $validationError = $validateCategoryLabel($categoryName);
        if ($validationError !== null) {
            redirectWithState('error', $validationError, $categoryManagementPath);
        }

        $categoryKey = $buildCategoryKey($categoryName);
        if ($categoryKey === '') {
            redirectWithState('error', 'Unable to generate a valid category key.', $categoryManagementPath);
        }

        $existingResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/document_categories?select=id,category_name,category_key&category_key=eq.' . rawurlencode($categoryKey) . '&limit=1',
            $headers
        );
        $existingCategory = isSuccessful($existingResponse) ? ($existingResponse['data'][0] ?? null) : null;
        if (is_array($existingCategory) && (string)($existingCategory['id'] ?? '') !== $categoryId) {
            redirectWithState('error', 'Another category already uses that key.', $categoryManagementPath);
        }

        if ((string)($currentCategory['category_name'] ?? '') === $categoryName && (string)($currentCategory['category_key'] ?? '') === $categoryKey) {
            redirectWithState('success', 'No category changes were needed.', $categoryManagementPath);
        }

        $patchResponse = apiRequest(
            'PATCH',
            $supabaseUrl . '/rest/v1/document_categories?id=eq.' . rawurlencode($categoryId),
            array_merge($headers, ['Prefer: return=minimal']),
            [
                'category_name' => $categoryName,
                'category_key' => $categoryKey,
            ]
        );

        if (!isSuccessful($patchResponse)) {
            redirectWithState('error', 'Failed to update document category.', $categoryManagementPath);
        }

        apiRequest(
            'POST',
            $supabaseUrl . '/rest/v1/activity_logs',
            array_merge($headers, ['Prefer: return=minimal']),
            [[
                'actor_user_id' => $adminUserId,
                'module_name' => 'document_management',
                'entity_name' => 'document_categories',
                'entity_id' => $categoryId,
                'action_name' => 'update_document_category',
                'old_data' => [
                    'category_name' => (string)($currentCategory['category_name'] ?? ''),
                    'category_key' => (string)($currentCategory['category_key'] ?? ''),
                ],
                'new_data' => [
                    'category_name' => $categoryName,
                    'category_key' => $categoryKey,
                ],
                'ip_address' => clientIp(),
            ]]
        );

        redirectWithState('success', 'Document category updated successfully.', $categoryManagementPath);
    }

    $usageResponse = apiRequest(
        'GET',
        $supabaseUrl . '/rest/v1/documents?select=id&category_id=eq.' . rawurlencode($categoryId) . '&limit=1',
        $headers
    );
    if (isSuccessful($usageResponse) && !empty((array)($usageResponse['data'] ?? []))) {
        redirectWithState('error', 'This category is still used by existing documents and cannot be removed.', $categoryManagementPath);
    }

    $deleteResponse = apiRequest(
        'DELETE',
        $supabaseUrl . '/rest/v1/document_categories?id=eq.' . rawurlencode($categoryId),
        array_merge($headers, ['Prefer: return=minimal'])
    );

    if (!isSuccessful($deleteResponse)) {
        redirectWithState('error', 'Failed to delete document category.', $categoryManagementPath);
    }

    apiRequest(
        'POST',
        $supabaseUrl . '/rest/v1/activity_logs',
        array_merge($headers, ['Prefer: return=minimal']),
        [[
            'actor_user_id' => $adminUserId,
            'module_name' => 'document_management',
            'entity_name' => 'document_categories',
            'entity_id' => $categoryId,
            'action_name' => 'delete_document_category',
            'old_data' => [
                'category_name' => (string)($currentCategory['category_name'] ?? ''),
                'category_key' => (string)($currentCategory['category_key'] ?? ''),
            ],
            'new_data' => null,
            'ip_address' => clientIp(),
        ]]
    );

    redirectWithState('success', 'Document category deleted successfully.', $categoryManagementPath);
}

$categoriesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/document_categories?select=id,category_key,category_name,requires_approval,created_at&order=category_name.asc&limit=1000',
    $headers
);
$categoryRows = isSuccessful($categoriesResponse) ? (array)($categoriesResponse['data'] ?? []) : [];

$usageResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/documents?select=id,category_id,document_status&limit=5000',
    $headers
);
$documentUsageRows = isSuccessful($usageResponse) ? (array)($usageResponse['data'] ?? []) : [];

$usageCountByCategoryId = [];
$activeUsageCountByCategoryId = [];
foreach ($documentUsageRows as $documentUsageRowRaw) {
    $documentUsageRow = (array)$documentUsageRowRaw;
    $categoryId = trim((string)($documentUsageRow['category_id'] ?? ''));
    if ($categoryId === '') {
        continue;
    }

    $usageCountByCategoryId[$categoryId] = (int)($usageCountByCategoryId[$categoryId] ?? 0) + 1;
    if (strtolower(trim((string)($documentUsageRow['document_status'] ?? ''))) !== 'archived') {
        $activeUsageCountByCategoryId[$categoryId] = (int)($activeUsageCountByCategoryId[$categoryId] ?? 0) + 1;
    }
}

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);
$dataLoadError = null;
if (!isSuccessful($categoriesResponse)) {
    $dataLoadError = 'Document categories could not be loaded.';
}
if (!isSuccessful($usageResponse)) {
    $dataLoadError = $dataLoadError !== null
        ? $dataLoadError . ' Document usage counts may be incomplete.'
        : 'Document usage counts could not be loaded.';
}

$categoryRows = array_values(array_filter($categoryRows, static function (array $row) use ($invalidCategoryNames): bool {
    $categoryName = strtolower(trim((string)($row['category_name'] ?? '')));
    return $categoryName !== '' && !in_array($categoryName, $invalidCategoryNames, true);
}));

$totalCategories = count($categoryRows);
$usedCategories = 0;
foreach ($categoryRows as $categoryRow) {
    if ((int)($usageCountByCategoryId[(string)($categoryRow['id'] ?? '')] ?? 0) > 0) {
        $usedCategories++;
    }
}

$pageTitle = 'Document Category Management | Admin';
$activePage = 'document-category-management.php';
$breadcrumbs = ['Document Category Management'];

ob_start();
?>

<div class="mb-6 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Document Category Management</h1>
        <p class="mt-1 text-sm text-slate-500">Add, rename, and remove document upload categories. Categories that are still attached to documents cannot be deleted.</p>
    </div>
    <a href="document-management.php" class="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Back to Document Management
    </a>
</div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($dataLoadError !== null): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars($dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-3">
    <article class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Total Categories</p>
        <p class="mt-1 text-2xl font-bold text-emerald-800"><?= htmlspecialchars((string)$totalCategories, ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Used by Documents</p>
        <p class="mt-1 text-2xl font-bold text-sky-800"><?= htmlspecialchars((string)$usedCategories, ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <article class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Available to Remove</p>
        <p class="mt-1 text-2xl font-bold text-slate-700"><?= htmlspecialchars((string)max(0, $totalCategories - $usedCategories), ENT_QUOTES, 'UTF-8') ?></p>
    </article>
</section>

<section class="mb-6 rounded-2xl border border-slate-200 bg-white">
    <header class="border-b border-slate-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-800">Add Category</h2>
        <p class="mt-1 text-sm text-slate-500">New categories become available in document upload and review workflows.</p>
    </header>
    <form action="document-category-management.php" method="POST" class="px-6 py-5 md:flex md:items-start md:gap-4">
        <input type="hidden" name="form_action" value="create_document_category">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="min-w-0 flex-1">
            <label class="text-sm text-slate-600">Category Name</label>
            <input type="text" name="category_name" maxlength="80" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Examples: Certification, Compliance Letter" required>
            <p class="mt-1 text-xs text-slate-500">Letters, numbers, spaces, and basic punctuation are allowed. Placeholder values such as haugafia are blocked.</p>
        </div>
        <div class="mt-4 md:mt-7 md:flex-none">
            <button type="submit" class="w-full rounded-md bg-slate-900 px-6 py-2 text-sm text-white hover:bg-slate-800 md:w-auto md:min-w-[220px]">Add Category</button>
        </div>
    </form>
</section>

<section class="rounded-2xl border border-slate-200 bg-white">
    <header class="border-b border-slate-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-800">Existing Categories</h2>
        <p class="mt-1 text-sm text-slate-500">Rename categories inline. Delete is available only when no document still references the category.</p>
    </header>
    <div class="overflow-x-auto px-6 py-5">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 text-left">Category</th>
                    <th class="px-4 py-3 text-left">Key</th>
                    <th class="px-4 py-3 text-left">Documents</th>
                    <th class="px-4 py-3 text-left">Created</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if ($categoryRows === []): ?>
                    <tr>
                        <td class="px-4 py-4 text-slate-500" colspan="5">No document categories found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categoryRows as $categoryRow): ?>
                        <?php
                        $categoryId = (string)($categoryRow['id'] ?? '');
                        $totalUsage = (int)($usageCountByCategoryId[$categoryId] ?? 0);
                        $activeUsage = (int)($activeUsageCountByCategoryId[$categoryId] ?? 0);
                        $createdAt = trim((string)($categoryRow['created_at'] ?? ''));
                        ?>
                        <tr>
                            <td class="px-4 py-4 align-top">
                                <form action="document-category-management.php" method="POST" class="space-y-3 md:flex md:items-start md:gap-3 md:space-y-0">
                                    <input type="hidden" name="form_action" value="update_document_category">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="category_id" value="<?= htmlspecialchars($categoryId, ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="min-w-[240px] flex-1">
                                        <input type="text" name="category_name" value="<?= htmlspecialchars((string)($categoryRow['category_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="80" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                                    </div>
                                    <button type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Save</button>
                                </form>
                            </td>
                            <td class="px-4 py-4 align-top font-mono text-xs text-slate-600"><?= htmlspecialchars((string)($categoryRow['category_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-4 align-top text-slate-700">
                                <span class="font-medium"><?= htmlspecialchars((string)$totalUsage, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="block text-xs text-slate-500">Active: <?= htmlspecialchars((string)$activeUsage, ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="px-4 py-4 align-top text-slate-600"><?= htmlspecialchars($createdAt !== '' ? date('M j, Y', strtotime($createdAt)) : '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-4 align-top">
                                <?php if ($totalUsage > 0): ?>
                                    <p class="text-xs text-slate-500">Remove unavailable while documents still use this category.</p>
                                <?php else: ?>
                                    <form action="document-category-management.php" method="POST" onsubmit="return confirm('Delete this document category?');">
                                        <input type="hidden" name="form_action" value="delete_document_category">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="category_id" value="<?= htmlspecialchars($categoryId, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700 hover:bg-rose-100">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
