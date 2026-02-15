-- DA HRIS Full Mock Seed (All Core Modules)
-- Run this AFTER:
-- 1) SUPABASE_SCHEMA.sql
-- 2) SUPABASE_SEED_MVP.sql
-- 3) Creating test users in Supabase Auth + mirroring to public.user_accounts
--
-- Recommended prep:
-- - Run SUPABASE_SEED_DEMO_USER_MANAGEMENT.sql first
--
-- Notes:
-- - This script is rerunnable (upsert/update-then-insert patterns).
-- - It seeds core tables across profile, recruitment, documents, timekeeping,
--   payroll, performance, training, reports, notifications, and logs.

begin;

-- -----------------------------------------------------
-- 0) Guardrails
-- -----------------------------------------------------
do $$
begin
  if not exists (select 1 from public.user_accounts) then
    raise exception using
      message = 'Full mock seed prerequisites missing.',
      detail = 'public.user_accounts is empty.',
      hint = 'Create Auth users first, then mirror them into public.user_accounts.';
  end if;

  if not exists (select 1 from public.roles) then
    raise exception using
      message = 'Full mock seed prerequisites missing.',
      detail = 'public.roles is empty.',
      hint = 'Run SUPABASE_SEED_MVP.sql first.';
  end if;

  if not exists (select 1 from public.offices) then
    raise exception using
      message = 'Full mock seed prerequisites missing.',
      detail = 'public.offices is empty.',
      hint = 'Run SUPABASE_SEED_MVP.sql first.';
  end if;
end
$$;

-- -----------------------------------------------------
-- 1) Ensure people profiles for users
-- -----------------------------------------------------
with missing_people as (
  select
    ua.id as user_id,
    lower(ua.email::text) as email,
    split_part(lower(ua.email::text), '@', 1) as handle
  from public.user_accounts ua
  left join public.people p on p.user_id = ua.id
  where p.id is null
)
insert into public.people (
  user_id,
  surname,
  first_name,
  personal_email,
  mobile_no,
  citizenship,
  agency_employee_no
)
select
  mp.user_id,
  initcap(split_part(replace(mp.handle, '.', ' '), ' ', 1)) || 'son',
  initcap(split_part(replace(mp.handle, '.', ' '), ' ', 1)),
  mp.email::citext,
  null,
  'Filipino',
  'AUTO-' || upper(replace(left(mp.user_id::text, 8), '-', ''))
from missing_people mp
on conflict (user_id) do update
set
  personal_email = excluded.personal_email,
  updated_at = now();

-- -----------------------------------------------------
-- 2) Resolve seed context
-- -----------------------------------------------------
create temporary table tmp_seed_context as
with admin_user as (
  select ua.id, ua.email
  from public.user_accounts ua
  join public.user_role_assignments ura on ura.user_id = ua.id and ura.is_primary = true
  join public.roles r on r.id = ura.role_id
  where r.role_key = 'admin'
  order by ua.created_at asc
  limit 1
),
staff_user as (
  select ua.id, ua.email
  from public.user_accounts ua
  join public.user_role_assignments ura on ura.user_id = ua.id and ura.is_primary = true
  join public.roles r on r.id = ura.role_id
  where r.role_key in ('hr_officer', 'staff')
  order by ua.created_at asc
  limit 1
),
supervisor_user as (
  select ua.id, ua.email
  from public.user_accounts ua
  join public.user_role_assignments ura on ura.user_id = ua.id and ura.is_primary = true
  join public.roles r on r.id = ura.role_id
  where r.role_key = 'supervisor'
  order by ua.created_at asc
  limit 1
),
employee_user as (
  select ua.id, ua.email
  from public.user_accounts ua
  join public.user_role_assignments ura on ura.user_id = ua.id and ura.is_primary = true
  join public.roles r on r.id = ura.role_id
  where r.role_key = 'employee'
  order by ua.created_at asc
  limit 1
),
applicant_user as (
  select ua.id, ua.email
  from public.user_accounts ua
  join public.user_role_assignments ura on ura.user_id = ua.id and ura.is_primary = true
  join public.roles r on r.id = ura.role_id
  where r.role_key = 'applicant'
  order by ua.created_at asc
  limit 1
),
fallback_users as (
  select id, email, row_number() over (order by created_at asc) as rn
  from public.user_accounts
)
select
  coalesce((select id from admin_user), (select id from fallback_users where rn = 1)) as admin_user_id,
  coalesce((select id from staff_user), (select id from fallback_users where rn = 2), (select id from fallback_users where rn = 1)) as staff_user_id,
  coalesce((select id from supervisor_user), (select id from fallback_users where rn = 3), (select id from fallback_users where rn = 1)) as supervisor_user_id,
  coalesce((select id from employee_user), (select id from fallback_users where rn = 4), (select id from fallback_users where rn = 1)) as employee_user_id,
  coalesce((select id from applicant_user), (select id from fallback_users where rn = 5), (select id from fallback_users where rn = 1)) as applicant_user_id,
  (select id from public.offices order by created_at asc limit 1) as office_id;

alter table tmp_seed_context
  add column admin_person_id uuid,
  add column staff_person_id uuid,
  add column supervisor_person_id uuid,
  add column employee_person_id uuid;

update tmp_seed_context c
set
  admin_person_id = (select p.id from public.people p where p.user_id = c.admin_user_id limit 1),
  staff_person_id = (select p.id from public.people p where p.user_id = c.staff_user_id limit 1),
  supervisor_person_id = (select p.id from public.people p where p.user_id = c.supervisor_user_id limit 1),
  employee_person_id = (select p.id from public.people p where p.user_id = c.employee_user_id limit 1);

-- -----------------------------------------------------
-- 3) Profile supporting tables
-- -----------------------------------------------------
insert into public.person_addresses (
  person_id,
  address_type,
  house_no,
  street,
  barangay,
  city_municipality,
  province,
  zip_code,
  country,
  is_primary
)
select c.employee_person_id, 'residential', '101', 'Mabini St.', 'San Isidro', 'Quezon City', 'Metro Manila', '1100', 'Philippines', true
from tmp_seed_context c
where c.employee_person_id is not null
  and not exists (
    select 1 from public.person_addresses pa
    where pa.person_id = c.employee_person_id and pa.address_type = 'residential'
  );

insert into public.person_addresses (
  person_id,
  address_type,
  house_no,
  street,
  barangay,
  city_municipality,
  province,
  zip_code,
  country,
  is_primary
)
select c.employee_person_id, 'permanent', '42', 'Rizal Ave.', 'Poblacion', 'Cabanatuan', 'Nueva Ecija', '3100', 'Philippines', false
from tmp_seed_context c
where c.employee_person_id is not null
  and not exists (
    select 1 from public.person_addresses pa
    where pa.person_id = c.employee_person_id and pa.address_type = 'permanent'
  );

insert into public.person_government_ids (person_id, id_type, id_value_encrypted, last4)
select c.employee_person_id, 'tin', encode(digest('TIN-1234-5678', 'sha256'), 'hex'), '5678'
from tmp_seed_context c
where c.employee_person_id is not null
on conflict (person_id, id_type) do update
set id_value_encrypted = excluded.id_value_encrypted,
    last4 = excluded.last4;

insert into public.person_educations (
  person_id, education_level, school_name, course_degree, period_from, period_to, year_graduated, sequence_no
)
select c.employee_person_id, 'college', 'Central Luzon State University', 'BS Information Technology', '2016', '2020', '2020', 1
from tmp_seed_context c
where c.employee_person_id is not null
  and not exists (
    select 1
    from public.person_educations pe
    where pe.person_id = c.employee_person_id and pe.education_level = 'college' and pe.sequence_no = 1
  );

insert into public.emergency_contacts (person_id, contact_name, relationship, mobile_no, address, is_primary)
select c.employee_person_id, 'Maria Santos', 'Mother', '09175550001', 'Quezon City', true
from tmp_seed_context c
where c.employee_person_id is not null
  and not exists (
    select 1 from public.emergency_contacts ec
    where ec.person_id = c.employee_person_id and ec.is_primary = true
  );

-- -----------------------------------------------------
-- 4) Employment and office scope
-- -----------------------------------------------------
insert into public.job_positions (
  position_code, position_title, salary_grade, employment_classification, is_supervisory, is_active
)
values
  ('HR-ASST-I', 'HR Assistant I', 'SG-8', 'regular', false, true),
  ('HR-OFF-II', 'HR Officer II', 'SG-15', 'regular', true, true),
  ('IT-ASST-I', 'IT Assistant I', 'SG-10', 'regular', false, true)
on conflict (position_code) do update
set
  position_title = excluded.position_title,
  salary_grade = excluded.salary_grade,
  employment_classification = excluded.employment_classification,
  is_supervisory = excluded.is_supervisory,
  is_active = excluded.is_active,
  updated_at = now();

insert into public.employment_records (
  person_id,
  office_id,
  position_id,
  hire_date,
  employment_status,
  immediate_supervisor_person_id,
  probation_end_date,
  is_current
)
select
  c.employee_person_id,
  c.office_id,
  (select id from public.job_positions where position_code = 'HR-ASST-I' limit 1),
  date '2024-03-01',
  'active',
  c.supervisor_person_id,
  date '2024-09-01',
  true
from tmp_seed_context c
where c.employee_person_id is not null
on conflict (person_id) where is_current = true do update
set
  office_id = excluded.office_id,
  position_id = excluded.position_id,
  employment_status = excluded.employment_status,
  immediate_supervisor_person_id = excluded.immediate_supervisor_person_id,
  updated_at = now();

insert into public.user_office_scopes (user_id, office_id, scope_type)
select c.staff_user_id, c.office_id, 'office' from tmp_seed_context c
where c.staff_user_id is not null
on conflict (user_id, office_id, scope_type) do nothing;

insert into public.user_office_scopes (user_id, office_id, scope_type)
select c.admin_user_id, c.office_id, 'organization' from tmp_seed_context c
where c.admin_user_id is not null
on conflict (user_id, office_id, scope_type) do nothing;

-- -----------------------------------------------------
-- 5) Access requests + notifications + settings
-- -----------------------------------------------------
insert into public.access_requests (
  full_name,
  official_email,
  office_unit,
  requested_role_id,
  employee_reference_no,
  reason,
  status,
  reviewed_by,
  reviewed_at,
  review_notes
)
select
  'Ramon Castillo',
  'ramon.castillo@da.gov.ph'::citext,
  'Regional HR Unit',
  (select id from public.roles where role_key = 'employee' limit 1),
  'REQ-2026-1001',
  'Requesting HRIS self-service access.',
  'approved'::public.request_status_enum,
  c.admin_user_id,
  now() - interval '3 days',
  'Approved for onboarding.'
from tmp_seed_context c
where not exists (
  select 1 from public.access_requests ar where lower(ar.official_email::text) = 'ramon.castillo@da.gov.ph'
);

insert into public.notifications (recipient_user_id, category, title, body, link_url, is_read, read_at)
select c.employee_user_id, 'leave', 'Leave request updated', 'Your leave request LR-2026-0001 was approved.', '/pages/employee/timekeeping.php', false, null
from tmp_seed_context c
where c.employee_user_id is not null
  and not exists (
    select 1 from public.notifications n
    where n.recipient_user_id = c.employee_user_id and n.title = 'Leave request updated'
  );

insert into public.notifications (recipient_user_id, category, title, body, link_url, is_read, read_at)
select c.staff_user_id, 'recruitment', 'New application received', 'A new application was submitted for IT Officer I.', '/pages/admin/applicant-tracking.php', true, now() - interval '1 day'
from tmp_seed_context c
where c.staff_user_id is not null
  and not exists (
    select 1 from public.notifications n
    where n.recipient_user_id = c.staff_user_id and n.title = 'New application received'
  );

insert into public.system_settings (setting_key, setting_value, updated_by)
select 'notifications.email_critical', jsonb_build_object('enabled', true, 'provider', 'resend'), c.admin_user_id
from tmp_seed_context c
on conflict (setting_key) do update
set setting_value = excluded.setting_value,
    updated_by = excluded.updated_by,
    updated_at = now();

-- -----------------------------------------------------
-- 6) Recruitment
-- -----------------------------------------------------
insert into public.job_requisitions (
  office_id,
  position_id,
  requested_by,
  required_headcount,
  justification,
  status,
  approved_by,
  approved_at
)
select
  c.office_id,
  (select id from public.job_positions where position_code = 'IT-ASST-I' limit 1),
  c.staff_user_id,
  1,
  'Replacement for vacated IT support role.',
  'approved',
  c.admin_user_id,
  now() - interval '10 days'
from tmp_seed_context c
where c.staff_user_id is not null
  and not exists (
    select 1
    from public.job_requisitions jr
    where jr.justification = 'Replacement for vacated IT support role.'
  );

insert into public.job_postings (
  requisition_id,
  office_id,
  position_id,
  title,
  description,
  qualifications,
  responsibilities,
  posting_status,
  open_date,
  close_date,
  published_by
)
select
  jr.id,
  c.office_id,
  jr.position_id,
  'IT Assistant I',
  'Supports HRIS maintenance and user support operations.',
  'BSIT/BSCS graduate; strong troubleshooting skills.',
  'Handle incident response, endpoint setup, and HRIS access support.',
  'published',
  current_date - 14,
  current_date + 21,
  c.admin_user_id
from tmp_seed_context c
join public.job_requisitions jr on jr.justification = 'Replacement for vacated IT support role.'
where not exists (
  select 1 from public.job_postings jp
  where jp.title = 'IT Assistant I' and jp.open_date = current_date - 14
);

with applicant_seed as (
  select *
  from (
    values
      ('Alex Rivera', 'alex.rivera@email.com', '09175550121', 'Pasig City', null::uuid),
      ('Joan Lim', 'joan.lim@email.com', '09175550122', 'Quezon City', null::uuid),
      ('Marco Uy', 'marco.uy@email.com', '09175550123', 'Taguig City', null::uuid)
  ) as t(full_name, email, mobile_no, current_address, user_id)
), updated as (
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
insert into public.applicant_profiles (user_id, full_name, email, mobile_no, current_address)
select s.user_id, s.full_name, s.email::citext, s.mobile_no, s.current_address
from applicant_seed s
where not exists (
  select 1 from public.applicant_profiles ap where lower(ap.email::text) = lower(s.email)
);

with posting_ref as (
  select id from public.job_postings where title = 'IT Assistant I' order by created_at desc limit 1
), applicant_ref as (
  select id, email
  from public.applicant_profiles
  where lower(email::text) in ('alex.rivera@email.com', 'joan.lim@email.com', 'marco.uy@email.com')
), app_seed as (
  select *
  from (
    values
      ('APP-2026-1001', 'alex.rivera@email.com', 'screening'),
      ('APP-2026-1002', 'joan.lim@email.com', 'interview'),
      ('APP-2026-1003', 'marco.uy@email.com', 'submitted')
  ) as t(ref_no, email, app_status)
)
insert into public.applications (application_ref_no, applicant_profile_id, job_posting_id, application_status, submitted_at)
select
  s.ref_no,
  ar.id,
  pr.id,
  s.app_status::public.application_status_enum,
  now() - interval '5 days'
from app_seed s
join applicant_ref ar on lower(ar.email::text) = lower(s.email)
cross join posting_ref pr
on conflict (application_ref_no) do update
set application_status = excluded.application_status,
    updated_at = now();

insert into public.application_status_history (application_id, old_status, new_status, changed_by, notes, created_at)
select
  a.id,
  null,
  a.application_status,
  c.staff_user_id,
  'Initial seeded status',
  now() - interval '4 days'
from public.applications a
cross join tmp_seed_context c
where a.application_ref_no in ('APP-2026-1001', 'APP-2026-1002', 'APP-2026-1003')
  and not exists (
    select 1 from public.application_status_history ash
    where ash.application_id = a.id and ash.notes = 'Initial seeded status'
  );

insert into public.application_documents (
  application_id,
  document_type,
  file_url,
  file_name,
  mime_type,
  file_size_bytes
)
select
  a.id,
  'resume',
  'https://example.local/storage/' || lower(a.application_ref_no) || '-resume.pdf',
  lower(a.application_ref_no) || '-resume.pdf',
  'application/pdf',
  220000
from public.applications a
where a.application_ref_no in ('APP-2026-1001', 'APP-2026-1002', 'APP-2026-1003')
  and not exists (
    select 1 from public.application_documents ad
    where ad.application_id = a.id and ad.document_type = 'resume'
  );

insert into public.application_interviews (
  application_id,
  interview_stage,
  scheduled_at,
  interview_mode,
  interviewer_user_id,
  score,
  result,
  remarks
)
select
  a.id,
  'hr',
  now() + interval '2 days',
  'online',
  c.staff_user_id,
  87.50,
  'pending',
  'Initial HR interview schedule.'
from public.applications a
cross join tmp_seed_context c
where a.application_ref_no = 'APP-2026-1002'
  and c.staff_user_id is not null
  and not exists (
    select 1 from public.application_interviews ai
    where ai.application_id = a.id and ai.interview_stage = 'hr'
  );

insert into public.application_feedback (application_id, decision, feedback_text, provided_by, provided_at)
select
  a.id,
  'for_next_step',
  'Candidate shows strong communication and baseline technical fit.',
  c.staff_user_id,
  now() - interval '1 day'
from public.applications a
cross join tmp_seed_context c
where a.application_ref_no = 'APP-2026-1001'
  and c.staff_user_id is not null
on conflict (application_id) do update
set
  decision = excluded.decision,
  feedback_text = excluded.feedback_text,
  provided_by = excluded.provided_by,
  provided_at = excluded.provided_at;

-- -----------------------------------------------------
-- 7) Document management
-- -----------------------------------------------------
insert into public.document_categories (category_key, category_name, requires_approval, retention_years)
values
  ('gov_id', 'Government ID', true, 10),
  ('payslip_support', 'Payslip Support', true, 7)
on conflict (category_key) do update
set
  category_name = excluded.category_name,
  requires_approval = excluded.requires_approval,
  retention_years = excluded.retention_years;

insert into public.documents (
  owner_person_id,
  category_id,
  title,
  description,
  storage_bucket,
  storage_path,
  current_version_no,
  document_status,
  uploaded_by
)
select
  c.employee_person_id,
  (select id from public.document_categories where category_key = 'pds' limit 1),
  'PDS - Employee 2026',
  'Updated personal data sheet for annual review.',
  'hris-documents',
  'documents/employee/pds-2026-v1.pdf',
  1,
  'submitted',
  c.employee_user_id
from tmp_seed_context c
where c.employee_person_id is not null
  and c.employee_user_id is not null
  and not exists (
    select 1 from public.documents d where d.storage_path = 'documents/employee/pds-2026-v1.pdf'
  );

insert into public.document_versions (
  document_id,
  version_no,
  file_name,
  mime_type,
  size_bytes,
  checksum_sha256,
  storage_path,
  uploaded_by
)
select
  d.id,
  1,
  'pds-2026-v1.pdf',
  'application/pdf',
  510000,
  encode(digest('documents/employee/pds-2026-v1.pdf', 'sha256'), 'hex'),
  d.storage_path,
  c.employee_user_id
from public.documents d
cross join tmp_seed_context c
where d.storage_path = 'documents/employee/pds-2026-v1.pdf'
  and not exists (
    select 1 from public.document_versions dv where dv.document_id = d.id and dv.version_no = 1
  );

insert into public.document_reviews (document_id, reviewer_user_id, review_status, review_notes, reviewed_at)
select
  d.id,
  c.staff_user_id,
  'approved',
  'Validated and approved by HR.',
  now() - interval '12 hours'
from public.documents d
cross join tmp_seed_context c
where d.storage_path = 'documents/employee/pds-2026-v1.pdf'
  and c.staff_user_id is not null
  and not exists (
    select 1 from public.document_reviews dr
    where dr.document_id = d.id and dr.reviewer_user_id = c.staff_user_id
  );

insert into public.document_access_logs (document_id, viewer_user_id, access_type, accessed_at)
select
  d.id,
  c.admin_user_id,
  'view',
  now() - interval '2 hours'
from public.documents d
cross join tmp_seed_context c
where d.storage_path = 'documents/employee/pds-2026-v1.pdf'
  and c.admin_user_id is not null
  and not exists (
    select 1 from public.document_access_logs dal
    where dal.document_id = d.id and dal.viewer_user_id = c.admin_user_id and dal.access_type = 'view'
  );

-- -----------------------------------------------------
-- 8) Timekeeping and leave
-- -----------------------------------------------------
insert into public.work_schedules (
  person_id,
  effective_from,
  effective_to,
  shift_name,
  time_in_expected,
  time_out_expected,
  break_minutes,
  is_flexible
)
select
  c.employee_person_id,
  date '2026-01-01',
  null,
  'Regular Day Shift',
  time '08:00',
  time '17:00',
  60,
  false
from tmp_seed_context c
where c.employee_person_id is not null
  and not exists (
    select 1 from public.work_schedules ws
    where ws.person_id = c.employee_person_id and ws.effective_from = date '2026-01-01'
  );

insert into public.attendance_logs (
  person_id,
  attendance_date,
  time_in,
  time_out,
  hours_worked,
  undertime_hours,
  late_minutes,
  attendance_status,
  source,
  recorded_by
)
select
  c.employee_person_id,
  date '2026-02-10',
  timestamptz '2026-02-10 08:06:00+08',
  timestamptz '2026-02-10 17:03:00+08',
  7.95,
  0,
  6,
  'late',
  'manual',
  c.staff_user_id
from tmp_seed_context c
where c.employee_person_id is not null
on conflict (person_id, attendance_date) do update
set
  time_in = excluded.time_in,
  time_out = excluded.time_out,
  hours_worked = excluded.hours_worked,
  undertime_hours = excluded.undertime_hours,
  late_minutes = excluded.late_minutes,
  attendance_status = excluded.attendance_status,
  source = excluded.source,
  recorded_by = excluded.recorded_by;

insert into public.leave_balances (
  person_id,
  leave_type_id,
  year,
  earned_credits,
  used_credits,
  remaining_credits
)
select
  c.employee_person_id,
  lt.id,
  2026,
  coalesce(lt.default_annual_credits, 0),
  2,
  greatest(coalesce(lt.default_annual_credits, 0) - 2, 0)
from tmp_seed_context c
join public.leave_types lt on lt.leave_code in ('VL', 'SL')
where c.employee_person_id is not null
on conflict (person_id, leave_type_id, year) do update
set
  earned_credits = excluded.earned_credits,
  used_credits = excluded.used_credits,
  remaining_credits = excluded.remaining_credits,
  updated_at = now();

insert into public.leave_requests (
  person_id,
  leave_type_id,
  date_from,
  date_to,
  days_count,
  reason,
  status,
  reviewed_by,
  reviewed_at,
  review_notes
)
select
  c.employee_person_id,
  (select id from public.leave_types where leave_code = 'VL' limit 1),
  date '2026-03-18',
  date '2026-03-19',
  2,
  'Family appointment.',
  'approved',
  c.staff_user_id,
  now() - interval '6 hours',
  'Approved based on available credits.'
from tmp_seed_context c
where c.employee_person_id is not null
  and not exists (
    select 1 from public.leave_requests lr
    where lr.person_id = c.employee_person_id and lr.date_from = date '2026-03-18' and lr.date_to = date '2026-03-19'
  );

insert into public.overtime_requests (
  person_id,
  overtime_date,
  start_time,
  end_time,
  hours_requested,
  reason,
  status,
  approved_by,
  approved_at
)
select
  c.employee_person_id,
  date '2026-02-11',
  time '18:00',
  time '20:00',
  2,
  'Urgent payroll validation support.',
  'approved',
  c.supervisor_user_id,
  now() - interval '5 hours'
from tmp_seed_context c
where c.employee_person_id is not null
  and not exists (
    select 1 from public.overtime_requests ot
    where ot.person_id = c.employee_person_id and ot.overtime_date = date '2026-02-11'
  );

insert into public.time_adjustment_requests (
  person_id,
  attendance_log_id,
  requested_time_in,
  requested_time_out,
  reason,
  status,
  reviewed_by,
  reviewed_at
)
select
  c.employee_person_id,
  al.id,
  timestamptz '2026-02-10 08:00:00+08',
  timestamptz '2026-02-10 17:05:00+08',
  'Correction due to network outage at check-in.',
  'approved',
  c.staff_user_id,
  now() - interval '4 hours'
from tmp_seed_context c
join public.attendance_logs al
  on al.person_id = c.employee_person_id and al.attendance_date = date '2026-02-10'
where c.employee_person_id is not null
  and not exists (
    select 1 from public.time_adjustment_requests tar
    where tar.attendance_log_id = al.id
  );

insert into public.holidays (holiday_date, holiday_name, holiday_type, office_id)
select date '2026-06-12', 'Independence Day', 'regular', c.office_id
from tmp_seed_context c
where not exists (
  select 1 from public.holidays h
  where h.holiday_date = date '2026-06-12' and h.office_id = c.office_id
);

-- -----------------------------------------------------
-- 9) Payroll
-- -----------------------------------------------------
insert into public.payroll_periods (
  period_code,
  period_start,
  period_end,
  payout_date,
  status
)
values
  ('2026-02-A', date '2026-02-01', date '2026-02-15', date '2026-02-20', 'closed'),
  ('2026-02-B', date '2026-02-16', date '2026-02-28', date '2026-03-05', 'processing')
on conflict (period_code) do update
set
  period_start = excluded.period_start,
  period_end = excluded.period_end,
  payout_date = excluded.payout_date,
  status = excluded.status,
  updated_at = now();

insert into public.employee_compensations (
  person_id,
  effective_from,
  effective_to,
  monthly_rate,
  daily_rate,
  hourly_rate,
  pay_frequency
)
select
  c.employee_person_id,
  date '2026-01-01',
  null,
  32000,
  1454.55,
  181.82,
  'semi_monthly'
from tmp_seed_context c
where c.employee_person_id is not null
  and not exists (
    select 1 from public.employee_compensations ec
    where ec.person_id = c.employee_person_id and ec.effective_from = date '2026-01-01'
  );

insert into public.payroll_runs (
  payroll_period_id,
  office_id,
  run_status,
  generated_by,
  approved_by,
  generated_at,
  approved_at
)
select
  pp.id,
  c.office_id,
  'approved',
  c.staff_user_id,
  c.admin_user_id,
  now() - interval '2 days',
  now() - interval '1 day'
from public.payroll_periods pp
cross join tmp_seed_context c
where pp.period_code = '2026-02-A'
  and c.staff_user_id is not null
  and not exists (
    select 1 from public.payroll_runs pr
    where pr.payroll_period_id = pp.id and pr.office_id = c.office_id
  );

insert into public.payroll_items (
  payroll_run_id,
  person_id,
  basic_pay,
  overtime_pay,
  allowances_total,
  deductions_total,
  gross_pay,
  net_pay
)
select
  pr.id,
  c.employee_person_id,
  16000,
  500,
  1000,
  750,
  17500,
  16750
from public.payroll_runs pr
cross join tmp_seed_context c
join public.payroll_periods pp on pp.id = pr.payroll_period_id
where pp.period_code = '2026-02-A'
  and c.employee_person_id is not null
on conflict (payroll_run_id, person_id) do update
set
  basic_pay = excluded.basic_pay,
  overtime_pay = excluded.overtime_pay,
  allowances_total = excluded.allowances_total,
  deductions_total = excluded.deductions_total,
  gross_pay = excluded.gross_pay,
  net_pay = excluded.net_pay,
  updated_at = now();

insert into public.payroll_adjustments (
  payroll_item_id,
  adjustment_type,
  adjustment_code,
  description,
  amount
)
select
  pi.id,
  'deduction',
  'HDMF',
  'Pag-IBIG contribution',
  100
from public.payroll_items pi
where not exists (
  select 1 from public.payroll_adjustments pa
  where pa.payroll_item_id = pi.id and pa.adjustment_code = 'HDMF'
);

insert into public.payslips (
  payroll_item_id,
  payslip_no,
  pdf_storage_path,
  released_at,
  viewed_at
)
select
  pi.id,
  'PS-2026-02A-' || right(replace(pi.person_id::text, '-', ''), 6),
  'payslips/2026/02A/' || replace(pi.person_id::text, '-', '') || '.pdf',
  now() - interval '18 hours',
  now() - interval '12 hours'
from public.payroll_items pi
on conflict (payroll_item_id) do update
set
  pdf_storage_path = excluded.pdf_storage_path,
  released_at = excluded.released_at,
  viewed_at = excluded.viewed_at;

-- -----------------------------------------------------
-- 10) Performance, PRAISE, L&D, reports
-- -----------------------------------------------------
insert into public.performance_cycles (
  cycle_name,
  period_start,
  period_end,
  status
)
select
  '2026 Midyear Cycle',
  date '2026-01-01',
  date '2026-06-30',
  'open'
where not exists (
  select 1
  from public.performance_cycles pc
  where pc.cycle_name = '2026 Midyear Cycle'
    and pc.period_start = date '2026-01-01'
    and pc.period_end = date '2026-06-30'
);

insert into public.performance_evaluations (
  cycle_id,
  employee_person_id,
  evaluator_user_id,
  final_rating,
  remarks,
  status
)
select
  pc.id,
  c.employee_person_id,
  c.supervisor_user_id,
  4.35,
  'Consistent delivery with good collaboration.',
  'submitted'
from (
  select id
  from public.performance_cycles
  where cycle_name = '2026 Midyear Cycle'
  order by created_at asc
  limit 1
) pc
cross join tmp_seed_context c
where c.employee_person_id is not null
  and c.supervisor_user_id is not null
on conflict (cycle_id, employee_person_id, evaluator_user_id) do update
set
  final_rating = excluded.final_rating,
  remarks = excluded.remarks,
  status = excluded.status,
  updated_at = now();

insert into public.praise_awards (
  award_code,
  award_name,
  description,
  criteria,
  is_active
)
values
  ('PRAISE-TEAMWORK', 'Outstanding Teamwork', 'Recognizes effective cross-unit collaboration.', 'Measurable collaboration outcomes', true)
on conflict (award_code) do update
set
  award_name = excluded.award_name,
  description = excluded.description,
  criteria = excluded.criteria,
  is_active = excluded.is_active,
  updated_at = now();

insert into public.praise_awards (
  award_code,
  award_name,
  description,
  criteria,
  is_active
)
values
  ('PRAISE-INNOVATION', 'Process Innovation', 'Recognizes process improvements that increase efficiency and service quality.', 'Documented impact and measurable outcomes', true)
on conflict (award_code) do update
set
  award_name = excluded.award_name,
  description = excluded.description,
  criteria = excluded.criteria,
  is_active = excluded.is_active,
  updated_at = now();

insert into public.praise_nominations (
  award_id,
  nominee_person_id,
  nominated_by_user_id,
  cycle_id,
  justification,
  status,
  reviewed_by,
  reviewed_at
)
select
  pa.id,
  c.employee_person_id,
  c.staff_user_id,
  pc.id,
  'Streamlined onboarding checklist and reduced handoff delays across teams.',
  'pending',
  null,
  null
from public.praise_awards pa
cross join (
  select id
  from public.performance_cycles
  where cycle_name = '2026 Midyear Cycle'
  order by created_at asc
  limit 1
) pc
cross join tmp_seed_context c
where pa.award_code = 'PRAISE-INNOVATION'
  and c.employee_person_id is not null
  and c.staff_user_id is not null
  and not exists (
    select 1
    from public.praise_nominations pn
    where pn.award_id = pa.id and pn.nominee_person_id = c.employee_person_id and pn.cycle_id = pc.id
  );

insert into public.praise_nominations (
  award_id,
  nominee_person_id,
  nominated_by_user_id,
  cycle_id,
  justification,
  status,
  reviewed_by,
  reviewed_at
)
select
  pa.id,
  c.employee_person_id,
  c.staff_user_id,
  pc.id,
  'Led HRIS data cleanup with high accuracy and timeliness.',
  'approved',
  c.admin_user_id,
  now() - interval '2 days'
from public.praise_awards pa
cross join (
  select id
  from public.performance_cycles
  where cycle_name = '2026 Midyear Cycle'
  order by created_at asc
  limit 1
) pc
cross join tmp_seed_context c
where pa.award_code = 'PRAISE-TEAMWORK'
  and c.employee_person_id is not null
  and c.staff_user_id is not null
  and not exists (
    select 1
    from public.praise_nominations pn
    where pn.award_id = pa.id and pn.nominee_person_id = c.employee_person_id and pn.cycle_id = pc.id
  );

insert into public.training_programs (
  program_code,
  title,
  provider,
  start_date,
  end_date,
  mode,
  status
)
values
  ('LND-2026-001', 'HRIS Data Privacy and Security', 'ATI Learning Office', date '2026-04-10', date '2026-04-12', 'hybrid', 'planned')
on conflict (program_code) do update
set
  title = excluded.title,
  provider = excluded.provider,
  start_date = excluded.start_date,
  end_date = excluded.end_date,
  mode = excluded.mode,
  status = excluded.status,
  updated_at = now();

insert into public.training_enrollments (
  program_id,
  person_id,
  enrollment_status,
  score,
  certificate_url
)
select
  tp.id,
  c.employee_person_id,
  'enrolled',
  null,
  null
from public.training_programs tp
cross join tmp_seed_context c
where tp.program_code = 'LND-2026-001'
  and c.employee_person_id is not null
on conflict (program_id, person_id) do update
set
  enrollment_status = excluded.enrollment_status,
  score = excluded.score,
  certificate_url = excluded.certificate_url,
  updated_at = now();

insert into public.generated_reports (
  requested_by,
  report_type,
  filters_json,
  file_format,
  storage_path,
  status,
  generated_at
)
select
  c.admin_user_id,
  'attendance',
  jsonb_build_object('from', '2026-02-01', 'to', '2026-02-15', 'office', 'central'),
  'csv',
  'reports/attendance/attendance-2026-02a.csv',
  'ready',
  now() - interval '1 day'
from tmp_seed_context c
where c.admin_user_id is not null
  and not exists (
    select 1 from public.generated_reports gr
    where gr.storage_path = 'reports/attendance/attendance-2026-02a.csv'
  );

-- -----------------------------------------------------
-- 10.1) Additional mock entries (2 scenarios: A/B)
-- -----------------------------------------------------
insert into public.access_requests (
  full_name,
  official_email,
  office_unit,
  requested_role_id,
  employee_reference_no,
  reason,
  status,
  reviewed_by,
  reviewed_at,
  review_notes
)
select
  s.full_name,
  s.email::citext,
  s.office_unit,
  (select id from public.roles where role_key = 'employee' limit 1),
  s.ref_no,
  s.reason,
  'approved'::public.request_status_enum,
  c.admin_user_id,
  now() - interval '2 days',
  'Approved in demo batch A/B.'
from tmp_seed_context c
cross join (
  values
    ('Nina Castillo', 'nina.castillo@da.gov.ph', 'Regional Extension Unit', 'REQ-2026-1002', 'Access for field reporting.'),
    ('Paolo Navarro', 'paolo.navarro@da.gov.ph', 'Provincial Admin Unit', 'REQ-2026-1003', 'Access for leave and document workflow.')
) as s(full_name, email, office_unit, ref_no, reason)
where not exists (
  select 1 from public.access_requests ar where lower(ar.official_email::text) = lower(s.email)
);

insert into public.notifications (recipient_user_id, category, title, body, link_url, is_read, read_at)
select
  c.employee_user_id,
  'documents',
  s.title,
  s.body,
  s.link_url,
  false,
  null
from tmp_seed_context c
cross join (
  values
    ('Document review completed A', 'Your uploaded document was approved.', '/pages/employee/document-management.php'),
    ('Document review completed B', 'A second uploaded document was approved.', '/pages/employee/document-management.php')
) as s(title, body, link_url)
where c.employee_user_id is not null
  and not exists (
    select 1
    from public.notifications n
    where n.recipient_user_id = c.employee_user_id
      and n.title = s.title
  );

insert into public.job_requisitions (
  office_id,
  position_id,
  requested_by,
  required_headcount,
  justification,
  status,
  approved_by,
  approved_at
)
select
  c.office_id,
  (select id from public.job_positions where position_code = 'HR-ASST-I' limit 1),
  c.staff_user_id,
  1,
  s.justification,
  'approved',
  c.admin_user_id,
  now() - interval '9 days'
from tmp_seed_context c
cross join (
  values
    ('Additional intake requirement A - HR support.'),
    ('Additional intake requirement B - HR support.')
) as s(justification)
where c.staff_user_id is not null
  and not exists (
    select 1 from public.job_requisitions jr where jr.justification = s.justification
  );

insert into public.job_postings (
  requisition_id,
  office_id,
  position_id,
  title,
  description,
  qualifications,
  responsibilities,
  posting_status,
  open_date,
  close_date,
  published_by
)
select
  jr.id,
  c.office_id,
  jr.position_id,
  s.title,
  s.description,
  'Relevant degree and government HR experience preferred.',
  'Provide operational HR and records support.',
  'published',
  current_date - 10,
  current_date + 20,
  c.admin_user_id
from tmp_seed_context c
join (
  values
    ('Additional intake requirement A - HR support.', 'HR Assistant I - Batch A', 'Additional HR operations support for office workflows.'),
    ('Additional intake requirement B - HR support.', 'HR Assistant I - Batch B', 'Additional HR operations support for filing and records.')
) as s(justification, title, description)
  on true
join public.job_requisitions jr on jr.justification = s.justification
where not exists (
  select 1 from public.job_postings jp where jp.title = s.title
);

with applicant_seed as (
  select *
  from (
    values
      ('Aira Mendoza', 'aira.mendoza@email.com', '09175550124', 'Mandaluyong City', 'APP-2026-1004', 'HR Assistant I - Batch A'),
      ('Bryan Flores', 'bryan.flores@email.com', '09175550125', 'Antipolo City', 'APP-2026-1005', 'HR Assistant I - Batch B')
  ) as t(full_name, email, mobile_no, current_address, app_ref, posting_title)
), upd as (
  update public.applicant_profiles ap
  set
    full_name = s.full_name,
    mobile_no = s.mobile_no,
    current_address = s.current_address,
    updated_at = now()
  from applicant_seed s
  where lower(ap.email::text) = lower(s.email)
  returning ap.id
), ins as (
  insert into public.applicant_profiles (full_name, email, mobile_no, current_address)
  select s.full_name, s.email::citext, s.mobile_no, s.current_address
  from applicant_seed s
  where not exists (
    select 1 from public.applicant_profiles ap where lower(ap.email::text) = lower(s.email)
  )
  returning id
)
insert into public.applications (application_ref_no, applicant_profile_id, job_posting_id, application_status, submitted_at)
select
  s.app_ref,
  ap.id,
  jp.id,
  'screening'::public.application_status_enum,
  now() - interval '3 days'
from applicant_seed s
join public.applicant_profiles ap on lower(ap.email::text) = lower(s.email)
join public.job_postings jp on jp.title = s.posting_title
on conflict (application_ref_no) do update
set application_status = excluded.application_status,
    updated_at = now();

insert into public.documents (
  owner_person_id,
  category_id,
  title,
  description,
  storage_bucket,
  storage_path,
  current_version_no,
  document_status,
  uploaded_by
)
select
  c.employee_person_id,
  (select id from public.document_categories where category_key = 'training-cert' limit 1),
  s.title,
  s.description,
  'hris-documents',
  s.storage_path,
  1,
  'approved'::public.doc_status_enum,
  c.employee_user_id
from tmp_seed_context c
cross join (
  values
    ('Training Certificate A', 'Seeded training document A.', 'documents/employee/training-cert-a-2026.pdf'),
    ('Training Certificate B', 'Seeded training document B.', 'documents/employee/training-cert-b-2026.pdf')
) as s(title, description, storage_path)
where c.employee_person_id is not null
  and c.employee_user_id is not null
  and not exists (
    select 1 from public.documents d where d.storage_path = s.storage_path
  );

insert into public.document_versions (
  document_id,
  version_no,
  file_name,
  mime_type,
  size_bytes,
  checksum_sha256,
  storage_path,
  uploaded_by
)
select
  d.id,
  1,
  split_part(d.storage_path, '/', 3),
  'application/pdf',
  300000,
  encode(digest(d.storage_path, 'sha256'), 'hex'),
  d.storage_path,
  c.employee_user_id
from public.documents d
cross join tmp_seed_context c
where d.storage_path in (
  'documents/employee/training-cert-a-2026.pdf',
  'documents/employee/training-cert-b-2026.pdf'
)
  and not exists (
    select 1 from public.document_versions dv where dv.document_id = d.id and dv.version_no = 1
  );

insert into public.attendance_logs (
  person_id,
  attendance_date,
  time_in,
  time_out,
  hours_worked,
  undertime_hours,
  late_minutes,
  attendance_status,
  source,
  recorded_by
)
select
  c.employee_person_id,
  s.attendance_date,
  s.time_in,
  s.time_out,
  s.hours_worked,
  0,
  s.late_minutes,
  s.attendance_status,
  'manual',
  c.staff_user_id
from tmp_seed_context c
cross join (
  values
    (date '2026-02-12', timestamptz '2026-02-12 08:01:00+08', timestamptz '2026-02-12 17:00:00+08', 7.98::numeric, 1, 'present'),
    (date '2026-02-13', timestamptz '2026-02-13 08:15:00+08', timestamptz '2026-02-13 17:00:00+08', 7.75::numeric, 15, 'late')
) as s(attendance_date, time_in, time_out, hours_worked, late_minutes, attendance_status)
where c.employee_person_id is not null
on conflict (person_id, attendance_date) do update
set
  time_in = excluded.time_in,
  time_out = excluded.time_out,
  hours_worked = excluded.hours_worked,
  undertime_hours = excluded.undertime_hours,
  late_minutes = excluded.late_minutes,
  attendance_status = excluded.attendance_status,
  source = excluded.source,
  recorded_by = excluded.recorded_by;

insert into public.leave_requests (
  person_id,
  leave_type_id,
  date_from,
  date_to,
  days_count,
  reason,
  status,
  reviewed_by,
  reviewed_at,
  review_notes
)
select
  c.employee_person_id,
  (select id from public.leave_types where leave_code = 'SL' limit 1),
  s.date_from,
  s.date_to,
  s.days_count,
  s.reason,
  'approved'::public.leave_request_status_enum,
  c.staff_user_id,
  now() - interval '2 hours',
  'Approved in extra seed scenario.'
from tmp_seed_context c
cross join (
  values
    (date '2026-04-02', date '2026-04-02', 1::numeric, 'Medical consultation.'),
    (date '2026-05-07', date '2026-05-08', 2::numeric, 'Recovery leave.')
) as s(date_from, date_to, days_count, reason)
where c.employee_person_id is not null
  and not exists (
    select 1 from public.leave_requests lr
    where lr.person_id = c.employee_person_id
      and lr.date_from = s.date_from
      and lr.date_to = s.date_to
  );

insert into public.generated_reports (
  requested_by,
  report_type,
  filters_json,
  file_format,
  storage_path,
  status,
  generated_at
)
select
  c.admin_user_id,
  s.report_type,
  s.filters_json,
  'csv',
  s.storage_path,
  'ready',
  now() - interval '6 hours'
from tmp_seed_context c
cross join (
  values
    ('payroll', jsonb_build_object('period_code', '2026-02-A'), 'reports/payroll/payroll-2026-02a.csv'),
    ('documents', jsonb_build_object('status', 'approved'), 'reports/documents/documents-approved-2026.csv')
) as s(report_type, filters_json, storage_path)
where c.admin_user_id is not null
  and not exists (
    select 1 from public.generated_reports gr where gr.storage_path = s.storage_path
  );

-- -----------------------------------------------------
-- 11) Additional employee (end-to-end core records)
-- -----------------------------------------------------
do $$
begin
  if not exists (
    select 1
    from auth.users
    where id = '6619caaf-af39-494a-8124-d82ac997b5af'::uuid
  ) then
    raise exception using
      message = 'Second employee auth user not found.',
      detail = 'auth.users row for 6619caaf-af39-494a-8124-d82ac997b5af is missing.',
      hint = 'Create this Auth user first, then rerun SUPABASE_SEED_DEMO_FULL.sql.';
  end if;
end
$$;

insert into public.user_accounts (
  id,
  email,
  account_status,
  created_at,
  updated_at
)
select
  au.id,
  au.email::citext,
  'active'::public.account_status_enum,
  now(),
  now()
from auth.users au
where au.id = '6619caaf-af39-494a-8124-d82ac997b5af'::uuid
on conflict (id) do update
set
  email = excluded.email,
  account_status = excluded.account_status,
  updated_at = now();

insert into public.user_role_assignments (
  user_id,
  role_id,
  office_id,
  assigned_by,
  is_primary,
  expires_at
)
select
  '6619caaf-af39-494a-8124-d82ac997b5af'::uuid,
  r.id,
  c.office_id,
  c.admin_user_id,
  true,
  null
from public.roles r
cross join tmp_seed_context c
where r.role_key = 'employee'
  and not exists (
    select 1
    from public.user_role_assignments ura
    where ura.user_id = '6619caaf-af39-494a-8124-d82ac997b5af'::uuid
      and ura.role_id = r.id
      and coalesce(ura.office_id, '00000000-0000-0000-0000-000000000000'::uuid) = coalesce(c.office_id, '00000000-0000-0000-0000-000000000000'::uuid)
  );

insert into public.user_office_scopes (user_id, office_id, scope_type)
select '6619caaf-af39-494a-8124-d82ac997b5af'::uuid, c.office_id, 'self'
from tmp_seed_context c
where c.office_id is not null
on conflict (user_id, office_id, scope_type) do nothing;

insert into public.people (
  user_id,
  surname,
  first_name,
  middle_name,
  date_of_birth,
  civil_status,
  citizenship,
  mobile_no,
  personal_email,
  agency_employee_no
)
values (
  '6619caaf-af39-494a-8124-d82ac997b5af'::uuid,
  'Dela Cruz',
  'Patricia',
  'Reyes',
  date '1996-08-14',
  'single',
  'Filipino',
  '09175550210',
  'patricia.delacruz@da.gov.ph'::citext,
  'EMP-2026-0002'
)
on conflict (agency_employee_no) do update
set
  surname = excluded.surname,
  first_name = excluded.first_name,
  middle_name = excluded.middle_name,
  user_id = excluded.user_id,
  date_of_birth = excluded.date_of_birth,
  civil_status = excluded.civil_status,
  citizenship = excluded.citizenship,
  mobile_no = excluded.mobile_no,
  personal_email = excluded.personal_email,
  updated_at = now();

create temporary table tmp_seed_employee_two as
select id as person_id, user_id
from public.people
where agency_employee_no = 'EMP-2026-0002'
limit 1;

insert into public.person_addresses (
  person_id,
  address_type,
  house_no,
  street,
  barangay,
  city_municipality,
  province,
  zip_code,
  country,
  is_primary
)
select
  e.person_id,
  'residential',
  '88',
  'Bonifacio St.',
  'Bagong Silang',
  'San Fernando',
  'Pampanga',
  '2000',
  'Philippines',
  true
from tmp_seed_employee_two e
where not exists (
  select 1 from public.person_addresses pa
  where pa.person_id = e.person_id and pa.address_type = 'residential'
);

insert into public.person_addresses (
  person_id,
  address_type,
  house_no,
  street,
  barangay,
  city_municipality,
  province,
  zip_code,
  country,
  is_primary
)
select
  e.person_id,
  'permanent',
  '12',
  'Maligaya Ave.',
  'San Nicolas',
  'Tarlac City',
  'Tarlac',
  '2300',
  'Philippines',
  false
from tmp_seed_employee_two e
where not exists (
  select 1 from public.person_addresses pa
  where pa.person_id = e.person_id and pa.address_type = 'permanent'
);

insert into public.person_government_ids (person_id, id_type, id_value_encrypted, last4)
select e.person_id, 'tin', encode(digest('TIN-9876-5432', 'sha256'), 'hex'), '5432'
from tmp_seed_employee_two e
on conflict (person_id, id_type) do update
set id_value_encrypted = excluded.id_value_encrypted,
    last4 = excluded.last4;

insert into public.person_educations (
  person_id,
  education_level,
  school_name,
  course_degree,
  period_from,
  period_to,
  year_graduated,
  sequence_no
)
select
  e.person_id,
  'college',
  'Don Honorio Ventura State University',
  'BS Information Systems',
  '2014',
  '2018',
  '2018',
  1
from tmp_seed_employee_two e
where not exists (
  select 1
  from public.person_educations pe
  where pe.person_id = e.person_id and pe.education_level = 'college' and pe.sequence_no = 1
);

insert into public.emergency_contacts (person_id, contact_name, relationship, mobile_no, address, is_primary)
select e.person_id, 'Rosa Dela Cruz', 'Mother', '09175550211', 'Tarlac City', true
from tmp_seed_employee_two e
where not exists (
  select 1 from public.emergency_contacts ec
  where ec.person_id = e.person_id and ec.is_primary = true
);

insert into public.employment_records (
  person_id,
  office_id,
  position_id,
  hire_date,
  employment_status,
  immediate_supervisor_person_id,
  probation_end_date,
  is_current
)
select
  e.person_id,
  c.office_id,
  (select id from public.job_positions where position_code = 'IT-ASST-I' limit 1),
  date '2025-07-15',
  'active',
  c.supervisor_person_id,
  date '2026-01-15',
  true
from tmp_seed_employee_two e
cross join tmp_seed_context c
on conflict (person_id) where is_current = true do update
set
  office_id = excluded.office_id,
  position_id = excluded.position_id,
  employment_status = excluded.employment_status,
  immediate_supervisor_person_id = excluded.immediate_supervisor_person_id,
  updated_at = now();

insert into public.documents (
  owner_person_id,
  category_id,
  title,
  description,
  storage_bucket,
  storage_path,
  current_version_no,
  document_status,
  uploaded_by
)
select
  e.person_id,
  (select id from public.document_categories where category_key = 'gov_id' limit 1),
  'Government ID - Patricia Dela Cruz',
  'Seeded supporting document for second employee.',
  'hris-documents',
  'documents/employee-two/gov-id-2026.pdf',
  1,
  'approved'::public.doc_status_enum,
  c.staff_user_id
from tmp_seed_employee_two e
cross join tmp_seed_context c
where c.staff_user_id is not null
  and not exists (
    select 1 from public.documents d where d.storage_path = 'documents/employee-two/gov-id-2026.pdf'
  );

insert into public.document_versions (
  document_id,
  version_no,
  file_name,
  mime_type,
  size_bytes,
  checksum_sha256,
  storage_path,
  uploaded_by
)
select
  d.id,
  1,
  'gov-id-2026.pdf',
  'application/pdf',
  260000,
  encode(digest(d.storage_path, 'sha256'), 'hex'),
  d.storage_path,
  c.staff_user_id
from public.documents d
cross join tmp_seed_context c
where d.storage_path = 'documents/employee-two/gov-id-2026.pdf'
  and c.staff_user_id is not null
  and not exists (
    select 1 from public.document_versions dv where dv.document_id = d.id and dv.version_no = 1
  );

insert into public.document_reviews (document_id, reviewer_user_id, review_status, review_notes, reviewed_at)
select
  d.id,
  c.staff_user_id,
  'approved',
  'Reviewed and approved for onboarding completeness.',
  now() - interval '10 hours'
from public.documents d
cross join tmp_seed_context c
where d.storage_path = 'documents/employee-two/gov-id-2026.pdf'
  and c.staff_user_id is not null
  and not exists (
    select 1 from public.document_reviews dr
    where dr.document_id = d.id and dr.reviewer_user_id = c.staff_user_id
  );

insert into public.work_schedules (
  person_id,
  effective_from,
  effective_to,
  shift_name,
  time_in_expected,
  time_out_expected,
  break_minutes,
  is_flexible
)
select
  e.person_id,
  date '2026-01-01',
  null,
  'Regular Day Shift',
  time '08:00',
  time '17:00',
  60,
  false
from tmp_seed_employee_two e
where not exists (
  select 1 from public.work_schedules ws
  where ws.person_id = e.person_id and ws.effective_from = date '2026-01-01'
);

insert into public.attendance_logs (
  person_id,
  attendance_date,
  time_in,
  time_out,
  hours_worked,
  undertime_hours,
  late_minutes,
  attendance_status,
  source,
  recorded_by
)
select
  e.person_id,
  s.attendance_date,
  s.time_in,
  s.time_out,
  s.hours_worked,
  0,
  s.late_minutes,
  s.attendance_status,
  'manual',
  c.staff_user_id
from tmp_seed_employee_two e
cross join tmp_seed_context c
cross join (
  values
    (date '2026-02-10', timestamptz '2026-02-10 08:00:00+08', timestamptz '2026-02-10 17:02:00+08', 8.03::numeric, 0, 'present'),
    (date '2026-02-11', timestamptz '2026-02-11 08:11:00+08', timestamptz '2026-02-11 17:00:00+08', 7.82::numeric, 11, 'late')
) as s(attendance_date, time_in, time_out, hours_worked, late_minutes, attendance_status)
on conflict (person_id, attendance_date) do update
set
  time_in = excluded.time_in,
  time_out = excluded.time_out,
  hours_worked = excluded.hours_worked,
  undertime_hours = excluded.undertime_hours,
  late_minutes = excluded.late_minutes,
  attendance_status = excluded.attendance_status,
  source = excluded.source,
  recorded_by = excluded.recorded_by;

insert into public.leave_balances (
  person_id,
  leave_type_id,
  year,
  earned_credits,
  used_credits,
  remaining_credits
)
select
  e.person_id,
  lt.id,
  2026,
  coalesce(lt.default_annual_credits, 0),
  1,
  greatest(coalesce(lt.default_annual_credits, 0) - 1, 0)
from tmp_seed_employee_two e
join public.leave_types lt on lt.leave_code in ('VL', 'SL')
on conflict (person_id, leave_type_id, year) do update
set
  earned_credits = excluded.earned_credits,
  used_credits = excluded.used_credits,
  remaining_credits = excluded.remaining_credits,
  updated_at = now();

insert into public.leave_requests (
  person_id,
  leave_type_id,
  date_from,
  date_to,
  days_count,
  reason,
  status,
  reviewed_by,
  reviewed_at,
  review_notes
)
select
  e.person_id,
  (select id from public.leave_types where leave_code = 'SL' limit 1),
  date '2026-03-05',
  date '2026-03-05',
  1,
  'Flu recovery and rest.',
  'approved',
  c.staff_user_id,
  now() - interval '5 hours',
  'Approved based on remaining credits.'
from tmp_seed_employee_two e
cross join tmp_seed_context c
where not exists (
  select 1 from public.leave_requests lr
  where lr.person_id = e.person_id and lr.date_from = date '2026-03-05' and lr.date_to = date '2026-03-05'
);

insert into public.overtime_requests (
  person_id,
  overtime_date,
  start_time,
  end_time,
  hours_requested,
  reason,
  status,
  approved_by,
  approved_at
)
select
  e.person_id,
  date '2026-02-14',
  time '18:00',
  time '20:00',
  2,
  'Month-end systems validation support.',
  'approved',
  c.supervisor_user_id,
  now() - interval '3 hours'
from tmp_seed_employee_two e
cross join tmp_seed_context c
where not exists (
  select 1 from public.overtime_requests ot
  where ot.person_id = e.person_id and ot.overtime_date = date '2026-02-14'
);

insert into public.time_adjustment_requests (
  person_id,
  attendance_log_id,
  requested_time_in,
  requested_time_out,
  reason,
  status,
  reviewed_by,
  reviewed_at
)
select
  e.person_id,
  al.id,
  timestamptz '2026-02-11 08:05:00+08',
  timestamptz '2026-02-11 17:00:00+08',
  'Adjusted due to turnstile sync delay.',
  'approved',
  c.staff_user_id,
  now() - interval '2 hours'
from tmp_seed_employee_two e
cross join tmp_seed_context c
join public.attendance_logs al on al.person_id = e.person_id and al.attendance_date = date '2026-02-11'
where not exists (
  select 1 from public.time_adjustment_requests tar
  where tar.attendance_log_id = al.id
);

insert into public.employee_compensations (
  person_id,
  effective_from,
  effective_to,
  monthly_rate,
  daily_rate,
  hourly_rate,
  pay_frequency
)
select
  e.person_id,
  date '2026-01-01',
  null,
  30000,
  1363.64,
  170.45,
  'semi_monthly'
from tmp_seed_employee_two e
where not exists (
  select 1 from public.employee_compensations ec
  where ec.person_id = e.person_id and ec.effective_from = date '2026-01-01'
);

insert into public.payroll_items (
  payroll_run_id,
  person_id,
  basic_pay,
  overtime_pay,
  allowances_total,
  deductions_total,
  gross_pay,
  net_pay
)
select
  pr.id,
  e.person_id,
  15000,
  350,
  900,
  650,
  16250,
  15600
from tmp_seed_employee_two e
join public.payroll_runs pr on true
join public.payroll_periods pp on pp.id = pr.payroll_period_id
where pp.period_code = '2026-02-A'
on conflict (payroll_run_id, person_id) do update
set
  basic_pay = excluded.basic_pay,
  overtime_pay = excluded.overtime_pay,
  allowances_total = excluded.allowances_total,
  deductions_total = excluded.deductions_total,
  gross_pay = excluded.gross_pay,
  net_pay = excluded.net_pay,
  updated_at = now();

insert into public.payslips (
  payroll_item_id,
  payslip_no,
  pdf_storage_path,
  released_at,
  viewed_at
)
select
  pi.id,
  'PS-2026-02A-' || right(replace(pi.person_id::text, '-', ''), 6),
  'payslips/2026/02A/' || replace(pi.person_id::text, '-', '') || '.pdf',
  now() - interval '16 hours',
  null
from public.payroll_items pi
join tmp_seed_employee_two e on e.person_id = pi.person_id
on conflict (payroll_item_id) do update
set
  pdf_storage_path = excluded.pdf_storage_path,
  released_at = excluded.released_at,
  viewed_at = excluded.viewed_at;

insert into public.training_enrollments (
  program_id,
  person_id,
  enrollment_status,
  score,
  certificate_url
)
select
  tp.id,
  e.person_id,
  'enrolled',
  null,
  null
from public.training_programs tp
cross join tmp_seed_employee_two e
where tp.program_code = 'LND-2026-001'
on conflict (program_id, person_id) do update
set
  enrollment_status = excluded.enrollment_status,
  score = excluded.score,
  certificate_url = excluded.certificate_url,
  updated_at = now();

insert into public.performance_evaluations (
  cycle_id,
  employee_person_id,
  evaluator_user_id,
  final_rating,
  remarks,
  status
)
select
  pc.id,
  e.person_id,
  c.supervisor_user_id,
  4.10,
  'Reliable output and consistent attendance trend.',
  'submitted'
from (
  select id
  from public.performance_cycles
  where cycle_name = '2026 Midyear Cycle'
  order by created_at asc
  limit 1
) pc
cross join tmp_seed_context c
cross join tmp_seed_employee_two e
where c.supervisor_user_id is not null
on conflict (cycle_id, employee_person_id, evaluator_user_id) do update
set
  final_rating = excluded.final_rating,
  remarks = excluded.remarks,
  status = excluded.status,
  updated_at = now();

insert into public.praise_nominations (
  award_id,
  nominee_person_id,
  nominated_by_user_id,
  cycle_id,
  justification,
  status,
  reviewed_by,
  reviewed_at
)
select
  pa.id,
  e.person_id,
  c.staff_user_id,
  pc.id,
  'Delivered accurate reports and assisted in HRIS cleanup sprint.',
  'approved',
  c.admin_user_id,
  now() - interval '1 day'
from public.praise_awards pa
cross join (
  select id
  from public.performance_cycles
  where cycle_name = '2026 Midyear Cycle'
  order by created_at asc
  limit 1
) pc
cross join tmp_seed_context c
cross join tmp_seed_employee_two e
where pa.award_code = 'PRAISE-TEAMWORK'
  and c.staff_user_id is not null
  and not exists (
    select 1
    from public.praise_nominations pn
    where pn.award_id = pa.id and pn.nominee_person_id = e.person_id and pn.cycle_id = pc.id
  );

insert into public.notifications (
  recipient_user_id,
  category,
  title,
  body,
  link_url,
  is_read,
  read_at
)
select
  e.user_id,
  'learning',
  'New training assigned',
  'You were enrolled in HRIS Data Privacy and Security (LND-2026-001).',
  '/pages/employee/praise.php',
  false,
  null
from tmp_seed_employee_two e
where e.user_id is not null
  and not exists (
    select 1
    from public.notifications n
    where n.recipient_user_id = e.user_id
      and n.title = 'New training assigned'
  );

insert into public.login_audit_logs (
  user_id,
  email_attempted,
  auth_provider,
  event_type,
  ip_address,
  user_agent,
  metadata
)
select
  e.user_id,
  ua.email,
  'password',
  'login_success',
  '127.0.0.1'::inet,
  'Mozilla/5.0 (Seed Script - Employee 2)',
  jsonb_build_object('source', 'SUPABASE_SEED_DEMO_FULL.sql', 'seed_employee', 'EMP-2026-0002')
from tmp_seed_employee_two e
join public.user_accounts ua on ua.id = e.user_id
where e.user_id is not null
  and not exists (
    select 1
    from public.login_audit_logs l
    where l.user_id = e.user_id
      and l.event_type = 'login_success'
      and l.user_agent = 'Mozilla/5.0 (Seed Script - Employee 2)'
  );

drop table if exists tmp_seed_employee_two;

-- -----------------------------------------------------
-- 12) Audit and login activity
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
  c.staff_user_id,
  'timekeeping',
  'leave_requests',
  lr.id,
  'approve',
  jsonb_build_object('status', 'pending'),
  jsonb_build_object('status', 'approved'),
  '127.0.0.1'::inet
from tmp_seed_context c
join public.leave_requests lr on lr.person_id = c.employee_person_id and lr.date_from = date '2026-03-18'
where c.staff_user_id is not null
  and not exists (
    select 1 from public.activity_logs al
    where al.entity_id = lr.id and al.action_name = 'approve' and al.module_name = 'timekeeping'
  );

insert into public.login_audit_logs (
  user_id,
  email_attempted,
  auth_provider,
  event_type,
  ip_address,
  user_agent,
  metadata
)
select
  c.employee_user_id,
  ua.email,
  'password',
  'login_success',
  '127.0.0.1'::inet,
  'Mozilla/5.0 (Seed Script)',
  jsonb_build_object('source', 'SUPABASE_SEED_DEMO_FULL.sql')
from tmp_seed_context c
join public.user_accounts ua on ua.id = c.employee_user_id
where c.employee_user_id is not null
  and not exists (
    select 1 from public.login_audit_logs l
    where l.user_id = c.employee_user_id and l.event_type = 'login_success' and l.user_agent = 'Mozilla/5.0 (Seed Script)'
  );

commit;

-- -----------------------------------------------------
-- Quick verification
-- -----------------------------------------------------
-- select count(*) from public.people;
-- select count(*) from public.applications;
-- select count(*) from public.documents;
-- select count(*) from public.leave_requests;
-- select count(*) from public.payroll_items;
-- select count(*) from public.performance_evaluations;
-- select count(*) from public.generated_reports;
