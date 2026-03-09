# Supabase Minimal Setup Guide

This guide is for beneficiary demos or acceptance testing on a fresh Supabase project with empty or minimal records, not the larger mock dataset used during development.

## Goal

After following this guide, the project will have:

- the full DA HRIS database schema
- the required RLS and storage policies
- the minimum baseline records needed by the PHP app
- four loginable users for each main role: admin, staff, employee, applicant

## What this setup does not include

This setup intentionally avoids loading broad sample data such as large applicant pools, payroll batches, reports, or document history. It only seeds the minimum baseline needed to let the beneficiary explore the system.

## 1. Create a new Supabase project

Create a new Supabase project from the dashboard, then collect these values from Project Settings:

- `SUPABASE_URL`
- `SUPABASE_ANON_KEY`
- `SUPABASE_SERVICE_ROLE_KEY`

## 2. Add the project keys to the local `.env`

Create or update the workspace `.env` file at the project root with at least:

```env
SUPABASE_URL=https://arxkzdplmkvjbhfxiokt.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
```

<!-- 
SUPABASE_URL=https://arxkzdplmkvjbhfxiokt.supabase.co
SUPABASE_ANON_KEY=sb_publishable_VmxyqkqExvsAYJMxfqIa3g_-FXtHtta
SUPABASE_SERVICE_ROLE_KEY=sb_secret_TUVxC7tiuy6Z8w7f45efyg_BRBOh_7O
 -->

The current PHP pages and auth flow read these exact variables.

## 3. Run the SQL files in this order

Open the Supabase SQL Editor and run these files in sequence.

### Step 1: Base schema

Run:

```text
.ignore/revisions/SUPABASE_SCHEMA.sql
```

This creates the core tables, enums, indexes, triggers, and the initial auth-linked public tables such as `user_accounts`, `user_role_assignments`, `people`, `employment_records`, and `applicant_profiles`.

### Step 2: Minimal baseline and four users

Run:

```text
revisions/SUPABASE_MINIMAL_BASELINE_AND_4_USERS.sql
```

This seed file creates only the minimum baseline records needed for a clean system startup:

- organization: `DA-ATI`
- office: `DA-ATI-CENTRAL`
- roles: `admin`, `staff`, `employee`, `applicant` plus `hr_officer` and `supervisor` for policy compatibility
- three basic job positions
- baseline system settings such as `employee_id_prefix`
- storage buckets used by the app
- four loginable users, one per main role

### Step 3: RLS phase 1

Run:

```text
.ignore/revisions/RLS_PHASE1.sql
```

This enables the recruitment, document, and several operational policies that the app expects beyond the base schema.

### Step 4: RLS phase 2

Run:

```text
.ignore/revisions/RLS_PHASE2_EMPLOYEE_HARDENING.sql
```

This adds employee-facing access restrictions for documents, leave, payroll, payslips, training, reports, and related tables.

### Step 5: Storage policies

Run:

```text
.ignore/revisions/STORAGE_POLICIES_MVP.sql
```

The minimal seed already creates these buckets so the storage policy SQL can be applied immediately:

- `hris-documents`
- `hris-applications`
- `hris-payslips`

## 4. Seeded user accounts

The minimal seed includes these four users:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@hris.local` | `P@ssw0rd!Admin123` |
| Staff | `staff@hris.local` | `P@ssw0rd!Staff123` |
| Employee | `employee@hris.local` | `P@ssw0rd!Employee123` |
| Applicant | `applicant@hris.local` | `P@ssw0rd!Applicant123` |

The seed marks all four accounts as active and confirmed, so they can log in immediately after the SQL finishes.

## 5. SQL snippet for the four users

If the beneficiary only wants the user-creation portion, the full query already exists in:

```text
revisions/SUPABASE_MINIMAL_BASELINE_AND_4_USERS.sql
```

The seeded user block creates:

- the `auth.users` records used by Supabase Auth
- matching `public.user_accounts` rows
- primary role assignments in `public.user_role_assignments`
- `people` rows for all four users
- active employment records for admin, staff, and employee
- an `applicant_profiles` row for the applicant account

## 6. Why the baseline seed is needed

An empty schema alone is not enough for this project because several pages assume a few baseline records already exist:

- registration looks for office code `DA-ATI-CENTRAL`
- login requires a matching `user_accounts` row and a primary role assignment
- employee pages require `people` and `employment_records`
- applicant pages require `applicant_profiles`
- hired-applicant conversion expects `system_settings.employee_id_prefix`
- file upload flows expect the three storage buckets to exist

## 7. Recommended smoke test after setup

After completing the SQL setup:

1. Log in as admin and verify the dashboard loads.
2. Log in as staff and verify the dashboard loads.
3. Log in as employee and verify the dashboard and employee profile context load.
4. Log in as applicant and verify the applicant dashboard loads.
5. Open a page that uses uploads to confirm the storage buckets and policies are available.

## 8. Notes for beneficiary handover

- This setup is safe for a clean Supabase project intended for evaluation or UAT.
- The seed is intentionally minimal and does not recreate the large mock testing dataset.
- The SQL file is written to be re-runnable without duplicating the baseline records.
- If the beneficiary later wants richer demo data, create a separate dedicated demo seed instead of modifying this minimal bootstrap.