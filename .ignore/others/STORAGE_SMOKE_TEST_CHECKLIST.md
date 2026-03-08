# Storage Smoke Test Checklist (MVP)

Use this checklist after running schema, seed, RLS, and storage policy scripts.

## Prerequisites

- Buckets created:
  - hris-documents
  - hris-applications
  - hris-payslips
- Buckets are private (Public bucket OFF)
- Storage policies applied from STORAGE_POLICIES_MVP.sql
- Test users exist and can sign in:
  - admin@da.gov.ph
  - staff@da.gov.ph
  - employee@da.gov.ph
  - applicant@da.gov.ph

## 1) Get User IDs (UIDs)

Run in SQL Editor:

```sql
select email, id
from public.user_accounts
where email in (
  'admin@da.gov.ph',
  'staff@da.gov.ph',
  'employee@da.gov.ph',
  'applicant@da.gov.ph'
)
order by email;
```

Record UIDs for test paths.

## 2) Confirm Policies Exist

Run:

```sql
select policyname, cmd, roles
from pg_policies
where schemaname = 'storage'
  and tablename = 'objects'
order by policyname;
```

Expected: policy rows for docs/apps/payslips owner and admin/hr/staff access.

## 3) Path Convention Check

Use this naming convention for uploads:

- hris-documents/users/{uid}/filename
- hris-applications/users/{uid}/filename
- hris-payslips/users/{uid}/filename

## 4) Access Tests (Pass/Fail Matrix)

### A. Employee tests

- [ ] Upload file as employee to hris-documents/users/{employee_uid}/doc1.pdf → PASS
- [ ] Upload file as employee to hris-documents/users/{staff_uid}/doc2.pdf → FAIL
- [ ] Read own payslip at hris-payslips/users/{employee_uid}/pay1.pdf → PASS
- [ ] Read another user payslip path → FAIL

### B. Applicant tests

- [ ] Upload file as applicant to hris-applications/users/{applicant_uid}/resume.pdf → PASS
- [ ] Upload file as applicant to hris-applications/users/{employee_uid}/resume.pdf → FAIL

### C. Staff/Admin tests

- [ ] Staff read employee file in hris-documents/users/{employee_uid}/... → PASS
- [ ] Staff read applicant file in hris-applications/users/{applicant_uid}/... → PASS
- [ ] Admin read all three buckets for test files → PASS

## 5) Negative Tests

- [ ] Anonymous user cannot list/read private bucket objects → FAIL (as expected)
- [ ] Wrong folder structure (missing users/{uid}) is denied for owner policies → FAIL (as expected)

## 6) Evidence to Capture

- Screenshot of successful upload for owner path
- Screenshot of denied upload for non-owner path
- Screenshot of policy list query output
- Screenshot of one staff/admin cross-user read success

## 7) Exit Criteria

Mark storage setup complete when all PASS/FAIL outcomes match expected behavior above.
