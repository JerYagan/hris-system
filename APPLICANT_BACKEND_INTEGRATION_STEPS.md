# Applicant Backend Integration Steps (Full Implementation)

This guide is the step-by-step execution plan to fully integrate backend logic for the Applicant module first, while admin/staff/employee enhancements are deferred.

Target area:
- [pages/applicant/dashboard.php](pages/applicant/dashboard.php)
- [pages/applicant/job-list.php](pages/applicant/job-list.php)
- [pages/applicant/job-view.php](pages/applicant/job-view.php)
- [pages/applicant/apply.php](pages/applicant/apply.php)
- [pages/applicant/applications.php](pages/applicant/applications.php)
- [pages/applicant/application-feedback.php](pages/applicant/application-feedback.php)
- [pages/applicant/notifications.php](pages/applicant/notifications.php)
- [pages/applicant/profile.php](pages/applicant/profile.php)
- [pages/applicant/support.php](pages/applicant/support.php)

Schema source:
- [SUPABASE_SCHEMA.sql](SUPABASE_SCHEMA.sql)

---

## Implementation Status

- ✅ Phase 1 scaffold implemented in codebase (backend lib + per-page bootstrap/actions/data wiring)
- ✅ Phase 2 completed (Identity and Profile Integration)
- ✅ Phase 3 completed (Job Discovery Backend: list + details)
- ✅ Phase 4 completed (Application submission core flow + storage upload)
- ✅ Phase 5 completed (My Applications tracker + Feedback page)
- ✅ Phase 6 completed (Notifications and Support)
- ✅ Phase 7 completed (Auth and registration adjustments with separate applicant signup path)
- ✅ Phase 7.5 completed (Applicant dashboard and topnav personalization)
- ✅ Phase 10 completed (Security and RLS checklist hardening)
- ✅ Phase 11 completed (UX state requirements across applicant pages)
- ✅ Phase 12 completed (Functional + security + integrity test execution)
- ✅ Phase 16 completed (Mobile responsiveness integration across applicant pages)
- ✅ Phase 17 first-pass implemented (Job listing + applicant data capture + PDS/requirements + dashboard/profile enhancements)
- ⏭️ Next active phase: Phase 17 QA iteration and refinements

### Completed Implementation Notes (as of latest update)

- Profile backend is live in:
  - [pages/applicant/profile.php](pages/applicant/profile.php)
  - [pages/applicant/includes/profile/actions.php](pages/applicant/includes/profile/actions.php)
  - [pages/applicant/includes/profile/data.php](pages/applicant/includes/profile/data.php)
- Job discovery backend is live in:
  - [pages/applicant/job-list.php](pages/applicant/job-list.php)
  - [pages/applicant/includes/job-list/data.php](pages/applicant/includes/job-list/data.php)
  - [pages/applicant/job-view.php](pages/applicant/job-view.php)
  - [pages/applicant/includes/job-view/data.php](pages/applicant/includes/job-view/data.php)
- Apply submission backend is live in:
  - [pages/applicant/apply.php](pages/applicant/apply.php)
  - [pages/applicant/includes/apply/actions.php](pages/applicant/includes/apply/actions.php)
  - [pages/applicant/includes/apply/data.php](pages/applicant/includes/apply/data.php)
  - [pages/applicant/includes/lib/common.php](pages/applicant/includes/lib/common.php) (file upload helpers)
- Applications tracker backend is live in:
  - [pages/applicant/applications.php](pages/applicant/applications.php)
  - [pages/applicant/includes/applications/data.php](pages/applicant/includes/applications/data.php)
- Application feedback backend is live in:
  - [pages/applicant/application-feedback.php](pages/applicant/application-feedback.php)
  - [pages/applicant/includes/application-feedback/data.php](pages/applicant/includes/application-feedback/data.php)
- Notifications backend is live in:
  - [pages/applicant/notifications.php](pages/applicant/notifications.php)
  - [pages/applicant/includes/notifications/actions.php](pages/applicant/includes/notifications/actions.php)
  - [pages/applicant/includes/notifications/data.php](pages/applicant/includes/notifications/data.php)
- Support backend is live in:
  - [pages/applicant/support.php](pages/applicant/support.php)
  - [pages/applicant/includes/support/actions.php](pages/applicant/includes/support/actions.php)
  - [pages/applicant/includes/support/data.php](pages/applicant/includes/support/data.php)
- Applicant registration path is live in:
  - [pages/auth/register-applicant.php](pages/auth/register-applicant.php)
  - [pages/auth/register-applicant-handler.php](pages/auth/register-applicant-handler.php)
  - [pages/auth/login.php](pages/auth/login.php) (signup link routing)
  - [pages/auth/register.php](pages/auth/register.php) (quick applicant path link)
- Applicant dashboard and topnav personalization is live in:
  - [pages/applicant/includes/topnav.php](pages/applicant/includes/topnav.php)
  - [pages/applicant/includes/dashboard/data.php](pages/applicant/includes/dashboard/data.php)
  - [pages/applicant/dashboard.php](pages/applicant/dashboard.php)
- Phase 10 security hardening is live in:
  - [pages/applicant/includes/lib/common.php](pages/applicant/includes/lib/common.php)
  - [pages/applicant/includes/apply/actions.php](pages/applicant/includes/apply/actions.php)
  - [pages/applicant/includes/profile/actions.php](pages/applicant/includes/profile/actions.php)
  - [pages/applicant/includes/notifications/actions.php](pages/applicant/includes/notifications/actions.php)
  - [pages/applicant/includes/support/actions.php](pages/applicant/includes/support/actions.php)
  - [pages/applicant/includes/applications/data.php](pages/applicant/includes/applications/data.php)
  - [pages/applicant/includes/application-feedback/data.php](pages/applicant/includes/application-feedback/data.php)
  - [pages/applicant/includes/apply/data.php](pages/applicant/includes/apply/data.php)
  - [pages/applicant/includes/job-view/data.php](pages/applicant/includes/job-view/data.php)
  - [pages/applicant/includes/job-list/data.php](pages/applicant/includes/job-list/data.php)
  - [pages/applicant/includes/notifications/data.php](pages/applicant/includes/notifications/data.php)
  - [pages/applicant/includes/support/data.php](pages/applicant/includes/support/data.php)
  - [pages/applicant/apply.php](pages/applicant/apply.php)
  - [pages/applicant/profile.php](pages/applicant/profile.php)
  - [pages/applicant/notifications.php](pages/applicant/notifications.php)
  - [pages/applicant/support.php](pages/applicant/support.php)
- Phase 11 UX state implementation is live in:
  - [pages/applicant/includes/layout.php](pages/applicant/includes/layout.php) (shared initial loading skeleton state)
  - [pages/applicant/dashboard.php](pages/applicant/dashboard.php) (error state with retry + success/error banners)
  - [pages/applicant/job-list.php](pages/applicant/job-list.php) (empty + filter-empty + error retry + success/error banners)
  - [pages/applicant/job-view.php](pages/applicant/job-view.php) (not-found empty + error retry + success/error banners)
  - [pages/applicant/apply.php](pages/applicant/apply.php) (empty variants + error retry + success/error banners)
  - [pages/applicant/applications.php](pages/applicant/applications.php) (empty + filter-empty + error retry + success/error banners)
  - [pages/applicant/application-feedback.php](pages/applicant/application-feedback.php) (empty + filter-empty + error retry + success/error banners)
  - [pages/applicant/notifications.php](pages/applicant/notifications.php) (empty + filter-empty + error retry + success/error banners)
  - [pages/applicant/profile.php](pages/applicant/profile.php) (error retry + success/error banners)
  - [pages/applicant/includes/application-feedback/data.php](pages/applicant/includes/application-feedback/data.php) (filter-empty detection)

---

## 0) Current-State Findings (Why this plan is needed)

Observed from current code:
- Applicant pages are mostly static/demo content.
- Some pages explicitly contain simulated data notes.
- Critical forms are not backend wired yet (`action="#"` in apply/profile).
- Auth guard exists and is correct for applicant role.
- Login already redirects `applicant` role to applicant dashboard.

Impacted files:
- [pages/applicant/apply.php](pages/applicant/apply.php)
- [pages/applicant/profile.php](pages/applicant/profile.php)
- [pages/applicant/job-list.php](pages/applicant/job-list.php)
- [pages/applicant/job-view.php](pages/applicant/job-view.php)
- [pages/applicant/includes/auth-guard.php](pages/applicant/includes/auth-guard.php)
- [pages/auth/login-handler.php](pages/auth/login-handler.php)

---

## 1) Scope Lock (Applicant-Only MVP)

Implement now:
1. Applicant profile view/update
2. Job listing + job details from DB
3. Apply flow with file upload and document records
4. My applications with status timeline
5. Application feedback page based on actual decision
6. Applicant notifications (read/unread, mark as read)
7. Basic support inquiry logging (optional table, see Phase 8)

Defer:
- Complex analytics
- Realtime subscriptions
- AI matching/ranking

---

## 2) Data Model Mapping (Applicant pages -> tables)

Use these existing tables in [SUPABASE_SCHEMA.sql](SUPABASE_SCHEMA.sql):

- Account and identity:
  - `user_accounts`
  - `user_role_assignments`
  - `roles`
  - `people`

- Recruitment:
  - `job_postings`
  - `applicant_profiles`
  - `applications`
  - `application_status_history`
  - `application_documents`
  - `application_feedback`
  - `application_interviews` (read-only display optional)

- Notifications:
  - `notifications`

- Audit:
  - `activity_logs`

Storage bucket expected:
- `hris-applications` (for resume/PDS/transcript/ID uploads)

---

## 3) Phase 1 – Shared Applicant Backend Foundation

## 3.1 Create applicant backend bootstrap

Create these files:
- `pages/applicant/includes/lib/common.php`
- `pages/applicant/includes/lib/supabase.php`
- `pages/applicant/includes/lib/applicant-backend.php`

Required helpers:
- `loadEnvFile`
- `cleanText`
- `apiRequest`
- `isSuccessful`
- `redirectWithState`
- `applicantBackendContext()`

`applicantBackendContext()` must return:
- `supabase_url`
- `service_role_key`
- `headers`
- `applicant_user_id` (from session)

## 3.2 Add route-level bootstrap pattern for each applicant page

For every applicant page, adopt this order:
1. `bootstrap.php`
2. `actions.php`
3. `data.php`
4. `view.php` render

Example file layout per page:
- `pages/applicant/includes/<page>/bootstrap.php`
- `pages/applicant/includes/<page>/actions.php`
- `pages/applicant/includes/<page>/data.php`
- `pages/applicant/includes/<page>/view.php` (or keep current page file and include data vars)

## 3.3 Add API timeout/resilience baseline

In applicant `apiRequest`:
- set connect timeout
- set total timeout
- safe JSON decode
- consistent response shape (`status`, `data`, `raw`)

---

## 4) Phase 2 – Identity and Profile Integration

Status: ✅ Implemented

## 4.1 Resolve current applicant context

On each applicant request:
1. Read `$_SESSION['user']['id']`
2. Query `applicant_profiles` by `user_id`
3. Query `people` by `user_id`
4. If `applicant_profiles` missing, auto-create from `people`/`user_accounts`

## 4.2 Wire profile page

File:
- [pages/applicant/profile.php](pages/applicant/profile.php)

Implement:
- GET: load real profile fields from `people` + `applicant_profiles`
- POST: update allowed fields only
  - `people`: names, mobile, address-related fields if applicable
  - `applicant_profiles`: `full_name`, `mobile_no`, `current_address`, optional portfolio
- Log to `activity_logs`

Validation rules:
- sanitize all user input
- strict email format (if editable)
- mobile format checks
- do not allow role/status changes from applicant side

---

## 5) Phase 3 – Job Discovery Backend

Status: ✅ Implemented

## 5.1 Job listing page integration

File:
- [pages/applicant/job-list.php](pages/applicant/job-list.php)

Replace static cards with DB query:
- Source: `job_postings`
- Filters:
  - `posting_status = published`
  - `open_date <= today`
  - `close_date >= today`

Support query params:
- `q` (title/description)
- `office`
- `employment_type` (map from joined position/classification if needed)
- `page`
- `page_size`

Response data to render:
- job id, title, office, close date, status badge, reference number

## 5.2 Job details page integration

File:
- [pages/applicant/job-view.php](pages/applicant/job-view.php)

Requirements:
- Accept `job_id` from query
- Load posting record by id
- 404-style empty state if not found/unpublished
- Compute flags:
  - deadline passed
  - already applied (exists in `applications` for current applicant_profile_id + job_posting_id)

CTA behavior:
- if deadline passed: disable apply
- if already applied: show “Already Applied”
- else: show “Apply Now” and pass `job_id`

---

## 6) Phase 4 – Application Submission (Core Flow)

Status: ✅ Implemented

## 6.1 Apply page backend wiring

File:
- [pages/applicant/apply.php](pages/applicant/apply.php)

Change form action from `#` to page handler.

POST transaction flow:
1. ✅ Validate authenticated applicant context
2. ✅ Validate target `job_posting_id` is open/published and within open/close dates
3. ✅ Ensure no duplicate application for same posting
4. ✅ Create `applications` row
   - generate `application_ref_no`
   - status = `submitted`
5. ✅ Insert `application_status_history` initial row
6. ✅ Upload files to Supabase Storage bucket `hris-applications`
7. ✅ Insert `application_documents` for each uploaded file
8. ✅ Create applicant notification acknowledgement
9. ✅ Log `activity_logs` event
10. ✅ Redirect with success state/message

Implementation notes:
- Required uploads currently enforced for: `application_letter`, `resume_pds`, `transcript_diploma`, `government_id`
- Max size currently enforced: 5MB/file
- Allowed types currently enforced: PDF, DOC, DOCX, JPG, JPEG, PNG
- Storage path implemented as: `applications/<application_id>/<document_type>/<timestamp>-<safe-file-name>`
- On upload/doc-write failure, a best-effort rollback deletes the created `applications` record

## 6.2 File upload rules

Status: ✅ Implemented

Allowed types:
- PDF, DOC/DOCX, JPG/PNG (define explicit list)

Enforce:
- max file size per type
- safe filename normalization
- deterministic storage path:
  - `applications/<application_id>/<document_type>/<timestamp>-<safe-file-name>`

Map document input names to `application_documents.document_type` values:
- ✅ `application_letter` -> `other`
- ✅ `resume_pds` -> `resume`
- ✅ `transcript_diploma` -> `transcript`
- ✅ `government_id` -> `id`

---

## 7) Phase 5 – My Applications and Feedback

Status: ✅ Implemented

## 7.1 Applications tracker page

File:
- [pages/applicant/applications.php](pages/applicant/applications.php)

Load from:
- `applications` for current applicant profile
- join `job_postings`
- join/aggregate `application_status_history`

Render:
- list of applications with status badges
- selected application timeline from status history
- quick links to feedback/details

Implementation notes:
- Loads applicant-owned applications from `applications` joined to `job_postings`
- Supports `status` filter and selected `application_id`
- Builds timeline from `application_status_history` (with fallback event if history is empty)
- Marks whether written feedback exists per application

## 7.2 Feedback page integration

File:
- [pages/applicant/application-feedback.php](pages/applicant/application-feedback.php)

Load from:
- `application_feedback` + `applications`

Rules:
- applicant can only view own feedback
- if no feedback yet: show empty pending state
- if feedback exists: render decision-specific content from DB

Implementation notes:
- Supports selecting target application via `application_id`
- Loads `application_feedback` by selected application
- Maps decision values (`for_next_step`, `hired`, `rejected`, `on_hold`) to page decision states
- Falls back to application status when written feedback is not yet posted

---

## 8) Phase 6 – Notifications and Support

Status: ✅ Implemented

## 8.1 Notifications page

File:
- [pages/applicant/notifications.php](pages/applicant/notifications.php)

Load:
- `notifications` where `recipient_user_id = auth user`
- order by `created_at desc`

Actions:
- mark one as read
- mark all as read

UI states required:
- loading
- empty inbox
- filter-empty
- error

Implementation notes:
- Loads notifications by `recipient_user_id` ordered by `created_at desc`
- Supports filters: `all`, `unread`, `application`, `system`
- Implements `mark_read` and `mark_all_read` server-side actions with ownership guard
- Includes empty state and filter-empty state messaging in UI

## 8.2 Support page

File:
- [pages/applicant/support.php](pages/applicant/support.php)

Option A (recommended): create table `support_tickets`
- fields: id, user_id, subject, message, status, created_at

Option B (fastest): log support submissions to `activity_logs` and notify HR users via `notifications`

Implementation notes:
- Implemented Option B (`activity_logs` + HR/admin notifications)
- Contact form validates subject/message server-side before submission
- Logs inquiry under `module_name=applicant_support`, `action_name=submit_support`
- Resolves HR/admin recipients via `roles` + `user_role_assignments` and inserts `notifications` rows
- Displays recent support inquiry history from `activity_logs`

---

## 9) Phase 7 – Auth and Registration Adjustments for Applicant

Status: ✅ Implemented

Current register flow appears employee-centric.

Files to update:
- [pages/auth/register.php](pages/auth/register.php)
- [pages/auth/register-handler.php](pages/auth/register-handler.php)

Required changes:
1. Provide separate applicant signup path
2. Keep existing employee/staff registration path intact
3. On applicant registration:
   - create `user_accounts`
   - assign applicant role in `user_role_assignments`
   - create `people`
   - create `applicant_profiles`
4. Keep staff/employee registration logic unchanged unless required

If you prefer separate applicant signup path, create:
- `pages/auth/register-applicant.php`
- `pages/auth/register-applicant-handler.php`

Implementation notes:
- Separate applicant signup route implemented with dedicated handler
- Login page now points applicant self-registration to `register-applicant.php`
- Existing `register.php` remains for employee/staff style registration flow

## 9.1) Phase 7.5 – Applicant Dashboard and Top Navigation Enhancements (Added)

Status: ✅ Implemented

Requirements:
1. Top nav should show unread message count
2. Top nav should show applicant first name
3. Dashboard should use backend-driven applicant snapshot data

Implementation notes:
- Topnav now resolves unread notification count from `notifications` table and renders badge value
- Topnav user label now renders first name from applicant session/profile context
- Dashboard now loads backend snapshot (`active_applications`, `open_jobs`, latest application stage, latest update)
- Dashboard latest updates panel now renders from recent applicant notifications

---

## 10) Security and RLS Checklist (Must Pass)

Use existing RLS in [SUPABASE_SCHEMA.sql](SUPABASE_SCHEMA.sql) and ensure applicant access remains least-privilege.

Status: ✅ Implemented

Implementation notes:
- Added shared UUID validation helper and enforced UUID checks for applicant session IDs and request object IDs (`job_id`, `application_id`, `notification_id`) before processing.
- Added CSRF token generation/validation helpers and enforced CSRF checks on all applicant POST mutation handlers (`apply`, `profile`, `notifications`, `support`) with hidden form tokens.
- Hardened file upload validation by using server-side MIME detection (`finfo`) instead of trusting client-reported MIME values.
- Strengthened storage path scoping for uploaded application files to include applicant user scope (`applications/<applicant_user_id>/<application_id>/...`).
- Removed over-broad feedback read pattern by restricting feedback queries to the current applicant's application IDs only.
- Added defensive URL sanitization for notification action links to block external/unsafe URL schemes before rendering.

Validate:
1. ✅ Applicant can only read/update own `applicant_profiles`
2. ✅ Applicant can only access own `applications`
3. ✅ Applicant cannot view other applicants’ documents/feedback
4. ✅ Storage path now includes owner scope and upload path is deterministic per applicant/application
5. ✅ Server-side validation exists even if UI prevents actions (UUID + CSRF + ownership checks)

---

## 11) UX State Requirements Per Applicant Page

Status: ✅ Implemented

Implementation notes:
- Added shared skeleton loading state through applicant layout with minimum 300ms display and reduced-motion support.
- Added explicit retry actions in data-load error states for all listed applicant pages.
- Preserved and validated page-level empty states across list and detail views.
- Preserved and validated filter-empty states for filtered pages (`job-list`, `applications`, `notifications`) and added filter-empty handling for `application-feedback` selection mismatch.
- Success-state mutation banners are available for submission/update flows and are consistently rendered via `state/message` query parameters.

Each applicant data page must have:
- skeleton loading state
- empty state
- filter-empty state
- error state with retry
- success state after mutations

Apply to:
- dashboard
- job-list
- job-view
- apply
- applications
- feedback
- notifications
- profile

---

## 12) Testing Plan (Step-by-Step)

Execution status: ✅ Completed

Automation evidence:
- Runtime suite implemented in [tools/phase12_runner.php](tools/phase12_runner.php)
- Latest run completed with all checks passing (`F1`-`F8`, `S1`-`S3`, `D1`-`D2`, and cleanup)
- Runner now auto-retries cURL requests without SSL verification only when local certificate validation fails, to support local Windows/XAMPP environments during testing.

## 12.1 Functional tests

1. Register applicant account
2. Login as applicant
3. Open job list and job details
4. Submit one application with required documents
5. Verify application appears in “My Applications”
6. Verify status history initial row exists
7. Verify notifications display and mark-read works
8. Update profile and verify persistence

## 12.2 Security tests

1. Attempt to access another applicant application id directly
2. Attempt to patch forbidden fields from client
3. Attempt to fetch unrestricted notifications
4. Validate storage URL access isolation

## 12.3 Data integrity tests

1. Duplicate apply to same job blocked
2. Closed-deadline job apply blocked
3. Missing required docs blocked (if configured as required)

---

## 13) Rollout Strategy

Recommended implementation order:
1. Backend foundation + context helpers
2. Job list + job view read integration
3. Apply submission + file upload flow
4. Applications tracker + feedback
5. Profile update
6. Notifications + support
7. Registration adjustments

Use feature flags or staged deployment:
- Start with read-only pages
- Enable submission flows after validation

---

## 14) Definition of Done (Applicant Module)

Applicant backend integration is complete when:
1. No applicant page uses hardcoded/simulated content for core entities
2. No critical applicant form uses `action="#"`
3. Apply flow creates DB records + uploads files reliably
4. Applicant only sees own data across all pages
5. Empty/skeleton/error/success states are implemented consistently
6. End-to-end applicant test script passes in staging

---

## 15) Fast-Start Task Breakdown (Copy to issue tracker)

1. Create applicant backend lib helpers and bootstrap
2. Integrate job listing query with filters/pagination
3. Integrate job details by id with deadline/applied flags
4. Wire apply POST handler with storage uploads
5. Build applications tracker from DB + timeline
6. Integrate application feedback page
7. Integrate notifications read/update actions
8. Integrate profile GET/POST persistence
9. Add applicant registration role path
10. Complete QA + security regression + UAT

---

## 16) Mobile Responsiveness Integration

Status: ✅ Completed

Goal:
- Integrate mobile responsiveness for all applicant pages across different screen sizes while preserving existing backend and UX-state behavior.

Target pages:
- [pages/applicant/dashboard.php](pages/applicant/dashboard.php)
- [pages/applicant/job-list.php](pages/applicant/job-list.php)
- [pages/applicant/job-view.php](pages/applicant/job-view.php)
- [pages/applicant/apply.php](pages/applicant/apply.php)
- [pages/applicant/applications.php](pages/applicant/applications.php)
- [pages/applicant/application-feedback.php](pages/applicant/application-feedback.php)
- [pages/applicant/notifications.php](pages/applicant/notifications.php)
- [pages/applicant/profile.php](pages/applicant/profile.php)
- [pages/applicant/support.php](pages/applicant/support.php)

Implemented scope notes:
1. Applied breakpoint-aware form/filter layouts and action button stacking for small screens across all applicant pages.
2. Updated page headers, panel paddings, and section controls to prevent mobile overflow while preserving existing information hierarchy.
3. Improved timeline/list/card readability on mobile by adjusting alignment, wrapping behavior, and responsive spacing.
4. Kept UX states intact (loading/skeleton/empty/filter-empty/error/success) while making state cards and actions touch-friendly.

---

## 17) Applicant Flow Enhancements

Status: ✅ First-pass implemented (core delivery complete; iterate after QA)

Implementation summary:
- Job listing and job view now support Plantilla Item No. rendering and optional CSC reference links when configured in job posting data.
- Apply flow now reads required document requirements from job posting configuration and enforces required uploads dynamically.
- PDS support was added in apply flow via explicit CSC Form 212 guidance/reference and required PDS upload support.
- Profile now supports multiple spouse entries and multiple education entries via backend persistence to `person_family_spouses` and `person_educations`.
- Dashboard static progress/checklist sections are now data-driven.
- Profile now includes a “My Uploaded Files” section with replace-file actions guarded by ownership + validation.
- Phase 17 migration file added: [SUPABASE_MIGRATION_PHASE17_APPLICANT_FLOW.sql](SUPABASE_MIGRATION_PHASE17_APPLICANT_FLOW.sql)

Latest QA refinement notes:
- Optimized applicant topnav data fetching with a short-lived session cache (45s) for first-name and unread-count data to reduce repeated Supabase calls.
- Reduced unread notification badge query payload from large-page fetch to capped fetch appropriate for `99+` badge behavior.
- Added topnav cache invalidation after `mark_read` and `mark_all_read` actions to keep badge state fresh immediately after notification updates.

### 17.1 Job Listing Enhancements

1. Add Plantilla Item No. to all jobs using CSC-style formatting.
  - Example format: `OSEC-DAB-DM02-90-2014`
  - Follow CSC Job Portal format for listing fields/presentation, except the instruction that applications must be sent via email.
  - Reference: https://csc.gov.ph/career/job/4897591

### 17.2 Applicant Background Data Enhancements

1. Family background expansion:
  - Allow multiple spouse entries to support applicants who need this (including Muslim applicants).

2. Educational background expansion:
  - Allow multiple degree-level records (e.g., degree, masteral, doctoral) as needed.

### 17.3 Submit Application / PDS + Requirements Enhancements

1. Align applicant PDS flow with CSC Form 212 Revised 2025 format.
  - Applicant can download a formatted copy.
  - Applicant can upload PDS as a required document.
  - Reference sheet: https://docs.google.com/spreadsheets/d/1XYXyBVqEKuUqPsCHxkf5Xr6I8iL7jGOKxCO6ZHKL9Rg/edit?usp=sharing

2. Required documents upload behavior:
  - Add upload inputs driven by the required document list specified per job posting.
  - Enforce required document completion before final application submission.

### 17.4 Applicant Dashboard Dynamic Data Completion

Concern:
- Some sections in Applicant Dashboard are still static and must be fully backend-driven.

Planned outcomes:
1. Replace remaining static cards/feeds/widgets with live Supabase-backed data.
2. Ensure dashboard sections follow complete UX states (loading/skeleton/empty/filter-empty/error/success where applicable).
3. Align dashboard fetch strategy with localized JS and performance standards in [FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md](FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md).

### 17.5 Profile Uploaded Files Management

Concern:
- Add a profile-page section where applicants can view all uploaded files and modify them.

Planned outcomes:
1. Add a “My Uploaded Files” section in applicant profile.
2. Show file inventory with metadata (document type, filename, upload date, size, status where available).
3. Allow replace/update actions for applicant-owned files with validation and ownership checks.
4. Preserve auditability and secure storage scoping when files are replaced.
5. Apply full UX states for this section (loading/skeleton/empty/error/success + retry on failure).

Implementation note for future execution:
- When this phase starts, include schema + UI + validation + storage-path updates and cross-check against the frontend standards in [FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md](FRONTEND_LOCALIZED_JS_PERFORMANCE_GUIDE.md).