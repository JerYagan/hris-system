# Admin Static Smoke Pass QA

Date: 2026-03-09
Scope: Admin modules under `pages/admin/*`
Reviewer: GitHub Copilot

## Summary

This was a static smoke pass only. I did not perform an authenticated browser walkthrough, so this report covers code-level health, route/module wiring, syntax validation, and high-confidence workflow inconsistencies visible from the repository.

What passed in this pass:

- All top-level admin PHP entry pages passed `php -l`.
- Modular admin JavaScript under `assets/js/admin/**` parsed cleanly with `node --check`.
- The legacy admin script `pages/admin/js/script.js` also parsed cleanly.

What did not fully pass:

- Admin page behavior is still split between a small modular loader and a large legacy fallback script.
- Native browser alert/confirm dialogs still exist in multiple admin flows.
- Announcement behavior is split between the dashboard draft/queue flow and the dedicated Create Announcement publish flow.

## Method

- Inventoried admin entry pages under `pages/admin/*.php`.
- Checked admin layout/bootstrap wiring.
- Ran PHP syntax validation on all top-level admin entry pages.
- Ran JS syntax validation on `assets/js/admin/**` and `pages/admin/js/script.js`.
- Reviewed high-risk admin flows for dashboard, announcements, recruitment, timekeeping, notifications, settings, support, and legacy admin JS behaviors.

## Limitations

- No admin credentials or active authenticated browser session were used.
- No live Supabase/API calls were exercised through the UI.
- No end-to-end form submission or modal interaction was executed in-browser.
- Findings below are limited to issues that are verifiable from the codebase and static checks.

## Live Route Probe

I performed a limited live route probe against localhost to determine whether browser-based admin QA was possible from this environment.

Verified live behavior:

- `http://localhost/hris-system/pages/admin/dashboard.php` returned `302 Found` with `Location: ../auth/login.php`.
- `http://localhost/hris-system/pages/admin/support.php` returned `302 Found` with `Location: ../auth/login.php`.
- Sampled admin routes `dashboard.php`, `support.php`, `settings.php`, `timekeeping.php`, `recruitment.php`, and `notifications.php` all returned `302 Found` to `../auth/login.php`.
- Fetching `dashboard.php` and `support.php` without a session returned the sign-in page content, confirming that the auth gate is active.

Result:

- Browser-based admin smoke testing beyond the auth gate is blocked in the current environment because there is no active admin session available to the agent.
- The only live browser-level behavior I could verify is that the admin auth guard is working for sampled routes.

## Findings

### 1. Admin JS coverage is incomplete and 16 admin pages still fall back to the legacy mega-script

Severity: Medium

Evidence:

- `pages/admin/includes/layout.php` sets `data-page` from the active page slug and always loads `assets/js/bootstrap.js`.
- `assets/js/bootstrap.js` only maps 9 admin page slugs: `dashboard`, `recruitment`, `evaluation`, `document-management`, `payroll-management`, `learning-and-development`, `report-analytics`, `user-management`, and `support`.
- All other admin pages fall back to `pages/admin/js/script.js`.

Verified impact:

- Admin has 25 top-level page entrypoints, but only 9 have dedicated modular JS coverage.
- The following pages currently rely on the legacy fallback script: `create-announcement`, `applicants`, `applicant-tracking`, `applicant-profile`, `applicant-document`, `employee-profile`, `document-preview`, `timekeeping`, `personal-information`, `notifications`, `settings`, `profile`, `praise`, `praise-awards-recognition`, `praise-employee-evaluation`, and `praise-reports-analytics`.

Why this matters:

- This increases regression risk because one large shared script continues to carry behavior for many unrelated admin modules.
- It also explains why older interaction patterns remain in admin even after newer modules were localized.

Recommended improvement:

- Continue page-by-page localization into `assets/js/admin/*` and remove dependency on the legacy admin mega-script for remaining pages.

### 2. Native browser alerts and confirms are still present in admin workflows

Severity: Medium

Evidence:

- `pages/admin/js/script.js` still contains `window.alert(...)` and multiple `window.confirm(...)` calls for payroll, personal information, evaluation, and bulk-delete/archive flows.
- `pages/admin/includes/timekeeping/view.php` still falls back to `window.confirm(...)` for leave and attendance logging if SweetAlert is unavailable.

Verified examples:

- Payroll summary alert: `pages/admin/js/script.js`
- Salary setup / payroll batch / payroll period delete confirmations: `pages/admin/js/script.js`
- Eligibility and work-experience delete confirmations: `pages/admin/js/script.js`
- Personal info archive / duplicate merge confirmations: `pages/admin/js/script.js`
- Evaluation approve/reject confirmation fallback: `pages/admin/js/script.js`
- Leave and attendance helper confirmation fallback: `pages/admin/includes/timekeeping/view.php`

Why this matters:

- The current admin UX is still inconsistent with the system-wide revision requirement to replace native browser dialogs.
- If SweetAlert fails to load on a page, admin falls back to generic browser dialogs instead of a standardized recovery path.

Recommended improvement:

- Remove native dialog fallbacks for admin actions and standardize all confirmation/error/status dialogs to one shared SweetAlert-based pattern.

### 3. Dashboard announcement draft/queue flow is disconnected from the actual Create Announcement publish flow

Severity: Medium

Evidence:

- `pages/admin/includes/dashboard/actions.php` stores announcement draft/queue actions in `activity_logs` under `module_name = dashboard` and actions `save_announcement_draft` / `queue_announcement`.
- `pages/admin/includes/dashboard/data.php` reads only those dashboard activity-log entries to compute announcement counts and “latest announcement” summary content.
- `pages/admin/includes/create-announcement/actions.php` performs the real publish workflow under `module_name = create_announcement`, inserts notification rows, optionally sends emails, and logs delivery summaries.
- `pages/admin/includes/create-announcement/data.php` reads only `create_announcement` publish logs for delivery statistics.

Verified impact:

- Dashboard “draft” and “queued” announcement indicators are not sourced from the same workflow as actual published announcements.
- Queueing on the dashboard does not represent a real publish queue shared with the Create Announcement page.
- The dashboard queue action currently notifies the current admin account about the queue event, but it does not itself publish the org-wide announcement.

Why this matters:

- An admin can reasonably assume dashboard queued announcements are part of the same lifecycle as Create Announcement publishes, but the code treats them as separate systems.
- This creates a reporting and workflow clarity problem rather than a syntax/runtime crash.

Recommended improvement:

- Unify announcement state into one authoritative flow, or clearly separate “dashboard quick drafts” from “published announcements” in the UI and data model.

## Module Coverage Notes

These are not all bugs. This section records what was verified in the static pass and where browser QA is still needed.

### Dashboard

- Static status: Parses clean.
- QA note: Announcement summary logic is split from the dedicated announcement publish flow.

### Recruitment / Applicants / Applicant Tracking / Evaluation

- Static status: Parses clean.
- QA note: No syntax blocker found in the reviewed recruitment create-posting path.
- QA note: `applicants`, `applicant-tracking`, `applicant-profile`, and `applicant-document` still rely on legacy admin JS behavior.
- QA note: These modules still need authenticated browser validation for scheduling, status transitions, email actions, and document access.

### Document Management / Document Preview

- Static status: Parses clean.
- QA note: Main document-management page has modular JS coverage.
- QA note: `document-preview` still relies on the legacy admin script path.

### Personal Information / Employee Profile

- Static status: Parses clean.
- QA note: Legacy admin JS still contains native confirmations for archive/merge/delete-adjacent flows tied to this area.

### Timekeeping

- Static status: Parses clean.
- QA note: Inline confirmation logic still falls back to native browser confirms.
- QA note: Needs live browser validation for modal behavior, date picker behavior, and helper form submissions.

### Payroll Management

- Static status: Parses clean.
- QA note: Modular page entry exists, but the legacy admin script still contains payroll alerts/confirms, indicating incomplete decoupling.

### Reports and Analytics

- Static status: Parses clean.
- QA note: Modular page entry exists.

### Notifications

- Static status: Parses clean.
- QA note: Page still relies on legacy/inlined behavior rather than a dedicated admin module entry.
- QA note: Needs browser validation for modal open/read state transitions and mark-all-as-read behavior.

### Create Announcement

- Static status: Parses clean.
- QA note: Publish flow is code-complete at a static level, but dashboard announcement metrics are not unified with it.

### Learning and Development

- Static status: Parses clean.
- QA note: Modular page entry exists.
- QA note: Still needs live admin-side workflow validation for create/enroll/update-attendance actions.

### User Management

- Static status: Parses clean.
- QA note: Modular page entry exists.
- QA note: Still needs authenticated browser validation for role assignment, account actions, and reset/change-password UX.

### Settings

- Static status: Parses clean.
- QA note: Page still falls back to legacy admin JS.

### Support

- Static status: Parses clean.
- QA note: Modular page entry exists.

### Praise / Praise Awards / Praise Employee Evaluation / Praise Reports / Profile

- Static status: Parses clean.
- QA note: These pages still rely on the legacy admin script path and need browser validation for actual module intent and retained-scope behavior.

## Improvements Backlog From This Pass

1. Finish admin JS localization so all admin pages stop depending on `pages/admin/js/script.js`.
2. Remove all native browser dialogs from admin and standardize on a shared modal/confirmation system.
3. Unify dashboard announcement drafting/queueing with the dedicated Create Announcement publish pipeline.
4. Run an authenticated browser smoke pass for the admin modules that still depend on legacy JS: applicants, applicant tracking, employee profile, document preview, timekeeping, personal information, notifications, settings, profile, and all praise pages.

## Validation Commands Run

```powershell
Get-ChildItem "pages/admin" -Filter *.php | ForEach-Object { php -l $_.FullName }
Get-ChildItem "assets/js/admin" -Recurse -Filter *.js | ForEach-Object { node --check $_.FullName }
node --check "pages/admin/js/script.js"
```

## Overall QA Verdict

Static plus limited live route-probe result: Admin entrypoints are syntactically healthy and sampled admin routes are correctly protected by the login gate, but the admin surface cannot yet be considered fully validated “working as intended” because there are still verified workflow inconsistencies and significant reliance on legacy shared JS behavior across many modules. A real post-login browser walkthrough is still pending.