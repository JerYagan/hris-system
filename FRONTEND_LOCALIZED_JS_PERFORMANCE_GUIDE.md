# DA HRIS Frontend Localized JS + Performance + UX State Standards

This document is the implementation standard for frontend behavior in DA HRIS.

Goal:
- Use localized JavaScript by user role and page/feature
- Improve page loading speed and runtime responsiveness
- Enforce complete UX states: loading, skeleton, empty, error, and success
- Keep code modular, maintainable, and audit-friendly

Scope:
- pages/admin/*
- pages/staff/*
- pages/employee/*
- pages/applicant/*

---

## 1) Non-Negotiable Rules

1. No single global mega-script per user area.
2. Each page loads only the JS it needs.
3. Each feature must support all UI states:
   - Initial loading
   - Skeleton loading
   - Empty state
   - Filter-empty state
   - Error state
   - Success state
4. Large tables must use server-side pagination/filtering/sorting.
5. Do not fetch thousands of rows on first page render.
6. Every expensive library is loaded conditionally (only when required by the current page).
7. Accessibility is mandatory for states and loading indicators.

---

## 2) Target JS Architecture (Localized by Role + Page + Feature)

## 2.1 Directory Structure

Use this target structure:

```text
assets/js/
  shared/
    core/
      dom.js
      events.js
      fetch.js
      state.js
      cache.js
      performance.js
    ui/
      modal.js
      dropdown.js
      toast.js
      skeleton.js
      empty-state.js
      error-state.js
    vendors/
      datatable-loader.js
      chart-loader.js
      flatpickr-loader.js
  admin/
    dashboard/
      index.js
      attendance-widget.js
      leave-widget.js
      announcements-widget.js
    personal-information/
      index.js
      list-table.js
      profile-modal.js
      family-modal.js
      education-modal.js
    payroll-management/
      index.js
      setup-table.js
      batch-table.js
      release-flow.js
  staff/
    dashboard/index.js
    recruitment/index.js
  employee/
    dashboard/index.js
    personal-information/index.js
  applicant/
    dashboard/index.js
    applications/index.js
```

## 2.2 Ownership Model

- shared/* = reusable primitives only
- role/page/index.js = page orchestrator
- role/page/*.js = feature-level modules

Never place page-specific logic in shared modules.

## 2.3 Naming Standard

- File names: kebab-case
- Module exports: named functions
- Main page entry function: init<Role><Page>Page

Example:
- initAdminDashboardPage
- initEmployeePersonalInformationPage

---

## 3) Page Boot Contract

Each page must expose metadata to JS:

```html
<body data-role="admin" data-page="dashboard">
```

Minimal loader pattern:

```php
<?php
$role = 'admin';
$page = 'dashboard';
?>
<script type="module" src="/hris-system/assets/js/bootstrap.js" defer></script>
```

In bootstrap.js:

```javascript
const role = document.body.dataset.role;
const page = document.body.dataset.page;

const map = {
  admin: {
    dashboard: () => import('/hris-system/assets/js/admin/dashboard/index.js'),
    'personal-information': () => import('/hris-system/assets/js/admin/personal-information/index.js'),
  },
  employee: {
    dashboard: () => import('/hris-system/assets/js/employee/dashboard/index.js'),
  },
};

const loader = map?.[role]?.[page];
if (loader) {
  loader().then((m) => m.default?.()).catch(console.error);
}
```

Outcome:
- Only the active page module is downloaded and executed

---

## 4) Performance Standards

## 4.1 Asset Loading

1. Remove globally loaded optional libraries from universal head includes.
2. Load DataTables, Chart.js, Flatpickr, SweetAlert only where used.
3. Use defer for non-critical scripts.
4. Use module scripts for feature chunks and tree-shaking readiness.
5. Keep shared bootstrap tiny (routing only).

## 4.2 Data Loading

1. First render must request only visible viewport data.
2. Default server page size:
   - table list: 10 or 20 rows
   - summary cards: aggregate endpoint only
3. Use explicit select columns only. No select-all payloads.
4. Use count-only strategy for totals (headers, dedicated endpoint, or RPC), not full-row fetch.
5. Cache top-nav profile/role/unread badge briefly (30-60 seconds) to avoid repeating calls on every page.

## 4.3 Runtime Efficiency

1. Build search indexes once, then update incrementally.
2. Avoid repeated document-wide querySelectorAll in input handlers.
3. Debounce expensive handlers (search/filter/resize/scroll).
4. Use event delegation for table row actions.
5. Do not instantiate DataTable for hidden/inactive tables.

## 4.4 Network and API Client

1. Add connect timeout and total timeout in API client.
2. Add retry with jitter for transient failures (idempotent GET only).
3. Return normalized API errors with user-safe messages.
4. Log slow requests for diagnostics.

---

## 5) UX State System (Required on Every Data-Driven Feature)

Each feature must implement these states:

1. Loading state
   - Visible immediately after action/page entry
   - Uses skeleton placeholders
2. Empty state (true empty dataset)
   - Message: what this section is
   - Action: primary CTA (Create/Add/Request)
3. Filter-empty state (user applied filters)
   - Message: no matches for current filters
   - Action: Clear filters
4. Error state
   - Clear message + retry button
5. Success state
   - Confirm action completion and next step

## 5.1 Skeleton Standard

Skeleton rules:
- Show skeleton within 100ms of data load start
- Keep minimum display 300ms to prevent flicker
- Match final content layout (card/table row/form block)
- Respect reduced-motion preference

Table skeleton example:
- 6 placeholder rows
- 5 columns
- pulse animation disabled under reduced motion

## 5.2 Empty State Content Standard

Every empty state must include:
- Short title
- One-line explanation
- Primary action button
- Optional secondary help link
- Context icon/illustration (lightweight)

Example copy:
- Title: No employee records yet
- Message: Employee records will appear here after profile creation.
- Action: Add employee

---

## 6) Role-Based Page Rules

## 6.1 Admin

- Heavy modules (Personal Information, Payroll, Documents, Reports) must be server-driven.
- No first-load full dataset hydration.
- Side panels/modals fetch data on open, not on page boot.

## 6.2 Staff

- Keep recruitment and operations pages lightweight.
- Preload only the next likely interaction if user intent is clear.

## 6.3 Employee

- Prioritize dashboard, personal info, notifications.
- Lazy load secondary tabs and historical data.

## 6.4 Applicant

- Prioritize jobs list, apply flow, application status.
- Keep form helpers local to apply page only.

---

## 7) Backend Query Rules Supporting Frontend Speed

1. Do not use huge static limits as a substitute for pagination.
2. Every list endpoint should support:
   - page
   - page_size
   - search
   - sort_by
   - sort_dir
   - filters
3. Add proper DB indexes for frequent filters and sorts.
4. Pre-compute or aggregate where possible for dashboard cards.
5. Return normalized payload shape:

```json
{
  "rows": [],
  "total": 0,
  "page": 1,
  "page_size": 20,
  "has_next": false
}
```

---

## 8) Accessibility and Inclusive UX

1. Skeleton regions use aria-busy=true while loading.
2. Status updates use aria-live=polite.
3. Empty/error states are keyboard reachable.
4. Buttons and links in state cards have clear labels.
5. Color is never the only status indicator.

---

## 9) Observability and Budgets

Track these per page:
- TTFB
- LCP
- CLS
- INP
- Total JS size loaded
- Number of API calls made on first load

Target budgets (local/LAN baseline):
- First-load API calls: <= 4 for normal pages, <= 6 for complex pages
- JS downloaded at first load: <= 120KB gzip per page
- LCP: <= 2.5s
- INP: <= 200ms

If page exceeds budget, PR is blocked until a mitigation note is added.

---

## 10) Migration Plan from Existing Monolithic Scripts

Phase 1: Stabilize
1. Freeze new additions to monolithic script.
2. Introduce bootstrap.js router + data-role/data-page.
3. Move topnav/sidebar shared behaviors to shared/core and shared/ui.

Phase 2: Extract high-impact modules
1. Extract admin dashboard feature modules.
2. Extract personal-information and payroll pages.
3. Load DataTables and charts only in extracted modules.

Phase 3: Complete role/page localization
1. Extract remaining admin pages.
2. Extract staff, employee, and applicant pages.
3. Remove legacy mega-script include.

Phase 4: Optimize and enforce
1. Add budgets in CI checklist.
2. Add lint rule for forbidden global selectors in loops.
3. Add performance regression checklist to PR template.

---

## 11) Definition of Done (Per Feature/Page)

A feature/page is not done unless:

1. JS is localized to role + page + feature.
2. Optional libraries are conditionally loaded.
3. Loading + skeleton + empty + filter-empty + error states exist.
4. API calls are paginated and column-scoped.
5. Accessibility checks pass for all states.
6. Performance budget is measured and documented.

---

## 12) PR Checklist Template

Copy into every PR:

- [ ] Page uses localized JS entry module
- [ ] No new logic added to legacy global script
- [ ] Optional vendors loaded only when needed
- [ ] Skeleton state implemented
- [ ] Empty and filter-empty states implemented
- [ ] Error state with retry implemented
- [ ] Data fetch is paginated and filtered server-side
- [ ] API response payload is minimal and scoped
- [ ] Accessibility checks completed
- [ ] Performance metrics captured before/after

---

## 13) Recommended Immediate Actions for This Repository

1. Create assets/js/bootstrap.js and role/page module map.
2. Split current admin script into:
   - shared shell interactions
   - admin/dashboard/index.js
   - admin/personal-information/index.js
   - admin/payroll-management/index.js
3. Replace global vendor includes with conditional loaders.
4. Implement standardized skeleton/empty/error components in shared/ui.
5. Refactor large first-load queries to server-side pagination and narrow selects.

---

## 14) Team Agreement

This standard is mandatory for all new frontend work and all touched pages.
If a legacy page is modified, it must be moved closer to this standard as part of the same change set.
