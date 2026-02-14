-- DA HRIS RLS Phase 1
-- Run this AFTER SUPABASE_SCHEMA.sql and SUPABASE_SEED_MVP.sql

begin;

-- =====================================================
-- 0) Helper Functions for Policies
-- =====================================================
create or replace function public.current_user_has_any_role(role_keys text[])
returns boolean
language sql
stable
security definer
set search_path = public
as $$
  select exists (
    select 1
    from public.user_role_assignments ura
    join public.roles r on r.id = ura.role_id
    where ura.user_id = auth.uid()
      and ura.expires_at is null
      and r.role_key = any(role_keys)
  );
$$;

create or replace function public.current_user_person_id()
returns uuid
language sql
stable
security definer
set search_path = public
as $$
  select p.id
  from public.people p
  where p.user_id = auth.uid()
  limit 1;
$$;

-- =====================================================
-- 1) Recruitment RLS
-- =====================================================
alter table public.job_requisitions enable row level security;
alter table public.job_postings enable row level security;
alter table public.application_status_history enable row level security;
alter table public.application_documents enable row level security;
alter table public.application_interviews enable row level security;
alter table public.application_feedback enable row level security;

-- job_requisitions
 drop policy if exists job_requisitions_admin_staff_all on public.job_requisitions;
create policy job_requisitions_admin_staff_all on public.job_requisitions
for all
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
);

-- job_postings
 drop policy if exists job_postings_public_read_published on public.job_postings;
create policy job_postings_public_read_published on public.job_postings
for select to anon, authenticated
using (posting_status = 'published');

 drop policy if exists job_postings_admin_staff_all on public.job_postings;
create policy job_postings_admin_staff_all on public.job_postings
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
);

-- application_status_history
 drop policy if exists app_status_history_applicant_or_hr on public.application_status_history;
create policy app_status_history_applicant_or_hr on public.application_status_history
for select to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or exists (
    select 1
    from public.applications a
    join public.applicant_profiles ap on ap.id = a.applicant_profile_id
    where a.id = application_status_history.application_id
      and ap.user_id = auth.uid()
  )
);

 drop policy if exists app_status_history_hr_write on public.application_status_history;
create policy app_status_history_hr_write on public.application_status_history
for insert to authenticated
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
);

-- application_documents
 drop policy if exists app_documents_applicant_or_hr_all on public.application_documents;
create policy app_documents_applicant_or_hr_all on public.application_documents
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
  or exists (
    select 1
    from public.applications a
    join public.applicant_profiles ap on ap.id = a.applicant_profile_id
    where a.id = application_documents.application_id
      and ap.user_id = auth.uid()
  )
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
  or exists (
    select 1
    from public.applications a
    join public.applicant_profiles ap on ap.id = a.applicant_profile_id
    where a.id = application_documents.application_id
      and ap.user_id = auth.uid()
  )
);

-- application_interviews
 drop policy if exists app_interviews_applicant_or_hr_read on public.application_interviews;
create policy app_interviews_applicant_or_hr_read on public.application_interviews
for select to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or exists (
    select 1
    from public.applications a
    join public.applicant_profiles ap on ap.id = a.applicant_profile_id
    where a.id = application_interviews.application_id
      and ap.user_id = auth.uid()
  )
);

 drop policy if exists app_interviews_hr_write on public.application_interviews;
create policy app_interviews_hr_write on public.application_interviews
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
);

-- application_feedback
 drop policy if exists app_feedback_applicant_or_hr_read on public.application_feedback;
create policy app_feedback_applicant_or_hr_read on public.application_feedback
for select to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
  or exists (
    select 1
    from public.applications a
    join public.applicant_profiles ap on ap.id = a.applicant_profile_id
    where a.id = application_feedback.application_id
      and ap.user_id = auth.uid()
  )
);

 drop policy if exists app_feedback_hr_write on public.application_feedback;
create policy app_feedback_hr_write on public.application_feedback
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
);

-- =====================================================
-- 2) Document Module RLS
-- =====================================================
alter table public.documents enable row level security;
alter table public.document_versions enable row level security;
alter table public.document_reviews enable row level security;
alter table public.document_access_logs enable row level security;
alter table public.document_categories enable row level security;

 drop policy if exists document_categories_read_all_auth on public.document_categories;
create policy document_categories_read_all_auth on public.document_categories
for select to authenticated
using (true);

 drop policy if exists document_categories_admin_write on public.document_categories;
create policy document_categories_admin_write on public.document_categories
for all to authenticated
using (public.current_user_is_admin())
with check (public.current_user_is_admin());

 drop policy if exists documents_owner_or_hr_or_admin on public.documents;
create policy documents_owner_or_hr_or_admin on public.documents
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or owner_person_id = public.current_user_person_id()
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or owner_person_id = public.current_user_person_id()
);

 drop policy if exists document_versions_owner_or_hr_or_admin on public.document_versions;
create policy document_versions_owner_or_hr_or_admin on public.document_versions
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or exists (
    select 1
    from public.documents d
    where d.id = document_versions.document_id
      and d.owner_person_id = public.current_user_person_id()
  )
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or exists (
    select 1
    from public.documents d
    where d.id = document_versions.document_id
      and d.owner_person_id = public.current_user_person_id()
  )
);

 drop policy if exists document_reviews_hr_admin on public.document_reviews;
create policy document_reviews_hr_admin on public.document_reviews
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
);

 drop policy if exists document_access_logs_owner_or_hr_admin on public.document_access_logs;
create policy document_access_logs_owner_or_hr_admin on public.document_access_logs
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or exists (
    select 1
    from public.documents d
    where d.id = document_access_logs.document_id
      and d.owner_person_id = public.current_user_person_id()
  )
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or viewer_user_id = auth.uid()
);

-- =====================================================
-- 3) Timekeeping / Leave RLS
-- =====================================================
alter table public.attendance_logs enable row level security;
alter table public.work_schedules enable row level security;
alter table public.leave_balances enable row level security;
alter table public.leave_requests enable row level security;
alter table public.overtime_requests enable row level security;
alter table public.time_adjustment_requests enable row level security;
alter table public.leave_types enable row level security;
alter table public.holidays enable row level security;

 drop policy if exists leave_types_read_all_auth on public.leave_types;
create policy leave_types_read_all_auth on public.leave_types
for select to authenticated
using (true);

 drop policy if exists holidays_read_all_auth on public.holidays;
create policy holidays_read_all_auth on public.holidays
for select to authenticated
using (true);

 drop policy if exists attendance_self_or_hr_admin on public.attendance_logs;
create policy attendance_self_or_hr_admin on public.attendance_logs
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
);

 drop policy if exists schedules_self_or_hr_admin on public.work_schedules;
create policy schedules_self_or_hr_admin on public.work_schedules
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
);

 drop policy if exists leave_balances_self_or_hr_admin on public.leave_balances;
create policy leave_balances_self_or_hr_admin on public.leave_balances
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
);

 drop policy if exists leave_requests_self_or_hr_admin on public.leave_requests;
create policy leave_requests_self_or_hr_admin on public.leave_requests
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
);

 drop policy if exists overtime_self_or_hr_admin on public.overtime_requests;
create policy overtime_self_or_hr_admin on public.overtime_requests
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
);

 drop policy if exists time_adjust_self_or_hr_admin on public.time_adjustment_requests;
create policy time_adjust_self_or_hr_admin on public.time_adjustment_requests
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or person_id = public.current_user_person_id()
);

-- =====================================================
-- 4) Payroll / Reports RLS
-- =====================================================
alter table public.payroll_periods enable row level security;
alter table public.payroll_runs enable row level security;
alter table public.payroll_items enable row level security;
alter table public.payroll_adjustments enable row level security;
alter table public.payslips enable row level security;
alter table public.generated_reports enable row level security;

 drop policy if exists payroll_periods_hr_admin on public.payroll_periods;
create policy payroll_periods_hr_admin on public.payroll_periods
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
);

 drop policy if exists payroll_runs_hr_admin on public.payroll_runs;
create policy payroll_runs_hr_admin on public.payroll_runs
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
);

 drop policy if exists payroll_items_self_or_hr_admin on public.payroll_items;
create policy payroll_items_self_or_hr_admin on public.payroll_items
for select to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
  or person_id = public.current_user_person_id()
);

 drop policy if exists payroll_items_hr_admin_write on public.payroll_items;
create policy payroll_items_hr_admin_write on public.payroll_items
for insert to authenticated
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
);

drop policy if exists payroll_items_hr_admin_update on public.payroll_items;
create policy payroll_items_hr_admin_update on public.payroll_items
for update to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
);

drop policy if exists payroll_items_hr_admin_delete on public.payroll_items;
create policy payroll_items_hr_admin_delete on public.payroll_items
for delete to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
);

 drop policy if exists payroll_adj_self_or_hr_admin on public.payroll_adjustments;
create policy payroll_adj_self_or_hr_admin on public.payroll_adjustments
for select to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
  or exists (
    select 1
    from public.payroll_items pi
    where pi.id = payroll_adjustments.payroll_item_id
      and pi.person_id = public.current_user_person_id()
  )
);

 drop policy if exists payroll_adj_hr_admin_write on public.payroll_adjustments;
create policy payroll_adj_hr_admin_write on public.payroll_adjustments
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
);

 drop policy if exists payslips_self_or_hr_admin on public.payslips;
create policy payslips_self_or_hr_admin on public.payslips
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
  or exists (
    select 1
    from public.payroll_items pi
    where pi.id = payslips.payroll_item_id
      and pi.person_id = public.current_user_person_id()
  )
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff'])
);

 drop policy if exists generated_reports_self_or_hr_admin on public.generated_reports;
create policy generated_reports_self_or_hr_admin on public.generated_reports
for all to authenticated
using (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or requested_by = auth.uid()
)
with check (
  public.current_user_is_admin()
  or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  or requested_by = auth.uid()
);

commit;
