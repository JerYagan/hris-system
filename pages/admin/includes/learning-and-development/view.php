<?php if ($state && $message): ?>
    <div
        id="learningFlashMessage"
        class="hidden"
        data-state="<?= htmlspecialchars((string)$state, ENT_QUOTES, 'UTF-8') ?>"
        data-message="<?= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') ?>"
        aria-hidden="true"
    ></div>
<?php endif; ?>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Reports and Analytics</h2>
        <p class="text-sm text-slate-500 mt-1">Review participation metrics and training completion insights.</p>
    </header>

    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <article class="rounded-xl border border-slate-200 p-4 bg-emerald-50">
            <p class="text-xs uppercase text-emerald-700">Completed Trainings</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$completedTrainings ?></p>
            <p class="text-xs text-slate-600 mt-1">For current quarter</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-slate-50">
            <p class="text-xs uppercase text-slate-600">Average Attendance</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$averageAttendance ?>%</p>
            <p class="text-xs text-slate-600 mt-1">Across all sessions</p>
        </article>
        <article class="rounded-xl border border-slate-200 p-4 bg-amber-50">
            <p class="text-xs uppercase text-amber-700">Pending Validations</p>
            <p class="text-2xl font-bold text-slate-800 mt-2"><?= (int)$pendingValidations ?></p>
            <p class="text-xs text-slate-600 mt-1">Training records awaiting admin review</p>
        </article>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Training Activity History</h2>
        <p class="text-sm text-slate-500 mt-1">Recent admin-side training creation and attendance updates with actor and timestamp.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search History</label>
            <input id="learningHistorySearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by action, actor, target, or details">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Action Filter</label>
            <select id="learningHistoryActionFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Actions</option>
                <?php foreach ($historyActionFilters as $actionFilter): ?>
                    <option value="<?= htmlspecialchars((string)$actionFilter, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$actionFilter, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="learningHistoryTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Timestamp</th>
                    <th class="text-left px-4 py-3">Action</th>
                    <th class="text-left px-4 py-3">Target</th>
                    <th class="text-left px-4 py-3">Details</th>
                    <th class="text-left px-4 py-3">Actor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($historyRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No activity history found for Learning and Development.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historyRows as $row): ?>
                        <tr data-learning-history-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-learning-history-action="<?= htmlspecialchars((string)$row['action_label'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['timestamp_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['action_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['target_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['details_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['actor_label'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-6 pt-1 flex items-center justify-between gap-3 text-xs text-slate-600">
        <p id="learningHistoryPageInfo">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button id="learningHistoryPrevPage" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
            <button id="learningHistoryNextPage" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Employee Training Records</h2>
        <p class="text-sm text-slate-500 mt-1">Track completed trainings, certifications, and development credits per employee.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Records</label>
            <input id="learningRecordsSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by employee or training title">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="learningRecordsStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="Completed">Completed</option>
                <option value="In Progress">In Progress</option>
                <option value="Needs Review">Needs Review</option>
                <option value="Dropped">Dropped</option>
            </select>
        </div>
        <div class="w-full md:w-40">
            <label class="text-sm text-slate-600">Entries</label>
            <select id="learningRecordsPageSize" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="10" selected>10</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="learningTrainingRecordsTable" data-simple-table="true" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Latest Training</th>
                    <th class="text-left px-4 py-3">Completion Date</th>
                    <th class="text-left px-4 py-3">Hours Earned</th>
                    <th class="text-left px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($trainingRecordRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No employee training records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trainingRecordRows as $row): ?>
                        <tr data-learning-record-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-learning-record-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['training'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['completion_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['hours_earned'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-6 pt-1 flex items-center justify-between gap-3 text-xs text-slate-600">
        <p id="learningRecordsPageInfo">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button id="learningRecordsPrevPage" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
            <button id="learningRecordsNextPage" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Add New Training</h2>
            <p class="text-sm text-slate-500 mt-1">Create training sessions with category, venue, schedule, and participant in-app/email notifications.</p>
            <?php if (isset($trainingSchemaHasExtendedFields) && $trainingSchemaHasExtendedFields === false): ?>
                <p class="text-xs text-amber-700 mt-1">Current database schema does not yet include dedicated columns for training type/category/venue/time. Values are handled using compatible fallback storage.</p>
            <?php endif; ?>
        </div>
        <button type="button" data-modal-open="learningScheduleModal" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Add New Training</button>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Schedules</label>
            <input id="learningScheduleSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by type, category, provider, or mode">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Status Filter</label>
            <select id="learningScheduleStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
                <option value="Needs Review">Needs Review</option>
            </select>
        </div>
        <div class="w-full md:w-40">
            <label class="text-sm text-slate-600">Entries</label>
            <select id="learningSchedulePageSize" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="10" selected>10</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="learningScheduleTable" data-simple-table="true" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Category</th>
                    <th class="text-left px-4 py-3">Schedule</th>
                    <th class="text-left px-4 py-3">Provider</th>
                    <th class="text-left px-4 py-3">Mode</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($trainingScheduleRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="7">No training schedules found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trainingScheduleRows as $row): ?>
                        <tr data-learning-schedule-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-learning-schedule-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['training_type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['training_category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['date_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['provider'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['mode'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-learning-training-view
                                    data-program-code="<?= htmlspecialchars((string)$row['program_code'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-title="<?= htmlspecialchars((string)$row['title'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-training-type="<?= htmlspecialchars((string)$row['training_type'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-training-category="<?= htmlspecialchars((string)$row['training_category'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-schedule-date="<?= htmlspecialchars((string)$row['schedule_date'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-schedule-time="<?= htmlspecialchars((string)$row['schedule_time'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-end-date="<?= htmlspecialchars((string)$row['end_date'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-provider="<?= htmlspecialchars((string)$row['provider'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-venue="<?= htmlspecialchars((string)$row['venue'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-mode="<?= htmlspecialchars((string)$row['mode'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-participants-count="<?= htmlspecialchars((string)$row['participants'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-participants-list="<?= htmlspecialchars((string)$row['participants_list'], ENT_QUOTES, 'UTF-8') ?>"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"
                                >
                                    <span class="material-symbols-outlined text-[15px]">visibility</span>View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-6 pt-1 flex items-center justify-between gap-3 text-xs text-slate-600">
        <p id="learningSchedulePageInfo">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button id="learningSchedulePrevPage" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
            <button id="learningScheduleNextPage" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
        </div>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800">Attendance Tracker</h2>
        <p class="text-sm text-slate-500 mt-1">Record and monitor employee attendance per training session.</p>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600">Search Attendance</label>
            <input id="learningAttendanceSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by training, employee, or date">
        </div>
        <div class="w-full md:w-56">
            <label class="text-sm text-slate-600">Attendance Filter</label>
            <select id="learningAttendanceStatusFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Statuses</option>
                <option value="Present">Present</option>
                <option value="Enrolled">Enrolled</option>
                <option value="Absent">Absent</option>
                <option value="Dropped">Dropped</option>
            </select>
        </div>
        <div class="w-full md:w-40">
            <label class="text-sm text-slate-600">Entries</label>
            <select id="learningAttendancePageSize" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="10" selected>10</option>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="learningAttendanceTable" data-simple-table="true" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Training</th>
                    <th class="text-left px-4 py-3">Employee</th>
                    <th class="text-left px-4 py-3">Date</th>
                    <th class="text-left px-4 py-3">Attendance</th>
                    <th class="text-left px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($attendanceRows)): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-500" colspan="5">No attendance records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attendanceRows as $row): ?>
                        <tr data-learning-attendance-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-learning-attendance-status="<?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['training'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['employee'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full <?= htmlspecialchars((string)$row['status_class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['status_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="px-4 py-3"><button type="button" data-learning-attendance-update data-enrollment-id="<?= htmlspecialchars((string)$row['enrollment_id'], ENT_QUOTES, 'UTF-8') ?>" data-training-title="<?= htmlspecialchars((string)$row['training'], ENT_QUOTES, 'UTF-8') ?>" data-employee-name="<?= htmlspecialchars((string)$row['employee'], ENT_QUOTES, 'UTF-8') ?>" data-current-status="<?= htmlspecialchars((string)$row['status_raw'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[15px]">edit_square</span>Update</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 pb-6 pt-1 flex items-center justify-between gap-3 text-xs text-slate-600">
        <p id="learningAttendancePageInfo">Page 1 of 1</p>
        <div class="flex items-center gap-2">
            <button id="learningAttendancePrevPage" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
            <button id="learningAttendanceNextPage" type="button" class="px-2.5 py-1.5 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Next</button>
        </div>
    </div>
</section>

<div id="learningScheduleModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="learningScheduleModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl max-h-[90vh] bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="text-lg font-semibold text-slate-800">Add New Training</h3>
                <button type="button" data-modal-close="learningScheduleModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="learning-and-development.php" method="POST" class="flex-1 overflow-y-auto">
                <input type="hidden" name="form_action" value="create_training">
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="text-slate-600">Training Type</label>
                        <input name="training_type" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="e.g. Technical, Compliance" required>
                    </div>
                    <div>
                        <label class="text-slate-600">Training Category</label>
                        <input name="training_category" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="e.g. Leadership, Safety" required>
                    </div>
                    <div>
                        <label class="text-slate-600">Schedule Date</label>
                        <input id="learningScheduleDateInput" name="schedule_date" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="YYYY-MM-DD" autocomplete="off" required>
                    </div>
                    <div>
                        <label class="text-slate-600">Schedule Time</label>
                        <input id="learningScheduleTimeInput" name="schedule_time" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="HH:MM" autocomplete="off" required>
                    </div>
                    <div>
                        <label class="text-slate-600">Provider</label>
                        <input name="provider" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Training provider" required>
                    </div>
                    <div>
                        <label class="text-slate-600">Venue</label>
                        <input name="venue" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" placeholder="Venue or platform" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-slate-600">Training Mode</label>
                        <select name="mode" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                            <option value="onsite">Onsite</option>
                            <option value="online">Online</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-slate-600">Enroll Employees</label>
                        <input
                            id="learningParticipantSearchInput"
                            type="search"
                            class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2"
                            placeholder="Search employee name or department"
                        >
                        <div id="learningParticipantList" class="mt-2 max-h-56 overflow-y-auto rounded-md border border-slate-300 bg-white divide-y divide-slate-100">
                            <?php foreach ($employeeOptions as $employeeOption): ?>
                                <label
                                    data-learning-participant-row
                                    data-search="<?= htmlspecialchars(strtolower((string)$employeeOption['name'] . ' ' . (string)$employeeOption['department']), ENT_QUOTES, 'UTF-8') ?>"
                                    class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        name="participant_ids[]"
                                        value="<?= htmlspecialchars((string)$employeeOption['person_id'], ENT_QUOTES, 'UTF-8') ?>"
                                        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                                    >
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-800 truncate"><?= htmlspecialchars((string)$employeeOption['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="text-xs text-slate-500 truncate"><?= htmlspecialchars((string)$employeeOption['department'], ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p id="learningParticipantEmptyHint" class="mt-1 text-xs text-amber-700 hidden">No employees match your search.</p>
                        <?php if (empty($employeeOptions)): ?>
                            <p class="mt-1 text-xs text-amber-700">No employees are currently available to display. Please verify employee records and refresh.</p>
                        <?php else: ?>
                            <p class="mt-1 text-xs text-slate-500">Selected employees will be enrolled immediately and receive in-app notifications. Employees can be enrolled even if they are currently attending other trainings.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="sticky bottom-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end gap-3">
                    <button type="button" data-modal-close="learningScheduleModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Training</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="learningTrainingDetailsModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="learningTrainingDetailsModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl max-h-[90vh] bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between sticky top-0 bg-white z-10">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Training Details</h3>
                    <p class="text-xs text-slate-500 mt-0.5">View complete schedule, provider, and participant information.</p>
                </div>
                <button type="button" data-modal-close="learningTrainingDetailsModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <div class="p-6 space-y-4 text-sm overflow-y-auto">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p id="learningDetailsTitle" class="text-base font-semibold text-slate-800">-</p>
                            <p id="learningDetailsProgramCode" class="text-xs text-slate-500 mt-1">-</p>
                        </div>
                        <span id="learningDetailsStatusPill" class="px-2.5 py-1 text-xs rounded-full bg-slate-100 text-slate-700">-</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="rounded-lg border border-slate-200 p-3 bg-white">
                        <p class="text-xs uppercase text-slate-500">Training Type</p>
                        <p id="learningDetailsType" class="text-slate-800 mt-1">-</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 bg-white">
                        <p class="text-xs uppercase text-slate-500">Training Category</p>
                        <p id="learningDetailsCategory" class="text-slate-800 mt-1">-</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 bg-white">
                        <p class="text-xs uppercase text-slate-500">Schedule Date</p>
                        <p id="learningDetailsDate" class="text-slate-800 mt-1">-</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 bg-white">
                        <p class="text-xs uppercase text-slate-500">Schedule Time</p>
                        <p id="learningDetailsTime" class="text-slate-800 mt-1">-</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 bg-white">
                        <p class="text-xs uppercase text-slate-500">Provider</p>
                        <p id="learningDetailsProvider" class="text-slate-800 mt-1">-</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 bg-white">
                        <p class="text-xs uppercase text-slate-500">Venue</p>
                        <p id="learningDetailsVenue" class="text-slate-800 mt-1">-</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 bg-white">
                        <p class="text-xs uppercase text-slate-500">Mode</p>
                        <p id="learningDetailsMode" class="text-slate-800 mt-1">-</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 bg-white">
                        <p class="text-xs uppercase text-slate-500">Participants</p>
                        <p id="learningDetailsParticipantsCount" class="text-slate-800 mt-1">0</p>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 p-3 bg-white">
                    <p class="text-xs uppercase text-slate-500">Participant List</p>
                    <div id="learningDetailsParticipantsList" class="mt-2 flex flex-wrap gap-2">
                        <span class="px-2 py-1 text-xs rounded bg-slate-100 text-slate-700">-</span>
                    </div>
                </div>
            </div>
            <div class="sticky bottom-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end">
                <button type="button" data-modal-close="learningTrainingDetailsModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="learningAttendanceModal" data-modal class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="learningAttendanceModal"></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl border border-slate-200 shadow-xl">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800">Update Attendance</h3>
                <button type="button" data-modal-close="learningAttendanceModal" class="text-slate-500 hover:text-slate-700">✕</button>
            </div>
            <form action="learning-and-development.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <input type="hidden" name="form_action" value="update_training_attendance">
                <input id="learningEnrollmentId" type="hidden" name="enrollment_id" value="">
                <div class="md:col-span-2">
                    <label class="text-slate-600">Training</label>
                    <input id="learningAttendanceTraining" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div class="md:col-span-2">
                    <label class="text-slate-600">Employee</label>
                    <input id="learningAttendanceEmployee" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">Current Status</label>
                    <input id="learningAttendanceCurrentStatus" type="text" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 bg-slate-50" readonly>
                </div>
                <div>
                    <label class="text-slate-600">New Status</label>
                    <select name="enrollment_status" id="learningAttendanceNewStatus" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2" required>
                        <option value="enrolled">Enrolled</option>
                        <option value="completed">Present</option>
                        <option value="failed">Absent</option>
                        <option value="dropped">Dropped</option>
                    </select>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" data-modal-close="learningAttendanceModal" class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-md bg-slate-900 text-white hover:bg-slate-800">Save Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>
