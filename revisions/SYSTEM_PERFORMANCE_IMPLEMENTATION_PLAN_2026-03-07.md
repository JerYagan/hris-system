# System Performance Implementation Plan

Date: 2026-03-07

Related checklist: [revisions/SYSTEM_PERFORMANCE_SLOW_PAGES_CHECKLIST_2026-03-07.md](revisions/SYSTEM_PERFORMANCE_SLOW_PAGES_CHECKLIST_2026-03-07.md)

## Goal

Reduce the slowest page loads first with the smallest, safest changes before deeper refactors.

## Strategy

1. Fix **shared request overhead** first
2. Reduce **initial payload size** on the heaviest admin/staff pages
3. Split **detail-heavy pages** into on-demand/lazy sections
4. Replace **fetch-then-count** and broad scans with summaries/aggregates
5. Only then tune lower-impact employee/applicant pages

---

## Phase 1 - Quick wins with highest system impact

### 1. Add request timing and slow-query logging
**Status**
- Completed on 2026-03-07

**Why first**
- Gives real timing data before major refactors
- Helps verify which queries are actually worst in production/local

**Files to change first**
- [pages/admin/includes/lib/common.php](pages/admin/includes/lib/common.php)
- [pages/staff/includes/lib/common.php](pages/staff/includes/lib/common.php)
- [pages/employee/includes/lib/common.php](pages/employee/includes/lib/common.php)
- [pages/applicant/includes/lib/common.php](pages/applicant/includes/lib/common.php)

**Changes**
- Add elapsed time measurement around `curl_exec()`
- Log method, URL path, status, duration, and optional row count
- Only log when request time exceeds a threshold, e.g. $300$ ms
- Add a page identifier if available

**Deliverable**
- Slow-query logs that can be reviewed per role/page

---

### 2. Cache admin topnav the same way as staff/employee
**Status**
- Completed on 2026-03-07

**Why first**
- Affects every admin page immediately
- Small change, large perceived improvement

**Files to change first**
- [pages/admin/includes/topnav.php](pages/admin/includes/topnav.php)
- Reference pattern from [pages/staff/includes/layout.php](pages/staff/includes/layout.php)
- Reference pattern from [pages/employee/includes/layout.php](pages/employee/includes/layout.php)

**Changes**
- Add session cache for:
  - display name
  - role label
  - profile photo URL
  - unread notification count
  - notification preview
- Use short TTL, e.g. 30-60 seconds
- Invalidate cache after notification/profile actions if needed

**Deliverable**
- Admin navigation no longer pays 4 requests every page load

---

### 3. Cache applicant topnav
**Status**
- Already present before this phase; no new code change required in this pass

**Why first**
- Affects every applicant page
- Same pattern as admin issue

**Files to change first**
- [pages/applicant/includes/topnav.php](pages/applicant/includes/topnav.php)

**Changes**
- Add session cache for applicant display name/photo/unread preview
- Use same approach as staff and employee

**Deliverable**
- Applicant navigation cost reduced across the whole applicant area

---

### 4. Reduce global frontend weight
**Why first**
- Affects first render before deep backend refactors finish
- Easy to roll out in stages

**Files to change first**
- [pages/admin/includes/head.php](pages/admin/includes/head.php)
- [pages/staff/includes/head.php](pages/staff/includes/head.php)
- [pages/employee/includes/head.php](pages/employee/includes/head.php)
- [pages/applicant/includes/head.php](pages/applicant/includes/head.php)
- [pages/employee/includes/layout.php](pages/employee/includes/layout.php)
- [pages/applicant/includes/layout.php](pages/applicant/includes/layout.php)
- [assets/js/script.js](assets/js/script.js)

**Changes**
- Stop loading `Chart.js` on pages without charts
- Stop loading `flatpickr` on pages without datepickers
- Keep DataTables limited to actual table pages
- Reduce use of legacy global script in employee/applicant areas
- Add page guards so `assets/js/script.js` does nothing unless required DOM exists

**Deliverable**
- Lower JS/CSS/network overhead on every page

---

## Phase 2 - Heaviest admin/staff pages

### 5. Refactor Admin Personal Information
**Files to change first**
- [pages/admin/personal-information.php](pages/admin/personal-information.php)
- [pages/admin/includes/personal-information/data.php](pages/admin/includes/personal-information/data.php)
- Supporting actions/views if needed under [pages/admin/includes/personal-information](pages/admin/includes/personal-information)

**Implementation order**
1. Keep only base people list on initial render
2. Remove eager loading of all related tables from first load
3. Load selected employee detail asynchronously
4. Load history/review/audit panels only when opened

**Target outcome**
- Replace 21-request full preload with lighter list + targeted detail fetch

---

### 6. Refactor Admin Payroll Management
**Files to change first**
- [pages/admin/payroll-management.php](pages/admin/payroll-management.php)
- [pages/admin/includes/payroll-management/data.php](pages/admin/includes/payroll-management/data.php)
- Supporting files under [pages/admin/includes/payroll-management](pages/admin/includes/payroll-management)

**Implementation order**
1. Load only active/recent periods and runs initially
2. Move payroll item breakdown/history into separate fetches
3. Load payslip/adjustment detail only when a run is selected
4. Add pagination for large item lists

**Target outcome**
- Remove most 5000-10000 row reads from first paint

---

### 7. Refactor Admin Recruitment
**Files to change first**
- [pages/admin/recruitment.php](pages/admin/recruitment.php)
- [pages/admin/includes/recruitment/data.php](pages/admin/includes/recruitment/data.php)
- Supporting files under [pages/admin/includes/recruitment](pages/admin/includes/recruitment)

**Implementation order**
1. Load posting list only on initial render
2. Load applications for one posting when selected
3. Load interview/history/documents/feedback per applicant modal or detail pane
4. Separate configuration/rules/settings from the main list

**Target outcome**
- Replace massive preloading with drill-down loading

---

### 8. Refactor Staff Payroll Management
**Files to change first**
- [pages/staff/payroll-management.php](pages/staff/payroll-management.php)
- [pages/staff/includes/payroll-management/data.php](pages/staff/includes/payroll-management/data.php)
- Supporting files under [pages/staff/includes/payroll-management](pages/staff/includes/payroll-management)

**Implementation order**
1. Limit initial render to current period and latest run
2. Defer item breakdown and review logs
3. Cache lookup maps for people/compensation when practical
4. Paginate employees/items in selected run

**Target outcome**
- Lower request count and lower row volume on first page load

---

### 9. Refactor Admin Report Analytics
**Status**
- In progress
- Completed safe payload slimming on 2026-03-07 in [pages/admin/includes/report-analytics/data.php](pages/admin/includes/report-analytics/data.php)

**Files to change first**
- [pages/admin/report-analytics.php](pages/admin/report-analytics.php)
- [pages/admin/includes/report-analytics/data.php](pages/admin/includes/report-analytics/data.php)
- [pages/admin/includes/report-analytics/actions.php](pages/admin/includes/report-analytics/actions.php)

**Implementation order**
1. Add default date window
2. Build summary cards from aggregated data, not raw record scans
3. Load chart tabs and exports only on demand
4. Move long-running exports into separate actions

**Completed in this pass**
- Narrowed oversized `select` lists to only fields actually used by the page
- Removed unnecessary `order` clauses from count-only analytics queries
- Scoped employment history fetch to current or recent 365-day records to reduce first-load volume without changing displayed metrics

**Target outcome**
- Analytics page becomes summary-first instead of scan-first

---

## Phase 3 - Important dashboards and data-heavy operational pages

### 10. Simplify Admin, Staff, and Employee dashboards
**Files to change first**
- [pages/admin/includes/dashboard/data.php](pages/admin/includes/dashboard/data.php)
- [pages/staff/includes/dashboard/data.php](pages/staff/includes/dashboard/data.php)
- [pages/employee/includes/dashboard/data.php](pages/employee/includes/dashboard/data.php)

**Implementation order**
1. Keep top summary cards only for initial render
2. Load notifications/activity/secondary widgets after page render
3. Replace fetch-and-count patterns with summary queries
4. Cache dashboard metrics briefly by user/role/office

**Target outcome**
- Faster first paint on landing pages for all roles

---

### 11. Simplify Admin User Management
**Files to change first**
- [pages/admin/user-management.php](pages/admin/user-management.php)
- [pages/admin/includes/user-management/data.php](pages/admin/includes/user-management/data.php)

**Implementation order**
1. Paginate user list
2. Load roles/offices/positions metadata once and cache briefly
3. Move hired-applicant conversion helper data to modal or async endpoint

---

### 12. Simplify Staff Personal Information
**Files to change first**
- [pages/staff/personal-information.php](pages/staff/personal-information.php)
- [pages/staff/includes/personal-information/data.php](pages/staff/includes/personal-information/data.php)

**Implementation order**
1. Load main record first
2. Load family/education/government/history tabs on demand
3. Defer recommendation and audit sections

---

### 13. Simplify Employee Personal Information
**Files to change first**
- [pages/employee/personal-information.php](pages/employee/personal-information.php)
- [pages/employee/includes/personal-information/data.php](pages/employee/includes/personal-information/data.php)

**Implementation order**
1. Split into visible tab + deferred tabs
2. Load login history only when opened
3. Defer document and evaluation-related side panels

---

## Phase 4 - Applicant workflow optimization

### 14. Refactor Applicant Apply flow
**Files to change first**
- [pages/applicant/apply.php](pages/applicant/apply.php)
- [pages/applicant/includes/apply/data.php](pages/applicant/includes/apply/data.php)
- [pages/applicant/includes/apply/actions.php](pages/applicant/includes/apply/actions.php)

**Implementation order**
1. Load applicant profile/core form first
2. Fetch job-specific criteria only for selected job
3. Run duplicate/application checks only at meaningful checkpoints
4. Cache static criteria/position rule metadata

---

### 15. Refactor Applicant Job View and Job List
**Files to change first**
- [pages/applicant/job-list.php](pages/applicant/job-list.php)
- [pages/applicant/includes/job-list/data.php](pages/applicant/includes/job-list/data.php)
- [pages/applicant/job-view.php](pages/applicant/job-view.php)
- [pages/applicant/includes/job-view/data.php](pages/applicant/includes/job-view/data.php)

**Implementation order**
1. Make job list filter metadata cacheable
2. Render job cards first
3. Load applicant-specific checks after base job data
4. Replace open-job and list count scans with aggregate queries/views

---

### 16. Refactor Applicant Profile and Dashboard
**Files to change first**
- [pages/applicant/profile.php](pages/applicant/profile.php)
- [pages/applicant/includes/profile/data.php](pages/applicant/includes/profile/data.php)
- [pages/applicant/dashboard.php](pages/applicant/dashboard.php)
- [pages/applicant/includes/dashboard/data.php](pages/applicant/includes/dashboard/data.php)

**Implementation order**
1. Split profile into async tabs
2. Cache profile completeness summary
3. Keep dashboard lightweight and defer timeline/history panels

---

## Phase 5 - Secondary employee/staff pages

### 17. Optimize Employee Timekeeping
**Files to change first**
- [pages/employee/timekeeping.php](pages/employee/timekeeping.php)
- [pages/employee/includes/timekeeping/data.php](pages/employee/includes/timekeeping/data.php)

### 18. Optimize Staff Reports
**Files to change first**
- [pages/staff/reports.php](pages/staff/reports.php)
- [pages/staff/includes/reports/data.php](pages/staff/includes/reports/data.php)
- [pages/staff/includes/reports/actions.php](pages/staff/includes/reports/actions.php)

### 19. Optimize Employee Payroll and Personal Reports
**Files to change first**
- [pages/employee/payroll.php](pages/employee/payroll.php)
- [pages/employee/includes/payroll/data.php](pages/employee/includes/payroll/data.php)
- [pages/employee/personal-reports.php](pages/employee/personal-reports.php)
- [pages/employee/includes/personal-reports/data.php](pages/employee/includes/personal-reports/data.php)

---

## Final Phase - Retire legacy global script safely

### 20. Remove direct shared global script usage from staff, employee, applicant, and auth
**Why last**
- These layouts still provide behavior that some pages may implicitly depend on
- Removing them too early risks silent UI regressions
- Best done only after localized page modules exist everywhere they are needed

**Direct legacy loaders still in use**
- [pages/staff/includes/layout.php](pages/staff/includes/layout.php)
- [pages/employee/includes/layout.php](pages/employee/includes/layout.php)
- [pages/applicant/includes/layout.php](pages/applicant/includes/layout.php)
- [pages/auth/includes/head.php](pages/auth/includes/head.php)

**Implementation order**
1. Inventory actual behaviors still supplied by [assets/js/script.js](assets/js/script.js)
2. Move each needed behavior into localized role/page modules or shared lightweight helpers
3. Remove direct loader from one role at a time
4. Validate each role's menus, modals, tabs, clocks, and charts before removing the next loader

---

### 21. Eliminate admin legacy fallback in bootstrap
**Status**
- In progress
- Completed localized admin page in this pass: [pages/admin/user-management.php](pages/admin/user-management.php) via [assets/js/admin/user-management/index.js](assets/js/admin/user-management/index.js) on 2026-03-07
- Completed localized admin page in this pass: [pages/admin/report-analytics.php](pages/admin/report-analytics.php) via [assets/js/admin/report-analytics/index.js](assets/js/admin/report-analytics/index.js) on 2026-03-07

**Why last**
- Admin still has many pages that rely on the fallback legacy script path
- Some admin modules are already localized, but many are not

**Files to change last**
- [assets/js/bootstrap.js](assets/js/bootstrap.js)
- [pages/admin/js/script.js](pages/admin/js/script.js)

**Admin pages still using fallback today**
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

**Implementation order**
1. Add page-specific modules for each remaining fallback page
2. Expand `adminModuleMap` in [assets/js/bootstrap.js](assets/js/bootstrap.js)
3. Test each mapped admin page after migration
4. Remove `loadLegacyAdminScript()` only when no admin page depends on it

**Target outcome**
- Admin is fully localized like the target architecture in [FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md](FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md)
- Legacy admin fallback is fully removable

---

## Query Refactor Patterns To Reuse

### Pattern A - Replace fetch-all-then-count
Use when the code does:
- `select=id...limit=5000`
- then `count((array)$response['data'])`

**Apply to**
- dashboards
- open jobs
- unread notifications
- request counters

### Pattern B - Initial summary + drill-down detail
Use when one page loads list + all nested relations.

**Apply to**
- recruitment
- payroll
- personal information
- reports

### Pattern C - Lazy tabs and side panels
Use when a page has tabs, modals, drawers, or accordions with hidden data.

**Apply to**
- profile pages
- personal information pages
- applicant flows
- document history/review sections

### Pattern D - Brief session cache for chrome/shared user context
Use for data repeated across many navigations.

**Apply to**
- topnav
- profile badge
- unread counts
- role labels

---

## Recommended First Coding Sprint

### Sprint A
- [ ] Add timing instrumentation in all `apiRequest()` helpers
- [ ] Cache admin topnav
- [ ] Cache applicant topnav
- [ ] Remove unnecessary global `Chart.js` and datepicker loads
- [ ] Harden `assets/js/script.js` to no-op on pages without matching DOM

### Sprint B
- [ ] Admin Personal Information first-load reduction
- [ ] Admin Payroll first-load reduction
- [ ] Admin Recruitment first-load reduction

### Sprint C
- [ ] Staff Payroll refactor
- [ ] Admin Report Analytics summary-first refactor
- [ ] Admin/Staff/Employee dashboard slimming

### Sprint D
- [ ] Employee Personal Information refactor
- [ ] Applicant Apply + Job View refactor
- [ ] Applicant Job List + Profile refactor

---

## Success Criteria

- Shared role chrome no longer performs uncached multi-request loads on every navigation
- First render for dashboards becomes summary-first
- Heaviest admin/staff pages stop preloading all nested data
- Large `limit=5000+` reads are reduced or paginated
- Slow-query logs identify remaining hotspots for the next pass
