-- DA HRIS Demo Seed (Admin - Recruitment)
-- Purpose: temporary mock data for Recruitment and Applicant Tracking pages
-- Safe to rerun: yes (uses deterministic keys + upserts)
--
-- Prerequisites:
-- 1) Run SUPABASE_SCHEMA.sql
-- 2) Run SUPABASE_SEED_MVP.sql
-- 3) Optional but recommended: run SUPABASE_SEED_DEMO_USER_MANAGEMENT.sql first

begin;

-- -----------------------------------------------------
-- A) Core supporting records (positions)
-- -----------------------------------------------------
insert into public.job_positions (
  position_code,
  position_title,
  salary_grade,
  employment_classification,
  is_supervisory,
  is_active
)
values
  ('ADM-AIDE-VI', 'Administrative Aide VI', 'SG-6', 'regular', false, true),
  ('IT-OFFICER-I', 'IT Officer I', 'SG-11', 'regular', false, true),
  ('TRNG-SPEC-I', 'Training Specialist I', 'SG-13', 'regular', false, true)
on conflict (position_code) do update
set
  position_title = excluded.position_title,
  salary_grade = excluded.salary_grade,
  employment_classification = excluded.employment_classification,
  is_supervisory = excluded.is_supervisory,
  is_active = excluded.is_active,
  updated_at = now();

-- -----------------------------------------------------
-- B) Recruitment postings
-- -----------------------------------------------------
with office_ref as (
  select id
  from public.offices
  where office_code = 'DA-ATI-CENTRAL'
  limit 1
), admin_ref as (
  select ua.id
  from public.user_accounts ua
  where lower(ua.email) = 'admin@da.gov.ph'
  limit 1
), posting_seed as (
  select *
  from (
    values
      (
        'POST-2026-ADM-AIDE-VI',
        'Administrative Aide VI',
        'published',
        current_date - 8,
        current_date + 7,
        'Performs frontline HR and records support tasks.',
        'Bachelor level or equivalent, CS eligibility preferred.'
      ),
      (
        'POST-2026-IT-OFFICER-I',
        'IT Officer I',
        'published',
        current_date - 4,
        current_date + 2,
        'Maintains HRIS services, hardware, and service desk operations.',
        'IT graduate with systems administration experience.'
      ),
      (
        'POST-2026-TRNG-SPEC-I',
        'Training Specialist I',
        'draft',
        current_date + 3,
        current_date + 18,
        'Supports training program design and facilitation logistics.',
        'Background in learning and development or HRD.'
      )
  ) as t(posting_key, title, posting_status, open_date, close_date, description, qualifications)
), positioned_seed as (
  select
    ps.*,
    jp.id as position_id
  from posting_seed ps
  join public.job_positions jp
    on jp.position_title = ps.title
)
insert into public.job_postings (
  title,
  description,
  qualifications,
  responsibilities,
  posting_status,
  open_date,
  close_date,
  office_id,
  position_id,
  published_by
)
select
  p.title,
  p.description,
  p.qualifications,
  'See posting details and competency requirements.',
  p.posting_status::public.posting_status_enum,
  p.open_date,
  p.close_date,
  o.id,
  p.position_id,
  a.id
from positioned_seed p
cross join office_ref o
left join admin_ref a on true
where not exists (
  select 1
  from public.job_postings jp
  where jp.title = p.title
    and jp.open_date = p.open_date
    and jp.close_date = p.close_date
);

-- -----------------------------------------------------
-- C) Applicant profiles
-- Note: applicant_profiles.email has no unique constraint in current schema,
-- so use update-then-insert instead of ON CONFLICT(email).
-- -----------------------------------------------------
with applicant_seed as (
  select *
  from (
    values
      ('Angela Mercado', 'angela.mercado@email.com', '09175550101', 'Quezon City'),
      ('Kevin Dela Cruz', 'kevin.delacruz@email.com', '09175550102', 'Pasig City'),
      ('Liza Manalo', 'liza.manalo@email.com', '09175550103', 'Marikina City'),
      ('Noel Garcia', 'noel.garcia@email.com', '09175550104', 'Makati City'),
      ('Patricia Lim', 'patricia.lim@email.com', '09175550105', 'Taguig City')
  ) as t(full_name, email, mobile_no, current_address)
), update_existing as (
  update public.applicant_profiles ap
  set
    full_name = s.full_name,
    mobile_no = s.mobile_no,
    current_address = s.current_address,
    updated_at = now()
  from applicant_seed s
  where lower(ap.email::text) = lower(s.email)
  returning ap.id
)
insert into public.applicant_profiles (
  full_name,
  email,
  mobile_no,
  current_address,
  resume_url,
  portfolio_url
)
select
  s.full_name,
  s.email::citext,
  s.mobile_no,
  s.current_address,
  null,
  null
from applicant_seed s
where not exists (
  select 1
  from public.applicant_profiles ap
  where lower(ap.email::text) = lower(s.email)
);

-- -----------------------------------------------------
-- D) Applications + status history
-- -----------------------------------------------------
with posting_ref as (
  select id, title
  from public.job_postings
  where title in ('Administrative Aide VI', 'IT Officer I', 'Training Specialist I')
), applicant_ref as (
  select id, full_name, email
  from public.applicant_profiles
  where lower(email) in (
    'angela.mercado@email.com',
    'kevin.delacruz@email.com',
    'liza.manalo@email.com',
    'noel.garcia@email.com',
    'patricia.lim@email.com'
  )
), app_seed as (
  select *
  from (
    values
      ('APP-2026-0001', 'angela.mercado@email.com', 'Administrative Aide VI', 'shortlisted'),
      ('APP-2026-0002', 'kevin.delacruz@email.com', 'IT Officer I', 'screening'),
      ('APP-2026-0003', 'liza.manalo@email.com', 'Training Specialist I', 'interview'),
      ('APP-2026-0004', 'noel.garcia@email.com', 'Administrative Aide VI', 'submitted'),
      ('APP-2026-0005', 'patricia.lim@email.com', 'IT Officer I', 'offer')
  ) as t(ref_no, applicant_email, posting_title, app_status)
), resolved_seed as (
  select
    s.ref_no,
    ar.id as applicant_profile_id,
    pr.id as job_posting_id,
    s.app_status
  from app_seed s
  join applicant_ref ar on lower(ar.email) = lower(s.applicant_email)
  join posting_ref pr on pr.title = s.posting_title
), upsert_apps as (
  insert into public.applications (
    application_ref_no,
    applicant_profile_id,
    job_posting_id,
    application_status,
    submitted_at,
    updated_at
  )
  select
    rs.ref_no,
    rs.applicant_profile_id,
    rs.job_posting_id,
    rs.app_status::public.application_status_enum,
    now() - interval '5 days',
    now()
  from resolved_seed rs
  on conflict (application_ref_no) do update
  set
    application_status = excluded.application_status,
    updated_at = now()
  returning id, application_status
)
insert into public.application_status_history (
  application_id,
  old_status,
  new_status,
  changed_by,
  notes,
  created_at
)
select
  ua.id,
  null,
  ua.application_status,
  null,
  'Seeded recruitment demo status',
  now()
from upsert_apps ua
where not exists (
  select 1
  from public.application_status_history ash
  where ash.application_id = ua.id
);

-- -----------------------------------------------------
-- E) Optional activity log proof
-- -----------------------------------------------------
insert into public.activity_logs (
  actor_user_id,
  module_name,
  entity_name,
  entity_id,
  action_name,
  old_data,
  new_data,
  ip_address
)
select
  ua.id,
  'recruitment',
  'job_postings',
  jp.id,
  'seed_demo_recruitment',
  null,
  jsonb_build_object('title', jp.title, 'posting_status', jp.posting_status),
  '127.0.0.1'::inet
from public.job_postings jp
left join public.user_accounts ua on lower(ua.email) = 'admin@da.gov.ph'
where jp.title in ('Administrative Aide VI', 'IT Officer I', 'Training Specialist I')
on conflict do nothing;

commit;

-- -----------------------------------------------------
-- Verification 1: postings with application counts
-- -----------------------------------------------------
select
  jp.title,
  jp.posting_status,
  jp.open_date,
  jp.close_date,
  count(a.id) as total_applications
from public.job_postings jp
left join public.applications a on a.job_posting_id = jp.id
where jp.title in ('Administrative Aide VI', 'IT Officer I', 'Training Specialist I')
group by jp.id
order by jp.updated_at desc;

-- -----------------------------------------------------
-- Verification 2: latest applications
-- -----------------------------------------------------
select
  a.application_ref_no,
  ap.full_name,
  jp.title,
  a.application_status,
  a.submitted_at
from public.applications a
join public.applicant_profiles ap on ap.id = a.applicant_profile_id
join public.job_postings jp on jp.id = a.job_posting_id
where a.application_ref_no like 'APP-2026-%'
order by a.submitted_at desc;
