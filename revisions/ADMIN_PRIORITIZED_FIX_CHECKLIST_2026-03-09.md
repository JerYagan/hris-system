# Admin Prioritized Fix Checklist

Date: 2026-03-09
Source: Static admin smoke pass plus limited localhost route probe

## Priority Order

### P0: Required before meaningful browser QA

- [x] Obtain or provide a valid admin session for post-login browser QA. Completed via session-cookie simulation against localhost using the temporary admin credential provided for testing.
- [x] Run authenticated browser smoke tests for the legacy-script admin pages: applicants, applicant tracking, applicant profile, applicant document, employee profile, document preview, timekeeping, personal information, notifications, settings, profile, praise, praise awards, praise employee evaluation, and praise reports analytics.

Execution notes:

- Authenticated route smoke findings were logged in `revisions/ADMIN_AUTHENTICATED_LEGACY_PAGE_SMOKE_2026-03-09.md`.
- The temporary test credential was used and should be changed immediately after use.

Original access options considered:

  - Setting up a secure staging environment with admin credentials.
    email: skywarrior.sw@gmail.com
    password: Password123!

    this is a temporary credential for testing purposes only and should be changed immediately after use.
  - Implementing a temporary authenticated route for smoke testing purposes.
  - Using session cookies or tokens to simulate an admin login in the browser.

Why this is first:

- The current environment confirms auth gating works, but no post-login validation can be completed without an admin session.
- These pages are the least isolated because they still depend on the legacy admin mega-script.

### P1: High-value structural fixes

- [ ] Finish admin JS localization so each admin page has explicit page-bound behavior instead of falling back to `pages/admin/js/script.js`.
- [ ] Remove the legacy admin mega-script dependency page by page as localized modules are completed.
- [ ] Add missing admin module entries to `assets/js/bootstrap.js` for the remaining admin pages.

Expected payoff:

- Lower regression risk.
- Clearer module ownership.
- Easier browser QA and faster defect isolation.

### P1: UX and consistency fixes already verified as open

- [x] Replace all remaining native `window.alert` and `window.confirm` usage in admin flows with the shared SweetAlert-based pattern.
- [x] Standardize destructive/status-changing confirmations across payroll, personal information, evaluation, timekeeping helper actions, and bulk actions.

Execution notes:

- Completed on 2026-03-09 in `pages/admin/js/script.js` and `pages/admin/includes/timekeeping/view.php`.
- Remaining admin `window.alert` and `window.confirm` usage under `pages/admin/**` was reduced to zero after the refactor.
- A focused authenticated post-change smoke pass confirmed `payroll-management.php`, `personal-information.php`, `evaluation.php`, and `timekeeping.php` still returned `200` with expected admin titles and retained the key action hooks/forms tied to the updated confirmation flows.
- Verification method was session-backed HTML inspection against localhost, not full browser click automation, so live interaction polish should still be covered during the later module-by-module QA walkthrough.

Expected payoff:

- Removes inconsistent admin interaction patterns.
- Prevents fallback to generic browser dialogs when shared UI is expected.

### P1: Workflow correctness fix

- [x] Unify the dashboard announcement draft/queue behavior with the dedicated Create Announcement publish workflow.
- [x] Decide whether dashboard announcement entries are real queue items or only quick drafts, then reflect that consistently in both UI and data.
- [x] Ensure announcement counts, latest-item summaries, and publish statistics come from one authoritative source.

Execution notes:

- Completed on 2026-03-09 by removing the dashboard-owned draft/queue behavior as a real workflow path and redirecting stale dashboard announcement submissions to `create-announcement.php`.
- Dashboard announcement reporting now reads the same published `create_announcement` activity log stream used by the Create Announcement page via a shared helper.
- The dashboard now exposes a read-only "Announcement Broadcasts" summary with published totals, delivery totals, and the latest published announcement, while Create Announcement remains the only publish entrypoint.
- Authenticated localhost smoke checks confirmed both `dashboard.php` and `create-announcement.php` loaded with the updated announcement workflow copy and shared summary markers.

Expected payoff:

- Prevents admin confusion about what is drafted, queued, and actually published.
- Makes reporting and troubleshooting announcement delivery more reliable.

### P2: Module-by-module authenticated QA after P0 and P1

- [x] Recruitment: verify create posting, edit posting, status transitions, email actions, and document viewing.
- [x] Applicant Tracking: verify scheduling, verification, forwarding, conversion-to-employee, and timeline/history visibility.
- [x] Evaluation: verify decision actions, final-result visibility, and hired-applicant handling.
- [x] Document Management: verify review, archive, preview, and employee/admin consistency.
- [ ] Timekeeping: verify modals, date pickers, helper forms, attendance logging, and leave logging.
- [ ] Payroll Management: verify summary review, period actions, bulk delete paths, and payroll generation flow.
- [ ] Notifications: verify modal details, mark-as-read behavior, mark-all-as-read behavior, and related-record links.
- [ ] Learning and Development: verify create training, enrollment management, and attendance status updates.
- [ ] User Management: verify role assignment, account actions, and reset-password flows.
- [ ] Settings: verify all save actions and persisted values, including recently added employee ID settings.
- [ ] Support: verify ticket review, forwarding, notes, and resolution flow.
- [ ] Praise modules: confirm retained scope, navigation intent, and whether each page still matches the approved product direction.

### P3: Cleanup and hardening

- [ ] Add browser-level regression notes to the QA markdown after each authenticated walkthrough.
- [ ] Convert repeated smoke-pass findings into module-specific acceptance criteria.
- [ ] Retire checklist items that become obsolete once legacy-script dependencies are removed.

Execution notes for first P2 slice:

- Authenticated QA for Recruitment, Applicant Tracking, Evaluation, and Document Management was completed on 2026-03-09 and logged in `revisions/ADMIN_P2_AUTHENTICATED_QA_RECRUITMENT_TRACKING_EVALUATION_DOCUMENTS_2026-03-09.md`.
- Recruitment and Document Management passed this level of authenticated HTML-level QA without a concrete blocking issue in the sampled flows.
- Applicant Tracking still lacks visible timeline/history presentation despite writing `application_status_history` records.
- Evaluation still lacks an admin final-decision/final-result action surface; the page currently exposes recommendation/configuration workflow rather than final disposition controls.
- Conversion-to-employee could not be live-exercised in this pass because the current authenticated dataset did not expose an actionable hired row.

## Recommended Execution Sequence

1. Secure admin-session access for browser QA.
2. Localize the remaining admin pages away from the legacy mega-script.
3. Remove native browser dialogs from admin.
4. Unify announcement workflow behavior.
5. Run authenticated browser smoke tests module by module, starting with legacy-script pages and high-risk state-changing modules.