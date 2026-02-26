-- DA HRIS Supabase RLS Phase 2 - Employee Security Hardening
-- Apply after SUPABASE_SCHEMA.sql and RLS_PHASE1.sql

begin;

-- =====================================================
-- 1) Enable RLS on employee-sensitive tables
-- =====================================================
alter table public.documents enable row level security;
alter table public.document_versions enable row level security;
alter table public.document_access_logs enable row level security;
alter table public.attendance_logs enable row level security;
alter table public.leave_balances enable row level security;
alter table public.leave_requests enable row level security;
alter table public.time_adjustment_requests enable row level security;
alter table public.overtime_requests enable row level security;
alter table public.payroll_items enable row level security;
alter table public.payroll_adjustments enable row level security;
alter table public.payslips enable row level security;
alter table public.performance_evaluations enable row level security;
alter table public.praise_nominations enable row level security;
alter table public.training_enrollments enable row level security;
alter table public.generated_reports enable row level security;
alter table public.system_settings enable row level security;

-- =====================================================
-- 2) Helper: resolve current auth user person_id
-- =====================================================
create or replace function public.current_person_id()
returns uuid
language sql
stable
as $$
  select p.id
  from public.people p
  where p.user_id = auth.uid()
  limit 1;
$$;

-- =====================================================
-- 3) Documents and versions
-- =====================================================
drop policy if exists documents_employee_owner_select on public.documents;
create policy documents_employee_owner_select on public.documents
for select using (
  public.current_user_is_admin()
  or owner_person_id = public.current_person_id()
);

drop policy if exists documents_employee_owner_update on public.documents;
create policy documents_employee_owner_update on public.documents
for update using (
  public.current_user_is_admin()
  or owner_person_id = public.current_person_id()
) with check (
  public.current_user_is_admin()
  or owner_person_id = public.current_person_id()
);

drop policy if exists documents_employee_owner_insert on public.documents;
create policy documents_employee_owner_insert on public.documents
for insert with check (
  public.current_user_is_admin()
  or owner_person_id = public.current_person_id()
);

drop policy if exists document_versions_employee_owner_select on public.document_versions;
create policy document_versions_employee_owner_select on public.document_versions
for select using (
  public.current_user_is_admin()
  or exists (
    select 1
    from public.documents d
    where d.id = document_versions.document_id
      and d.owner_person_id = public.current_person_id()
  )
);

drop policy if exists document_versions_employee_owner_insert on public.document_versions;
create policy document_versions_employee_owner_insert on public.document_versions
for insert with check (
  public.current_user_is_admin()
  or exists (
    select 1
    from public.documents d
    where d.id = document_versions.document_id
      and d.owner_person_id = public.current_person_id()
  )
);

drop policy if exists document_access_logs_employee_scope on public.document_access_logs;
create policy document_access_logs_employee_scope on public.document_access_logs
for all using (
  public.current_user_is_admin()
  or viewer_user_id = auth.uid()
) with check (
  public.current_user_is_admin()
  or viewer_user_id = auth.uid()
);

-- =====================================================
-- 4) Timekeeping and leave tables
-- =====================================================
drop policy if exists attendance_logs_employee_owner on public.attendance_logs;
create policy attendance_logs_employee_owner on public.attendance_logs
for select using (
  public.current_user_is_admin()
  or person_id = public.current_person_id()
);

drop policy if exists leave_balances_employee_owner on public.leave_balances;
create policy leave_balances_employee_owner on public.leave_balances
for select using (
  public.current_user_is_admin()
  or person_id = public.current_person_id()
);

drop policy if exists leave_requests_employee_scope on public.leave_requests;
create policy leave_requests_employee_scope on public.leave_requests
for all using (
  public.current_user_is_admin()
  or person_id = public.current_person_id()
) with check (
  public.current_user_is_admin()
  or person_id = public.current_person_id()
);

drop policy if exists time_adjustment_requests_employee_scope on public.time_adjustment_requests;
create policy time_adjustment_requests_employee_scope on public.time_adjustment_requests
for all using (
  public.current_user_is_admin()
  or person_id = public.current_person_id()
) with check (
  public.current_user_is_admin()
  or person_id = public.current_person_id()
);

drop policy if exists overtime_requests_employee_scope on public.overtime_requests;
create policy overtime_requests_employee_scope on public.overtime_requests
for all using (
  public.current_user_is_admin()
  or person_id = public.current_person_id()
) with check (
  public.current_user_is_admin()
  or person_id = public.current_person_id()
);

-- =====================================================
-- 5) Payroll and payslip tables
-- =====================================================
drop policy if exists payroll_items_employee_owner on public.payroll_items;
create policy payroll_items_employee_owner on public.payroll_items
for select using (
  public.current_user_is_admin()
  or person_id = public.current_person_id()
);

drop policy if exists payroll_adjustments_employee_owner on public.payroll_adjustments;
create policy payroll_adjustments_employee_owner on public.payroll_adjustments
for select using (
  public.current_user_is_admin()
  or exists (
    select 1
    from public.payroll_items pi
    where pi.id = payroll_adjustments.payroll_item_id
      and pi.person_id = public.current_person_id()
  )
);

drop policy if exists payslips_employee_owner_select on public.payslips;
create policy payslips_employee_owner_select on public.payslips
for select using (
  public.current_user_is_admin()
  or exists (
    select 1
    from public.payroll_items pi
    where pi.id = payslips.payroll_item_id
      and pi.person_id = public.current_person_id()
  )
);

drop policy if exists payslips_employee_owner_update on public.payslips;
create policy payslips_employee_owner_update on public.payslips
for update using (
  public.current_user_is_admin()
  or exists (
    select 1
    from public.payroll_items pi
    where pi.id = payslips.payroll_item_id
      and pi.person_id = public.current_person_id()
  )
) with check (
  public.current_user_is_admin()
  or exists (
    select 1
    from public.payroll_items pi
    where pi.id = payslips.payroll_item_id
      and pi.person_id = public.current_person_id()
  )
);

-- =====================================================
-- 6) Performance, reports, and settings
-- =====================================================
drop policy if exists performance_evaluations_employee_scope on public.performance_evaluations;
create policy performance_evaluations_employee_scope on public.performance_evaluations
for select using (
  public.current_user_is_admin()
  or employee_person_id = public.current_person_id()
  or evaluator_user_id = auth.uid()
);

drop policy if exists performance_evaluations_employee_insert on public.performance_evaluations;
create policy performance_evaluations_employee_insert on public.performance_evaluations
for insert with check (
  public.current_user_is_admin()
  or (
    employee_person_id = public.current_person_id()
    and evaluator_user_id = auth.uid()
  )
);

drop policy if exists performance_evaluations_employee_update on public.performance_evaluations;
create policy performance_evaluations_employee_update on public.performance_evaluations
for update using (
  public.current_user_is_admin()
  or (
    employee_person_id = public.current_person_id()
    and evaluator_user_id = auth.uid()
  )
) with check (
  public.current_user_is_admin()
  or (
    employee_person_id = public.current_person_id()
    and evaluator_user_id = auth.uid()
  )
);

drop policy if exists praise_nominations_employee_scope on public.praise_nominations;
create policy praise_nominations_employee_scope on public.praise_nominations
for select using (
  public.current_user_is_admin()
  or nominee_person_id = public.current_person_id()
  or nominated_by_user_id = auth.uid()
);

drop policy if exists training_enrollments_employee_scope on public.training_enrollments;
create policy training_enrollments_employee_scope on public.training_enrollments
for select using (
  public.current_user_is_admin()
  or person_id = public.current_person_id()
);

drop policy if exists generated_reports_employee_scope on public.generated_reports;
create policy generated_reports_employee_scope on public.generated_reports
for all using (
  public.current_user_is_admin()
  or requested_by = auth.uid()
) with check (
  public.current_user_is_admin()
  or requested_by = auth.uid()
);

drop policy if exists system_settings_employee_settings_key on public.system_settings;
create policy system_settings_employee_settings_key on public.system_settings
for all using (
  public.current_user_is_admin()
  or (
    setting_key = ('employee_settings_' || auth.uid()::text)
    and updated_by = auth.uid()
  )
) with check (
  public.current_user_is_admin()
  or (
    setting_key = ('employee_settings_' || auth.uid()::text)
    and updated_by = auth.uid()
  )
);

commit;
