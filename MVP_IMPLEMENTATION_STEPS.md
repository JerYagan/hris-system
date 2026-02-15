# DA HRIS MVP Implementation Steps (DB + System Migration)

This is the practical, step-by-step guide to move your current frontend-first HRIS into the recommended MVP stack:

- PHP + Tailwind + Vanilla JS (keep current)
- Supabase (Postgres + Auth + Storage + Realtime)
- Email notifications (SMTP)

---

## 0) MVP Scope Lock (Do This First)

Only implement these in MVP:

1. Authentication + role-based access (Admin, Staff, Employee, Applicant)
2. Profile/Personal Information CRUD
3. Recruitment (job posts + applications + status tracking)
4. Document Management (upload + review status)
5. Timekeeping + leave request basic workflow
6. Notifications (in-app + critical email)
7. Basic reports export (CSV first, PDF optional)

Everything else (advanced analytics, full payroll automation, 2FA, deep BI) goes to Phase 2.

---

## 1) Environment Setup

### 1.1 Create Supabase Project

- Create project in Supabase dashboard
- Save:
  - `SUPABASE_URL`
  - `SUPABASE_ANON_KEY`
  - `SUPABASE_SERVICE_ROLE_KEY` (server-side only)

### 1.2 Add Environment Config in PHP

Create `.env` (or config file) with:

- `SUPABASE_URL`
- `SUPABASE_ANON_KEY`
- `SUPABASE_SERVICE_ROLE_KEY`
- `APP_BASE_URL`
- `MAIL_FROM`
- `MAIL_FROM_NAME`
- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_USERNAME`
- `SMTP_PASSWORD`
- `SMTP_ENCRYPTION` (`tls`, `ssl`, or empty)
- `SMTP_AUTH` (`1` or `0`)

### 1.3 Add Basic PHP Dependencies

- Supabase REST calls via `curl` or Guzzle
- `vlucas/phpdotenv` (if using `.env`)
- `monolog/monolog`
- `phpmailer/phpmailer` (if SMTP route)

---

## 2) Database Rollout (DB First)

Use these files already in your project:

- Schema SQL: [SUPABASE_SCHEMA.sql](SUPABASE_SCHEMA.sql)
- Data dictionary: [SUPABASE_SCHEMA_TABLES.md](SUPABASE_SCHEMA_TABLES.md)

### 2.1 Apply Base Schema

1. Open Supabase SQL Editor
2. Run full [SUPABASE_SCHEMA.sql](SUPABASE_SCHEMA.sql)
3. Confirm no errors

### 2.2 Seed Minimum Data

Seed these rows first:

- `organizations` (DA-ATI)
- `offices` (main office + units)
- `roles`:
  - `admin`
  - `hr_officer`
  - `supervisor`
  - `staff`
  - `employee`
  - `applicant`
- `permissions`
- `role_permissions`

### 2.3 Create First Admin Account

1. Create user in Supabase Auth (`auth.users`)
2. Insert mirror record in `user_accounts`
3. Assign `admin` role in `user_role_assignments` (`is_primary=true`)
4. Insert `people` profile for admin

### 2.4 Storage Setup

Create buckets:

- `hris-documents`
- `hris-applications`
- `hris-payslips`

Set policies:

- user can upload only own records
- admin/staff can read scoped records
- signed URL for private downloads

### 2.5 Validate RLS

Test with 4 test accounts:

- admin
- staff
- employee
- applicant

Verify each can only access allowed rows for:

- `user_accounts`
- `people`
- `applications`
- `notifications`
- `documents`

---

## 3) Backend Integration Layer (PHP)

### 3.1 Create Shared Service Modules

Create minimal service files (or classes):

- `AuthService`
- `UserService`
- `ProfileService`
- `RecruitmentService`
- `DocumentService`
- `TimekeepingService`
- `NotificationService`

### 3.2 Standardize API Pattern

Each service method should:

1. validate input
2. call Supabase (REST/RPC)
3. map errors to user-friendly messages
4. write `activity_logs` for critical actions

### 3.3 Add Auth Guard Middleware

Centralize role checks:

- page guard checks login session
- role guard checks role assignment
- office scope checks for staff/admin data views

---

## 4) Auth Module Implementation (First Functional Module)

Related pages:

- [pages/auth/login.php](pages/auth/login.php)
- [pages/auth/register.php](pages/auth/register.php)
- [pages/auth/request-access.php](pages/auth/request-access.php)
- [pages/auth/forgot-password.php](pages/auth/forgot-password.php)
- [pages/auth/reset-password.php](pages/auth/reset-password.php)

### Steps

1. Replace demo login with Supabase Auth sign-in
2. On success, fetch role from `user_role_assignments`
3. Redirect by role to dashboard
4. Register flow:
   - create auth user
   - insert `user_accounts` + `people`
   - assign requested/default role
5. Request-access flow inserts to `access_requests`
6. Forgot/reset password uses Supabase reset flow
7. Log login success/fail in `login_audit_logs`

Done criteria:

- all auth pages save/read real DB data
- role-based redirect works
- invalid login path is handled

---

## 5) Personal Information Module (Second)

Related pages:

- [pages/employee/personal-information.php](pages/employee/personal-information.php)
- [pages/admin/personal-information.php](pages/admin/personal-information.php)
- [pages/staff/personal-information.php](pages/staff/personal-information.php)

### Steps

1. Read profile from `people`, `person_addresses`, `person_educations`
2. Save updates with validation
3. Save government IDs to `person_government_ids` (encrypted values)
4. Add `activity_logs` for update actions
5. Restrict edits by role/scope

Done criteria:

- employee can update own profile
- admin/staff can view by scope
- all updates are logged

---

## 6) Recruitment + Applicant Tracking (Third)

Related pages:

- [pages/applicant/job-list.php](pages/applicant/job-list.php)
- [pages/applicant/job-view.php](pages/applicant/job-view.php)
- [pages/applicant/apply.php](pages/applicant/apply.php)
- [pages/applicant/applications.php](pages/applicant/applications.php)
- [pages/admin/recruitment.php](pages/admin/recruitment.php)
- [pages/admin/applicant-tracking.php](pages/admin/applicant-tracking.php)
- [pages/staff/recruitment.php](pages/staff/recruitment.php)

### Steps

1. Admin/staff create `job_requisitions` and `job_postings`
2. Applicant pages load only published postings
3. Application submit creates:
   - `applications`
   - `application_documents` (file upload)
   - `application_status_history`
4. Tracking pages update status (screening/interview/etc.)
5. Feedback page reads `application_feedback`
6. Trigger notifications on status change

Done criteria:

- full apply-to-status lifecycle works with real data

---

## 7) Document Management (Fourth)

Related pages:

- [pages/employee/document-management.php](pages/employee/document-management.php)
- [pages/admin/document-management.php](pages/admin/document-management.php)
- [pages/staff/document-management.php](pages/staff/document-management.php)

### Steps

1. Create document categories in `document_categories`
2. Upload files to Supabase Storage
3. Save metadata to `documents` + `document_versions`
4. Review workflow in `document_reviews`
5. Log reads/downloads in `document_access_logs`

Done criteria:

- employee uploads and tracks status
- admin/staff review and update status
- access events are auditable

---

## 8) Timekeeping + Leave (Fifth)

Related pages:

- [pages/employee/timekeeping.php](pages/employee/timekeeping.php)
- [pages/admin/timekeeping.php](pages/admin/timekeeping.php)
- [pages/staff/timekeeping.php](pages/staff/timekeeping.php)

### Steps

1. Seed `leave_types`
2. Implement attendance list from `attendance_logs`
3. Implement leave request create/update in `leave_requests`
4. Implement staff/admin approval actions
5. Adjust leave balances in `leave_balances`
6. Send approval/rejection notifications

Done criteria:

- employee can request leave
- staff/admin can approve/reject
- balance and status reflect correctly

---

## 9) Notifications (In-App + Email)

### 9.1 In-App Notifications

- Insert row in `notifications` for important events
- Notification page reads user’s notifications sorted by `created_at desc`
- Add mark-as-read update

### 9.2 Live Update Strategy

Recommended for MVP:

- Supabase Realtime subscription for current user notifications
- Poll fallback every 30–60 seconds

### 9.3 Email Notifications

Send email for critical events only:

- access request approved/rejected
- recruitment decision updates
- leave request approved/rejected
- password/security alerts

Implement queue table (`notification_deliveries`) in Phase 1.5 if needed.

Done criteria:

- user gets in-app notification instantly or near-instantly
- critical notifications also arrive via email

---

## 10) Reports and Export (MVP Level)

Related pages:

- [pages/employee/personal-reports.php](pages/employee/personal-reports.php)
- [pages/staff/reports.php](pages/staff/reports.php)
- [pages/admin/report-analytics.php](pages/admin/report-analytics.php)

### Steps

1. Implement CSV export first for attendance/payroll/performance summaries
2. Save report generation records to `generated_reports`
3. Add role checks before export
4. Add PDF export only for top 1–2 required reports

Done criteria:

- exports work with filters and role access
- generated report entries are logged

---

## 11) QA and Hardening

### Functional Checklist

- Auth per role works end-to-end
- Profile save/read works
- Applicant apply flow works
- Document review flow works
- Leave approval flow works
- Notifications and unread count work

### Security Checklist

- RLS verified per role
- all form inputs validated server-side
- file upload validation + size limits
- sensitive IDs encrypted/masked
- activity logs generated for critical actions

### Performance Checklist

- add indexes for top queries
- paginate table-heavy views
- optimize notification and dashboard queries

---

## 12) Deployment Steps

1. Backup DB snapshot
2. Run migrations in staging
3. Run smoke tests
4. Promote to production
5. Monitor logs/error tracker first 48 hours
6. Fix priority regressions immediately

---

## 13) Suggested Implementation Timeline (6 Weeks)

### Week 1
- Environment, Supabase setup, schema migration, seed data, first admin

### Week 2
- Auth module full integration + role guard

### Week 3
- Personal info module + profile CRUD

### Week 4
- Recruitment and applicant tracking flows

### Week 5
- Document management + timekeeping/leave basic workflow

### Week 6
- Notifications, exports, QA hardening, go-live prep

---

## 14) MVP Go-Live Criteria

Go live when all are true:

- [ ] All MVP modules use real DB data (no demo placeholders)
- [ ] Role-based access works for all roles
- [ ] RLS policies validated with test accounts
- [ ] Critical flows have audit logs
- [ ] Notification flow (in-app + critical email) is active
- [ ] Staging QA sign-off completed
- [ ] Rollback procedure documented

---

## 15) Next File You Should Create

After this playbook, create:

- `SUPABASE_SEED_MVP.sql` (roles, permissions, offices, sample leave types)
- `SUPABASE_SEED_DEMO_FULL.sql` (comprehensive mock data across all core tables for end-to-end UI/demo testing)
- `MIGRATION_RUNBOOK.md` (exact migration order + rollback)

This keeps implementation consistent and safer for team execution.

### Suggested Seed Run Order for Demo Environments

1. `SUPABASE_SCHEMA.sql`
2. `SUPABASE_SEED_MVP.sql`
3. Create test users in Supabase Auth
4. `SUPABASE_SEED_DEMO_USER_MANAGEMENT.sql`
5. `SUPABASE_SEED_DEMO_FULL.sql`

---

## 16) Audit Trail Standard (Required)

Use this as the single team standard for logging across all modules.

### 16.1 Log Tables and Purpose

- `login_audit_logs`: authentication events (`login_success`, `login_failed`, `logout`, `password_reset`)
- `activity_logs`: business actions (`create`, `update`, `approve`, `reject`, `delete`, `export`)
- `document_access_logs`: document view/download/print access tracking

### 16.2 Required Events in MVP

You must write an `activity_logs` row for these actions:

- Role assignment changes and account status changes
- Personal information updates
- Recruitment status changes and interview decisions
- Leave approvals/rejections and document approvals/rejections
- Report export actions and sensitive record downloads

You must write a `login_audit_logs` row for:

- Every login attempt (success and failure)
- Logout
- Password reset request/complete events

### 16.3 Payload Standard (activity_logs)

Minimum fields per log entry:

- `actor_user_id`
- `module_name`
- `entity_name`
- `entity_id`
- `action_name`
- `old_data` (JSON)
- `new_data` (JSON)
- `ip_address`
- `created_at`

Example JSON payload shape:

```json
{
  "actor_user_id": "8d39f75a-7d6f-47b4-b228-9d4f9f43f2f8",
  "module_name": "timekeeping",
  "entity_name": "leave_requests",
  "entity_id": "ed0ec6a8-4c4b-44df-b7fd-5dd6b9478b9f",
  "action_name": "approve",
  "old_data": {"status": "pending"},
  "new_data": {"status": "approved"},
  "ip_address": "203.177.12.5"
}
```

### 16.4 Implementation Rule (Critical)

- Write the business update and the log write in one transaction whenever possible.
- If the update succeeds, the log must succeed.
- Do not update/delete log rows in normal operations (append-only behavior).

### 16.5 Minimal PHP Logging Helper Pattern

```php
function writeActivityLog(array $log, string $supabaseUrl, string $serviceRoleKey): void
{
  $payload = [
    'actor_user_id' => $log['actor_user_id'] ?? null,
    'module_name' => $log['module_name'],
    'entity_name' => $log['entity_name'],
    'entity_id' => $log['entity_id'] ?? null,
    'action_name' => $log['action_name'],
    'old_data' => $log['old_data'] ?? null,
    'new_data' => $log['new_data'] ?? null,
    'ip_address' => $log['ip_address'] ?? null,
  ];

  $ch = curl_init($supabaseUrl . '/rest/v1/activity_logs');
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      'apikey: ' . $serviceRoleKey,
      'Authorization: Bearer ' . $serviceRoleKey,
      'Content-Type: application/json',
      'Prefer: return=minimal',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
  ]);

  curl_exec($ch);
  curl_close($ch);
}
```

### 16.6 Access and Retention Rules

- Restrict full audit log read access to `admin` role only.
- Keep audit rows for minimum 3–5 years based on policy.
- Use archive strategy for old logs instead of hard delete.

### 16.7 Audit QA Checklist

- [ ] Approve/reject actions always create an `activity_logs` row
- [ ] Login failures are visible in `login_audit_logs`
- [ ] Document downloads create `document_access_logs` rows
- [ ] `old_data` and `new_data` are both present for update actions
- [ ] Non-admin users cannot access system-wide audit trails