# System-Wide Revision Sprint Tracker (Execution Board)

Source of truth:
- `revisions/SYSTEM_WIDE_COMPREHENSIVE_REVISION_CHECKLIST.md`

Use this tracker for sprint planning, ownership, and weekly execution.

Status legend:
- `TODO` | `IN PROGRESS` | `BLOCKED` | `FOR QA` | `DONE`

Priority legend:
- `P0` Critical blocker
- `P1` High business value
- `P2` Medium
- `P3` Nice-to-have / defer-capable

---

## 1) Global Foundations (Sprint 1)

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| GF-01 | Enforce PST (UTC+08:00) for all UI displays, filters, and operations | BE + FE | P0 | TODO | - |
| GF-02 | Replace native alerts with SweetAlert2 across modules | FE | P0 | TODO | - |
| GF-03 | Standardize Flatpickr for all date/date-time fields | FE | P1 | TODO | GF-01 |
| GF-04 | Add global status-change confirmation + reason capture policy | FE + BE | P0 | TODO | GF-02 |
| GF-05 | Add immutable audit logs for all final decisions/overrides | BE | P0 | TODO | GF-04 |
| GF-06 | Apply branding updates (ATI naming, Bagong Pilipinas logo, Department->Division labels) | FE | P1 | TODO | - |
| GF-07 | Implement normalized status dictionaries and transition rules | BE + FE | P0 | TODO | GF-05 |
| GF-08 | Implement global in-app notification payload standard (module, record, decision, actor, PST timestamp) | BE + FE | P0 | TODO | GF-01, GF-05 |

---

## 2) Staff -> Admin Approval Chain (Mandatory System Pattern)

### 2.1 Canonical Flow (to implement per module)
1. Staff performs `Submit to Admin for Approval/Reject`.
2. Record status moves to `For Admin Approval` (or module-approved equivalent).
3. Admin sees record in module pending-approval table.
4. Admin decides (`Approve`/`Reject`/`Needs Revision` etc.) with remarks if required.
5. Staff sees Admin decision in queue + timeline + detail view.
6. Staff receives in-app notification with decision details and PST timestamp.

### 2.2 Shared Tasks

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| SA-01 | Build reusable `Submit to Admin` action contract for staff-facing modules | FE + BE | P0 | TODO | GF-07 |
| SA-02 | Build admin pending-approval list template (search/status/date/office/type, stable columns, pill status, compact actions) | FE | P0 | TODO | GF-02 |
| SA-03 | Build reusable admin review modal template (readonly context -> decision -> notes) | FE | P0 | TODO | SA-02 |
| SA-04 | Add backend gate: staff cannot set final admin-only states | BE | P0 | TODO | GF-07 |
| SA-05 | Add lock rule: staff cannot edit recommendation after submit unless returned by admin | BE + FE | P0 | TODO | SA-01 |
| SA-06 | Emit in-app notifications on final admin decisions | BE + FE | P0 | TODO | GF-08 |
| SA-07 | Add QA test matrix for full staff-admin loop per module | QA | P0 | TODO | SA-01..SA-06 |

---

## 3) Module Sprint Backlog (System-Wide)

## 3.1 Dashboard

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| DB-01 | Replace/add summary cards per revision list | FE + BE | P1 | TODO | GF-07 |
| DB-02 | Add chart timestamps + configurable schedules (PST) | FE + BE | P1 | TODO | GF-01 |
| DB-03 | Convert plantilla chart to donut | FE | P2 | TODO | DB-02 |
| DB-04 | Align status labels with notification module statuses | FE + BE | P1 | TODO | GF-07 |

## 3.2 Recruitment (Listing, Applicants, Tracking, Evaluation)

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| RC-01 | Fix `Selected office or position is invalid` new-job blocker | BE + FE | P0 | TODO | - |
| RC-02 | Canonicalize `Job Title` vs `Position Title` naming | Product + FE + BE | P0 | TODO | - |
| RC-03 | Add required docs + structured fields + profile preview in review views | FE + BE | P1 | TODO | RC-02 |
| RC-04 | Sync status timeline across applicants/tracking/evaluation | BE + FE | P0 | TODO | GF-07 |
| RC-05 | Implement staff->admin decision handoff with per-module pending table | FE + BE | P0 | TODO | SA-01..SA-06 |
| RC-06 | Add auto email workflow (submitted, passed/failed, next-stage notice) | BE | P1 | TODO | GF-08 |
| RC-07 | Rule-engine scope implementation (global MVP or position-specific by approved decision) | BE + FE | P0 | TODO | RC-02 |

## 3.3 Applicant Portal

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| AP-01 | Jobs/applications/feedback pages status consistency and timeline clarity | FE + BE | P1 | TODO | RC-04 |
| AP-02 | Add standardized empty/filter-empty/error/success states | FE | P1 | TODO | GF-02 |
| AP-03 | Ensure applicant notifications match decision events | BE + FE | P1 | TODO | RC-06 |

## 3.4 Personal Information + PDS

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| PI-01 | Fix cannot-add-employee issue | BE + FE | P0 | TODO | - |
| PI-02 | Fix tab-highlight bug | FE | P1 | TODO | - |
| PI-03 | Searchable geo/civil/blood dropdowns + ZIP autofill | FE | P1 | TODO | - |
| PI-04 | Enforce contact requirement guardrail on Add Employee | BE + FE | P0 | TODO | PI-01 |
| PI-05 | Add spouse extension + print-ready PDS option | FE + BE | P2 | TODO | PI-01 |
| PI-06 | Duplicate employee merge/delete flow with audit logging | BE + FE | P1 | TODO | GF-05 |

## 3.5 Document Management

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| DM-01 | Fix `Needs Revision` shown as `Draft` bug | BE + FE | P0 | TODO | GF-07 |
| DM-02 | Add search/filter and review queue UX standard | FE + BE | P1 | TODO | SA-02 |
| DM-03 | Restrict final actions and enforce return-with-notes flow | BE + FE | P1 | TODO | GF-04 |
| DM-04 | Add admin 201 records tab and finalize official 201 list decision | FE + BE + Product | P1 | TODO | - |
| DM-05 | Implement staff->admin approval chain + decision notifications | FE + BE | P0 | TODO | SA-01..SA-06 |

## 3.6 Timekeeping + Leave + RFID

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| TK-01 | Implement approved late-policy mode (no-late/flexi/strict) | BE + FE | P0 | TODO | Open decision |
| TK-02 | Leave/time-adjustment lock rules and rejection refile rules | BE + FE | P0 | TODO | GF-07 |
| TK-03 | Add OB handling and leave preview details | FE + BE | P1 | TODO | TK-02 |
| TK-04 | Build RFID + OTP flow with attempt limits and logs | BE + FE | P1 | TODO | GF-01, GF-05 |
| TK-05 | Implement staff->admin leave/adjustment approval loop + notifications | FE + BE | P0 | TODO | SA-01..SA-06 |

## 3.7 Payroll

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| PR-01 | Integrate timekeeping outputs to payroll computation | BE | P0 | TODO | TK-02 |
| PR-02 | Salary grade computation + deductions breakdown in UI | BE + FE | P0 | TODO | PR-01 |
| PR-03 | Enforce staff prepare -> admin final payroll decision flow | FE + BE | P0 | TODO | SA-01..SA-06 |
| PR-04 | Fix payslip generation/sending and secure email handling | BE + FE | P1 | TODO | PR-02 |
| PR-05 | Add audit logs for compute/export/send/approve actions | BE | P0 | TODO | GF-05 |

## 3.8 Reports and Analytics

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| RA-01 | Rename to `REPORTS and Analytics` | FE | P2 | TODO | - |
| RA-02 | Apply no-late report rules based on approved policy | BE + FE | P1 | TODO | TK-01 |
| RA-03 | Add audit-centric cross-module KPI reports | BE + FE | P1 | TODO | GF-05 |

## 3.9 Learning and Development

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| LD-01 | Add new training creation + advance notification | FE + BE | P1 | TODO | GF-08 |
| LD-02 | Single attendance log per employee + history view | FE + BE | P1 | TODO | LD-01 |
| LD-03 | Reorder module sections and audit log coverage | FE + BE | P2 | TODO | GF-05 |

## 3.10 User Management

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| UM-01 | Employment classification options + division autopopulate | FE + BE | P1 | TODO | GF-06 |
| UM-02 | Enforce admin safety rules (max 2 active admins, protected admin disable rule) | BE + FE | P0 | TODO | - |
| UM-03 | Keep role assignment/support routing aligned to privileges | BE + FE | P1 | TODO | GF-07 |

## 3.11 Notifications, Announcements, Profile, Settings, Support

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| NS-01 | Targeted announcements by group/employee | FE + BE | P1 | TODO | GF-08 |
| NS-02 | Add admin password change + verification/recovery clarity | FE + BE | P1 | TODO | - |
| NS-03 | Standardize settings/support flows and replace inconsistent file button behavior | FE + BE | P2 | TODO | GF-02 |

## 3.12 Praise / Employee Evaluation (Decision-Dependent)

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| PE-01 | Finalize keep/limit/remove scope decision | Product + Admin Stakeholders | P0 | TODO | - |
| PE-02 | If retained: privilege-aligned finalization flow + reports placement | FE + BE | P1 | TODO | PE-01 |
| PE-03 | If removed: clean route/nav/permission deprecation plan | FE + BE | P1 | TODO | PE-01 |

---

## 4) Frontend Localized JS Migration Tracker

| ID | Work Item | Owner | Priority | Status | Depends On |
|---|---|---|---|---|---|
| LJ-01 | Validate `bootstrap.js` role/page dynamic map completeness | FE | P0 | TODO | - |
| LJ-02 | Ensure all pages use `data-role` + `data-page` boot contract | FE | P0 | TODO | LJ-01 |
| LJ-03 | Extract admin page-localized `index.js` modules | FE | P0 | TODO | LJ-02 |
| LJ-04 | Extract staff/employee/applicant localized modules | FE | P1 | TODO | LJ-02 |
| LJ-05 | Remove global mega-script dependencies safely | FE | P0 | TODO | LJ-03, LJ-04 |
| LJ-06 | Add conditional vendor loaders (SweetAlert2, Flatpickr, tables/charts) | FE | P1 | TODO | LJ-03 |
| LJ-07 | Enforce skeleton/empty/filter-empty/error/success on all data features | FE | P1 | TODO | LJ-03, LJ-04 |
| LJ-08 | Capture payload/API-call performance baseline and improvements | FE + QA | P1 | TODO | LJ-05 |

---

## 5) QA Sprint Gate Checklist

- [ ] Functional: all P0 items pass on staging.
- [ ] Privilege: Staff cannot execute final admin-only actions.
- [ ] Workflow: Staff submit -> Admin pending table -> Admin decision -> Staff visibility + notification is verified per module.
- [ ] Timezone: all timestamps and schedules display in PST consistently.
- [ ] UX: SweetAlert2 and Flatpickr are consistently used.
- [ ] Performance: localized JS loading verified and global script regressions absent.
- [ ] Security: audit logs and override reason enforcement verified.

---

## 6) Suggested Sprint Sequence

- **Sprint 1 (P0 Foundations):** `GF-*`, `SA-*`, `RC-01`, `PI-01`, `DM-01`, `UM-02`, `LJ-01..LJ-03`
- **Sprint 2 (Core Workflows):** `RC-*`, `DM-*`, `TK-01..TK-03`, `PR-01..PR-03`, `LJ-04..LJ-06`
- **Sprint 3 (Expansion + Hardening):** `AP-*`, `DB-*`, `RA-*`, `LD-*`, `NS-*`, `LJ-07..LJ-08`
- **Sprint 4 (Decision-Dependent + Rollout):** `PE-*`, regressions, full QA gate, release readiness
