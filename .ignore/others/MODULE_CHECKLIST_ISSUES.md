# DA HRIS Module Checklist (Issue-Ready)

Use this file as a copy-paste source for GitHub Issues, Azure DevOps, or any tracker that supports Markdown checkboxes.

## How to Use in Project Issues

- Copy one full section below and paste it into a new issue.
- Keep the heading as issue title (recommended).
- Assign owner, target sprint, and priority.
- Update checkboxes as work progresses.

---

## Global Foundation (All Modules)

- [ ] Finalize Supabase project setup (env, keys, auth providers)
- [ ] Apply base schema migration
- [ ] Apply seed data migration (roles, permissions, offices)
- [ ] Confirm Storage buckets and policies
- [ ] Implement shared API/service layer
- [ ] Implement shared validation/error handling pattern
- [ ] Implement shared audit log helper
- [ ] Implement notification dispatch helper
- [ ] Add common loading/empty/error UI states
- [ ] Add role-based route/page guards

---

## Authentication Module (pages/auth)

### Screens
- [ ] Login (login.php)
- [ ] Register (register.php)
- [ ] Request Access (request-access.php)
- [ ] Forgot Password (forgot-password.php)
- [ ] Forgot Password Sent (forgot-password-sent.php)
- [ ] Reset Password (reset-password.php)
- [ ] Reset Success (reset-success.php)

### Backend and Security
- [ ] Connect login to Supabase Auth
- [ ] Connect register flow to auth and profile creation
- [ ] Connect request-access flow to access_requests table
- [ ] Add account lockout threshold handling
- [ ] Add session timeout / refresh handling
- [ ] Add password policy checks
- [ ] Add email verification handling
- [ ] Add login audit logging
- [ ] Add CSRF protection for auth forms

---

## Admin Module (pages/admin)

### Core Admin Pages
- [ ] Dashboard (dashboard.php)
- [ ] User Management (user-management.php)
- [ ] Settings (settings.php)
- [ ] Notifications (notifications.php)
- [ ] Profile (profile.php)

### HR Operations
- [ ] Recruitment (recruitment.php)
- [ ] Applicant Tracking (applicant-tracking.php)
- [ ] Applicants (applicants.php)
- [ ] Document Management (document-management.php)
- [ ] Timekeeping (timekeeping.php)
- [ ] Payroll Management (payroll-management.php)
- [ ] Personal Information (personal-information.php)
- [ ] Evaluation (evaluation.php)
- [ ] Learning and Development (learning-and-development.php)

### PRAISE and Analytics
- [ ] PRAISE Main (praise.php)
- [ ] PRAISE Awards and Recognition (praise-awards-recognition.php)
- [ ] PRAISE Employee Evaluation (praise-employee-evaluation.php)
- [ ] PRAISE Reports and Analytics (praise-reports-analytics.php)
- [ ] Reports and Analytics (report-analytics.php)

### Admin Backlog Tasks
- [ ] Role and permission assignment UI wired to role tables
- [ ] User credential controls wired to account status
- [ ] Office scope management wired to access scope table
- [ ] Settings persistence wired to system_settings
- [ ] Admin action audit trail enabled for all updates

---

## Staff Module (pages/staff)

### Staff Pages
- [ ] Dashboard (dashboard.php)
- [ ] Applicant Registration (applicant-registration.php)
- [ ] Applicant Tracking (applicant-tracking.php)
- [ ] Recruitment (recruitment.php)
- [ ] Document Management (document-management.php)
- [ ] Timekeeping (timekeeping.php)
- [ ] Payroll Management (payroll-management.php)
- [ ] Evaluation (evaluation.php)
- [ ] Reports (reports.php)
- [ ] Notifications (notifications.php)
- [ ] Personal Information (personal-information.php)
- [ ] PRAISE (praise.php)
- [ ] Profile (profile.php)

### Export
- [ ] Attendance Export (export/attendance.php)
- [ ] Payroll Export (export/payroll.php)
- [ ] Performance Export (export/performance.php)

### Staff Backlog Tasks
- [ ] Staff permissions aligned with role-based policy
- [ ] Staff workflow approvals (document/timekeeping/recruitment)
- [ ] Staff-generated report history persisted

---

## Employee Module (pages/employee)

### Employee Pages
- [ ] Dashboard (dashboard.php)
- [ ] Personal Information (personal-information.php)
- [ ] Document Management (document-management.php)
- [ ] Timekeeping (timekeeping.php)
- [ ] Payroll (payroll.php)
- [ ] PRAISE (praise.php)
- [ ] Personal Reports (personal-reports.php)
- [ ] Notifications (notifications.php)
- [ ] Settings (settings.php)
- [ ] Support (support.php)

### Export
- [ ] Attendance Export (export/attendance.php)
- [ ] Payroll Export (export/payroll.php)
- [ ] Performance Export (export/performance.php)

### Employee Backlog Tasks
- [ ] Self-service profile update workflow with audit logs
- [ ] Leave request and approval status tracking
- [ ] Attendance correction request workflow
- [ ] Payslip retrieval from storage

---

## Applicant Module (pages/applicant)

### Applicant Pages
- [ ] Dashboard (dashboard.php)
- [ ] Job List (job-list.php)
- [ ] Job View (job-view.php)
- [ ] Apply (apply.php)
- [ ] Applications (applications.php)
- [ ] Application Feedback (application-feedback.php)
- [ ] Notifications (notifications.php)
- [ ] Profile (profile.php)
- [ ] Support (support.php)

### Applicant Backlog Tasks
- [ ] Job posting list connected to published postings
- [ ] Application submission with file upload to storage
- [ ] Application timeline/status history rendering
- [ ] Interview schedule and feedback visibility

---

## Shared Layout and Navigation Includes

### Admin Includes
- [ ] includes/head.php
- [ ] includes/layout.php
- [ ] includes/sidebar.php
- [ ] includes/topnav.php

### Staff Includes
- [ ] includes/head.php
- [ ] includes/layout.php
- [ ] includes/sidebar.php
- [ ] includes/topnav.php

### Employee Includes
- [ ] includes/head.php
- [ ] includes/layout.php
- [ ] includes/sidebar.php
- [ ] includes/topnav.php
- [ ] includes/footer.php
- [ ] includes/breadcrumbs.php
- [ ] includes/auth-guard.php

### Applicant Includes
- [ ] includes/head.php
- [ ] includes/layout.php
- [ ] includes/sidebar.php
- [ ] includes/topnav.php

### Auth Includes
- [ ] includes/head.php
- [ ] includes/auth-layout.php

### Shared UI Tasks
- [ ] Role-aware navigation visibility
- [ ] Consistent active-state routing
- [ ] Logout flow and session cleanup
- [ ] Consistent mobile responsiveness checks

---

## JavaScript and Frontend Logic

### Existing JS Assets
- [ ] assets/js/alert.js
- [ ] assets/js/script.js
- [ ] pages/admin/js/script.js
- [ ] pages/applicant/js/alert.js
- [ ] pages/applicant/js/script.js
- [ ] pages/employee/js/alert.js
- [ ] pages/employee/js/script.js

### Frontend Tasks
- [ ] Centralize reusable form validation
- [ ] Standardize API request helper and error mapping
- [ ] Add client-side input sanitation before submit
- [ ] Add success/error toast standards across roles

---

## Database and API Modules (Supabase)

### Access and Identity
- [ ] auth.users integration complete
- [ ] user_accounts CRUD and profile sync
- [ ] roles/permissions/role_permissions seed and usage
- [ ] user_role_assignments and office scope enforcement
- [ ] access_requests lifecycle workflow

### HR Core Data
- [ ] people and addresses
- [ ] government IDs with masking/encryption strategy
- [ ] family background and education
- [ ] employment records and job positions

### Recruitment Data
- [ ] requisitions and postings
- [ ] applicant profiles and applications
- [ ] application documents and interviews
- [ ] status history and feedback records

### Operations Data
- [ ] document categories and document versions
- [ ] attendance logs and schedules
- [ ] leave balances and leave requests
- [ ] overtime and time adjustments

### Finance and Performance
- [ ] payroll periods/runs/items/adjustments/payslips
- [ ] performance cycles and evaluations
- [ ] PRAISE awards and nominations
- [ ] training programs and enrollments
- [ ] generated reports history

---

## Security, Compliance, and Audit

- [ ] Row Level Security enabled on all user-facing tables
- [ ] RLS policies tested per role (admin/staff/employee/applicant)
- [ ] Sensitive data encryption strategy implemented
- [ ] Data retention rules implemented per document category
- [ ] Full audit logging for approvals/rejections/exports
- [ ] Privacy policy and consent text validated for forms
- [ ] Backup and restore test completed

---

## Testing and QA

### Functional
- [ ] Auth happy path and failure path tests
- [ ] Role-based access tests for every role
- [ ] CRUD tests for core HR records
- [ ] Recruitment flow end-to-end tests
- [ ] Timekeeping and leave workflow tests
- [ ] Payroll calculation and export tests

### Non-Functional
- [ ] Accessibility pass (forms, contrast, keyboard)
- [ ] Responsive pass (mobile/tablet/desktop)
- [ ] Performance pass (initial load and key pages)
- [ ] Security checks (input validation, unauthorized access)

### UAT
- [ ] Admin UAT sign-off
- [ ] HR staff UAT sign-off
- [ ] Employee UAT sign-off
- [ ] Applicant UAT sign-off

---

## Deployment Readiness

- [ ] Production environment variables configured
- [ ] Migration order documented
- [ ] Rollback script prepared
- [ ] Monitoring and alerting enabled
- [ ] Error logging dashboard configured
- [ ] Release notes prepared
- [ ] Go-live checklist signed

---

## Optional Issue Templates (Copy-Paste)

### Template: Module Implementation

- [ ] Confirm scope and acceptance criteria
- [ ] Wire UI to API
- [ ] Add validation and error handling
- [ ] Add activity logs
- [ ] Add role checks and RLS verification
- [ ] Perform QA and attach evidence
- [ ] Mark module complete

### Template: Bug Fix

- [ ] Reproduce issue with clear steps
- [ ] Identify root cause
- [ ] Implement fix
- [ ] Add regression test/check
- [ ] Verify in staging
- [ ] Close with before/after notes
