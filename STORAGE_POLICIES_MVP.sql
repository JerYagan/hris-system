-- DA HRIS Storage Policies (MVP)
-- Run this AFTER:
-- 1) SUPABASE_SCHEMA.sql
-- 2) SUPABASE_SEED_MVP.sql
-- 3) RLS_PHASE1.sql (for role helper functions)
-- 4) Creating buckets: hris-documents, hris-applications, hris-payslips

-- =====================================================
-- Path convention (required for owner checks)
-- =====================================================
-- hris-documents:   users/{auth.uid()}/... (example: users/<uuid>/201file.pdf)
-- hris-applications: users/{auth.uid()}/... (example: users/<uuid>/resume.pdf)
-- hris-payslips:     users/{auth.uid()}/... (example: users/<uuid>/payroll-2026-02.pdf)
--
-- Owner check uses: (storage.foldername(name))[2] = auth.uid()::text
-- because first segment is 'users' and second is user uuid.

begin;

-- =====================================================
-- 1) hris-documents
-- =====================================================
drop policy if exists docs_owner_select on storage.objects;
create policy docs_owner_select
on storage.objects
for select
to authenticated
using (
  bucket_id = 'hris-documents'
  and (storage.foldername(name))[1] = 'users'
  and (storage.foldername(name))[2] = auth.uid()::text
);

drop policy if exists docs_owner_insert on storage.objects;
create policy docs_owner_insert
on storage.objects
for insert
to authenticated
with check (
  bucket_id = 'hris-documents'
  and (storage.foldername(name))[1] = 'users'
  and (storage.foldername(name))[2] = auth.uid()::text
);

drop policy if exists docs_owner_update on storage.objects;
create policy docs_owner_update
on storage.objects
for update
to authenticated
using (
  bucket_id = 'hris-documents'
  and (storage.foldername(name))[1] = 'users'
  and (storage.foldername(name))[2] = auth.uid()::text
)
with check (
  bucket_id = 'hris-documents'
  and (storage.foldername(name))[1] = 'users'
  and (storage.foldername(name))[2] = auth.uid()::text
);

drop policy if exists docs_owner_delete on storage.objects;
create policy docs_owner_delete
on storage.objects
for delete
to authenticated
using (
  bucket_id = 'hris-documents'
  and (storage.foldername(name))[1] = 'users'
  and (storage.foldername(name))[2] = auth.uid()::text
);

-- Admin/HR/Staff read override for operational review
drop policy if exists docs_hr_admin_read on storage.objects;
create policy docs_hr_admin_read
on storage.objects
for select
to authenticated
using (
  bucket_id = 'hris-documents'
  and (
    public.current_user_is_admin()
    or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  )
);

-- =====================================================
-- 2) hris-applications
-- =====================================================
drop policy if exists apps_owner_select on storage.objects;
create policy apps_owner_select
on storage.objects
for select
to authenticated
using (
  bucket_id = 'hris-applications'
  and (storage.foldername(name))[1] = 'users'
  and (storage.foldername(name))[2] = auth.uid()::text
);

drop policy if exists apps_owner_insert on storage.objects;
create policy apps_owner_insert
on storage.objects
for insert
to authenticated
with check (
  bucket_id = 'hris-applications'
  and (storage.foldername(name))[1] = 'users'
  and (storage.foldername(name))[2] = auth.uid()::text
);

drop policy if exists apps_owner_update on storage.objects;
create policy apps_owner_update
on storage.objects
for update
to authenticated
using (
  bucket_id = 'hris-applications'
  and (storage.foldername(name))[1] = 'users'
  and (storage.foldername(name))[2] = auth.uid()::text
)
with check (
  bucket_id = 'hris-applications'
  and (storage.foldername(name))[1] = 'users'
  and (storage.foldername(name))[2] = auth.uid()::text
);

drop policy if exists apps_owner_delete on storage.objects;
create policy apps_owner_delete
on storage.objects
for delete
to authenticated
using (
  bucket_id = 'hris-applications'
  and (storage.foldername(name))[1] = 'users'
  and (storage.foldername(name))[2] = auth.uid()::text
);

-- Admin/HR/Staff read/write override for recruitment operations
drop policy if exists apps_hr_admin_all on storage.objects;
create policy apps_hr_admin_all
on storage.objects
for all
to authenticated
using (
  bucket_id = 'hris-applications'
  and (
    public.current_user_is_admin()
    or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  )
)
with check (
  bucket_id = 'hris-applications'
  and (
    public.current_user_is_admin()
    or public.current_user_has_any_role(array['hr_officer','staff','supervisor'])
  )
);

-- =====================================================
-- 3) hris-payslips
-- =====================================================
-- Employee owner can read only
drop policy if exists payslip_owner_select on storage.objects;
create policy payslip_owner_select
on storage.objects
for select
to authenticated
using (
  bucket_id = 'hris-payslips'
  and (storage.foldername(name))[1] = 'users'
  and (storage.foldername(name))[2] = auth.uid()::text
);

-- Admin/HR/Staff full management for payroll release flow
drop policy if exists payslip_hr_admin_all on storage.objects;
create policy payslip_hr_admin_all
on storage.objects
for all
to authenticated
using (
  bucket_id = 'hris-payslips'
  and (
    public.current_user_is_admin()
    or public.current_user_has_any_role(array['hr_officer','staff'])
  )
)
with check (
  bucket_id = 'hris-payslips'
  and (
    public.current_user_is_admin()
    or public.current_user_has_any_role(array['hr_officer','staff'])
  )
);

commit;

-- =====================================================
-- Optional verification query
-- =====================================================
-- select policyname, cmd, roles, qual, with_check
-- from pg_policies
-- where schemaname = 'storage' and tablename = 'objects'
-- order by policyname;
