<section class="bg-white border border-slate-200 rounded-2xl mb-6">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Performance Summary Reports</h2>
            <p class="text-sm text-slate-500 mt-1">Analyze consolidated performance scores and trend indicators by period.</p>
        </div>
        <div class="flex items-center gap-2">
            <button id="praisePerformanceExportBtn" type="button" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Export CSV</button>
            <button id="praisePerformanceExportPdfBtn" type="button" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Export PDF</button>
        </div>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600" for="praisePerformanceSearch">Search Reports</label>
            <input id="praisePerformanceSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by period or top category">
        </div>
        <div class="w-full md:w-1/4">
            <label class="text-sm text-slate-600" for="praisePerformancePeriodFilter">Period</label>
            <select id="praisePerformancePeriodFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Periods</option>
                <?php foreach ($performanceReportRows as $row): ?>
                    <option value="<?= htmlspecialchars((string)$row['period'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$row['period'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="praisePerformanceReportsTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Period</th>
                    <th class="text-left px-4 py-3">Evaluated Employees</th>
                    <th class="text-left px-4 py-3">Average Rating</th>
                    <th class="text-left px-4 py-3">Top Category</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($performanceReportRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="4">No performance summary data found.</td></tr>
                <?php else: ?>
                    <?php foreach ($performanceReportRows as $row): ?>
                        <tr data-praise-performance-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-praise-performance-period="<?= htmlspecialchars((string)$row['period'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['period'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['evaluated_employees'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['average_rating'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['top_category'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="bg-white border border-slate-200 rounded-2xl">
    <header class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Recognition History</h2>
            <p class="text-sm text-slate-500 mt-1">View timeline of previous awardees and historical recognition records.</p>
        </div>
        <div class="flex items-center gap-2">
            <button id="praiseRecognitionExportBtn" type="button" class="px-4 py-2 rounded-md border border-slate-300 text-slate-700 text-sm hover:bg-slate-50">Export CSV</button>
            <button id="praiseRecognitionExportPdfBtn" type="button" class="px-4 py-2 rounded-md bg-slate-900 text-white text-sm hover:bg-slate-800">Export PDF</button>
        </div>
    </header>

    <div class="px-6 pb-3 pt-4 flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
        <div class="w-full md:w-1/2">
            <label class="text-sm text-slate-600" for="praiseRecognitionSearch">Search History</label>
            <input id="praiseRecognitionSearch" type="search" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm" placeholder="Search by cycle, category, or awardee">
        </div>
        <div class="w-full md:w-1/4">
            <label class="text-sm text-slate-600" for="praiseRecognitionCycleFilter">Cycle</label>
            <select id="praiseRecognitionCycleFilter" class="w-full mt-1 border border-slate-300 rounded-md px-3 py-2 text-sm">
                <option value="">All Cycles</option>
                <?php
                $cycleOptions = [];
                foreach ($recognitionHistoryRows as $historyRow) {
                    $cycleOptions[(string)$historyRow['cycle_name']] = true;
                }
                foreach (array_keys($cycleOptions) as $cycleOption):
                ?>
                    <option value="<?= htmlspecialchars($cycleOption, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cycleOption, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="p-6 overflow-x-auto">
        <table id="praiseRecognitionHistoryTable" class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Award Cycle</th>
                    <th class="text-left px-4 py-3">Award Category</th>
                    <th class="text-left px-4 py-3">Awardee</th>
                    <th class="text-left px-4 py-3">Published Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($recognitionHistoryRows)): ?>
                    <tr><td class="px-4 py-3 text-slate-500" colspan="4">No recognition history records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($recognitionHistoryRows as $row): ?>
                        <tr data-praise-recognition-search="<?= htmlspecialchars((string)$row['search_text'], ENT_QUOTES, 'UTF-8') ?>" data-praise-recognition-cycle="<?= htmlspecialchars((string)$row['cycle_name'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['cycle_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['award_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['awardee'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars((string)$row['published_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
