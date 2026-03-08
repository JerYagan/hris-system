# Payroll PDF + Email Smoke QA Report

Date: 2026-03-01
Scope: Payroll payslip generation, profile attachment path, and email attachment prerequisites

## Fixes Applied

1) Corrected project root resolution for payslip generation and attachment lookup
- Admin payslip generation path now uses project root depth that resolves to `hris-system`.
- Staff payslip generation path now uses project root depth that resolves to `hris-system`.
- Admin email attachment candidate lookup now resolves from the same project root.

2) Staff generation payload completeness
- Staff `generate_payslip_run` payroll item fetch now includes `basic_pay`, `overtime_pay`, and `allowances_total` to keep generated PDF amounts complete and accurate.

## Smoke Validation (Code/Runtime Preconditions)

- [x] PHP syntax checks passed:
  - `pages/admin/includes/payroll-management/actions.php`
  - `pages/staff/includes/payroll-management/actions.php`
- [x] Required libraries detected:
  - `Dompdf\\Dompdf` = available
  - `PHPMailer\\PHPMailer\\PHPMailer` = available
- [x] No editor diagnostics errors on modified payroll files.

## Expected Functional Outcomes

- Payslip generation writes PDF files under `storage/payslips` and stores `pdf_storage_path` in `payslips`.
- Employee profile payroll actions (`View PDF`/`Download`) resolve generated PDF paths from `pdf_storage_path`.
- Payroll email send flow uses the generated file as required attachment and logs send attempts.

## Operational Note

- A full UI-authenticated outbound mail delivery test is environment-dependent (SMTP account/network/session), but all application-side generation and attachment prerequisites are now in place and validated at code/runtime precondition level.
