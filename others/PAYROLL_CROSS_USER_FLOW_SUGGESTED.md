# Suggested Payroll Module Flow (All Users)

## 1) Goal
Define a single payroll workflow across Admin, Staff/HR, and Employee portals that:
- uses timekeeping outputs (including absences) to affect payroll,
- requires HR review and approval before release,
- generates payslips for employee self-service access.

Scope:
- Included users: Admin, Staff/HR, Employee
- Excluded: Applicant

---

## 2) Role Responsibilities

### Admin
- Owns payroll policy/configuration (period calendar, deduction rules, approval thresholds).
- Can override or reopen payroll runs with strict audit logs.
- Monitors release completion and compliance reports.

### Staff / HR
- Prepares payroll run inputs.
- Reviews computed payroll, verifies anomalies, and resolves adjustment items.
- Approves payroll run for release.
- Triggers payslip generation/release after approval.

### Employee
- Views own payroll history only.
- Views/downloads released payslips only.
- Sees deduction breakdown (including absence-related deductions).
- Cannot modify payroll values.

---

## 3) End-to-End Flow

## Phase A: Timekeeping-to-Payroll Input Build
1. Collect approved timekeeping records for the payroll period:
   - attendance logs
   - approved leave requests
   - approved CTO leave-with-pay requests
   - approved time adjustments
2. Compute attendance summary per employee:
   - days present
   - approved leave days (paid/unpaid by leave type)
   - lateness/undertime totals
   - unexcused absences
3. Convert timekeeping summary into payroll-impact values:
   - absence deduction
   - lateness/undertime deduction
   - CTO leave-with-pay earnings
4. Store computed components as payroll inputs (item-level components or adjustments) before final run approval.

## Phase B: Payroll Computation
1. Start payroll run for selected payroll period.
2. For each active employee in-scope:
   - load latest active compensation setup,
   - calculate gross pay,
   - apply deductions (tax + government + other + timekeeping-based deductions),
   - calculate net pay.
3. Set run status to `computed` and persist payroll items.

## Phase C: HR Review and Approval (Mandatory Gate)
1. HR opens computed run and reviews:
   - variance vs prior run,
   - zero/negative net pay anomalies,
   - high absence deductions,
   - missing timekeeping edge cases.
2. HR applies/approves salary adjustments as needed.
3. HR confirms payroll run decision:
   - `approved` -> allowed to release
   - `rejected/cancelled` -> return for recompute/fix
4. Record all decisions in activity logs with actor, timestamp, old/new state, and notes.

## Phase D: Payslip Generation and Release
1. System generates payslip records for all payroll items in approved run.
2. Generate PDF (or storage path) per payslip.
3. Mark payslips as released and set run status to `released`.
4. Send employee notifications that payslip is available in Employee portal.

## Phase E: Employee Self-Service Access
1. Employee portal shows only released payroll entries.
2. Employee can:
   - open payroll details,
   - view earnings/deductions breakdown,
   - view/download payslip PDF.
3. Employee cannot edit payroll items, status, or totals.

---

## 4) Payroll Calculation Contract (Including Absences)

For each employee and period:

- Base Earnings:
  - base pay per cycle
  - allowances per cycle
- Timekeeping Earnings:
   - approved CTO leave UT w/ pay amount
- Statutory/Fixed Deductions:
  - tax
  - government deductions
  - other configured deductions
- Attendance Deductions:
  - unexcused absence deduction
  - lateness/undertime deduction
  - unpaid leave deduction

Net Pay Formula:

`net_pay = (base_pay + allowances + cto_leave_earnings + other_earnings) - (tax + government + other_deductions + absence_deduction + late_undertime_deduction + unpaid_leave_deduction)`

Implementation note:
- Absence and lateness deductions must be traceable to underlying timekeeping records for auditability.

---

## 5) Status Lifecycle (Recommended)

### Payroll Period
- `open -> processing -> posted -> closed`

### Payroll Run
- `draft -> computed -> approved -> released`
- Optional failure path: `computed/approved -> cancelled` (with reason and audit)

### Payslip
- `pending -> generated -> released`

Guardrails:
- Release is blocked unless run is `approved`.
- Employee portal visibility is blocked unless payslip is `released`.

---

## 6) Required Approval Rules

1. Timekeeping approval dependency:
   - only approved leave/CTO/time-adjustment records are included in payroll computation.
2. HR approval dependency:
   - release and employee visibility require HR-approved payroll run.
3. Dual-control recommendation:
   - if preparer and approver are same user, require admin override flag + reason.
4. Re-open policy:
   - any post-approval changes must reopen run and require re-approval.

---

## 7) Data Objects and Source Tables

Timekeeping sources:
- `attendance_logs`
- `leave_requests`
- `time_adjustment_requests`
- `overtime_requests` (legacy storage for CTO leave-with-pay entries)
- `leave_types`

Payroll core:
- `payroll_periods`
- `payroll_runs`
- `payroll_items`
- `payroll_adjustments`
- `payslips`
- `employee_compensations`

Security and traceability:
- `activity_logs`
- `notifications`

---

## 8) Minimum UX by Portal

### Staff/HR Payroll Management
1. Compute Monthly Payroll
2. Review Salary Adjustments
3. Approve Payroll Run
4. Generate/Release Payslips

Each action requires:
- confirmation prompt,
- server transition validation,
- success/error redirect alert,
- activity log write.

### Admin Payroll Management
- Full visibility across periods/runs/items/payslips.
- Override/reopen actions with mandatory reason.
- Compliance reporting and audit trail review.

### Employee Payroll
- Payroll history list (released only).
- Payslip detail + download.
- Breakdown view showing absence/timekeeping deductions.

---

## 9) Notifications

Trigger notifications for:
- HR approval request (run moved to `computed`).
- HR decision recorded (`approved` / `rejected`).
- Payslip released (employee recipient).

Notification payload should include:
- period code,
- run reference,
- action summary,
- portal link.

---

## 10) Audit, Security, and Controls

1. CSRF validation on all payroll write actions.
2. Role/scope checks on all reads/writes.
3. Transition matrix enforcement at server layer.
4. Immutable log record for every payroll status mutation.
5. Payslip release idempotency (safe re-run without duplicates).
6. Reconciliation check before release:
   - sum(net pay) and count(items) must match run totals.

---

## 11) Suggested Acceptance Criteria

1. Absence from timekeeping reduces payroll net pay for the same period.
2. Payroll run cannot be released before HR approval.
3. Payslips are generated and accessible only after release.
4. Employee portal does not show unreleased payroll/payslips.
5. All payroll decisions are searchable in activity logs.
6. Re-open/recompute flow preserves audit trail and requires re-approval.

---

## 12) Practical Rollout Sequence

1. Finalize deduction rules mapping from timekeeping to payroll components.
2. Implement compute pipeline using approved timekeeping inputs.
3. Enforce HR approval gate before release.
4. Implement payslip generation + storage + employee portal retrieval.
5. Add reconciliation checks and audit dashboards.
6. Run UAT with scenarios:
   - perfect attendance,
   - with absences,
   - with CTO leave-with-pay entries,
   - with manual adjustment,
   - re-open and re-approve cycle.
