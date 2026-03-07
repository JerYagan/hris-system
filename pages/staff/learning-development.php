<?php
require_once __DIR__ . '/includes/learning-development/bootstrap.php';
require_once __DIR__ . '/includes/learning-development/actions.php';
require_once __DIR__ . '/includes/learning-development/data.php';

$pageTitle = 'Learning and Development | Staff';
$activePage = 'learning-development.php';
$breadcrumbs = ['Learning and Development'];

$state = cleanText($_GET['state'] ?? null);
$message = cleanText($_GET['message'] ?? null);

$isManager = in_array(strtolower((string)$staffRoleKey), ['staff', 'hr_officer', 'supervisor', 'admin'], true);

$statusPillClass = static function (string $status): string {
    $normalized = strtolower(trim($status));
    if ($normalized === 'published' || $normalized === 'approved' || $normalized === 'attended' || $normalized === 'present') {
        return 'bg-emerald-100 text-emerald-800';
    }
    if ($normalized === 'pending' || $normalized === 'draft' || $normalized === 'scheduled' || $normalized === 'enrolled') {
        return 'bg-amber-100 text-amber-800';
    }
    if ($normalized === 'archived' || $normalized === 'rejected' || $normalized === 'missed' || $normalized === 'absent' || $normalized === 'dropped') {
        return 'bg-rose-100 text-rose-800';
    }

    return 'bg-slate-100 text-slate-700';
};

ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Learning and Development</h1>
    <p class="text-sm text-gray-500">Manage reports, employee training records, schedules, and attendance with office-scoped controls.</p>
</div>

<?php if ($state && $message): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $state === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($dataLoadError)): ?>
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <?= htmlspecialchars((string)$dataLoadError, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Reports and Analytics</h2>
        <p class="text-sm text-slate-500 mt-1">Training effectiveness indicators and completion metrics for your office scope.</p>
    </header>
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Trainings</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($courseCounts['total'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Published Trainings</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($courseCounts['published'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Enrollments</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)($enrollmentCounts['total'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Training Schedule</h2>
            <p class="text-sm text-slate-500 mt-1">View admin-created training schedules and manage attendance updates within office scope.</p>
        </div>
    </header>

    <div class="p-6 pt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label for="staffLearningCourseSearch" class="text-slate-600">Search Schedule</label>
            <input id="staffLearningCourseSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search by title, provider, venue, mode, or status">
        </div>
        <div>
            <label for="staffLearningCourseStatusFilter" class="text-slate-600">Status</label>
            <select id="staffLearningCourseStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="">All Statuses</option>
                <?php foreach ($courseStatusFilters as $courseStatus): ?>
                    <option value="<?= htmlspecialchars((string)$courseStatus, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$courseStatus, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="px-6 pb-6 overflow-x-auto">
        <table id="staffLearningCoursesTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Title</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Schedule</th>
                    <th class="text-left px-4 py-3">Provider</th>
                    <th class="text-left px-4 py-3">Location</th>
                    <th class="text-left px-4 py-3">Participants</th>
                    <th class="text-left px-4 py-3">Mode</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($courseRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="10">No training schedule records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($courseRows as $courseRow): ?>
                        <?php
                        $courseId = (string)$courseRow['id'];
                        $courseDetail = (array)($courseById[$courseId] ?? []);
                        $courseViewPayload = [
                            'title' => (string)$courseRow['title'],
                            'status_label' => (string)$courseRow['status_label'],
                            'provider' => (string)($courseDetail['provider'] ?? ''),
                            'training_type' => (string)($courseDetail['training_type'] ?? ''),
                            'training_category' => (string)($courseDetail['training_category'] ?? ''),
                            'venue' => (string)($courseDetail['venue'] ?? ''),
                            'mode' => (string)($courseDetail['mode'] ?? ''),
                            'schedule' => (string)($courseDetail['schedule_label'] ?? '-'),
                            'enrollees' => (array)($courseEnrolleesByCourseId[$courseId] ?? []),
                        ];
                        ?>
                        <tr data-learning-course-row data-learning-course-search="<?= htmlspecialchars((string)$courseRow['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-learning-course-status="<?= htmlspecialchars((string)$courseRow['status_label'], ENT_QUOTES, 'UTF-8') ?>" class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800"><?= htmlspecialchars((string)$courseRow['title'], ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3\"><?= htmlspecialchars((string)$courseRow['training_type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3\"><?= htmlspecialchars((string)$courseRow['category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3\"><?= htmlspecialchars((string)$courseRow['schedule'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3\"><?= htmlspecialchars((string)$courseRow['provider'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3\"><?= htmlspecialchars((string)$courseRow['location'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3\"><?= htmlspecialchars((string)$courseRow['participants'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3\"><?= htmlspecialchars((string)$courseRow['mode'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center justify-center min-w-[96px] px-2.5 py-1 text-xs rounded-full <?= htmlspecialchars($statusPillClass((string)$courseRow['status_raw']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$courseRow['status_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-course-view-open
                                    data-course-view-payload="<?= htmlspecialchars((string)json_encode($courseViewPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs rounded-md border border-slate-300 hover:bg-slate-50"
                                >
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="staffLearningCourseFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="10">No courses match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-6 flex items-center justify-between gap-3 text-sm">
        <p id="staffLearningCoursePaginationSummary" class="text-slate-500">Showing 0 of 0</p>
        <div class="flex items-center gap-2">
            <button id="staffLearningCoursePrev" type="button" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 disabled:opacity-50" disabled>Previous</button>
            <span id="staffLearningCoursePage" class="text-slate-600 min-w-[88px] text-center">Page 1 of 1</span>
            <button id="staffLearningCourseNext" type="button" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 disabled:opacity-50" disabled>Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Employee Training Records</h2>
            <p class="text-sm text-slate-500 mt-1">Monitor employee-linked training history and update attendance outcomes.</p>
        </div>
    </header>

    <div class="p-6 pt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <label for="staffLearningEnrollmentSearch" class="text-slate-600">Search Enrollments</label>
            <input id="staffLearningEnrollmentSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Search by employee, course, category, date, provider, location, or status">
        </div>
        <div>
            <label for="staffLearningEnrollmentStatusFilter" class="text-slate-600">Status</label>
            <select id="staffLearningEnrollmentStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                <option value="">All Statuses</option>
                <?php foreach ($enrollmentStatusFilters as $enrollmentStatus): ?>
                    <option value="<?= htmlspecialchars((string)$enrollmentStatus, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$enrollmentStatus, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="px-6 pb-6 overflow-x-auto">
        <table id="staffLearningEnrollmentsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee Name</th>
                    <th class="text-left px-4 py-3">Training Title</th>
                    <th class="text-left px-4 py-3">Training Category</th>
                    <th class="text-left px-4 py-3">Schedule</th>
                    <th class="text-left px-4 py-3">Provider</th>
                    <th class="text-left px-4 py-3">Location</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($enrollmentRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="8">No employee training records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($enrollmentRows as $enrollmentRow): ?>
                        <tr data-learning-enrollment-row data-learning-enrollment-search="<?= htmlspecialchars((string)$enrollmentRow['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-learning-enrollment-status="<?= htmlspecialchars((string)$enrollmentRow['attendance_status'], ENT_QUOTES, 'UTF-8') ?>" class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800"><?= htmlspecialchars((string)$enrollmentRow['employee_name'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$enrollmentRow['department'], ENT_QUOTES, 'UTF-8') ?></p>
                            </td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$enrollmentRow['course_title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3\"><?= htmlspecialchars((string)$enrollmentRow['course_category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3\"><?= htmlspecialchars((string)$enrollmentRow['course_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3\"><?= htmlspecialchars((string)$enrollmentRow['provider'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3\"><?= htmlspecialchars((string)$enrollmentRow['location'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center justify-center min-w-[96px] px-2.5 py-1 text-xs font-medium rounded-full <?= htmlspecialchars($statusPillClass((string)$enrollmentRow['attendance_status']), ENT_QUOTES, 'UTF-8') ?>\"><?= htmlspecialchars((string)$enrollmentRow['attendance_status'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($isManager): ?>
                                    <button
                                        type="button"
                                        data-attendance-update-open
                                        data-attendance-enrollment-id="<?= htmlspecialchars((string)$enrollmentRow['id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-attendance-employee="<?= htmlspecialchars((string)$enrollmentRow['employee_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-attendance-course="<?= htmlspecialchars((string)$enrollmentRow['course_title'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-attendance-current-status="<?= htmlspecialchars((string)$enrollmentRow['attendance_status'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-attendance-notes="<?= htmlspecialchars((string)$enrollmentRow['notes'], ENT_QUOTES, 'UTF-8') ?>"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs rounded-md border border-slate-300 hover:bg-slate-50"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                        Update
                                    </button>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500">View only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="staffLearningEnrollmentFilterEmpty" class="hidden">
                    <td class="px-4 py-3 text-slate-500" colspan="8">No employee training records match your search/filter criteria.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-6 flex items-center justify-between gap-3 text-sm">
        <p id="staffLearningEnrollmentPaginationSummary" class="text-slate-500">Showing 0 of 0</p>
        <div class="flex items-center gap-2">
            <button id="staffLearningEnrollmentPrev" type="button" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 disabled:opacity-50" disabled>Previous</button>
            <span id="staffLearningEnrollmentPage" class="text-slate-600 min-w-[88px] text-center">Page 1 of 1</span>
            <button id="staffLearningEnrollmentNext" type="button" class="px-3 py-1.5 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 disabled:opacity-50" disabled>Next</button>
        </div>
    </div>
</section>

<div id="staffLearningCourseViewModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="staffLearningCourseViewModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Training Details</h3>
                <button type="button" data-modal-close="staffLearningCourseViewModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <div class="p-6 text-sm space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <p><span class="text-slate-500">Training:</span> <span id="staffLearningCourseViewTitle" class="font-medium text-slate-800"></span></p>
                    <p><span class="text-slate-500">Status:</span> <span id="staffLearningCourseViewStatus" class="font-medium text-slate-800"></span></p>
                    <p><span class="text-slate-500">Provider:</span> <span id="staffLearningCourseViewProvider" class="font-medium text-slate-800"></span></p>
                    <p><span class="text-slate-500">Type:</span> <span id="staffLearningCourseViewType" class="font-medium text-slate-800"></span></p>
                    <p><span class="text-slate-500">Category:</span> <span id="staffLearningCourseViewCategory" class="font-medium text-slate-800"></span></p>
                    <p><span class="text-slate-500">Mode:</span> <span id="staffLearningCourseViewMode" class="font-medium text-slate-800"></span></p>
                    <p class="md:col-span-2"><span class="text-slate-500">Schedule:</span> <span id="staffLearningCourseViewSchedule" class="font-medium text-slate-800"></span></p>
                    <p class="md:col-span-2"><span class="text-slate-500">Venue:</span> <span id="staffLearningCourseViewVenue" class="font-medium text-slate-800"></span></p>
                </div>

                <div>
                    <h4 class="text-base font-semibold text-slate-800 mb-2">Enrolled Employees</h4>
                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="text-left px-4 py-2">Employee</th>
                                    <th class="text-left px-4 py-2">Department</th>
                                    <th class="text-left px-4 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody id="staffLearningCourseViewEnrolleesBody" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isManager): ?>
<div id="staffLearningCourseCreateModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="staffLearningCourseCreateModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Create Learning Course</h3>
                <button type="button" data-modal-close="staffLearningCourseCreateModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="staffLearningCourseCreateForm" action="learning-development.php" method="POST" class="p-6 space-y-4 text-sm">
                <input type="hidden" name="form_action" value="create_learning_course">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div>
                    <label class="text-slate-600">Course Title</label>
                    <input name="course_title" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                </div>
                <div>
                    <label class="text-slate-600">Description</label>
                    <textarea name="course_description" rows="4" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Describe learning objectives and expected outcomes"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-slate-600">Initial Status</label>
                        <select name="course_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-600">Mode</label>
                        <select name="course_mode" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                            <option value="onsite">Onsite</option>
                            <option value="online">Online</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-slate-600">Provider</label>
                        <input name="course_provider" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Internal HR Team">
                    </div>
                    <div>
                        <label class="text-slate-600">Venue</label>
                        <input name="course_venue" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Training Room A / Online">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-slate-600">Start Date</label>
                        <input name="course_start_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="text-slate-600">End Date</label>
                        <input name="course_end_date" type="date" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="staffLearningCourseCreateModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" id="staffLearningCourseCreateSubmit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Create Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="staffLearningAttendanceUpdateModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="staffLearningAttendanceUpdateModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-3 sm:p-4">
        <div class="w-full max-w-lg bg-white rounded-2xl border border-slate-200 shadow-xl max-h-[88vh] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Update Attendance Status</h3>
                <button type="button" data-modal-close="staffLearningAttendanceUpdateModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form id="staffLearningAttendanceUpdateForm" action="learning-development.php" method="POST" class="flex flex-col min-h-0">
                <input type="hidden" name="form_action" value="update_training_attendance">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="staffLearningAttendanceUpdateId" name="enrollment_id" value="">

                <div class="p-5 space-y-3 text-sm overflow-y-auto min-h-0">
                    <div>
                        <label class="text-slate-600">Employee</label>
                        <input id="staffLearningAttendanceUpdateEmployee" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                    </div>
                    <div>
                        <label class="text-slate-600">Course</label>
                        <input id="staffLearningAttendanceUpdateCourse" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-slate-600">Current Status</label>
                            <input id="staffLearningAttendanceUpdateCurrent" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                        </div>
                        <div>
                            <label class="text-slate-600">New Status</label>
                            <select id="staffLearningAttendanceUpdateNew" name="attendance_status" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                                <option value="enrolled">Enrolled</option>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="dropped">Dropped</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-slate-600">Notes</label>
                        <textarea id="staffLearningAttendanceUpdateNotes" rows="2" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly></textarea>
                    </div>
                    <div>
                        <label class="text-slate-600">Update Note</label>
                        <textarea name="attendance_note" rows="2" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Add attendance context"></textarea>
                    </div>
                </div>

                <div class="px-5 py-3 border-t border-slate-200 bg-white sticky bottom-0 flex justify-end gap-3">
                    <button type="button" data-modal-close="staffLearningAttendanceUpdateModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" id="staffLearningAttendanceUpdateSubmit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Update Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<script src="../../assets/js/staff/learning-development/index.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
