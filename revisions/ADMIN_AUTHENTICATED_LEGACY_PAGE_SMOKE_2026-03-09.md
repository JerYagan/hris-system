# Admin Authenticated Legacy Page Smoke Pass

Date: 2026-03-09
Scope: Post-login smoke pass for legacy-script admin pages
Method: Session-cookie simulation against localhost using the temporary admin credential provided by the user

## Scope Covered

Authenticated GET smoke-tested routes:

- `applicants.php`
- `applicant-tracking.php`
- `applicant-profile.php`
- `applicant-document.php`
- `employee-profile.php`
- `document-preview.php`
- `timekeeping.php`
- `personal-information.php`
- `notifications.php`
- `settings.php`
- `profile.php`
- `praise.php`
- `praise-awards-recognition.php`
- `praise-employee-evaluation.php`
- `praise-reports-analytics.php`

What was verified:

- Login handler accepted the provided admin credential and returned admin dashboard HTML in the authenticated flow.
- Authenticated requests no longer rendered the sign-in page for the tested routes.
- Route-level load behavior, rendered title, final effective URL, and obvious plain-text/request-validation failures were checked.

What was not verified:

- No in-browser click-path testing of buttons, modals, filters, or forms.
- No mutation flows were executed.
- No JavaScript runtime errors were captured from a real browser console.

## Results Matrix

### Loaded successfully as authenticated admin

- `applicants.php` returned `200` and rendered `Applicants Registration | Admin`.
- `applicant-tracking.php` returned `200` and rendered `Applicant Tracking | Admin`.
- `applicant-profile.php` returned `200` and rendered `Applicant Profile | Admin`.
- `employee-profile.php` returned `200` and rendered `Employee Profile | Admin`.
- `timekeeping.php` returned `200` and rendered `Timekeeping | Admin`.
- `personal-information.php` returned `200` and rendered `Personal Information | Admin`.
- `notifications.php` returned `200` and rendered `Notifications | Admin`.
- `settings.php` returned `200` and rendered `Settings | Admin`.
- `profile.php` returned `200` and rendered `My Profile | Admin`.

### Loaded, but route behavior is not user-friendly without required parameters

- `applicant-document.php` returned `404` with plain-text response `Invalid document reference.` when opened without `document_id`.
- `document-preview.php` returned `400` with plain-text response `Invalid document preview request.` when opened without `document_id`.

Assessment:

- These appear to be parameterized detail endpoints, not list/dashboard pages.
- They are not broken in the strict sense, but direct access without the required query string degrades to raw text instead of an admin-shell error state or guided empty state.

### Route stubs / redirect-only pages

- `praise.php` resolved to `dashboard.php` and rendered `Admin Dashboard | DA HRIS`.
- `praise-awards-recognition.php` resolved to `dashboard.php` and rendered `Admin Dashboard | DA HRIS`.
- `praise-employee-evaluation.php` resolved to `dashboard.php` and rendered `Admin Dashboard | DA HRIS`.
- `praise-reports-analytics.php` resolved to `dashboard.php` and rendered `Admin Dashboard | DA HRIS`.

Assessment:

- These admin praise routes currently function as redirect stubs rather than independent module pages.
- If those routes are still present in navigation, bookmarks, or user expectations, this is a functional gap.
- If the module is intentionally deprecated, navigation and documentation should be aligned so the redirect is not surprising.

## Verified Findings

### 1. `applicant-document.php` does not provide a graceful admin-shell fallback when accessed without `document_id`

Severity: Medium

Evidence:

- Authenticated route response returned `404` plain text: `Invalid document reference.`
- File behavior in `pages/admin/applicant-document.php` explicitly emits a text/plain `404` when `document_id` is missing or invalid.

Impact:

- Direct route access, stale links, or malformed links drop the admin user out of the normal admin shell into a bare text response.

Suggested improvement:

- Return an admin-layout error state or redirect back to the relevant source page with a user-facing message.

### 2. `document-preview.php` does not provide a graceful admin-shell fallback when accessed without `document_id`

Severity: Medium

Evidence:

- Authenticated route response returned `400` plain text: `Invalid document preview request.`
- File behavior in `pages/admin/document-preview.php` explicitly exits early when `document_id` is missing or invalid.

Impact:

- Stale preview links or malformed URLs produce a hard request failure instead of a recoverable admin UI.

Suggested improvement:

- Render a standard admin-shell error page or bounce back to document management with context.

### 3. PRAISE admin routes are redirect stubs to dashboard

Severity: Medium

Evidence:

- Authenticated requests to `praise.php`, `praise-awards-recognition.php`, `praise-employee-evaluation.php`, and `praise-reports-analytics.php` all landed on `dashboard.php`.
- Corresponding files are simple auth-guard plus `header('Location: dashboard.php', true, 302);` stubs.

Impact:

- The routes exist but do not provide module content.
- This is effectively an unimplemented or intentionally retired module surface.

Suggested improvement:

- Either remove/retire the route surface from navigation and docs, or restore real module entry pages.

### 4. `applicant-profile.php` renders with `data-page="applicants"` instead of its own slug

Severity: Low

Evidence:

- Authenticated HTML for `applicant-profile.php` rendered `data-page=applicants`.
- File entrypoint sets `$activePage = 'applicants.php'` in `pages/admin/applicant-profile.php`.

Impact:

- Today this page still uses the legacy fallback script, so the practical impact is limited.
- Once the page is modularized, this slug mismatch will cause the wrong page module to load unless corrected.

Suggested improvement:

- Give the page its own stable slug and local JS entry when it is localized.

## Notes, Not Bugs

- `employee-profile.php` renders with `data-page=personal-information`, which appears intentional because it is grouped under the personal information admin area.
- `personal-information.php` displayed `Missing key profile details`, but this looked like a data-quality notice in the rendered page rather than a route failure.
- `profile.php` contained `No pending verification code was found. Send a new code first.` in the response body, which appears to be workflow text rather than a load failure.

## Conclusion

The temporary admin credential allowed a meaningful authenticated smoke pass for the legacy-script admin routes.

The strongest post-login issues found were:

1. Two parameterized detail endpoints (`applicant-document.php` and `document-preview.php`) fail with raw-text error responses when opened without required IDs.
2. The PRAISE admin routes are redirect stubs to the dashboard rather than functioning module pages.
3. `applicant-profile.php` has a page-slug mismatch that should be corrected before modular JS localization.

## Operational Note

The temporary admin credential was used for this smoke pass and should be rotated immediately after testing.