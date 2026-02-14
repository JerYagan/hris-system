-- DA HRIS Test Users Bootstrap
-- Run this AFTER:
-- 1) SUPABASE_SCHEMA.sql
-- 2) SUPABASE_SEED_MVP.sql
-- 3) Creating test users in Supabase Authentication

-- =====================================================
-- A) Create these users first in Supabase Auth UI
-- =====================================================
-- Authentication > Users > Add user
-- Suggested test accounts:
--   staff@da.gov.ph
--   employee@da.gov.ph
--   applicant@da.gov.ph

begin;

-- =====================================================
-- B) Mirror Auth users into user_accounts + assign roles
-- =====================================================
with test_users as (
  select *
  from (
    values
      ('staff@da.gov.ph', 'staff', 'Juan', 'Staff', true),
      ('employee@da.gov.ph', 'employee', 'Maria', 'Employee', true),
      ('applicant@da.gov.ph', 'applicant', 'Alex', 'Applicant', false)
  ) as t(email, role_key, first_name, surname, needs_people_profile)
), auth_matches as (
  select au.id as user_id, au.email, tu.role_key, tu.first_name, tu.surname, tu.needs_people_profile
  from test_users tu
  join auth.users au on lower(au.email) = lower(tu.email)
), upsert_accounts as (
  insert into public.user_accounts (id, email, account_status, email_verified_at)
  select am.user_id, am.email::citext, 'active', now()
  from auth_matches am
  on conflict (id) do update
  set email = excluded.email,
      account_status = excluded.account_status,
      email_verified_at = coalesce(public.user_accounts.email_verified_at, excluded.email_verified_at),
      updated_at = now()
  returning id
), role_assignments as (
  insert into public.user_role_assignments (user_id, role_id, office_id, is_primary, assigned_at)
  select am.user_id, r.id, o.id, true, now()
  from auth_matches am
  join public.roles r on r.role_key = am.role_key
  join public.offices o on o.office_code = 'DA-ATI-CENTRAL'
  on conflict do nothing
  returning user_id
)
insert into public.people (user_id, surname, first_name, created_at, updated_at)
select am.user_id, am.surname, am.first_name, now(), now()
from auth_matches am
where am.needs_people_profile = true
on conflict (user_id) do update
set surname = excluded.surname,
    first_name = excluded.first_name,
    updated_at = now();

commit;

-- =====================================================
-- C) Verification Queries
-- =====================================================
-- 1) Role assignment check
select u.email, r.role_key, ura.is_primary, o.office_code
from public.user_accounts u
join public.user_role_assignments ura on ura.user_id = u.id
join public.roles r on r.id = ura.role_id
left join public.offices o on o.id = ura.office_id
where u.email in ('staff@da.gov.ph','employee@da.gov.ph','applicant@da.gov.ph')
order by u.email;

-- 2) People profile link check
-- Note: applicant is expected to be NULL here in this MVP bootstrap,
-- because needs_people_profile is set to false for applicant.
select u.email, p.id as person_id, p.first_name, p.surname
from public.user_accounts u
left join public.people p on p.user_id = u.id
where u.email in ('staff@da.gov.ph','employee@da.gov.ph','applicant@da.gov.ph')
order by u.email;
