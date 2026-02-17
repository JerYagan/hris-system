# Employee Backend Integration Steps (Full Implementation)

This guide is the execution plan to fully integrate backend logic for the Employee module using the same architecture and quality gates already used in Applicant.

Target area:
- [pages/employee/dashboard.php](pages/employee/dashboard.php)
- [pages/employee/personal-information.php](pages/employee/personal-information.php)
- [pages/employee/document-management.php](pages/employee/document-management.php)
- [pages/employee/timekeeping.php](pages/employee/timekeeping.php)
- [pages/employee/payroll.php](pages/employee/payroll.php)
- [pages/employee/praise.php](pages/employee/praise.php)
- [pages/employee/personal-reports.php](pages/employee/personal-reports.php)
- [pages/employee/notifications.php](pages/employee/notifications.php)
- [pages/employee/settings.php](pages/employee/settings.php)
- [pages/employee/support.php](pages/employee/support.php)
- [pages/employee/learning-and-development.php](pages/employee/learning-and-development.php)

Schema source:
- [SUPABASE_SCHEMA.sql](SUPABASE_SCHEMA.sql)

Frontend/performance standard:
- [FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md](FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md)

---

## Implementation Status

- ✅ Phase 1 completed (Employee backend foundation + per-page bootstrap/actions/data pattern)
- ✅ Phase 2 completed (Identity and employee context resolution)
- ✅ Phase 3 completed (Dashboard backend integration)
- ✅ Phase 4 completed (Personal Information backend integration)
- ✅ Phase 5 completed (Document Management backend integration)
- ✅ Phase 6 completed (Timekeeping + leave/correction requests)
- ✅ Phase 7 completed (Payroll and payslip retrieval)
- ✅ Phase 8 completed (PRAISE + personal reports)
- ✅ Phase 9 completed (Notifications + support)
- ✅ Phase 10 completed (Settings + account preferences)
- ✅ Phase 11 completed (Security and RLS hardening)
- ✅ Phase 12 completed (UX states + localized JS/performance pass)
- ✅ Phase 13 completed (Functional/security/integrity test execution)
- ✅ Phase 14 completed (Learning and Development self-service module + dashboard upcoming trainings card/layout pass)
- ⏭️ Next active phase: None (all planned employee phases completed)

### Completed Implementation Notes (as of latest update)

- Employee backend shared library scaffold is live in:
  - [pages/employee/includes/lib/common.php](pages/employee/includes/lib/common.php)
  - [pages/employee/includes/lib/supabase.php](pages/employee/includes/lib/supabase.php)
  - [pages/employee/includes/lib/employee-backend.php](pages/employee/includes/lib/employee-backend.php)
- Per-page include scaffolds (`bootstrap.php`, `actions.php`, `data.php`) are live in:
  - [pages/employee/includes/dashboard/bootstrap.php](pages/employee/includes/dashboard/bootstrap.php)
  - [pages/employee/includes/personal-information/bootstrap.php](pages/employee/includes/personal-information/bootstrap.php)
  - [pages/employee/includes/document-management/bootstrap.php](pages/employee/includes/document-management/bootstrap.php)
  - [pages/employee/includes/timekeeping/bootstrap.php](pages/employee/includes/timekeeping/bootstrap.php)
  - [pages/employee/includes/payroll/bootstrap.php](pages/employee/includes/payroll/bootstrap.php)
  - [pages/employee/includes/praise/bootstrap.php](pages/employee/includes/praise/bootstrap.php)
  - [pages/employee/includes/personal-reports/bootstrap.php](pages/employee/includes/personal-reports/bootstrap.php)
  - [pages/employee/includes/notifications/bootstrap.php](pages/employee/includes/notifications/bootstrap.php)
  - [pages/employee/includes/settings/bootstrap.php](pages/employee/includes/settings/bootstrap.php)
  - [pages/employee/includes/support/bootstrap.php](pages/employee/includes/support/bootstrap.php)
- Employee page entry files are now wired to standardized include order (`bootstrap` → `actions` → `data`) for all target pages in [pages/employee](pages/employee).
- Baseline helpers now include CSRF generation/validation, UUID checks, normalized API response handling, and redirect helpers for future phase actions.
- Phase 2 identity resolution is live via `resolveEmployeeIdentityContext(...)` in [pages/employee/includes/lib/employee-backend.php](pages/employee/includes/lib/employee-backend.php), validating session user UUID, employee role assignment, linked person record, and active employment record with office/position metadata.
- All employee `bootstrap.php` files now resolve and expose employee context (`person_id`, `employment_id`, `office_id`, `position_id`, status/title fields) and fail closed with a safe 403 error state when context is invalid.
- All employee `actions.php` files now block mutations when employee context is unresolved, and all employee `data.php` files surface resolver errors through `dataLoadError`.
- Dashboard Phase 3 is now live in [pages/employee/includes/dashboard/data.php](pages/employee/includes/dashboard/data.php) and [pages/employee/dashboard.php](pages/employee/dashboard.php) with live summary cards and datasets for attendance, pending documents, open leave/overtime/time-adjustment requests, notifications/announcements, PRAISE status, and recent activity.
- Dashboard placeholders were replaced by Supabase-backed rendering with safe empty/error states while preserving the existing page structure and quick links.
- Personal Information Phase 4 is now live in [pages/employee/includes/personal-information/data.php](pages/employee/includes/personal-information/data.php), [pages/employee/includes/personal-information/actions.php](pages/employee/includes/personal-information/actions.php), and [pages/employee/personal-information.php](pages/employee/personal-information.php), including live profile/address/employment reads and self-service profile update flow.
- Profile updates are constrained to allowed self-service fields, validated with CSRF and input checks, and recorded in `activity_logs` with before/after payload snapshots for auditability.
- Phase 4 revision now includes localized employee JS at [assets/js/employee/personal-information/index.js](assets/js/employee/personal-information/index.js) for profile/upload/edit-document modal behavior instead of relying on global script handlers.
- Personal documents in [pages/employee/personal-information.php](pages/employee/personal-information.php) are now backend-driven with working upload, edit, remove, and view actions (`upload_document`, `update_document`, `remove_document`) handled in [pages/employee/includes/personal-information/actions.php](pages/employee/includes/personal-information/actions.php).
- Document viewing is implemented through [pages/employee/view-document.php](pages/employee/view-document.php) with employee ownership checks and `document_access_logs` write before inline file response.
- Personal profile coverage has been expanded toward PDS scope (personal/contact/address details, emergency contact, and education record handling) with corresponding reads/writes in [pages/employee/includes/personal-information/data.php](pages/employee/includes/personal-information/data.php) and [pages/employee/includes/personal-information/actions.php](pages/employee/includes/personal-information/actions.php).
- Phase 5 Document Management is now live in [pages/employee/document-management.php](pages/employee/document-management.php), [pages/employee/includes/document-management/data.php](pages/employee/includes/document-management/data.php), and [pages/employee/includes/document-management/actions.php](pages/employee/includes/document-management/actions.php) with employee-owned document listing, category-scoped upload, version upload, and review/version history rendering.
- Document open/download is now ownership-scoped and access-logged through [pages/employee/view-document.php](pages/employee/view-document.php) and [pages/employee/download-document.php](pages/employee/download-document.php), with deterministic local storage paths and MIME/size validation at upload time.
- Localized page script for document management interactions is now active at [assets/js/employee/document-management/index.js](assets/js/employee/document-management/index.js) (modal control, filters, history toggles, version modal binding).
- Phase 7 Payroll is now live in [pages/employee/payroll.php](pages/employee/payroll.php) and [pages/employee/includes/payroll/data.php](pages/employee/includes/payroll/data.php), with employee-scoped payroll item listing, summary cards, year filtering, and detailed earnings/deductions breakdown modal.
- Payslip retrieval is now ownership-scoped in [pages/employee/view-payslip.php](pages/employee/view-payslip.php) and [pages/employee/download-payslip.php](pages/employee/download-payslip.php), validating payroll-item ownership before serving files and updating `payslips.viewed_at`.
- Localized payroll page script is now active at [assets/js/employee/payroll/index.js](assets/js/employee/payroll/index.js) for year filtering and payslip detail modal orchestration.
- Phase 6 Timekeeping is now live in [pages/employee/timekeeping.php](pages/employee/timekeeping.php), [pages/employee/includes/timekeeping/data.php](pages/employee/includes/timekeeping/data.php), and [pages/employee/includes/timekeeping/actions.php](pages/employee/includes/timekeeping/actions.php), with attendance history filtering/pagination, leave balance snapshot, leave request creation, time adjustment request creation, and overtime request creation/status tracking.
- Timekeeping actions now enforce CSRF, UUID checks, date/time validation, positive numeric constraints, duplicate/overlap request guards, and ownership checks for attendance-linked adjustment requests.
- Localized timekeeping page script is now active at [assets/js/employee/timekeeping/index.js](assets/js/employee/timekeeping/index.js) for leave/overtime/adjustment modal orchestration and attendance-context prefill for adjustment requests.
- Phase 8 PRAISE self-view is now live in [pages/employee/praise.php](pages/employee/praise.php), [pages/employee/includes/praise/data.php](pages/employee/includes/praise/data.php), and [pages/employee/includes/praise/actions.php](pages/employee/includes/praise/actions.php), including employee-scoped supervisor evaluations, PRAISE nominations, and training completion snapshot.
- Personal Reports Phase 8 implementation is now live in [pages/employee/personal-reports.php](pages/employee/personal-reports.php), [pages/employee/includes/personal-reports/data.php](pages/employee/includes/personal-reports/data.php), and [pages/employee/includes/personal-reports/actions.php](pages/employee/includes/personal-reports/actions.php), including report history/status listing (`generated_reports`) and self-service report request queueing.
- Localized scripts are now active at [assets/js/employee/praise/index.js](assets/js/employee/praise/index.js) and [assets/js/employee/personal-reports/index.js](assets/js/employee/personal-reports/index.js) for page-specific modal/detail interactions.
- Phase 9 Notifications is now live in [pages/employee/notifications.php](pages/employee/notifications.php), [pages/employee/includes/notifications/data.php](pages/employee/includes/notifications/data.php), and [pages/employee/includes/notifications/actions.php](pages/employee/includes/notifications/actions.php), including employee-scoped notification listing, category/status filters, mark-one-read, and mark-all-read flows.
- Phase 9 Support is now live in [pages/employee/support.php](pages/employee/support.php), [pages/employee/includes/support/data.php](pages/employee/includes/support/data.php), and [pages/employee/includes/support/actions.php](pages/employee/includes/support/actions.php), including support inquiry submission and recent inquiry history from activity logs.
- Localized scripts are now active at [assets/js/employee/notifications/index.js](assets/js/employee/notifications/index.js) and [assets/js/employee/support/index.js](assets/js/employee/support/index.js) for page-specific modal/detail and form interactions.
- Phase 10 Settings is now live in [pages/employee/settings.php](pages/employee/settings.php), [pages/employee/includes/settings/data.php](pages/employee/includes/settings/data.php), and [pages/employee/includes/settings/actions.php](pages/employee/includes/settings/actions.php), including safe account preference updates, password reset handoff visibility, and persisted notification preference toggles backed by `system_settings`.
- Localized script is now active at [assets/js/employee/settings/index.js](assets/js/employee/settings/index.js) for settings tab orchestration.
- Phase 11 app-layer hardening is now live in [pages/employee/includes/lib/common.php](pages/employee/includes/lib/common.php) via centralized secure path helpers (`normalizeRelativeStoragePath`, `resolveStorageFilePath`) used by sensitive file operations.
- File retrieval hardening is now enforced in [pages/employee/view-document.php](pages/employee/view-document.php), [pages/employee/download-document.php](pages/employee/download-document.php), [pages/employee/view-payslip.php](pages/employee/view-payslip.php), and [pages/employee/download-payslip.php](pages/employee/download-payslip.php) with fail-closed storage path validation and root-anchored file resolution.
- Document upload security hardening is now applied in [pages/employee/includes/document-management/actions.php](pages/employee/includes/document-management/actions.php), including normalized storage path generation and safe cleanup behavior.
- RLS hardening migration for employee-sensitive tables/policies is now prepared in [RLS_PHASE2_EMPLOYEE_HARDENING.sql](RLS_PHASE2_EMPLOYEE_HARDENING.sql) for Phase 11 rollout.
- Localized settings script refinement is now active in [assets/js/employee/settings/index.js](assets/js/employee/settings/index.js) to support security-focused tab routing (`tab=security` / hash-based activation).
- Phase 12 UX-state standardization is now live through shared helper [assets/js/shared/ui/page-state.js](assets/js/shared/ui/page-state.js), applied across employee localized scripts for document management, payroll, timekeeping, praise, personal reports, notifications, support, and settings pages.
- Filter-empty behavior is now aligned to filtered-result context (instead of generic empty state) in localized scripts where client-side filtering is used, improving UX consistency with the performance guide.
- Phase 13 test execution report is now documented in [EMPLOYEE_PHASE13_TEST_REPORT.md](EMPLOYEE_PHASE13_TEST_REPORT.md), covering functional/security/integrity static checks, employee localized JS coverage, and required staging runtime QA scenarios.
- Additional employee UX/task bundle is now implemented across layout + localized page modules: backend-driven unread badges in topnav/sidebar, backend-driven topnav employee name/photo + dropdown alignment, dashboard personalized welcome + direct quick actions, personal reports quarter-based evaluation listing/filter, document-management 201-file filters, notifications modal-open auto-read sync, and personal-information profile-photo upload/display using `people.profile_photo_url`.
- Phase 14 Learning and Development self-service module is now live in [pages/employee/learning-and-development.php](pages/employee/learning-and-development.php), [pages/employee/includes/learning-and-development/bootstrap.php](pages/employee/includes/learning-and-development/bootstrap.php), [pages/employee/includes/learning-and-development/actions.php](pages/employee/includes/learning-and-development/actions.php), [pages/employee/includes/learning-and-development/data.php](pages/employee/includes/learning-and-development/data.php), and [assets/js/employee/learning-and-development/index.js](assets/js/employee/learning-and-development/index.js) with available/taken training listings, enrollment action, upcoming training notifications, and certificate visibility.
- Dashboard training enhancements are now live in [pages/employee/includes/dashboard/data.php](pages/employee/includes/dashboard/data.php) and [pages/employee/dashboard.php](pages/employee/dashboard.php) with a new “Upcoming Trainings” card (next 3 enrolled schedules + view-all link) and updated grid layout placing Upcoming Trainings, Open Requests, and Recent Activity in a shared row.

### Git Commit Comment

- `feat(employee): implement Phase 9 notifications and support backend flows with localized JS modules`
- `feat(employee): implement Phase 10 settings preferences with localized JS and secure backend actions`
- `feat(employee): complete Phase 11 security hardening with safe file path guards and RLS phase2 migration`
- `feat(employee): complete Phase 12 UX-state standardization with shared page-state helper across localized JS`
- `test(employee): complete Phase 13 functional-security-integrity verification and document execution report`
- `feat(employee): implement additional UX enhancements for nav badges/profile, dashboard quick actions, reports quartering, 201-file filters, notifications auto-read, and profile photo upload`
- `feat(employee): implement Phase 14 learning-and-development self-service module and dashboard upcoming trainings card layout`

---

## 0) Current-State Findings (Why this plan is needed)

Observed from current employee pages:
- Pages are mostly static/demo and not yet bound to live Supabase records.
- Dashboard is now connected to live backend records (completed in Phase 3).
- Personal Information is now connected to live backend records and profile updates (completed in Phase 4).
- Personal Information now has localized JS interactions and employee document self-management actions (Phase 4 revision).
- Shared employee includes exist but no full per-page backend flow pattern yet.
- Existing auth guard is present and should be reused as the first security gate.

Reference checklist:
- [MODULE_CHECKLIST_ISSUES.md](MODULE_CHECKLIST_ISSUES.md)

---

## 1) Scope Lock (Employee-Only MVP)

Implement now:
1. Employee profile/self-service personal information
2. Employee document inventory + upload/version history
3. Timekeeping summary + leave request + time adjustment request
4. Payroll summary + payslip retrieval
5. Employee-facing PRAISE/personal reports view
6. Notifications read/update actions
7. Support inquiry logging
8. Settings (safe preferences and account-level updates only)

Defer:
- Heavy analytics dashboards beyond employee self-view
- Realtime subscriptions
- Advanced reporting exports outside employee self-service scope

---

## 2) Data Model Mapping (Employee pages -> tables)

Use these existing tables in [SUPABASE_SCHEMA.sql](SUPABASE_SCHEMA.sql):

- Identity and access:
  - `user_accounts`
  - `user_role_assignments`
  - `roles`
  - `people`
  - `employment_records`

- Timekeeping and requests:
  - `attendance_logs`
  - `work_schedules`
  - `leave_requests`
  - `leave_types`
  - `leave_balances`
  - `time_adjustment_requests`
  - `overtime_requests`
  - `holidays`

- Payroll:
  - `payroll_periods`
  - `payroll_runs`
  - `payroll_items`
  - `payroll_adjustments`
  - `payslips`

- Document management:
  - `documents`
  - `document_versions`
  - `document_reviews`
  - `document_categories`
  - `document_access_logs`

- Performance/PRAISE/L&D/Reports:
  - `performance_cycles`
  - `performance_evaluations`
  - `praise_awards`
  - `praise_nominations`
  - `training_programs`
  - `training_enrollments`
  - `generated_reports` (employee self-generated records only if allowed)

- Shared:
  - `notifications`
  - `activity_logs`

Storage buckets expected:
- employee documents bucket (or existing documents bucket strategy)
- payslip PDF path from `payslips.pdf_storage_path`

---

## 3) Phase 1 – Shared Employee Backend Foundation

## 3.1 Create employee backend library

Create:
- `pages/employee/includes/lib/common.php`
- `pages/employee/includes/lib/supabase.php`
- `pages/employee/includes/lib/employee-backend.php`

Required helpers:
- `loadEnvFile`
- `cleanText`
- `apiRequest`
- `isSuccessful`
- `redirectWithState`
- `ensureCsrfToken`
- `isValidCsrfToken`
- `isValidUuid`
- `employeeBackendContext()`

`employeeBackendContext()` should return:
- `supabase_url`
- `service_role_key`
- `headers`
- `employee_user_id`

## 3.2 Adopt per-page backend structure

For each employee page, use:
1. `bootstrap.php`
2. `actions.php`
3. `data.php`
4. page render (`.php` view)

Target include folders:
- `pages/employee/includes/dashboard/`
- `pages/employee/includes/personal-information/`
- `pages/employee/includes/document-management/`
- `pages/employee/includes/timekeeping/`
- `pages/employee/includes/payroll/`
- `pages/employee/includes/praise/`
- `pages/employee/includes/personal-reports/`
- `pages/employee/includes/notifications/`
- `pages/employee/includes/settings/`
- `pages/employee/includes/support/`

## 3.3 API resilience baseline

In `apiRequest`:
- connect timeout + total timeout
- safe JSON decode
- normalized response shape (`status`, `data`, `raw`, `error`)
- optional GET retry with jitter for transient failures

---

## 4) Phase 2 – Identity and Employee Context

1. Resolve `$_SESSION['user']['id']`.
2. Validate user has employee role assignment.
3. Resolve `people.id` and current `employment_records` (`is_current=true`).
4. Resolve scoped office/position metadata for dashboard headers and permissions.
5. Fail closed with safe error state if context cannot be resolved.

---

## 5) Phase 3 – Dashboard Backend Integration

File:
- [pages/employee/dashboard.php](pages/employee/dashboard.php)

Replace static cards with live data:
- attendance today (`attendance_logs`)
- pending document reviews (`documents` / `document_reviews` self-related)
- open requests (leave/time adjustment/overtime)
- latest notifications/announcements
- recent employee activity (`activity_logs` self entries)
- Change quick links into ( Create Leave Requests, Upload Documents, Generate Reports, Submit Self-Evaluation)

Rules:
- Use minimal select columns.
- Use aggregate/count strategy for summary cards.
- Keep first-load API call count within frontend budget.

---

## 6) Phase 4 – Personal Information Backend Integration

File:
- [pages/employee/personal-information.php](pages/employee/personal-information.php)

Implement:
- GET profile data from `people`, related contact/address records as available.
- POST updates only to allowed self-service fields.
- Write `activity_logs` for profile updates.

Validation:
- strict field sanitization
- UUID and CSRF checks
- disallow role/status/security field changes

---

## 7) Phase 5 – Document Management Backend Integration

File:
- [pages/employee/document-management.php](pages/employee/document-management.php)

Implement:
- list own documents with category/status/version
- upload new document version
- view review status and history
- secure file open/download actions

Rules:
- deterministic storage path
- MIME and size validation
- ownership checks before any read/write action
- log access in `document_access_logs`

---

## 8) Phase 6 – Timekeeping + Leave/Adjustment Workflows

File:
- [pages/employee/timekeeping.php](pages/employee/timekeeping.php)

Implement:
- attendance history query with server-side pagination/filtering
- leave balance snapshot + leave request creation
- time adjustment request creation and status tracking
- overtime request creation and status tracking (if in scope)

Validation:
- date range validity
- positive numeric constraints
- duplicate/same-day policy checks (app-side guard)

---

## 9) Phase 7 – Payroll + Payslip Retrieval

File:
- [pages/employee/payroll.php](pages/employee/payroll.php)

Implement:
- payroll period list + summary values from `payroll_items`
- breakdown view (`payroll_adjustments`)
- payslip retrieval via `payslips.pdf_storage_path`

Rules:
- employee can only access own payroll items/payslips
- signed URL/file access must be scoped and time-limited
- no sensitive over-fetch on first load

---

## 10) Phase 8 – PRAISE + Personal Reports

Files:
- [pages/employee/praise.php](pages/employee/praise.php)
- [pages/employee/personal-reports.php](pages/employee/personal-reports.php)

Implement:
- employee self-view of PRAISE nominations/evaluations
- personal report history and statuses (if self-service export is enabled)
- optional training completion snapshot from `training_enrollments`

Rules:
- read-only where employee update rights do not apply
- no cross-employee visibility

---

## 11) Phase 9 – Notifications + Support

Files:
- [pages/employee/notifications.php](pages/employee/notifications.php)
- [pages/employee/support.php](pages/employee/support.php)

Implement:
- notifications list by `recipient_user_id`
- mark one / mark all read actions
- support inquiry submit (activity log or dedicated table)
- recent inquiry history for employee

---

## 12) Phase 10 – Settings

File:
- [pages/employee/settings.php](pages/employee/settings.php)

Implement:
- safe account preferences only (non-privileged settings)
- password reset handoff flow if needed
- notification preference toggles only if backed by schema/setting key

Rules:
- never expose or mutate role assignments from employee UI

---

## 13) Phase 11 – Security and RLS Checklist

Must pass before rollout:
1. Employee can only read/update own profile data.
2. Employee cannot access another employee’s attendance/payroll/documents.
3. All POST mutations use CSRF validation.
4. All request object IDs validated as UUID before processing.
5. File actions enforce ownership + MIME + size + path scoping.
6. Sensitive fields are write-protected at app layer.
7. Activity and access logs are written for critical operations.

---

## 14) Phase 12 – UX States + Localized JS + Performance

Align implementation to:
- [FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md](FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md)

Per employee page, ensure:
- loading/skeleton state
- empty state
- filter-empty state
- error state with retry
- success state after mutations

Performance requirements:
- no heavy global JS for employee module
- page-localized JS entrypoints
- server-side pagination/filtering/sorting for large lists
- narrow select columns only

---

## 15) Phase 13 – Testing Plan (Step-by-Step)

## 15.1 Functional tests

1. Login as employee and resolve context.
2. Open dashboard and verify live card values.
3. Update personal information and verify persistence.
4. Upload/view document and verify status display.
5. Submit leave request and verify list/status entry.
6. Submit time adjustment request and verify list/status entry.
7. Open payroll breakdown and retrieve payslip.
8. Read/mark notifications.
9. Submit support inquiry and verify log/history.

## 15.2 Security tests

1. Attempt direct access to another employee record IDs.
2. Attempt forbidden field patching from client.
3. Attempt cross-user file URL access.
4. Attempt unscoped payroll/timekeeping query.

## 15.3 Data integrity tests

1. Duplicate request constraints behave correctly.
2. Invalid date ranges are blocked.
3. File validation blocks invalid MIME/size.
4. Activity/access logs are created for audited actions.

---

## 16) Rollout Strategy

Recommended implementation order:
1. Foundation + context + auth guards
2. Dashboard + notifications
3. Personal information
4. Document management
5. Timekeeping workflows
6. Payroll + payslip retrieval
7. PRAISE + reports + settings
8. Security hardening + QA pass

Deployment strategy:
- ship read-only views first
- enable mutation flows after phase-specific validation

---

## 17) Definition of Done (Employee Module)

Employee backend integration is complete when:
1. Employee pages no longer rely on static/demo core data.
2. Critical forms/actions are backend-wired (no placeholder action targets).
3. Employee only sees and mutates own allowed data.
4. UX states are complete and consistent across employee pages.
5. Security/RLS checklist passes.
6. End-to-end employee test script passes in staging.

---

## 18) Fast-Start Task Breakdown (Copy to issue tracker)

1. Create employee backend lib and context helpers
2. Add per-page bootstrap/actions/data structure
3. Integrate dashboard summary + recent activity queries
4. Integrate personal information GET/POST
5. Integrate document list/upload/version actions
6. Integrate timekeeping + leave/adjustment workflows
7. Integrate payroll and payslip retrieval
8. Integrate notifications and support actions
9. Integrate settings safe preferences
10. Complete security + performance + QA gate