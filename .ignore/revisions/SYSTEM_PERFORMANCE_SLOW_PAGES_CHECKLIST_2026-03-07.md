# System Performance Slow Pages Checklist

Date: 2026-03-07

## Scope

This checklist prioritizes slow pages across the HRIS based on:

- Approximate server-side `apiRequest()` count per page/data loader
- Presence of large `limit=5000+` or `limit=10000+` Supabase reads
- Whether the cost is paid on every page load through shared layout/topnav files
- Whether the page is a common landing page or high-traffic workflow page

## Priority Legend

- **P0 - Shared system slowdown**: affects multiple pages or all pages for a role
- **P1 - Critical page slowdown**: among the slowest pages, high traffic, or both
- **P2 - High page slowdown**: clearly heavy, but narrower impact
- **P3 - Medium page slowdown**: noticeable, but not first-wave work

---

## P0 - Shared System Slowdowns

### [x] Cache admin topnav/profile/notification queries
- **Impact**: all admin pages
- **Current issue**: admin topnav performs 4 blocking requests on every page load.
- **Status**: Completed on 2026-03-07
- **Files**:
  - [pages/admin/includes/topnav.php](pages/admin/includes/topnav.php)
- **What to change**:
  - Add session cache similar to staff/employee topnav caches
  - Cache display name, role, unread count, and preview list for 30-60 seconds
  - Refresh cache only after notification/profile-changing actions

### [x] Cache applicant topnav/profile/notification queries
- **Impact**: all applicant pages
- **Current issue**: applicant topnav performs 4 blocking requests on every page load.
- **Status**: Applicant topnav cache was already present before this pass; verified on 2026-03-07
- **Files**:
  - [pages/applicant/includes/topnav.php](pages/applicant/includes/topnav.php)
- **What to change**:
  - Add session cache for applicant name, profile photo, unread count, and preview
  - Avoid re-querying on every navigation

### [ ] Reduce shared layout/topnav notification overhead
- **Impact**: all staff and employee pages, plus shared runtime overhead
- **Current issue**:
  - Staff topnav still performs 3 requests on cache miss
  - Employee topnav performs 4 requests on cache miss
  - Notification script polls and may load Supabase client dynamically
- **Files**:
  - [pages/staff/includes/layout.php](pages/staff/includes/layout.php)
  - [pages/employee/includes/layout.php](pages/employee/includes/layout.php)
  - [assets/js/shared/topnav-notifications.js](assets/js/shared/topnav-notifications.js)
- **What to change**:
  - Keep unread count in cache longer than preview payload
  - Reduce polling fallback frequency if realtime is unavailable
  - Only initialize realtime/polling on pages that show topnav notifications

### [ ] Stop loading heavy CDN assets globally when not needed
- **Impact**: all roles
- **Current issue**: Tailwind CDN, icon fonts, Chart.js, Flatpickr, SweetAlert, and DataTables are loaded broadly in shared heads.
- **Files**:
  - [pages/admin/includes/head.php](pages/admin/includes/head.php)
  - [pages/staff/includes/head.php](pages/staff/includes/head.php)
  - [pages/employee/includes/head.php](pages/employee/includes/head.php)
  - [pages/applicant/includes/head.php](pages/applicant/includes/head.php)
- **What to change**:
  - Replace Tailwind CDN with compiled CSS for production
  - Load Chart.js only on pages with charts
  - Load Flatpickr/DataTables only on pages that use them
  - Minimize Google Fonts/icon requests where possible

### [ ] Remove or split legacy global scripts for employee/applicant layouts
- **Impact**: many employee/applicant pages
- **Current issue**: global scripts are loaded for every page and still execute page-wide DOM/chart logic.
- **Audit status**: Re-audited on 2026-03-07
- **Files**:
  - [pages/employee/includes/layout.php](pages/employee/includes/layout.php)
  - [pages/applicant/includes/layout.php](pages/applicant/includes/layout.php)
  - [assets/js/script.js](assets/js/script.js)
- **What to change**:
  - Convert to per-page modules
  - Guard all chart/export/init logic behind page checks
  - Remove unused listeners from pages that do not need them

#### Remaining legacy global script usage audit

**Direct loaders still present**
- Staff area via shared layout:
  - [pages/staff/includes/layout.php](pages/staff/includes/layout.php)
- Employee area via shared layout:
  - [pages/employee/includes/layout.php](pages/employee/includes/layout.php)
- Applicant area via shared layout:
  - [pages/applicant/includes/layout.php](pages/applicant/includes/layout.php)
- Auth area head include:
  - [pages/auth/includes/head.php](pages/auth/includes/head.php)

**Admin indirect legacy fallback still present**
- Bootstrap still loads legacy admin script when a page is not mapped:
  - [assets/js/bootstrap.js](assets/js/bootstrap.js)
  - [pages/admin/js/script.js](pages/admin/js/script.js)

**Completed admin localization in this pass**
- [pages/admin/user-management.php](pages/admin/user-management.php) is now mapped through [assets/js/bootstrap.js](assets/js/bootstrap.js) to [assets/js/admin/user-management/index.js](assets/js/admin/user-management/index.js) as of 2026-03-07.
- [pages/admin/report-analytics.php](pages/admin/report-analytics.php) is now mapped through [assets/js/bootstrap.js](assets/js/bootstrap.js) to [assets/js/admin/report-analytics/index.js](assets/js/admin/report-analytics/index.js) as of 2026-03-07.

**Admin pages still falling back to legacy admin script**
- [pages/admin/applicant-document.php](pages/admin/applicant-document.php)
- [pages/admin/applicant-profile.php](pages/admin/applicant-profile.php)
- [pages/admin/applicants.php](pages/admin/applicants.php)
- [pages/admin/applicant-tracking.php](pages/admin/applicant-tracking.php)
- [pages/admin/create-announcement.php](pages/admin/create-announcement.php)
- [pages/admin/employee-profile.php](pages/admin/employee-profile.php)
- [pages/admin/notifications.php](pages/admin/notifications.php)
- [pages/admin/personal-information.php](pages/admin/personal-information.php)
- [pages/admin/praise.php](pages/admin/praise.php)
- [pages/admin/praise-awards-recognition.php](pages/admin/praise-awards-recognition.php)
- [pages/admin/praise-employee-evaluation.php](pages/admin/praise-employee-evaluation.php)
- [pages/admin/praise-reports-analytics.php](pages/admin/praise-reports-analytics.php)
- [pages/admin/profile.php](pages/admin/profile.php)
- [pages/admin/settings.php](pages/admin/settings.php)
- [pages/admin/timekeeping.php](pages/admin/timekeeping.php)

**Pages already localized but still also affected by shared global script include**
- Employee localized modules are present, but the shared global script still loads through [pages/employee/includes/layout.php](pages/employee/includes/layout.php)
- Staff localized modules are present on several pages, but the shared global script still loads through [pages/staff/includes/layout.php](pages/staff/includes/layout.php)
- Applicant pages still rely on the shared global script through [pages/applicant/includes/layout.php](pages/applicant/includes/layout.php)

### [x] Add request timing instrumentation to all Supabase calls
- **Impact**: system-wide observability
- **Current issue**: slow queries are inferred from code shape, not yet timed live.
- **Status**: Completed on 2026-03-07
- **Files**:
  - [pages/admin/includes/lib/common.php](pages/admin/includes/lib/common.php)
  - [pages/staff/includes/lib/common.php](pages/staff/includes/lib/common.php)
  - [pages/employee/includes/lib/common.php](pages/employee/includes/lib/common.php)
  - [pages/applicant/includes/lib/common.php](pages/applicant/includes/lib/common.php)
- **What to change**:
  - Log URL, elapsed time, HTTP status, and row count
  - Capture top 10 slowest requests per role/page
  - Use logs to validate this priority list before deeper refactors

---

## P1 - Critical Slow Pages

### [ ] Admin Personal Information
- **Estimated severity**: P1
- **Approximate backend cost**: 21 requests
- **Likely reason**: loads many wide datasets with large row limits across people, employment, roles, addresses, government IDs, family, education, work history, and review logs.
- **Entry page**:
  - [pages/admin/personal-information.php](pages/admin/personal-information.php)
- **Main data file**:
  - [pages/admin/includes/personal-information/data.php](pages/admin/includes/personal-information/data.php)
- **Checklist**:
  - [ ] Paginate list view instead of loading full related datasets
  - [ ] Load person detail relations only when opening a record
  - [ ] Move audit/history panels to async fetches
  - [ ] Replace broad preload with targeted modal/detail endpoints
  - [ ] Add a loading state to avoid rendering empty tables while waiting for data

### [ ] Admin Payroll Management
- **Estimated severity**: P1
- **Approximate backend cost**: 17 requests
- **Likely reason**: loads periods, runs, items, payslips, compensations, approvals, breakdown logs, and related activity with 5000-10000 row ranges.
- **Entry page**:
  - [pages/admin/payroll-management.php](pages/admin/payroll-management.php)
- **Main data file**:
  - [pages/admin/includes/payroll-management/data.php](pages/admin/includes/payroll-management/data.php)
- **Checklist**:
  - [ ] Load only current period/runs on initial page render
  - [ ] Move item breakdown and audit trails to on-demand fetches
  - [ ] Avoid loading all payroll items and payslips at once
  - [ ] Add server-side pagination and summary endpoints

### [ ] Admin Recruitment
- **Estimated severity**: P1
- **Approximate backend cost**: 16 requests
- **Likely reason**: loads postings, applications, people, education, status history, documents, interviews, feedback, settings, and recommendation rules in one pass.
- **Entry page**:
  - [pages/admin/recruitment.php](pages/admin/recruitment.php)
- **Main data file**:
  - [pages/admin/includes/recruitment/data.php](pages/admin/includes/recruitment/data.php)
- **Checklist**:
  - [ ] Load posting list first, then lazy-load applicant details per posting
  - [ ] Avoid loading all applications/documents/interviews up front
  - [ ] Precompute summary counts per posting
  - [ ] Split analytics/configuration from operational list view

### [ ] Staff Payroll Management
- **Estimated severity**: P1
- **Approximate backend cost**: 15 requests
- **Likely reason**: heavy joins and large batch reads for periods, runs, role assignments, people, compensations, items, adjustments, reviews, breakdown logs, and payslips.
- **Entry page**:
  - [pages/staff/payroll-management.php](pages/staff/payroll-management.php)
- **Main data file**:
  - [pages/staff/includes/payroll-management/data.php](pages/staff/includes/payroll-management/data.php)
- **Checklist**:
  - [ ] Restrict initial load to current payroll period and active run
  - [ ] Defer adjustment review and breakdown history until requested
  - [ ] Cache employee/compensation lookup maps
  - [ ] Reduce 10000-row scans where possible

### [ ] Admin Report Analytics
- **Estimated severity**: P1
- **Approximate backend cost**: 15 requests
- **Likely reason**: large analytics pulls across employment, people, attendance, payroll, documents, performance, applications, logs, training, and roles.
- **Frontend modularization status**: Admin localized page module completed on 2026-03-07; summary-first backend refactor still pending.
- **Backend optimization status**: Safe query slimming completed on 2026-03-07; full summary-first refactor still pending.
- **Entry page**:
  - [pages/admin/report-analytics.php](pages/admin/report-analytics.php)
- **Main data file**:
  - [pages/admin/includes/report-analytics/data.php](pages/admin/includes/report-analytics/data.php)
- **Checklist**:
  - [x] Reduce initial payload by removing unused selected columns and unnecessary sort/order clauses
  - [x] Scope employment history analytics fetch to current or recent 365-day records only
  - [ ] Replace raw table scans with aggregated summary endpoints/views
  - [ ] Generate charts from pre-aggregated data
  - [ ] Async-load secondary tabs/exports instead of all at once
  - [ ] Limit default date range on first load

### [ ] Employee Personal Information
- **Estimated severity**: P1
- **Approximate backend cost**: 13 requests
- **Likely reason**: loads personal profile plus many related records, documents, requests, evaluations, and login history.
- **Entry page**:
  - [pages/employee/personal-information.php](pages/employee/personal-information.php)
- **Main data file**:
  - [pages/employee/includes/personal-information/data.php](pages/employee/includes/personal-information/data.php)
- **Checklist**:
  - [ ] Split tabs into async sections
  - [ ] Load only visible tab data initially
  - [ ] Defer request history and login history
  - [ ] Avoid loading all document/review data on first render

### [ ] Employee Dashboard
- **Estimated severity**: P1
- **Approximate backend cost**: 12 requests
- **Likely reason**: dashboard tries to render attendance, documents, leave balances, requests, training, praise, notifications, leave forecast, and recent activity all at once.
- **Optimization status**: Staged async hydration completed on 2026-03-08; first paint now renders immediately with dashboard skeletons, summary cards load first, and announcements/requests/trainings/activity load in a second deferred pass.
- **Backend cache status**: Brief employee-context and dashboard summary/secondary session caches added on 2026-03-08, and the extra employee profile lookup was removed from the summary stage.
- **Backend batching status**: Independent summary and secondary Supabase reads were batched in parallel on 2026-03-08 to reduce cold-load round-trip time.
- **Smoke test status**: Passed syntax smoke test on 2026-03-08 with PHP lint on [pages/employee/dashboard.php](pages/employee/dashboard.php), [pages/employee/includes/dashboard/data.php](pages/employee/includes/dashboard/data.php), and [pages/employee/includes/dashboard/content.php](pages/employee/includes/dashboard/content.php), plus JS syntax check on [assets/js/employee/dashboard/index.js](assets/js/employee/dashboard/index.js). Full authenticated browser smoke test is still pending.
- **Entry page**:
  - [pages/employee/dashboard.php](pages/employee/dashboard.php)
- **Main data file**:
  - [pages/employee/includes/dashboard/data.php](pages/employee/includes/dashboard/data.php)
- **Deferred content partial**:
  - [pages/employee/includes/dashboard/content.php](pages/employee/includes/dashboard/content.php)
- **Localized frontend module**:
  - [assets/js/employee/dashboard/index.js](assets/js/employee/dashboard/index.js)
- **Checklist**:
  - [x] Render an immediate shell and skeleton state, then async-load the dashboard body after first paint
  - [x] Render core summary first, lazy-load secondary cards
  - [x] Reuse cached open-request detail rows across dashboard stages when available
  - [x] Cache stable data such as employee context and dashboard summary/secondary payloads briefly
  - [x] Batch independent summary/secondary queries in parallel instead of serial request chaining
  - [ ] Reduce dashboard payload to top 3-5 items per widget

---

## P2 - High Slow Pages

### [ ] Staff Personal Information
- **Estimated severity**: P2
- **Approximate backend cost**: 11 requests
- **Entry page**:
  - [pages/staff/personal-information.php](pages/staff/personal-information.php)
- **Main data file**:
  - [pages/staff/includes/personal-information/data.php](pages/staff/includes/personal-information/data.php)
- **Checklist**:
  - [ ] Defer family/education/history sections
  - [ ] Avoid loading all related records until needed
  - [ ] Paginate recommendation/audit views

### [ ] Staff Dashboard
- **Estimated severity**: P2
- **Approximate backend cost**: 11 requests
- **Entry page**:
  - [pages/staff/dashboard.php](pages/staff/dashboard.php)
- **Main data file**:
  - [pages/staff/includes/dashboard/data.php](pages/staff/includes/dashboard/data.php)
- **Checklist**:
  - [ ] Replace full-scope counts with summarized counters
  - [ ] Lazy-load approvals and activity feeds
  - [ ] Cache dashboard metrics for a short interval

### [ ] Admin Dashboard
- **Estimated severity**: P2
- **Approximate backend cost**: 11 requests
- **Entry page**:
  - [pages/admin/dashboard.php](pages/admin/dashboard.php)
- **Main data file**:
  - [pages/admin/includes/dashboard/data.php](pages/admin/includes/dashboard/data.php)
- **Checklist**:
  - [ ] Replace full attendance/application scans with aggregates
  - [ ] Cache dashboard metric cards
  - [ ] Move notifications/announcement history to async load

### [ ] Admin User Management
- **Estimated severity**: P2
- **Approximate backend cost**: 11 requests
- **Frontend modularization status**: Admin localized page module completed on 2026-03-07; backend payload reduction still pending.
- **Backend optimization status**: Safe metadata caching and dead-query removal completed on 2026-03-07; deeper pagination/refactor still pending.
- **Entry page**:
  - [pages/admin/user-management.php](pages/admin/user-management.php)
- **Main data file**:
  - [pages/admin/includes/user-management/data.php](pages/admin/includes/user-management/data.php)
- **Checklist**:
  - [x] Cache roles/offices/positions metadata briefly in session instead of re-querying every page load
  - [x] Remove unused organizations query from initial page load
  - [ ] Load user list separately from roles/offices/positions metadata
  - [ ] Paginate user list
  - [ ] Avoid loading all hired applications on first render

### [ ] Applicant Apply
- **Estimated severity**: P2
- **Approximate backend cost**: 10 requests
- **Entry page**:
  - [pages/applicant/apply.php](pages/applicant/apply.php)
- **Main data file**:
  - [pages/applicant/includes/apply/data.php](pages/applicant/includes/apply/data.php)
- **Checklist**:
  - [ ] Load applicant profile once and reuse it across steps
  - [ ] Lazy-load job criteria/doc requirements only when job changes
  - [ ] Avoid repeated duplicate/application checks until submit
  - [ ] Cache static job/criteria metadata

### [ ] Staff Recruitment
- **Estimated severity**: P2
- **Approximate backend cost**: 10 requests
- **Entry page**:
  - [pages/staff/recruitment.php](pages/staff/recruitment.php)
- **Main data file**:
  - [pages/staff/includes/recruitment/data.php](pages/staff/includes/recruitment/data.php)
- **Checklist**:
  - [ ] Use the same lazy applicant-detail strategy as admin recruitment
  - [ ] Defer status history/documents/interviews/feedback until selection

### [ ] Employee Timekeeping
- **Estimated severity**: P2
- **Approximate backend cost**: 8 requests
- **Entry page**:
  - [pages/employee/timekeeping.php](pages/employee/timekeeping.php)
- **Main data file**:
  - [pages/employee/includes/timekeeping/data.php](pages/employee/includes/timekeeping/data.php)
- **Checklist**:
  - [ ] Split attendance summary, leave balances, leave history, overtime, and adjustments into separate async requests
  - [ ] Limit historical ranges by default

### [ ] Staff Reports
- **Estimated severity**: P2
- **Approximate backend cost**: 8 requests
- **Entry page**:
  - [pages/staff/reports.php](pages/staff/reports.php)
- **Main data file**:
  - [pages/staff/includes/reports/data.php](pages/staff/includes/reports/data.php)
- **Checklist**:
  - [ ] Use aggregate queries for chart cards
  - [ ] Only load the active report tab
  - [ ] Limit default date ranges and add export-on-demand

### [ ] Applicant Job View
- **Estimated severity**: P2
- **Approximate backend cost**: 9 requests
- **Entry page**:
  - [pages/applicant/job-view.php](pages/applicant/job-view.php)
- **Main data file**:
  - [pages/applicant/includes/job-view/data.php](pages/applicant/includes/job-view/data.php)
- **Checklist**:
  - [ ] Load base job details first
  - [ ] Lazy-load applicant-specific eligibility checks
  - [ ] Cache criteria and position rule metadata

### [ ] Applicant Job List
- **Estimated severity**: P2
- **Approximate backend cost**: 9 requests
- **Entry page**:
  - [pages/applicant/job-list.php](pages/applicant/job-list.php)
- **Main data file**:
  - [pages/applicant/includes/job-list/data.php](pages/applicant/includes/job-list/data.php)
- **Checklist**:
  - [ ] Replace full job count scans with lightweight count query/view
  - [ ] Avoid loading applicant-specific application state until after job list is rendered
  - [ ] Cache office/employment type filters

### [ ] Applicant Profile
- **Estimated severity**: P2
- **Approximate backend cost**: 9 requests
- **Entry page**:
  - [pages/applicant/profile.php](pages/applicant/profile.php)
- **Main data file**:
  - [pages/applicant/includes/profile/data.php](pages/applicant/includes/profile/data.php)
- **Checklist**:
  - [ ] Split account/profile/applications/documents/login history into tabs
  - [ ] Load tab content on demand

---

## P3 - Medium Slow Pages

### [ ] Applicant Dashboard
- **Estimated severity**: P3
- **Approximate backend cost**: 8 requests
- **Entry page**:
  - [pages/applicant/dashboard.php](pages/applicant/dashboard.php)
- **Main data file**:
  - [pages/applicant/includes/dashboard/data.php](pages/applicant/includes/dashboard/data.php)
- **Checklist**:
  - [ ] Cache open job count briefly
  - [ ] Defer progress timeline and non-critical notifications
  - [ ] Replace education/work completion counts with summarized profile completeness state

### [ ] Applicant Applications
- **Estimated severity**: P3
- **Approximate backend cost**: 5 requests
- **Entry page**:
  - [pages/applicant/applications.php](pages/applicant/applications.php)
- **Main data file**:
  - [pages/applicant/includes/applications/data.php](pages/applicant/includes/applications/data.php)
- **Checklist**:
  - [ ] Lazy-load interview/feedback/history panels
  - [ ] Paginate applications if user has many

### [ ] Employee Payroll
- **Estimated severity**: P3
- **Approximate backend cost**: 6 requests
- **Entry page**:
  - [pages/employee/payroll.php](pages/employee/payroll.php)
- **Main data file**:
  - [pages/employee/includes/payroll/data.php](pages/employee/includes/payroll/data.php)
- **Checklist**:
  - [ ] Load latest payslips first
  - [ ] Defer adjustment review and item breakdown until row expand/view

### [ ] Employee Personal Reports
- **Estimated severity**: P3
- **Approximate backend cost**: 4 requests
- **Entry page**:
  - [pages/employee/personal-reports.php](pages/employee/personal-reports.php)
- **Main data file**:
  - [pages/employee/includes/personal-reports/data.php](pages/employee/includes/personal-reports/data.php)
- **Checklist**:
  - [ ] Keep exports/report history off initial paint
  - [ ] Load only the selected report type

---

## Cross-Cutting Query Cleanup Checklist

### [ ] Replace fetch-and-count patterns with true count or summary queries
- **Seen in** dashboards, notifications, job list, open jobs, and many admin/staff list pages
- **Goal**: stop downloading thousands of IDs just to call `count()` in PHP

### [ ] Roll out staged shell-first deferred loading to all slow pages
- **Seen in** dashboards, personal information, payroll, recruitment, reports, applicant profile, and job/apply flows
- **Goal**: return the page shell immediately, show section-matched skeletons, load summary/visible content first, then defer secondary panels/history/widgets to follow-up requests

### [ ] Reduce `limit=5000+` and `limit=10000+` reads
- **Seen heavily in** admin payroll/recruitment/personal info, staff payroll/reports, applicant apply/job flows
- **Goal**: paginate or scope by active page context

### [ ] Defer secondary tabs/modals/history panels
- **Applies to** profile, personal information, recruitment, payroll, reports, document management
- **Goal**: first paint should only load immediately visible data

### [ ] Add or verify database indexes for common filters
- **Likely filters**: `recipient_user_id`, `person_id`, `user_id`, `application_id`, `job_posting_id`, `created_at`, `updated_at`, `status`, `office_id`
- **Goal**: reduce Supabase query latency for the existing request volume

### [ ] Consolidate duplicate role/person/profile lookups
- **Seen across** layout files and dashboard/data files
- **Goal**: resolve user context once per request and reuse it

---

## Recommended Implementation Order

### Wave 1
- [ ] Cache admin topnav
- [ ] Cache applicant topnav
- [ ] Remove global unnecessary CDN/script loading
- [ ] Add timing logs to `apiRequest()`

### Wave 2
- [ ] Admin Personal Information
- [ ] Admin Payroll Management
- [ ] Admin Recruitment
- [ ] Staff Payroll Management
- [ ] Admin Report Analytics

### Wave 3
- [ ] Employee Personal Information
- [ ] Employee Dashboard
- [ ] Staff Dashboard
- [ ] Admin Dashboard
- [ ] Admin User Management

### Wave 4
- [ ] Applicant Apply
- [ ] Applicant Job View
- [ ] Applicant Job List
- [ ] Applicant Profile
- [ ] Staff Recruitment
- [ ] Staff Reports
- [ ] Employee Timekeeping

### Wave 5
- [ ] Audit and retire shared legacy global script usage
- [ ] Replace direct `assets/js/script.js` loaders in staff/employee/applicant layouts with localized page modules
- [ ] Expand admin modular page map until legacy fallback in [assets/js/bootstrap.js](assets/js/bootstrap.js) is no longer needed
  - Progress: [pages/admin/user-management.php](pages/admin/user-management.php) was migrated on 2026-03-07.
  - Progress: [pages/admin/report-analytics.php](pages/admin/report-analytics.php) was migrated on 2026-03-07.
- [ ] Remove dependency on [pages/admin/js/script.js](pages/admin/js/script.js) after admin page coverage is complete

---

## Notes

- XAMPP is **not** the primary cause. Deployment may help somewhat, but these page structures will remain slow until the request fan-out and oversized reads are reduced.
- The biggest wins will come from **shared cache**, **smaller initial payloads**, **per-page lazy loading**, and **replacing full scans with summaries**.
