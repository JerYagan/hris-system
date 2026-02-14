-- DA HRIS Demo Seed (Admin - User Management)
-- Purpose: temporary mock data for backend/demo validation
-- Safe to rerun: yes (idempotent updates/inserts)
--
-- Prerequisites:
-- 1) Run SUPABASE_SCHEMA.sql
-- 2) Run SUPABASE_SEED_MVP.sql
-- 3) Create demo users in Supabase Auth (Authentication > Users):
--    admin@da.gov.ph
--    hr.staff@da.gov.ph
--    supervisor@da.gov.ph
--    employee.one@da.gov.ph
--    employee.two@da.gov.ph
--    archived.user@da.gov.ph

begin;

-- -----------------------------------------------------
-- A) Define demo profile + role/status mapping
-- -----------------------------------------------------
with demo_seed as (
  select *
  from (
    values
      ('admin@da.gov.ph',        'admin',      'Carla', 'Admin',      'DA-EMP-0001', 'active',   '09170000001'),
      ('hr.staff@da.gov.ph',     'hr_officer', 'Maria', 'Santos',     'DA-EMP-0002', 'active',   '09170000002'),
      ('supervisor@da.gov.ph',   'supervisor', 'John',  'Reyes',      'DA-EMP-0003', 'active',   '09170000003'),
      ('employee.one@da.gov.ph', 'employee',   'Ana',   'Dela Cruz',  'DA-EMP-0004', 'active',   '09170000004'),
      ('employee.two@da.gov.ph', 'employee',   'Mark',  'Villanueva', 'DA-EMP-0005', 'suspended','09170000005'),
      ('archived.user@da.gov.ph','employee',   'Lea',   'Ramos',      'DA-EMP-0006', 'archived', '09170000006')
  ) as t(email, role_key, first_name, surname, agency_employee_no, account_status, mobile_no)
), auth_matches as (
  select
    au.id as user_id,
    lower(au.email) as email,
    ds.role_key,
    ds.first_name,
    ds.surname,
    ds.agency_employee_no,
    ds.account_status,
    ds.mobile_no
  from demo_seed ds
  join auth.users au
    on lower(au.email) = lower(ds.email)
), upsert_accounts as (
  insert into public.user_accounts (id, email, mobile_no, account_status, email_verified_at)
  select
    am.user_id,
    am.email::citext,
    am.mobile_no,
    am.account_status::public.account_status_enum,
    now()
  from auth_matches am
  on conflict (id) do update
  set
    email = excluded.email,
    mobile_no = excluded.mobile_no,
    account_status = excluded.account_status,
    email_verified_at = coalesce(public.user_accounts.email_verified_at, excluded.email_verified_at),
    updated_at = now()
  returning id
), upsert_people as (
  insert into public.people (user_id, surname, first_name, personal_email, mobile_no, agency_employee_no, citizenship)
  select
    am.user_id,
    am.surname,
    am.first_name,
    am.email::citext,
    am.mobile_no,
    am.agency_employee_no,
    'Filipino'
  from auth_matches am
  on conflict (user_id) do update
  set
    surname = excluded.surname,
    first_name = excluded.first_name,
    personal_email = excluded.personal_email,
    mobile_no = excluded.mobile_no,
    agency_employee_no = excluded.agency_employee_no,
    updated_at = now()
  returning user_id
), reset_primary as (
  update public.user_role_assignments ura
  set is_primary = false
  where ura.user_id in (select user_id from auth_matches)
  returning ura.user_id
)
insert into public.user_role_assignments (user_id, role_id, office_id, is_primary, assigned_at)
select
  am.user_id,
  r.id,
  o.id,
  true,
  now()
from auth_matches am
join public.roles r
  on r.role_key = am.role_key
join public.offices o
  on o.office_code = 'DA-ATI-CENTRAL'
on conflict do nothing;

-- -----------------------------------------------------
-- B) Demo activity/log entries for UI proof
-- -----------------------------------------------------
insert into public.activity_logs (actor_user_id, module_name, entity_name, entity_id, action_name, old_data, new_data, ip_address)
select
  am.user_id,
  'user_management',
  'user_accounts',
  am.user_id,
  'seed_demo',
  null,
  jsonb_build_object('email', am.email, 'role_key', am.role_key, 'account_status', am.account_status),
  '127.0.0.1'::inet
from (
  select
    au.id as user_id,
    lower(au.email) as email,
    ds.role_key,
    ds.account_status
  from (
    values
      ('admin@da.gov.ph', 'admin', 'active'),
      ('hr.staff@da.gov.ph', 'hr_officer', 'active'),
      ('employee.two@da.gov.ph', 'employee', 'suspended'),
      ('archived.user@da.gov.ph', 'employee', 'archived')
  ) as ds(email, role_key, account_status)
  join auth.users au on lower(au.email) = lower(ds.email)
) am
on conflict do nothing;

insert into public.login_audit_logs (user_id, email_attempted, auth_provider, event_type, ip_address, metadata)
select
  au.id,
  lower(au.email)::citext,
  'password',
  'seed_login_event',
  '127.0.0.1'::inet,
  jsonb_build_object('source', 'SUPABASE_SEED_DEMO_USER_MANAGEMENT.sql')
from auth.users au
where lower(au.email) in (
  'admin@da.gov.ph',
  'hr.staff@da.gov.ph',
  'supervisor@da.gov.ph',
  'employee.one@da.gov.ph',
  'employee.two@da.gov.ph',
  'archived.user@da.gov.ph'
);

commit;

-- -----------------------------------------------------
-- C) Verification Queries
-- -----------------------------------------------------
-- 1) Account + profile + role status
select
  ua.email,
  ua.account_status,
  p.first_name,
  p.surname,
  p.agency_employee_no,
  r.role_key,
  ura.is_primary,
  o.office_code,
  ua.created_at
from public.user_accounts ua
left join public.people p on p.user_id = ua.id
left join public.user_role_assignments ura on ura.user_id = ua.id and ura.is_primary = true
left join public.roles r on r.id = ura.role_id
left join public.offices o on o.id = ura.office_id
where lower(ua.email) in (
  'admin@da.gov.ph',
  'hr.staff@da.gov.ph',
  'supervisor@da.gov.ph',
  'employee.one@da.gov.ph',
  'employee.two@da.gov.ph',
  'archived.user@da.gov.ph'
)
order by ua.email;

-- 2) Activity log proof
select actor_user_id, module_name, entity_name, action_name, created_at
from public.activity_logs
where module_name = 'user_management'
order by created_at desc
limit 20;

-- 3) Login audit proof
select email_attempted, event_type, created_at
from public.login_audit_logs
where event_type = 'seed_login_event'
order by created_at desc
limit 20;
