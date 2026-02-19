# Staff Backend Integration Steps (Execution Plan)

This guide is the execution plan to fully integrate backend logic for the Staff module, aligned with current Admin implementation patterns.

Target area:
- [pages/staff/dashboard.php](pages/staff/dashboard.php)
- [pages/staff/personal-information.php](pages/staff/personal-information.php)
- [pages/staff/document-management.php](pages/staff/document-management.php)
- [pages/staff/recruitment.php](pages/staff/recruitment.php)
- [pages/staff/applicant-tracking.php](pages/staff/applicant-tracking.php)
- [pages/staff/applicant-registration.php](pages/staff/applicant-registration.php)
- [pages/staff/timekeeping.php](pages/staff/timekeeping.php)
- [pages/staff/payroll-management.php](pages/staff/payroll-management.php)
- [pages/staff/evaluation.php](pages/staff/evaluation.php)
- [pages/staff/praise.php](pages/staff/praise.php)
- [pages/staff/reports.php](pages/staff/reports.php)
- [pages/staff/notifications.php](pages/staff/notifications.php)
- [pages/staff/profile.php](pages/staff/profile.php)

Schema source:
- [SUPABASE_SCHEMA.sql](SUPABASE_SCHEMA.sql)

Admin UI reference (tables/search/filter + review modals):
- [pages/admin/includes/timekeeping/view.php](pages/admin/includes/timekeeping/view.php)

---

## Implementation Status

- ✅ Phase 1 completed (Shared staff backend foundation)
- ✅ Phase 2 completed (Identity and role context)
- ✅ Phase 3 completed (Dashboard backend)
- ✅ Phase 4 completed (Document management workflow)
- ✅ Phase 5 completed (Personal information + profile actions)
- ✅ Phase 6 completed (Recruitment + applicant tracking)
- ✅ Phase 7 completed (Timekeeping approvals + attendance)
- ✅ Phase 8 completed (Payroll management)
- ✅ Phase 9 completed (Evaluation + PRAISE)
- ✅ Phase 10 completed (Reports + exports)
- ✅ Phase 11 completed (Notifications + audit)
- ✅ Phase 12 completed (Security hardening + RLS validation)
- ✅ Phase 13 completed (UI consistency + QA completion)

### Completed Implementation Notes (Phase 1-13)

- Shared backend library scaffold is live in:
  - [pages/staff/includes/lib/common.php](pages/staff/includes/lib/common.php)
  - [pages/staff/includes/lib/supabase.php](pages/staff/includes/lib/supabase.php)
  - [pages/staff/includes/lib/staff-backend.php](pages/staff/includes/lib/staff-backend.php)
- `staff-backend.php` now provides:
  - `staffBackendContext()`
  - `resolveStaffIdentityContext(...)`
  - `staffModuleBootstrapContext()`
  - `canTransitionStatus(...)`
- Per-page bootstrap scaffolds are live for all staff modules under `pages/staff/includes/<module>/bootstrap.php`.
- All staff entry pages now include module bootstrap initialization at the top of each file.
- Phase 2 identity enforcement is now active in [pages/staff/includes/lib/staff-backend.php](pages/staff/includes/lib/staff-backend.php), including user account status validation, active/non-expired role assignment resolution, role allowlist checks, person/employment context resolution, and office-scope enforcement for non-admin staff.
- All staff module `bootstrap.php` files now fail closed when credentials or context are invalid via `renderStaffContextErrorAndExit(...)`, and expose resolved identity fields (`role`, `person`, `employment`, `office`, `position`) for subsequent phase actions/data wiring.
- User handling hardening is now active for staff actor and recruitment applicant targets:
  - Staff actor allowlist is now centralized and reused by auth/context resolution (`staff`, `hr_officer`, `supervisor`, `admin`).
  - Recruitment applicant actions now validate that target user accounts still have an active `applicant` role assignment before status mutation and notification dispatch.
- Credential-first user handling is now active in staff employee-facing workflows:
  - Shared helper support for account credential checks remains available for controlled use per module.
  - Employee-facing staff pages are currently configured to include employee-scope records even when linked user credentials are missing, to avoid over-filtering operational lists.
  - Recruitment applicant flows continue to enforce applicant-role constraints for status mutation and notification dispatch.
- Dashboard backend integration is now live in:
  - [pages/staff/includes/dashboard/actions.php](pages/staff/includes/dashboard/actions.php)
  - [pages/staff/includes/dashboard/data.php](pages/staff/includes/dashboard/data.php)
  - [pages/staff/dashboard.php](pages/staff/dashboard.php)
- Staff dashboard cards, updates, tasks, pending approvals, and recent activity now render from office-scoped Supabase data with fallback empty/error states and pre-filter links to related modules.
- Document management backend workflow is now live in:
  - [pages/staff/includes/document-management/actions.php](pages/staff/includes/document-management/actions.php)
  - [pages/staff/includes/document-management/data.php](pages/staff/includes/document-management/data.php)
  - [pages/staff/document-management.php](pages/staff/document-management.php)
  - [assets/js/staff/document-management/index.js](assets/js/staff/document-management/index.js)
- Staff document management now uses backend-driven office-scoped table data with search/status filters, a review modal contract, confirmation before status changes, redirect-based success/error alerts, and server-side transition/scope validation with activity log + notification side effects.
- Personal information + profile backend workflow is now live in:
  - [pages/staff/includes/personal-information/actions.php](pages/staff/includes/personal-information/actions.php)
  - [pages/staff/includes/personal-information/data.php](pages/staff/includes/personal-information/data.php)
  - [pages/staff/personal-information.php](pages/staff/personal-information.php)
  - [assets/js/staff/personal-information/index.js](assets/js/staff/personal-information/index.js)
  - [pages/staff/includes/profile/actions.php](pages/staff/includes/profile/actions.php)
  - [pages/staff/includes/profile/data.php](pages/staff/includes/profile/data.php)
  - [pages/staff/profile.php](pages/staff/profile.php)
  - [assets/js/staff/profile/index.js](assets/js/staff/profile/index.js)
- Staff personal information now uses backend-driven office-scoped employee list/search/filter, modal-based employee profile update flow, modal-based status transition decisions with confirmation, and server-side CSRF/scope/transition validation with notification + activity logging side effects.
- Staff profile page now loads live account/person/role context and supports profile updates through secured backend actions with audit logs and redirect-based success/error alerts.
- Recruitment + applicant tracking backend workflow is now live in:
  - [pages/staff/includes/recruitment/actions.php](pages/staff/includes/recruitment/actions.php)
  - [pages/staff/includes/recruitment/data.php](pages/staff/includes/recruitment/data.php)
  - [pages/staff/recruitment.php](pages/staff/recruitment.php)
  - [assets/js/staff/recruitment/index.js](assets/js/staff/recruitment/index.js)
  - [pages/staff/includes/applicant-tracking/actions.php](pages/staff/includes/applicant-tracking/actions.php)
  - [pages/staff/includes/applicant-tracking/data.php](pages/staff/includes/applicant-tracking/data.php)
  - [pages/staff/applicant-tracking.php](pages/staff/applicant-tracking.php)
  - [assets/js/staff/applicant-tracking/index.js](assets/js/staff/applicant-tracking/index.js)
  - [pages/staff/includes/applicant-registration/actions.php](pages/staff/includes/applicant-registration/actions.php)
  - [pages/staff/includes/applicant-registration/data.php](pages/staff/includes/applicant-registration/data.php)
  - [pages/staff/applicant-registration.php](pages/staff/applicant-registration.php)
  - [assets/js/staff/applicant-registration/index.js](assets/js/staff/applicant-registration/index.js)
- Staff recruitment and applicant pages now use office-scoped datasets with searchable/filterable tables, modal-based review decisions, status transition confirmation prompts, redirect-based success/error alerts, and server-side scope + transition validation with activity/notification side effects.
- Timekeeping backend workflow is now live in:
  - [pages/staff/includes/timekeeping/actions.php](pages/staff/includes/timekeeping/actions.php)
  - [pages/staff/includes/timekeeping/data.php](pages/staff/includes/timekeeping/data.php)
  - [pages/staff/timekeeping.php](pages/staff/timekeeping.php)
  - [assets/js/staff/timekeeping/index.js](assets/js/staff/timekeeping/index.js)
- Staff timekeeping now uses office-scoped attendance and request datasets (leave, overtime, time adjustments) with searchable/filterable tables, modal-based decision flows, confirmation prompts before status mutations, redirect-based success/error alerts, and server-side CSRF/scope/transition enforcement with activity log and notification side effects.
- Payroll management backend workflow is now live in:
  - [pages/staff/includes/payroll-management/actions.php](pages/staff/includes/payroll-management/actions.php)
  - [pages/staff/includes/payroll-management/data.php](pages/staff/includes/payroll-management/data.php)
  - [pages/staff/payroll-management.php](pages/staff/payroll-management.php)
  - [assets/js/staff/payroll-management/index.js](assets/js/staff/payroll-management/index.js)
- Staff payroll management now uses office-scoped payroll period/run datasets with searchable/filterable tables, modal-based status decisions, transition-safe confirmation prompts, redirect-based success/error alerts, and server-side CSRF/scope/transition enforcement with activity logging and notification side effects.
- Evaluation + PRAISE backend workflow is now live in:
  - [pages/staff/includes/evaluation/actions.php](pages/staff/includes/evaluation/actions.php)
  - [pages/staff/includes/evaluation/data.php](pages/staff/includes/evaluation/data.php)
  - [pages/staff/evaluation.php](pages/staff/evaluation.php)
  - [assets/js/staff/evaluation/index.js](assets/js/staff/evaluation/index.js)
  - [pages/staff/includes/praise/actions.php](pages/staff/includes/praise/actions.php)
  - [pages/staff/includes/praise/data.php](pages/staff/includes/praise/data.php)
  - [pages/staff/praise.php](pages/staff/praise.php)
  - [assets/js/staff/praise/index.js](assets/js/staff/praise/index.js)
- Staff evaluation and PRAISE pages now use office-scoped cycles/evaluations/nominations datasets with searchable/filterable tables, modal-based decision flows, confirmation prompts before status updates, redirect-based success/error alerts, and server-side CSRF/scope/transition enforcement with activity log and notification side effects.
- Reports backend workflow is now live in:
  - [pages/staff/includes/reports/actions.php](pages/staff/includes/reports/actions.php)
  - [pages/staff/includes/reports/data.php](pages/staff/includes/reports/data.php)
  - [pages/staff/reports.php](pages/staff/reports.php)
  - [assets/js/staff/reports/index.js](assets/js/staff/reports/index.js)
- Staff reports now use office-scoped workforce metrics and attendance/payroll summaries, searchable/filterable employee snapshot table, and secure export generation (PDF/XLSX/CSV) with CSRF validation, generated report tracking, and activity log side effects.
- Notifications backend workflow is now live in:
  - [pages/staff/includes/notifications/actions.php](pages/staff/includes/notifications/actions.php)
  - [pages/staff/includes/notifications/data.php](pages/staff/includes/notifications/data.php)
  - [pages/staff/notifications.php](pages/staff/notifications.php)
  - [assets/js/staff/notifications/index.js](assets/js/staff/notifications/index.js)
- Staff notifications now use account-scoped notification feed/search/status filters, mark-read and mark-all-read actions with CSRF + ownership validation, and an integrated recent audit trail table sourced from `activity_logs`.
- Phase 12 security hardening + RLS validation is now live in:
  - [pages/staff/includes/lib/staff-backend.php](pages/staff/includes/lib/staff-backend.php)
  - [pages/staff/includes/reports/actions.php](pages/staff/includes/reports/actions.php)
  - [pages/staff/includes/notifications/actions.php](pages/staff/includes/notifications/actions.php)
  - [tools/staff_phase12_rls_validator.php](tools/staff_phase12_rls_validator.php)
- Shared security helpers now centralize POST+CSRF enforcement, UUID `in.(...)` filter sanitization, and security-event logging for unexpected action attempts in high-risk write handlers.
- Staff reports/notifications handlers now use the shared helper contract for fail-closed request validation and safer batched update filter construction.
- A dedicated Phase 12 validator script now checks required RLS SQL clauses and performs read-access probes for anon vs service role on `notifications` and `generated_reports` endpoints.
- Phase 13 UI consistency + QA completion is now live in:
  - [tools/staff_phase13_ui_qa_runner.php](tools/staff_phase13_ui_qa_runner.php)
  - [STAFF_PHASE13_TEST_REPORT.md](STAFF_PHASE13_TEST_REPORT.md)
  - [pages/staff/includes/dashboard/actions.php](pages/staff/includes/dashboard/actions.php)
- Staff module QA execution now includes automated checks for page/bootstrap wiring, localized JS include coverage, and POST+CSRF guard baselines across all staff action handlers.
- Dashboard actions are now aligned with shared request guard consistency via `requireStaffPostWithCsrf(...)`.
- Phase 13 test evidence is documented in a dedicated staff report with automated/static pass results and runtime UAT checklist scenarios.

---

## 0) Current-State Findings (Why this plan is needed)

Observed from current staff pages:
- Staff pages are mostly static/demo and not yet bound to Supabase records.
- Timekeeping list/actions exist visually but still need full backend wiring and status-safe workflows.
- Admin already has stable patterns for searchable/filterable tables and review modals.
- Staff should use the same interaction shape as Admin for consistency and lower maintenance cost.

Reference checklist:
- [MODULE_CHECKLIST_ISSUES.md](MODULE_CHECKLIST_ISSUES.md)

---

## 1) Scope Lock (Staff MVP)

Implement now:
1. Staff read/write operations for assigned HR workflows only.
2. Structured table/list views with search + status filters across all list-heavy modules.
3. Standardized review/update modals for approval and status transitions.
4. Confirmation + success/error alerts for every status-changing action.
5. Audit logging for all status transitions.

Defer:
- Realtime subscriptions.
- Advanced analytics beyond current reports pages.
- New module creation outside existing staff pages.

### 1.1 User Handling Integration Plan (Staff as Employee-First + Applicant in Recruitment)

Objective:
- Keep Staff as the operational HR workspace that primarily manages employees.
- Allow Staff to handle applicants only within recruitment-related workflows.
- Keep employee and applicant self-service access in their own portals.

Role boundary contract:
1. **Staff portal access (`pages/staff/*`)**
  - Allowed actors: `staff`, `hr_officer`, `supervisor`, `admin`.
  - Disallowed actors: `employee`, `applicant`.
2. **Employee portal access (`pages/employee/*`)**
  - Allowed actors: `employee` (and `admin` for support/admin override).
3. **Applicant portal access (`pages/applicant/*`)**
  - Allowed actors: `applicant` only.

Staff handling matrix:
- **Employee-first modules (full staff processing expected):**
  - `personal-information.php`
  - `document-management.php`
  - `timekeeping.php`
  - `payroll-management.php`
  - `evaluation.php`
  - `praise.php`
  - `reports.php`
  - `notifications.php`
- **Applicant handling modules (staff acts as reviewer/processor only):**
  - `recruitment.php`
  - `applicant-tracking.php`
  - `applicant-registration.php`

Data ownership + scope rules:
1. Employee-facing staff actions must resolve target users through:
  - `people` + `employment_records` + office scope checks.
2. Applicant-facing staff actions must resolve target users through:
  - `applications` -> `applicant_profiles` -> `user_accounts`.
3. Non-admin staff must be office-scoped for both employee and applicant processing.
4. Any cross-office or unresolved identity relationship must fail closed.

Recruitment-specific applicant handling requirements:
1. Staff can review/update applicant records only via recruitment tables:
  - `job_postings`, `applications`, `application_status_history`, `application_feedback`, `application_interviews`, `application_documents`, `applicant_profiles`.
2. Applicant status transitions must use server transition guards.
3. Applicant notifications must target the linked applicant user account.
4. Staff must not use employee-only tables/flows (`employment_records`, payroll, timekeeping) for applicants.

Portal separation requirements:
1. Staff pages must continue to reject `employee` and `applicant` role sessions.
2. Employee and applicant pages must keep self-owned read/write boundaries.
3. Login role routing must always redirect users to their portal family:
  - `staff/hr_officer/supervisor/admin` -> Staff/Admin portal,
  - `employee` -> Employee portal,
  - `applicant` -> Applicant portal.

Implementation checklist (user handling hardening pass):
- [ ] Add/retain a single role allowlist source for each portal guard.
- [ ] Validate role assignment from `user_role_assignments` before module bootstrap.
- [ ] Enforce office scope on all non-admin staff reads/writes.
- [ ] Enforce applicant-only handling inside recruitment modules and avoid employee-table updates for applicant actors.
- [ ] Add regression tests/checklist items for role-to-portal access and forbidden access cases.

---

## 2) Data Model Mapping (Staff pages -> tables)

Use these existing tables in [SUPABASE_SCHEMA.sql](SUPABASE_SCHEMA.sql):

- Identity/access and scope:
  - `user_accounts`
  - `user_role_assignments`
  - `roles`
  - `user_office_scopes`
  - `people`
  - `employment_records`

- Recruitment/applicant operations:
  - `job_postings`
  - `applications`
  - `application_status_history`
  - `application_interviews`
  - `application_feedback`
  - `applicant_profiles`
  - `application_documents`

- Employee records/document workflows:
  - `documents`
  - `document_versions`
  - `document_reviews`
  - `document_categories`
  - `document_access_logs`

- Timekeeping/payroll:
  - `attendance_logs`
  - `leave_requests`
  - `time_adjustment_requests`
  - `overtime_requests`
  - `leave_types`
  - `leave_balances`
  - `payroll_periods`
  - `payroll_runs`
  - `payroll_items`
  - `payroll_adjustments`
  - `payslips`

- Evaluation/PRAISE/reports/notifications:
  - `performance_cycles`
  - `performance_evaluations`
  - `praise_awards`
  - `praise_nominations`
  - `generated_reports`
  - `notifications`
  - `activity_logs`

---

## 3) Shared UI Contract (Mandatory in All Staff Modules)

### 3.1 Table/List Layout Contract

Use the same list layout pattern as Admin reference pages:

1. Section header with title + short helper text.
2. Control row above table:
   - left: search input (`Search Requests` style label)
   - right: status filter (`All Statuses` default)
   - optional extra filters: date range, office, type.
3. Table area with:
   - stable column order
   - pill-based status rendering
   - action cell with compact action buttons.
4. Empty states:
   - no records state
   - filter-empty state.
5. Error state:
   - user-friendly message + retry action.

Minimum searchable/filterable staff pages:
- `timekeeping.php`
- `document-management.php`
- `recruitment.php`
- `applicant-tracking.php`
- `payroll-management.php`
- `reports.php`
- `notifications.php`
- `personal-information.php` (employee list section)

### 3.2 Modal Layout Contract

Follow Admin modal structure (review-style forms):

- Header row:
  - modal title (e.g., `Review Leave Request`)
  - close icon button on right.
- Body form:
  - readonly context fields first (employee, current status, date range/requested window).
  - decision selector (`Approve`, `Reject`, etc.).
  - notes textarea.
- Footer actions:
  - left/secondary: `Cancel`
  - right/primary: `Save Decision` or `Submit Review`.
- Behavior:
  - close via cancel, close icon, or backdrop click.
  - reset form state when closed.

Use this modal contract in all staff review/update actions, not only timekeeping.

### 3.3 Status-Change Confirmation + Alerts (Required)

For every status change (no exceptions), implement:

1. **Pre-submit confirmation**
   - Show confirmation dialog before submit.
   - Include entity name + old status + new status.
   - Confirm button text must reflect action (`Approve Request`, `Reject Request`, `Publish Job`, etc.).

2. **Server-side status validation**
   - Validate allowed transitions only.
   - Reject invalid transitions with safe error message.

3. **Post-submit feedback alert**
   - Success alert when update succeeds.
   - Error alert when update fails.
   - Use redirect state/message pattern (`state=success|error`, `message=...`) for PHP render flows.

4. **Audit and notification side effects**
   - Insert `activity_logs` record for each transition.
   - Insert `notifications` record for impacted user when applicable.

---

## 4) Backend Architecture Standard (Staff)

### 4.1 Create staff backend library

Create:
- `pages/staff/includes/lib/common.php`
- `pages/staff/includes/lib/supabase.php`
- `pages/staff/includes/lib/staff-backend.php`

Required helpers:
- `loadEnvFile`
- `cleanText`
- `apiRequest`
- `isSuccessful`
- `redirectWithState`
- `ensureCsrfToken`
- `isValidCsrfToken`
- `isValidUuid`
- `resolveStaffIdentityContext`
- `canTransitionStatus`

### 4.2 Per-page include structure

For each staff page, adopt:
1. `bootstrap.php`
2. `actions.php`
3. `data.php`
4. page render (`.php` view)

Create include folders under `pages/staff/includes/<module>/`.

### 4.3 Resilience baseline

In `apiRequest`:
- request timeouts
- safe JSON decode
- normalized response shape (`status`, `data`, `raw`, `error`)
- retry policy for transient GET failures.

### 4.4 Localized JS + Performance Optimization Baseline (Required)

Follow:
- [FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md](FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md)

Rules:
1. Use localized per-page scripts only (no new page logic in global script files).
  - Pattern: `assets/js/staff/<module>/index.js`
2. Load scripts only on the page that needs them.
  - Use `defer` and avoid blocking render.
3. Keep table interactions client-side and lightweight.
  - Debounce search input.
  - Reuse cached row text for filtering.
  - Avoid repeated DOM queries inside loops.
4. Lazily initialize modal and heavy UI handlers.
  - Bind modal listeners once.
  - Initialize charts/expensive components only when modal/section is opened.
5. Minimize network and render overhead.
  - Request only needed fields from Supabase (`select` discipline).
  - Paginate large datasets and enforce sensible default limits.
  - Do not refetch unchanged datasets after status updates when local patching is enough.
6. Keep UX states cheap and predictable.
  - Reuse shared state helpers for loading, empty, filter-empty, and error views.
  - Prevent duplicate submissions with in-flight guards and disabled submit buttons.

---

## 5) Phase Plan

## Phase 1 – Foundation + Identity

- Implement shared staff backend library.
- Resolve current staff user context from session.
- Validate role assignment (`staff`) and office scope.
- Fail closed when context is missing/invalid.

Deliverables:
- `pages/staff/includes/lib/*`
- per-page `bootstrap.php` scaffolds.

## Phase 2 – Dashboard Integration

- Replace static dashboard cards and activity widgets with live data.
- Show office-scoped pending counts (applications, documents, leave, payroll tasks).
- Add links to module pages with pre-applied filters where applicable.

Deliverables:
- `pages/staff/dashboard.php`
- `pages/staff/includes/dashboard/{actions.php,data.php}`

## Phase 3 – Personal Information + Profile

- Wire employee list/search/filter.
- Add profile view/update modal using modal contract.
- Add status transition guards for employee-related updates (if permitted by scope).

Deliverables:
- `pages/staff/personal-information.php`
- `pages/staff/profile.php`

## Phase 4 – Document Management

- List employee documents with category/status filters.
- Implement review modal (`approve/reject/needs_revision`).
- Enforce confirmation + alerts on review decision submit.

Deliverables:
- `pages/staff/document-management.php`
- `pages/staff/includes/document-management/*`

## Phase 5 – Recruitment + Applicant Tracking

- Wire requisitions/postings/applications datasets.
- Implement application status update workflow with transition matrix.
- Add review modal + confirmation + success/error alerts.

Deliverables:
- `pages/staff/recruitment.php`
- `pages/staff/applicant-tracking.php`
- `pages/staff/applicant-registration.php`

## Phase 6 – Timekeeping

- Integrate attendance list, leave requests, overtime requests, time adjustments.
- Reuse Admin table layout pattern (search + status filter + review modal).
- Require confirmation before status decisions.

Deliverables:
- `pages/staff/timekeeping.php`
- `pages/staff/includes/timekeeping/*`

## Phase 7 – Payroll Management

- Integrate payroll period/run/item listings and status updates.
- Add controlled status transitions (`open -> processing -> posted -> closed`).
- Require confirmation + alert feedback for each update.

Deliverables:
- `pages/staff/payroll-management.php`
- `pages/staff/includes/payroll-management/*`

## Phase 8 – Evaluation + PRAISE

- Integrate evaluation cycles/records and praise nominations.
- Implement decision modal flows and status guards.
- Add activity and notification writes for decisions.

Deliverables:
- `pages/staff/evaluation.php`
- `pages/staff/praise.php`

## Phase 9 – Reports + Notifications

- Wire reports request/queue history and export links.
- Wire notifications list with read/unread status actions.
- Add confirmations for status-mutating bulk actions.

Deliverables:
- `pages/staff/reports.php`
- `pages/staff/notifications.php`

## Phase 10 – Security + QA Completion

- CSRF on all write actions.
- Input and UUID validation.
- Ownership/office-scope checks on all reads/writes.
- Execute regression QA against table and modal contracts.

Deliverables:
- hardening pass across all `pages/staff/includes/*` files
- final checklist report.

---

## 6) Status Transition Matrix (Minimum Rules)

Enforce in server logic via `canTransitionStatus($entity, $old, $new)`.

- `leave_requests`: pending -> approved/rejected/cancelled
- `time_adjustment_requests`: pending -> approved/rejected/needs_revision
- `overtime_requests`: pending -> approved/rejected/cancelled
- `applications`: submitted -> screening -> shortlisted -> interview -> offer -> hired/rejected
- `documents`: draft/submitted -> approved/rejected/needs_revision
- `payroll_periods`: open -> processing -> posted -> closed
- `payroll_runs`: draft -> computed -> approved -> released/cancelled

Invalid transitions must:
1. block update,
2. show error alert,
3. keep data unchanged.

---

## 7) Acceptance Criteria

A. **UI consistency**
- All staff list modules use standard table layout with search + filter controls.
- All staff decision/update forms use standard modal layout.

B. **Status safety**
- Every status mutation requires confirmation before submit.
- Every mutation shows success/error alert after submit.
- Every mutation writes `activity_logs`.

C. **Security and integrity**
- All write operations enforce CSRF + role/scope checks.
- No cross-office unauthorized updates.
- Invalid status transitions are blocked.

D. **Operational quality**
- Empty/filter-empty/error states are visible and usable.
- No page-level fatal errors when data sources are empty.
- All core staff module actions are testable end-to-end.

E. **Performance compliance**
- Staff modules use localized JS entry points and avoid adding page logic to global scripts.
- Search/filter/modal interactions remain responsive on large tables.
- Queries use field-limited selects + pagination and avoid unnecessary refetch cycles.

---

## 8) Suggested Commit Sequence

1. `feat(staff): add shared backend foundation and identity context resolver`
2. `feat(staff): integrate dashboard and personal information data flows`
3. `feat(staff): integrate document and recruitment status workflows`
4. `feat(staff): integrate timekeeping and payroll status decision flows`
5. `feat(staff): standardize table filters, review modals, and status alerts`
6. `chore(staff): harden validation, transition guards, and audit logging`
7. `test(staff): complete backend integration QA checklist`

---

## 9) Commit Comment (Phase 1-13)

- `feat(staff): scaffold Phase 1 backend foundation with shared lib, identity resolver, and per-module bootstrap wiring`
- `feat(staff): complete Phase 2 identity-role context enforcement with fail-closed staff bootstrap validation`
- `feat(staff): complete Phase 3 dashboard backend wiring with scoped metrics, tasks, approvals, and activity feeds`
- `feat(staff): complete Phase 4 document-management workflow with scoped data, review modal, and status transition safeguards`
- `feat(staff): complete Phase 5 personal-information and profile workflows with scoped employee updates, modal actions, and audit-safe backend handlers`
- `feat(staff): complete Phase 6 recruitment and applicant-tracking workflows with scoped queues, transition-safe modals, and audit logging`
- `feat(staff): complete Phase 7 timekeeping approvals and attendance workflows with scoped request queues, review modals, and transition-safe decision actions`
- `feat(staff): complete Phase 8 payroll-management workflows with scoped period/run queues, review modals, and transition-safe status decision actions`
- `feat(staff): complete Phase 9 evaluation and praise workflows with scoped review queues, decision modals, and transition-safe status actions`
- `feat(staff): complete Phase 10 reports and exports workflow with scoped analytics, export generation, and audit-safe report logging`
- `feat(staff): complete Phase 11 notifications and audit workflow with scoped inbox actions, read-state controls, and staff activity trail`
- `chore(staff): complete Phase 12 security hardening with shared request guards and staff RLS validation runner`
- `test(staff): complete Phase 13 UI consistency and QA pass with staff module checker and execution report`
