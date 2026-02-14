-- DA HRIS Demo Reset (Admin - User Management)
-- Purpose: remove temporary demo data created by SUPABASE_SEED_DEMO_USER_MANAGEMENT.sql
--
-- IMPORTANT:
-- - This only removes known demo emails.
-- - It does NOT touch baseline seed/reference tables (roles, permissions, offices, etc.).

begin;

with demo_users as (
  select id as user_id, lower(email) as email
  from auth.users
  where lower(email) in (
    'admin@da.gov.ph',
    'hr.staff@da.gov.ph',
    'supervisor@da.gov.ph',
    'employee.one@da.gov.ph',
    'employee.two@da.gov.ph',
    'archived.user@da.gov.ph'
  )
), people_ids as (
  select p.id
  from public.people p
  join demo_users du on du.user_id = p.user_id
)

-- remove child/profile rows first
,
_delete_person_educations as (
  delete from public.person_educations pe
  using people_ids pid
  where pe.person_id = pid.id
  returning pe.id
),
_delete_person_parents as (
  delete from public.person_parents pp
  using people_ids pid
  where pp.person_id = pid.id
  returning pp.id
),
_delete_person_children as (
  delete from public.person_family_children pc
  using people_ids pid
  where pc.person_id = pid.id
  returning pc.id
),
_delete_person_spouses as (
  delete from public.person_family_spouses ps
  using people_ids pid
  where ps.person_id = pid.id
  returning ps.id
),
_delete_person_addresses as (
  delete from public.person_addresses pa
  using people_ids pid
  where pa.person_id = pid.id
  returning pa.id
),
_delete_person_ids as (
  delete from public.person_government_ids pg
  using people_ids pid
  where pg.person_id = pid.id
  returning pg.id
),
_delete_logs as (
  delete from public.login_audit_logs lal
  using demo_users du
  where lal.user_id = du.user_id
     or lower(coalesce(lal.email_attempted::text, '')) = du.email
  returning lal.id
),
_delete_activity as (
  delete from public.activity_logs al
  using demo_users du
  where al.actor_user_id = du.user_id
  returning al.id
),
_delete_notifications as (
  delete from public.notifications n
  using demo_users du
  where n.recipient_user_id = du.user_id
  returning n.id
),
_delete_roles as (
  delete from public.user_role_assignments ura
  using demo_users du
  where ura.user_id = du.user_id
  returning ura.id
),
_delete_people as (
  delete from public.people p
  using demo_users du
  where p.user_id = du.user_id
  returning p.id
),
_delete_accounts as (
  delete from public.user_accounts ua
  using demo_users du
  where ua.id = du.user_id
  returning ua.id
)

-- optional: delete auth users too (uncomment only if you want full cleanup)
-- delete from auth.users au
-- using demo_users du
-- where au.id = du.user_id

select
  (select count(*) from _delete_accounts) as deleted_user_accounts,
  (select count(*) from _delete_people) as deleted_people,
  (select count(*) from _delete_roles) as deleted_role_assignments,
  (select count(*) from _delete_activity) as deleted_activity_logs,
  (select count(*) from _delete_logs) as deleted_login_logs;

commit;
