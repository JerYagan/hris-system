# Admin Modules Comprehensive Revision Plan

This document is the unified execution plan for Admin module revisions and improvements, based on:
- [Admin_Revisions.md](Admin_Revisions.md)
- [Admin_Revisions_2.md](Admin_Revisions_2.md)
- [User_Privileges.md](User_Privileges.md)
- [FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md](FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md)

---

## 1) Plan Objectives

1. Implement all requested Admin-facing revisions across modules.
2. Align workflows and actions to Admin/Staff/Employee/Applicant privileges.
3. Standardize statuses, audit trails, and confirmation flows system-wide.
4. Improve frontend performance by implementing localized JS loading for Admin pages.
5. Deliver changes in prioritized phases with measurable acceptance criteria.

---

## 2) Scope Lock (Admin Area)

Target pages in `pages/admin/`:
- `dashboard.php`
- `recruitment.php`
- `applicants.php`
- `applicant-tracking.php`
- `evaluation.php`
- `document-management.php`
- `personal-information.php`
- `timekeeping.php`
- `payroll-management.php`
- `report-analytics.php`
- `praise.php`
- `praise-employee-evaluation.php`
- `praise-reports-analytics.php`
- `learning-and-development.php`
- `user-management.php`
- `notifications.php`
- `create-announcement.php`
- `profile.php`
- `settings.php`

Global admin UX/layout scope:
- Header branding updates
- Global terminology updates
- Status-change confirmation and warning standard

---

## 3) Cross-Module Foundations (Implement First)

## 3.1 Branding and terminology
- Add Bagong Pilipinas logo to admin header (ATI clean logo placement aligned with current header layout).
- Replace **Department** labels with **Division** in admin modules/forms/filters/tables.
- Keep database column names unchanged unless migration is explicitly required; prioritize UI label updates.

## 3.2 Global status and workflow standards
- Add confirmation modal before all status-changing actions:
  - Approve/Reject
  - Forward/Return for revision
  - Archive/Restore
  - Hire/Reject
  - Leave/Time adjustment final decisions
- Require reason input for override/final rejection when mandated by privileges and audit policy.
- Add immutable audit log entry for every status mutation.

## 3.2.1 Two-step approval chain (Staff recommendation -> Admin final decision)
Apply this pattern to modules where Staff prepares/recommends and Admin is final authority:
- Staff action sets intermediate state (examples: `For Admin Approval`, `Forwarded to Admin`, `For Final Review`).
- Admin action sets final state (examples: `Approved`, `Rejected`, `Needs Revision`).
- Final admin decision must automatically reflect back to Staff through:
  - updated queue/status badges
  - decision history/timeline
  - notification entry (with decision timestamp and actor)
- Staff cannot directly set final Admin-only states.

Minimum audit fields per transition:
- `module`
- `record_id`
- `action_by_role` (`staff`/`admin`)
- `previous_status`
- `new_status`
- `remarks_or_reason`
- `acted_at`

## 3.3 Status dictionary normalization
Establish one status dictionary and map all modules to it:
- Recruitment pipeline: `Applied`, `Verified`, `Interview`, `Evaluation`, `For Approval`, `Hired`, `Rejected`
- Documents: `Draft`, `Submitted`, `Under Review`, `Needs Revision`, `Approved`, `Rejected`, `Archived`
- Leave/adjustment: `Pending`, `Approved`, `Rejected`, `Locked`
- Job posting lifecycle: `Open`, `Closed`, `Archived`

## 3.4 Definition lock (to remove ambiguity)
- **Closed** = no new applications; record remains active for reports/history.
- **Archived** = hidden from active operational queues; read-only except restore.

---

## 4) Privilege Alignment Baseline (from User_Privileges)

Apply these policy rules while implementing all revisions:
- Admin remains final authority for hiring decisions, payroll batch approval, leave/time adjustment final decisions, and recognition approvals.
- Staff handles operational preparation (review/forward/recommend) but not final authority actions.
- Employee and applicant views remain self-service and read/submit only where defined.
- Any Admin override action must be logged with actor, timestamp, previous value, new value, and reason.

---

## 5) Module-by-Module Revision Backlog

### 5.0 Module Revision Change Logs

Use this index to jump to module-level revision logs. Each module section below includes its own `Revision Change Log` subsection.

- [Dashboard Revision Log](#51-dashboard)
- [Recruitment Revision Log](#52-recruitment-job-listing-applicants-evaluation-linkage)
- [Rule-Based Qualification Revision Log](#53-rule-based-qualification-engine-recruitment--evaluation)
- [Applicant Tracking Revision Log](#54-applicant-tracking--applicants--evaluation)
- [Document Management Revision Log](#55-document-management)
- [Personal Information Revision Log](#56-personal-information)
- [Timekeeping Revision Log](#57-timekeeping)
- [Payroll Management Revision Log](#58-payroll-management)
- [Reports and Analytics Revision Log](#59-reports-and-analytics)
- [Praise / Awards Revision Log](#510-praise--awards--employee-evaluation)
- [Learning and Development Revision Log](#511-learning-and-development)
- [User Management Revision Log](#512-user-management)
- [My Profile Revision Log](#513-my-profile)
- [Announcements and Notifications Revision Log](#514-announcements-and-notifications)

## 5.1 Dashboard
Required changes:
- Replace cards:
  - `Attendance Alerts` → `Pending Time Adjustments`
  - `Draft Announcements` → `Pending Recruitment Decision`
  - `Unread Notifications` → `Pending Documents`
  - `Absent` → `Absence Rate This Week (%)`
- Add cards:
  - `Total Employees`
  - `On Leave`
- Add `Pending Document for Verification` to available space (as requested in revisions).
- Change absent presentation to include **today** context where applicable.
- Align notification statuses with Notifications module statuses.
- Show chart data timestamps.
- Attendance chart label format:
  - `Attendance Summary - <DATE>`
  - `(Auto-updated <DATE> at 5:30AM)`
- Recruitment chart auto-update schedule at `12:00 NN`.
- Add settings to configure chart update times.
- Convert plantilla summary visualization to donut chart.

Acceptance criteria:
- Dashboard cards are data-driven and mapped to standardized statuses.
- Each chart displays last updated timestamp and uses configured update schedule.

Revision Change Log:
- [2026-02-24] Dashboard scope retained; no structural backlog change in this revision.

## 5.2 Recruitment (Job Listing, Applicants, Evaluation linkage)
Required changes:
- Fix New Job error: `Selected office or position is invalid`.
- Clarify and keep one field only: `Job Title` or `Job Position` (single canonical field in UI + backend validation).
- Show only confirmed available positions in dropdown.
- Add employment classification in listings:
  - `Plantilla/Permanent`
  - `Contractual/COS`
- Include requirements in job listing and application review:
  - Eligibility
  - Education
  - Experience
  - Training
- During application, display required documents:
  - PDS
  - WES
  - Eligibility (CSC/PRC)
  - Transcript of Records
- Remove redundant bottom colored filters.
- Standardize statuses across Applicants, Evaluation, Applicant Tracking.
- Add in job listing view:
  - Applicant count/list
  - Requirement profile
  - Recommendation score
- Add `Add as Employee` action when applicant status is `Hired`.
- Auto-extract PDS data on conversion to employee and connect to:
  - Personal Information
  - Document Management (201 file initialization)
- Add applicant profile preview for submitted PDS/career/work experience.
- Add email notification workflow for recommended/approved next-stage applicants (final review and office signing notice).

Acceptance criteria:
- Recruitment can create valid jobs without office/position mismatch errors.
- Global requirements and threshold drive evaluation inputs and recommendation output for MVP.

Revision Change Log:
- [2026-02-24] Confirmed MVP direction: global requirements + threshold (position-specific criteria deferred).

## 5.3 Rule-Based Qualification Engine (Recruitment + Evaluation)
Functional requirements:
- Remove UI label text `Rule-Based Algorithm` where requested, while retaining backend logic.
- Admin can configure one global minimum criteria set for MVP:
  - Required eligibility
  - Required education years
  - Required training hours
  - Required experience years
- Structured applicant fields (encoded by applicant/admin verification):
  - Eligibility type
  - Education years/level
  - Training hours
  - Experience years
- Algorithm checks encoded values first; uploaded files are for human verification.
- Default scoring model:
  - Eligibility: 25%
  - Education: 25%
  - Training: 25%
  - Experience: 25%
- Qualification threshold:
  - `score >= 75` → `Qualified for Evaluation`
  - otherwise `Not Qualified`
- Remove exam score from criteria set.
- Support Admin remarks and reject reason that is visible to applicant.
- Defer position-specific criteria to post-launch enhancement.

Core logic reference:
```text
IF eligibility == required_eligibility
AND education >= required_education
AND training_hours >= required_training_hours
AND experience_years >= required_experience_years
THEN status = Qualified for Evaluation
ELSE status = Not Qualified
```

Acceptance criteria:
- Global criteria are configurable by Admin and immediately effective for new evaluations.
- Decision output, score breakdown, and reviewer remarks are auditable.

Revision Change Log:
- [2026-02-24] Criteria model finalized to one global set (eligibility, education years, training hours, experience years) + threshold.

## 5.4 Applicant Tracking + Applicants + Evaluation
Required changes:
- Keep one synchronized status timeline for the full pipeline.
- Add verification and forwarding metadata capture:
  - who verified / date verified
  - who forwarded / date forwarded
- Ensure Admin can set interview schedule and monitor history with read-only oversight plus override where allowed.
- Ensure Staff can record interview results and forward applicants based on privileges.
- Keep Admin final approval in evaluation chain.

Acceptance criteria:
- Timeline and status history are consistent across all three pages.
- Privilege boundaries are enforced server-side and reflected in UI actions.

Revision Change Log:
- [2026-02-24] Tracking scope unchanged; status/timeline synchronization requirement remains active.

## 5.5 Document Management
Required changes:
- Add search and filters for uploads/review queues.
- Clarify archived behavior and move archive warning banner into review view.
- Restrict final review actions to `Approve` or `Reject`.
- Return rejected documents to employee with notes.
- Fix status bug: `Needs Revision` incorrectly shown as `Draft`.
- Enforce document review handoff flow:
  - Staff verifies/completes review -> `For Admin Approval`
  - Admin performs final decision -> `Approved`/`Rejected`
  - Final decision is reflected back to Staff queues/history and notifications
- Add separate admin tab for all employee records/submissions organized by 201 file categories:
  - PDS
  - SSS
  - Pag-IBIG
  - PhilHealth
  - NBI
  - Mayor’s Permit
  - Medical
  - Drug Test
  - Health Card
  - Cedula
  - Resume/CV

Acceptance criteria:
- Document statuses render correctly across queues and detail views.
- Admin can review both workflow queue and 201-category inventory view.
- Staff can clearly see when Admin has finalized approval/rejection for forwarded documents.

Revision Change Log:
- [2026-02-24] `Needs Revision` status retained as required canonical document workflow state.

## 5.6 Personal Information
Required changes:
- Fix tab highlight bug (Personal Information should not highlight Document Management).
- Convert fields to searchable dropdowns:
  - Place of Birth
  - Civil Status
  - Blood Type
  - City/Municipality
  - Barangay
- Auto-fill ZIP code based on city/municipality + barangay.
- Add checkbox: permanent address same as residential address.
- Support requirements/document upload tie-in and 201 file linking.
- Prevent Add Employee if contact number and email are missing.
- Investigate and fix inability to add new employee record.
- Provide duplicate employee record deletion/merge flow with audit log.
- Add optional spouse extension for Muslim profile requirement and print-ready PDS output option.

Acceptance criteria:
- Employee creation enforces required contact fields.
- Address and geographic selectors provide searchable UX with ZIP auto-fill.

Revision Change Log:
- [2026-02-24] Contact-field guardrail remains required for Add Employee (email or mobile).

## 5.7 Timekeeping
Required changes:
- Show suspensions/holidays per employee per day.
- Remove Late status in standard attendance logic (flex schedule), while preserving policy exceptions if configured.
- Leave request default status = `Pending`.
- Lock time adjustment decisions after final submission.
- Rejected time adjustments require new submission.
- Rejected leave requests cannot be modified.
- Add complete employee timekeeping history view.
- Connect timekeeping outputs to payroll inputs for deduction computation.
- Enforce handoff flow for staff-assisted approvals:
  - Staff reviews/recommends leave, overtime, and time-adjustment requests -> `For Admin Approval`
  - Admin issues final decision -> `Approved` / `Rejected`
  - Final Admin decision is reflected back to Staff queues, timelines, and notifications
- Official Business (OB) handling:
  - Time in before leaving
  - Time in after event
  - Count as whole day if tagged OB
- Add Official Business management options for admin and pending request view.
- Pending Leave Request preview should include leave type and description.
- RFID enhancement plan:
  - card tap match
  - OTP verification (email/SMS, 1–2 minute validity)
  - 3-attempt control and denial handling

Acceptance criteria:
- Attendance/leave/time-adjustment flows follow locked status rules.
- OB and holiday/suspension handling is reflected in daily attendance results.
- Staff can track Admin final decisions for requests they reviewed/forwarded.

Revision Change Log:
- [2026-02-24] Timekeeping scope unchanged; no-late policy and OB handling remain in planned implementation.

## 5.8 Payroll Management
Required changes:
- Confirm and implement timekeeping-payroll integration.
- Automatic salary computation by salary grade.
- Apply deductions (including absences/leave impacts based on policy).
- Show full payroll computation breakdown in admin UI.
- Enable payroll summary/payslip sending to employee email with secure handling.
- Ensure Admin final approval of payroll batches and full audit trail logging.
- Enforce payroll handoff flow:
  - Staff computes/prepares payroll and submits batch -> `For Admin Approval`
  - Admin performs final batch decision -> `Approved for Release` / `Returned for Correction`
  - Final Admin decision is reflected back to Staff payroll queue/history and notifications

Acceptance criteria:
- Payroll run includes traceable source inputs (attendance, salary setup, deductions).
- Admin can inspect and approve full breakdown prior to release.
- Staff can see final Admin disposition of submitted payroll batches with decision timestamp.

Revision Change Log:
- [2026-02-24] Payroll approval chain retained with Admin final authority and staff feedback loop.

## 5.9 Reports and Analytics
Required changes:
- Rename module/page labels to `REPORTS and Analytics`.
- Remove late incidents from report outputs where policy requires no-late handling.
- Include audit logs and cross-module KPI reporting consistent with Admin privileges.

Acceptance criteria:
- Reports naming and content align with updated policy and statuses.

Revision Change Log:
- [2026-02-24] Reports module naming and no-late output constraints retained without new change.

## 5.10 Praise / Awards / Employee Evaluation
Required changes:
- Add admin evaluate/nominate action.
- Improve New Cycle and Category button UI consistency.
- Clarify publication destination (Notification and/or Dashboard) and make behavior explicit.
- Move Praise Reports entry to Reports and Analytics module.
- Keep evaluation fields minimal in relevant views:
  - Who
  - When
  - Final Rating
- Ensure Admin approval and finalization authority over recognition workflows.

Acceptance criteria:
- Praise workflows are privilege-aligned and have clear publish destinations.

Revision Change Log:
- [2026-02-24] Praise backlog remains aligned to privilege finalization and publish destination clarity.

## 5.11 Learning and Development
Required changes:
- Add New Training creation with advance email notification.
- Use a single attendance log per employee per training.
- Add View History action.
- Move Reports and Analytics section to top of module.
- Ensure L&D audit trail logging for creation, updates, attendance changes, and report access.

Acceptance criteria:
- Admin can create training, notify participants, and track attendance/history in one flow.

Revision Change Log:
- [2026-02-24] L&D scope unchanged; notification + attendance-history requirements remain active.

## 5.12 User Management
Required changes:
- Employment classification options:
  - Plantilla/Permanent
  - Contractual/COS
- Remove Office Type field if deployment is Central Office only.
- Auto-populate Division when user is selected.
- Add validation that admin account should not be disabled.
- Enforce max admin count = 2 active admins.
- Keep role assignment and support-ticket routing behavior aligned with privileges.

Acceptance criteria:
- User creation/edit forms enforce classification, division, and admin-limit controls.

Revision Change Log:
- [2026-02-24] User Management retains admin-limit and division-first terminology requirements.

## 5.13 My Profile
Required changes:
- Add admin password change feature.
- Clarify verification method (email or phone) and make recovery method explicit in UI.
- Preserve login activity and account preferences in profile/settings scope.

Acceptance criteria:
- Admin can securely update password and understand active verification path.

Revision Change Log:
- [2026-02-24] Profile security scope unchanged; password update + verification clarity remain required.

## 5.14 Announcements and Notifications
Required changes:
- Allow announcement visibility targeting to specific employees/groups.
- Keep dashboard/notification alignment for publication destination.

Acceptance criteria:
- Admin can choose audience scope and users only see intended announcements.

Revision Change Log:
- [2026-02-24] Announcement targeting requirement retained; no additional scope change in this revision.

---

## 6) Localized JS Implementation Plan (Admin Performance Requirement)

This is required to avoid loading all Admin JS on every page.

## 6.1 Target architecture
Create/complete localized entry points under:

```text
assets/js/admin/
  dashboard/index.js
  recruitment/index.js
  applicants/index.js
  applicant-tracking/index.js
  evaluation/index.js
  document-management/index.js
  personal-information/index.js
  timekeeping/index.js
  payroll-management/index.js
  report-analytics/index.js
  praise/index.js
  praise-employee-evaluation/index.js
  praise-reports-analytics/index.js
  learning-and-development/index.js
  user-management/index.js
  notifications/index.js
  create-announcement/index.js
  profile/index.js
  settings/index.js
```

Use shared utilities only from `assets/js/shared/*`.

## 6.2 Page boot contract
Each admin page should expose:
```html
<body data-role="admin" data-page="<module-slug>">
```
Load only one bootstrap script:
```html
<script type="module" src="/hris-system/assets/js/bootstrap.js" defer></script>
```

`bootstrap.js` dynamically imports only the active admin module by `data-page`.

## 6.3 Performance standards
- Remove universal global admin mega-script includes.
- Lazy-load expensive libraries (charts, table plugins, date pickers, alerts) only in pages that use them.
- Standardize alert/confirmation UX with **SweetAlert2** for status-changing and destructive actions.
- Standardize date/time picking UX with **Flatpickr** on applicable admin forms (recruitment schedules, filters, and date fields).
- Server-side paginate/filter large tables (default 10/20 rows).
- Load summary counts via dedicated aggregate endpoints.
- Add debounce for search/filter input handlers.
- Add minimal short cache (30–60 sec) for repeated top-nav badge/profile requests.

## 6.4 UX state compliance per page module
Each localized module must support:
- loading + skeleton
- empty
- filter-empty
- error (retry)
- success confirmation

## 6.5 Migration sequence for localized JS
1. Build/verify `assets/js/bootstrap.js` dynamic loader map for all admin pages.
2. For each `pages/admin/*.php` file:
   - remove non-required global module scripts
   - set `data-role` and `data-page`
   - route behavior into page-localized `index.js`
  - use SweetAlert2 for confirmation modals and Flatpickr for date inputs where needed
3. Move page-specific logic from shared/global scripts into module-local files.
4. Keep shared primitives in `assets/js/shared/*` only.
5. Validate no page loads unrelated admin modules.

Acceptance criteria:
- Opening one admin page downloads/executes only its module + shared dependencies.
- No regression in existing UI interactions after localization.

---

## 7) Delivery Phases and Priorities

## Phase 0 – Foundation and policy lock
- Branding/logo, Department→Division, status dictionary, confirmation modal standard, audit log hooks.

## Phase 1 – High-risk functional blockers
- Recruitment New Job invalid office/position bug.
- Personal Information add employee failure.
- Document status bug (`Needs Revision` vs `Draft`).
- Tab highlight bug in personal information.

## Phase 2 – Recruitment and evaluation core
- Global criteria rule engine (eligibility, education, training, experience), scoring threshold, applicant previews, email next-stage notices.

## Phase 3 – Personal Info + Document Management integration
- Add-as-Employee flow, PDS extraction, 201 linkage, searchable geo fields, ZIP auto-fill.

## Phase 4 – Timekeeping and payroll dependency chain
- No-late policy implementation, OB handling, leave/time adjustment locks, payroll deduction integration.

## Phase 5 – Dashboard and reports alignment
- Card replacements/additions, chart scheduling and timestamps, donut chart, reports rename and data rules.

## Phase 6 – Praise, L&D, User Management, Profile, Announcements
- Remaining module-specific enhancements and privilege hardening.

## Phase 7 – Localized JS migration and performance pass
- Admin per-page JS rollout, lazy-loading enforcement, UX state compliance checks.

## Phase 8 – QA, security checks, and rollout readiness
- Cross-module regression, role-based action checks, audit log validation, performance baseline verification.

---

## 8) Data, Security, and Audit Requirements

- Every final decision action by Admin must be audit logged.
- Override actions require reason and are immutable in logs.
- Email notifications should use secure templates and avoid exposing sensitive payloads.
- Payslip/document delivery should follow secure access and encryption-safe handling policy.
- RFID + OTP rollout should be implemented as a controlled phase with fallback/manual override policy.

---

## 9) QA and Validation Checklist

## 9.1 Functional QA
- Verify each module acceptance criteria in Section 5.
- Verify status transitions are valid and blocked when invalid.
- Verify privilege-restricted actions are server-enforced.

## 9.2 Integration QA
- Recruitment → Employee conversion creates linked profile and initial document records.
- Timekeeping outputs feed payroll calculations correctly.
- Announcement targeting displays only for intended users.

## 9.3 Performance QA
- Confirm admin pages only load localized JS modules.
- Measure initial JS payload reduction before vs after migration.
- Verify table pages are server-paginated and remain responsive.

## 9.4 Security/Audit QA
- Confirm all admin overrides and approvals generate complete audit logs.
- Confirm restricted views/actions cannot be bypassed by direct requests.

---

## 10) Open Decision Items (Resolve Early)

1. Confirm exact canonical field naming: `Job Title` vs `Position Title`.
2. Confirm whether dashboard publication should duplicate to both Notification and Dashboard by default.
3. Confirm policy-level formula for absence deduction in payroll (per day/hour basis).
4. Confirm if RFID+OTP launches in MVP scope or post-MVP phase.
5. Confirm final official 201 list for production enforcement (current list marked non-official in revisions).

---

## 11) Definition of Done (Admin Revisions)

Admin revisions are considered complete when:
- All module requirements in Section 5 are implemented or explicitly deferred with approved reason.
- Privilege alignment from `User_Privileges.md` is enforced in UI and backend.
- Localized JS architecture is active for all Admin pages.
- Cross-module workflows (Recruitment→Personal Information→Documents; Timekeeping→Payroll) are verified.
- Audit logs, confirmation flows, and status dictionary are consistent across modules.

