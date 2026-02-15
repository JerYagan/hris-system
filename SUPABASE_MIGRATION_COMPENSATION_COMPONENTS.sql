-- Migration: add compensation component columns used by payroll generation
-- Safe to run on existing databases.

begin;

alter table public.employee_compensations
  add column if not exists base_pay numeric(14,2) not null default 0,
  add column if not exists allowance_total numeric(14,2) not null default 0,
  add column if not exists tax_deduction numeric(14,2) not null default 0,
  add column if not exists government_deductions numeric(14,2) not null default 0,
  add column if not exists other_deductions numeric(14,2) not null default 0;

-- Backfill legacy rows that previously only had monthly_rate.
-- Preserves prior behavior by treating monthly_rate as base pay when no components are set.
update public.employee_compensations
set base_pay = monthly_rate
where coalesce(base_pay, 0) = 0
  and coalesce(allowance_total, 0) = 0
  and coalesce(tax_deduction, 0) = 0
  and coalesce(government_deductions, 0) = 0
  and coalesce(other_deductions, 0) = 0
  and coalesce(monthly_rate, 0) > 0;

-- Add constraints only if missing.
do $$
begin
  if not exists (
    select 1 from pg_constraint
    where conname = 'employee_compensations_base_pay_nonnegative'
      and conrelid = 'public.employee_compensations'::regclass
  ) then
    alter table public.employee_compensations
      add constraint employee_compensations_base_pay_nonnegative check (base_pay >= 0);
  end if;

  if not exists (
    select 1 from pg_constraint
    where conname = 'employee_compensations_allowance_nonnegative'
      and conrelid = 'public.employee_compensations'::regclass
  ) then
    alter table public.employee_compensations
      add constraint employee_compensations_allowance_nonnegative check (allowance_total >= 0);
  end if;

  if not exists (
    select 1 from pg_constraint
    where conname = 'employee_compensations_tax_nonnegative'
      and conrelid = 'public.employee_compensations'::regclass
  ) then
    alter table public.employee_compensations
      add constraint employee_compensations_tax_nonnegative check (tax_deduction >= 0);
  end if;

  if not exists (
    select 1 from pg_constraint
    where conname = 'employee_compensations_gov_nonnegative'
      and conrelid = 'public.employee_compensations'::regclass
  ) then
    alter table public.employee_compensations
      add constraint employee_compensations_gov_nonnegative check (government_deductions >= 0);
  end if;

  if not exists (
    select 1 from pg_constraint
    where conname = 'employee_compensations_other_nonnegative'
      and conrelid = 'public.employee_compensations'::regclass
  ) then
    alter table public.employee_compensations
      add constraint employee_compensations_other_nonnegative check (other_deductions >= 0);
  end if;
end $$;

commit;
