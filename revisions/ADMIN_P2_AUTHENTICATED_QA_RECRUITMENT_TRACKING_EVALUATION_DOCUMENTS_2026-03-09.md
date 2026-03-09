# Admin P2 Authenticated QA: Recruitment, Applicant Tracking, Evaluation, Document Management

Date: 2026-03-09
Scope: Authenticated admin QA for the first four P2 modules
Method: Session-backed localhost requests using the temporary admin credential previously provided for testing
Reviewer: GitHub Copilot

## Coverage

Routes checked:

- `pages/admin/recruitment.php`
- `pages/admin/applicant-tracking.php`
- `pages/admin/evaluation.php`
- `pages/admin/document-management.php`
- Follow-through document/view routes reached from live page content:
  - `pages/admin/applicant-profile.php?application_id=...`
  - `pages/admin/applicant-document.php?document_id=...`
  - `pages/admin/document-preview.php?document_id=...`

What was verified:

- Authenticated route load success and expected admin module titles.
- Presence of the key forms, modals, and action hooks tied to the requested workflows.
- Live follow-through on one recruitment applicant profile link, one applicant document link, and one document preview link exposed by the authenticated UI.
- Employee/applicant grouping hooks in document management.

What was not verified:

- No browser click automation was available, so modal opening, in-browser JS execution order, and mutation success were verified at the HTML/action-surface level rather than through real clicks.
- No mutating POST actions were executed for create/edit posting, schedule interview, update status, review, archive, or evaluation runs.
- Conversion-to-employee could not be exercised because the current authenticated dataset did not expose an actionable hired row.

## Findings

### 1. Applicant Tracking does not expose an end-to-end timeline/history view even though status history is being recorded

Severity: Medium

Evidence:

- The authenticated page rendered tables with `Latest Interview` and `Interview & Feedback`, plus schedule and status-update modals.
- Backend actions write to `application_status_history` for both `schedule_interview` and `update_status`.
- The rendered admin UI did not expose any visible timeline/history section or table for those recorded transitions.

Impact:

- Admin can update applicant stages, but cannot review a full transition history from the Applicant Tracking page itself.
- This leaves the module short of the checklist requirement to verify timeline/history visibility.

Suggested fix:

- Add a visible transition history/timeline panel sourced from `application_status_history` and interview history for each application.

### 2. Evaluation exposes recommendation/configuration flows, but not an admin final decision action surface

Severity: Medium

Evidence:

- Authenticated `evaluation.php` rendered `Generate System Recommendations`, `Run Rule-Based Evaluation`, criteria configuration, hired-applicant hide/show filter, and the rule-results table.
- `pages/admin/includes/evaluation/actions.php` only handles criteria save, position-criteria save, recommendation generation, and rule-evaluation runs.
- `pages/admin/includes/evaluation/bootstrap.php` loads `application_feedback`, but the rendered view does not expose approval/reject/final-result decision controls or a dedicated final-result visibility section.

Impact:

- The module supports scoring and recommendation review, but not the checklist requirement to verify decision actions and final-result visibility from the admin evaluation surface.
- Final evaluation disposition still appears to live outside this page.

Suggested fix:

- Decide whether final evaluation disposition belongs in this module or another admin module, then expose the final-result state clearly in the UI and add admin decision actions if Evaluation remains the authoritative page.

## Verified Working Paths

### Recruitment

Verified:

- `recruitment.php` returned `200` authenticated.
- Create and edit job posting forms were present.
- Archive action hooks were present.
- Recruitment email template configuration form was present.
- Recruitment page exposed live applicant profile links.
- A sampled applicant profile route loaded successfully and rendered `Submitted Documents`.
- A sampled applicant document route loaded successfully with `application/pdf` content.

Assessment:

- The requested recruitment surfaces for create posting, edit posting, email actions, and document-view follow-through are present and reachable in the authenticated admin flow.

### Applicant Tracking

Verified:

- `applicant-tracking.php` returned `200` authenticated.
- Schedule Interview modal/form was present.
- Update Status modal/form was present.
- Hired Applicants section was present.
- Latest interview and feedback columns were present.
- Backend actions include applicant notification and email-delivery logging for interview scheduling and status updates.

Limit:

- The current dataset did not expose an actionable hired conversion row, so `Add as Employee` could not be exercised live in this pass.

### Evaluation

Verified:

- `evaluation.php` returned `200` authenticated.
- Recommendation generation action was present.
- Rule-based evaluation action was present.
- Hired-applicant hide/show control was present.
- Rule result, threshold, and score visibility were present in the table.

Assessment:

- Recommendation and rule-run workflow surfaces are present.
- Final admin decision workflow is the missing piece, not the scoring/configuration path.

### Document Management

Verified:

- `document-management.php` returned `200` authenticated.
- Upload, review, and archive action surfaces were present.
- Both Employee and Applicant account tabs were rendered.
- Employee and applicant row grouping markers were present in the table HTML.
- A sampled `document-preview.php?document_id=...` route returned `200` and served an inline HTML preview shell.

Assessment:

- Review, archive, preview, and employee/applicant grouping consistency are all present in the authenticated document-management flow sampled here.

## Route Check Summary

- `recruitment.php` loaded and exposed create/edit/archive/email-template hooks.
- `applicant-tracking.php` loaded and exposed schedule/update-status flows; hired conversion remained data-dependent in this run.
- `evaluation.php` loaded and exposed recommendation/configuration workflow, but not final admin disposition controls.
- `document-management.php` loaded and exposed upload/review/archive flows with working preview follow-through.

## Conclusion

The authenticated P2 QA pass for these four modules found two real gaps:

1. Applicant Tracking lacks visible timeline/history presentation despite writing status history.
2. Evaluation lacks an admin final-decision/final-result action surface even though recommendation and criteria tooling are present.

Recruitment and Document Management both passed this level of authenticated QA without a concrete blocking issue in the sampled flows.

## Operational Note

The temporary admin credential was used again for this QA pass and should be rotated immediately after testing.
