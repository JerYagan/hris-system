# Timekeeping Payroll CTO-Only QA Report

Date: 2026-03-01
Scope: Employee -> Staff -> Admin CTO-only leave-style flow and payroll linkage

## QA Checks and Outcomes

### 1) Employee CTO submission (leave-style)
- [x] CTO is submitted through leave request flow (`create_leave_request`) with leave type `CTO`.
- [x] CTO validation rules are enforced:
  - no past date filing,
  - same payroll month date range,
  - no cross cut-off window (1-15 vs 16-end),
  - overlap checks against pending/approved requests.
- [x] Request is created with `pending` status and logged in activity logs.

### 2) Staff recommendation stage
- [x] Staff recommendation uses leave-style recommendation action (`recommend_leave_request`).
- [x] Recommendation is routed to Admin as recommendation-only (`submitted_for_admin_approval=true`).
- [x] Requester and Admin notifications are generated for recommendation handoff.

### 3) Admin final approval stage
- [x] Admin final decision for leave-style CTO uses `review_leave_request` with final status update on `leave_requests`.
- [x] Finalized statuses are locked (approved/rejected/cancelled cannot be altered via review action).
- [x] Immutable transition logs are written (`logStatusTransition`).

### 4) Status/visibility alignment
- [x] Employee side shows CTO under Leave/CTO request table.
- [x] Staff side already uses a combined Leave/CTO queue.
- [x] Admin side is now aligned to a combined Leave/CTO queue table.

### 5) Payroll computation + payslip linkage
- [x] Payroll compute reads attendance source and calculates timekeeping deductions.
- [x] Item-level payroll breakdown logs include `timekeeping_deductions` (`compute_item_breakdown`).
- [x] Export/payslip breakdown surfaces timekeeping deductions in outputs.

## Notes
- Legacy `overtime_requests` paths remain for official business (`[OB]`) and historical CTO entries, but employee-facing overtime filing is blocked and replaced by CTO leave-style filing.
- Current CTO-only operational path is leave-style via `leave_requests` with `CTO` leave type.
