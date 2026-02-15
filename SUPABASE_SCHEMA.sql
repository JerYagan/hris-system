-- DA HRIS Supabase Schema (Admin-First)
-- Execute in Supabase SQL Editor or as migration.

begin;

-- =====================================================
-- 0) Extensions
-- =====================================================
create extension if not exists pgcrypto;
create extension if not exists citext;

-- =====================================================
-- 1) Utility Functions
-- =====================================================
create or replace function public.set_updated_at()
returns trigger
language plpgsql
as $$
begin
  new.updated_at = now();
  return new;
end;
$$;

-- =====================================================
-- 2) Enums
-- =====================================================
create type public.account_status_enum as enum ('pending','active','suspended','disabled','archived');
create type public.request_status_enum as enum ('pending','approved','rejected','cancelled');
create type public.posting_status_enum as enum ('draft','published','closed','archived');
create type public.application_status_enum as enum ('submitted','screening','shortlisted','interview','offer','hired','rejected','withdrawn');
create type public.doc_status_enum as enum ('draft','submitted','approved','rejected','archived');
create type public.approval_status_enum as enum ('pending','approved','rejected','needs_revision');
create type public.leave_request_status_enum as enum ('pending','approved','rejected','cancelled');
create type public.payroll_period_status_enum as enum ('open','processing','posted','closed');
create type public.payroll_run_status_enum as enum ('draft','computed','approved','released','cancelled');
create type public.performance_cycle_status_enum as enum ('draft','open','closed','archived');
create type public.training_status_enum as enum ('planned','open','ongoing','completed','cancelled');

-- =====================================================
-- 3) Admin / RBAC Foundation
-- =====================================================
create table if not exists public.organizations (
  id uuid primary key default gen_random_uuid(),
  code text not null unique,
  name text not null,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.offices (
  id uuid primary key default gen_random_uuid(),
  organization_id uuid not null references public.organizations(id) on delete restrict,
  office_code text not null unique,
  office_name text not null,
  office_type text not null,
  parent_office_id uuid references public.offices(id) on delete set null,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (office_type in ('central','regional','provincial','division','unit'))
);

create table if not exists public.roles (
  id uuid primary key default gen_random_uuid(),
  role_key text not null unique,
  role_name text not null,
  description text,
  is_system boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.permissions (
  id uuid primary key default gen_random_uuid(),
  permission_key text not null unique,
  module_name text not null,
  action_name text not null,
  description text,
  created_at timestamptz not null default now()
);

create table if not exists public.role_permissions (
  id uuid primary key default gen_random_uuid(),
  role_id uuid not null references public.roles(id) on delete cascade,
  permission_id uuid not null references public.permissions(id) on delete cascade,
  created_at timestamptz not null default now(),
  unique(role_id, permission_id)
);

create table if not exists public.user_accounts (
  id uuid primary key references auth.users(id) on delete cascade,
  email citext not null unique,
  username citext unique,
  mobile_no text,
  account_status public.account_status_enum not null default 'pending',
  email_verified_at timestamptz,
  last_login_at timestamptz,
  lockout_until timestamptz,
  failed_login_count int not null default 0,
  must_change_password boolean not null default false,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.user_role_assignments (
  id uuid primary key default gen_random_uuid(),
  user_id uuid not null references public.user_accounts(id) on delete cascade,
  role_id uuid not null references public.roles(id) on delete restrict,
  office_id uuid references public.offices(id) on delete set null,
  assigned_by uuid references public.user_accounts(id) on delete set null,
  assigned_at timestamptz not null default now(),
  is_primary boolean not null default false,
  expires_at timestamptz,
  created_at timestamptz not null default now(),
  check (expires_at is null or expires_at > assigned_at)
);

create unique index if not exists uq_user_role_assignment_scope
  on public.user_role_assignments(user_id, role_id, coalesce(office_id, '00000000-0000-0000-0000-000000000000'::uuid));

create table if not exists public.access_requests (
  id uuid primary key default gen_random_uuid(),
  full_name text not null,
  official_email citext not null,
  office_unit text not null,
  requested_role_id uuid not null references public.roles(id) on delete restrict,
  employee_reference_no text,
  reason text not null,
  status public.request_status_enum not null default 'pending',
  reviewed_by uuid references public.user_accounts(id) on delete set null,
  reviewed_at timestamptz,
  review_notes text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.login_audit_logs (
  id uuid primary key default gen_random_uuid(),
  user_id uuid references public.user_accounts(id) on delete set null,
  email_attempted citext,
  auth_provider text not null,
  event_type text not null,
  ip_address inet,
  user_agent text,
  metadata jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now()
);

create table if not exists public.activity_logs (
  id uuid primary key default gen_random_uuid(),
  actor_user_id uuid references public.user_accounts(id) on delete set null,
  module_name text not null,
  entity_name text not null,
  entity_id uuid,
  action_name text not null,
  old_data jsonb,
  new_data jsonb,
  ip_address inet,
  created_at timestamptz not null default now()
);

create table if not exists public.notifications (
  id uuid primary key default gen_random_uuid(),
  recipient_user_id uuid not null references public.user_accounts(id) on delete cascade,
  category text not null,
  title text not null,
  body text not null,
  link_url text,
  is_read boolean not null default false,
  read_at timestamptz,
  created_at timestamptz not null default now()
);

create table if not exists public.system_settings (
  id uuid primary key default gen_random_uuid(),
  setting_key text not null unique,
  setting_value jsonb not null default '{}'::jsonb,
  updated_by uuid references public.user_accounts(id) on delete set null,
  updated_at timestamptz not null default now()
);

create or replace function public.current_user_is_admin()
returns boolean
language sql
stable
as $$
  select exists (
    select 1
    from public.user_role_assignments ura
    join public.roles r on r.id = ura.role_id
    where ura.user_id = auth.uid()
      and ura.expires_at is null
      and r.role_key = 'admin'
  );
$$;

-- =====================================================
-- 4) Person / PDS / Profile
-- =====================================================
create table if not exists public.people (
  id uuid primary key default gen_random_uuid(),
  user_id uuid unique references public.user_accounts(id) on delete set null,
  surname text not null,
  first_name text not null,
  middle_name text,
  name_extension text,
  date_of_birth date,
  place_of_birth text,
  sex_at_birth text,
  civil_status text,
  height_m numeric(4,2),
  weight_kg numeric(5,2),
  blood_type text,
  citizenship text,
  dual_citizenship boolean,
  dual_citizenship_country text,
  telephone_no text,
  mobile_no text,
  personal_email citext,
  agency_employee_no text unique,
  profile_photo_url text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (sex_at_birth is null or sex_at_birth in ('male','female'))
);

create table if not exists public.person_addresses (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  address_type text not null,
  house_no text,
  street text,
  subdivision text,
  barangay text,
  city_municipality text,
  province text,
  zip_code text,
  country text not null default 'Philippines',
  is_primary boolean not null default false,
  created_at timestamptz not null default now(),
  check (address_type in ('residential','permanent','business','mailing'))
);

create table if not exists public.person_government_ids (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  id_type text not null,
  id_value_encrypted text not null,
  last4 text,
  created_at timestamptz not null default now(),
  unique(person_id, id_type),
  check (id_type in ('umid','pagibig','philhealth','psn','tin'))
);

create table if not exists public.person_family_spouses (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  surname text,
  first_name text,
  middle_name text,
  extension_name text,
  occupation text,
  employer_business_name text,
  business_address text,
  telephone_no text,
  sequence_no int not null default 1,
  created_at timestamptz not null default now()
);

create table if not exists public.person_family_children (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  full_name text not null,
  birth_date date,
  sequence_no int not null default 1,
  created_at timestamptz not null default now()
);

create table if not exists public.person_parents (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  parent_type text not null,
  surname text,
  first_name text,
  middle_name text,
  extension_name text,
  created_at timestamptz not null default now(),
  unique(person_id, parent_type),
  check (parent_type in ('father','mother'))
);

create table if not exists public.person_educations (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  education_level text not null,
  school_name text,
  course_degree text,
  period_from text,
  period_to text,
  highest_level_units text,
  year_graduated text,
  honors_received text,
  sequence_no int not null default 1,
  created_at timestamptz not null default now(),
  check (education_level in ('elementary','secondary','vocational','college','graduate'))
);

create table if not exists public.emergency_contacts (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  contact_name text not null,
  relationship text not null,
  mobile_no text not null,
  address text,
  is_primary boolean not null default false,
  created_at timestamptz not null default now()
);

-- =====================================================
-- 5) Employment and Office Assignment
-- =====================================================
create table if not exists public.job_positions (
  id uuid primary key default gen_random_uuid(),
  position_code text not null unique,
  position_title text not null,
  salary_grade text,
  employment_classification text not null,
  is_supervisory boolean not null default false,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (employment_classification in ('regular','coterminous','contractual','casual','job_order'))
);

create table if not exists public.employment_records (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  office_id uuid not null references public.offices(id) on delete restrict,
  position_id uuid not null references public.job_positions(id) on delete restrict,
  hire_date date not null,
  employment_status text not null,
  immediate_supervisor_person_id uuid references public.people(id) on delete set null,
  probation_end_date date,
  separation_date date,
  separation_reason text,
  is_current boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (employment_status in ('active','on_leave','resigned','retired','terminated'))
);

create unique index if not exists uq_employment_current_person
on public.employment_records(person_id)
where is_current = true;

create table if not exists public.user_office_scopes (
  id uuid primary key default gen_random_uuid(),
  user_id uuid not null references public.user_accounts(id) on delete cascade,
  office_id uuid not null references public.offices(id) on delete cascade,
  scope_type text not null,
  created_at timestamptz not null default now(),
  unique(user_id, office_id, scope_type),
  check (scope_type in ('self','office','subtree','organization'))
);

-- =====================================================
-- 6) Recruitment and Applicant Tracking
-- =====================================================
create table if not exists public.job_requisitions (
  id uuid primary key default gen_random_uuid(),
  office_id uuid not null references public.offices(id) on delete restrict,
  position_id uuid not null references public.job_positions(id) on delete restrict,
  requested_by uuid not null references public.user_accounts(id) on delete restrict,
  required_headcount int not null check (required_headcount > 0),
  justification text not null,
  status text not null default 'draft',
  approved_by uuid references public.user_accounts(id) on delete set null,
  approved_at timestamptz,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (status in ('draft','submitted','approved','rejected','closed'))
);

create table if not exists public.job_postings (
  id uuid primary key default gen_random_uuid(),
  requisition_id uuid references public.job_requisitions(id) on delete set null,
  office_id uuid not null references public.offices(id) on delete restrict,
  position_id uuid not null references public.job_positions(id) on delete restrict,
  title text not null,
  description text not null,
  qualifications text,
  responsibilities text,
  posting_status public.posting_status_enum not null default 'draft',
  open_date date not null,
  close_date date not null,
  published_by uuid references public.user_accounts(id) on delete set null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (close_date >= open_date)
);

create table if not exists public.applicant_profiles (
  id uuid primary key default gen_random_uuid(),
  user_id uuid unique references public.user_accounts(id) on delete set null,
  full_name text not null,
  email citext not null,
  mobile_no text,
  current_address text,
  resume_url text,
  portfolio_url text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.applications (
  id uuid primary key default gen_random_uuid(),
  applicant_profile_id uuid not null references public.applicant_profiles(id) on delete cascade,
  job_posting_id uuid not null references public.job_postings(id) on delete restrict,
  application_ref_no text not null unique,
  application_status public.application_status_enum not null default 'submitted',
  submitted_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique(applicant_profile_id, job_posting_id)
);

create table if not exists public.application_status_history (
  id uuid primary key default gen_random_uuid(),
  application_id uuid not null references public.applications(id) on delete cascade,
  old_status public.application_status_enum,
  new_status public.application_status_enum not null,
  changed_by uuid references public.user_accounts(id) on delete set null,
  notes text,
  created_at timestamptz not null default now()
);

create table if not exists public.application_documents (
  id uuid primary key default gen_random_uuid(),
  application_id uuid not null references public.applications(id) on delete cascade,
  document_type text not null,
  file_url text not null,
  file_name text not null,
  mime_type text,
  file_size_bytes bigint,
  uploaded_at timestamptz not null default now(),
  check (document_type in ('resume','pds','transcript','certificate','id','other'))
);

create table if not exists public.application_interviews (
  id uuid primary key default gen_random_uuid(),
  application_id uuid not null references public.applications(id) on delete cascade,
  interview_stage text not null,
  scheduled_at timestamptz not null,
  interview_mode text not null,
  interviewer_user_id uuid not null references public.user_accounts(id) on delete restrict,
  score numeric(5,2),
  result text,
  remarks text,
  created_at timestamptz not null default now(),
  check (interview_stage in ('hr','technical','final')),
  check (interview_mode in ('onsite','online','phone')),
  check (result is null or result in ('pass','fail','pending'))
);

create table if not exists public.application_feedback (
  id uuid primary key default gen_random_uuid(),
  application_id uuid not null unique references public.applications(id) on delete cascade,
  decision text not null,
  feedback_text text,
  provided_by uuid not null references public.user_accounts(id) on delete restrict,
  provided_at timestamptz not null default now(),
  check (decision in ('for_next_step','on_hold','rejected','hired'))
);

-- =====================================================
-- 7) Document Management
-- =====================================================
create table if not exists public.document_categories (
  id uuid primary key default gen_random_uuid(),
  category_key text not null unique,
  category_name text not null,
  requires_approval boolean not null default true,
  retention_years int,
  created_at timestamptz not null default now()
);

create table if not exists public.documents (
  id uuid primary key default gen_random_uuid(),
  owner_person_id uuid not null references public.people(id) on delete cascade,
  category_id uuid not null references public.document_categories(id) on delete restrict,
  title text not null,
  description text,
  storage_bucket text not null,
  storage_path text not null,
  current_version_no int not null default 1,
  document_status public.doc_status_enum not null default 'draft',
  uploaded_by uuid not null references public.user_accounts(id) on delete restrict,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.document_versions (
  id uuid primary key default gen_random_uuid(),
  document_id uuid not null references public.documents(id) on delete cascade,
  version_no int not null,
  file_name text not null,
  mime_type text,
  size_bytes bigint,
  checksum_sha256 text,
  storage_path text not null,
  uploaded_by uuid references public.user_accounts(id) on delete set null,
  uploaded_at timestamptz not null default now(),
  unique(document_id, version_no)
);

create table if not exists public.document_reviews (
  id uuid primary key default gen_random_uuid(),
  document_id uuid not null references public.documents(id) on delete cascade,
  reviewer_user_id uuid not null references public.user_accounts(id) on delete restrict,
  review_status public.approval_status_enum not null default 'pending',
  review_notes text,
  reviewed_at timestamptz,
  created_at timestamptz not null default now()
);

create table if not exists public.document_access_logs (
  id uuid primary key default gen_random_uuid(),
  document_id uuid not null references public.documents(id) on delete cascade,
  viewer_user_id uuid not null references public.user_accounts(id) on delete cascade,
  access_type text not null,
  accessed_at timestamptz not null default now(),
  check (access_type in ('view','download','print'))
);

-- =====================================================
-- 8) Timekeeping and Leave
-- =====================================================
create table if not exists public.work_schedules (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  effective_from date not null,
  effective_to date,
  shift_name text not null,
  time_in_expected time not null,
  time_out_expected time not null,
  break_minutes int not null default 60,
  is_flexible boolean not null default false,
  created_at timestamptz not null default now(),
  check (effective_to is null or effective_to >= effective_from),
  check (break_minutes >= 0)
);

create table if not exists public.attendance_logs (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  attendance_date date not null,
  time_in timestamptz,
  time_out timestamptz,
  hours_worked numeric(5,2),
  undertime_hours numeric(5,2),
  late_minutes int,
  attendance_status text not null,
  source text not null,
  recorded_by uuid references public.user_accounts(id) on delete set null,
  created_at timestamptz not null default now(),
  unique(person_id, attendance_date),
  check (attendance_status in ('present','late','absent','leave','holiday','rest_day')),
  check (source in ('manual','biometric','import','api'))
);

create table if not exists public.leave_types (
  id uuid primary key default gen_random_uuid(),
  leave_code text not null unique,
  leave_name text not null,
  default_annual_credits numeric(6,2),
  requires_attachment boolean not null default false,
  is_active boolean not null default true,
  created_at timestamptz not null default now()
);

create table if not exists public.leave_balances (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  leave_type_id uuid not null references public.leave_types(id) on delete restrict,
  year int not null,
  earned_credits numeric(6,2) not null default 0,
  used_credits numeric(6,2) not null default 0,
  remaining_credits numeric(6,2) not null default 0,
  updated_at timestamptz not null default now(),
  unique(person_id, leave_type_id, year),
  check (year >= 2000)
);

create table if not exists public.leave_requests (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  leave_type_id uuid not null references public.leave_types(id) on delete restrict,
  date_from date not null,
  date_to date not null,
  days_count numeric(5,2) not null,
  reason text not null,
  status public.leave_request_status_enum not null default 'pending',
  reviewed_by uuid references public.user_accounts(id) on delete set null,
  reviewed_at timestamptz,
  review_notes text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (date_to >= date_from),
  check (days_count > 0)
);

create table if not exists public.overtime_requests (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  overtime_date date not null,
  start_time time not null,
  end_time time not null,
  hours_requested numeric(5,2) not null,
  reason text not null,
  status public.leave_request_status_enum not null default 'pending',
  approved_by uuid references public.user_accounts(id) on delete set null,
  approved_at timestamptz,
  created_at timestamptz not null default now(),
  check (hours_requested > 0)
);

create table if not exists public.time_adjustment_requests (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  attendance_log_id uuid not null references public.attendance_logs(id) on delete cascade,
  requested_time_in timestamptz,
  requested_time_out timestamptz,
  reason text not null,
  status public.approval_status_enum not null default 'pending',
  reviewed_by uuid references public.user_accounts(id) on delete set null,
  reviewed_at timestamptz,
  created_at timestamptz not null default now()
);

create table if not exists public.holidays (
  id uuid primary key default gen_random_uuid(),
  holiday_date date not null,
  holiday_name text not null,
  holiday_type text not null,
  office_id uuid references public.offices(id) on delete set null,
  created_at timestamptz not null default now(),
  unique(holiday_date, office_id),
  check (holiday_type in ('regular','special','local'))
);

-- =====================================================
-- 9) Payroll
-- =====================================================
create table if not exists public.payroll_periods (
  id uuid primary key default gen_random_uuid(),
  period_code text not null unique,
  period_start date not null,
  period_end date not null,
  payout_date date not null,
  status public.payroll_period_status_enum not null default 'open',
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (period_end >= period_start),
  check (payout_date >= period_end)
);

create table if not exists public.employee_compensations (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  effective_from date not null,
  effective_to date,
  base_pay numeric(14,2) not null default 0,
  allowance_total numeric(14,2) not null default 0,
  tax_deduction numeric(14,2) not null default 0,
  government_deductions numeric(14,2) not null default 0,
  other_deductions numeric(14,2) not null default 0,
  monthly_rate numeric(14,2) not null,
  daily_rate numeric(14,2),
  hourly_rate numeric(14,2),
  pay_frequency text not null,
  created_at timestamptz not null default now(),
  check (base_pay >= 0),
  check (allowance_total >= 0),
  check (tax_deduction >= 0),
  check (government_deductions >= 0),
  check (other_deductions >= 0),
  check (monthly_rate >= 0),
  check (pay_frequency in ('monthly','semi_monthly','weekly')),
  check (effective_to is null or effective_to >= effective_from)
);

create table if not exists public.payroll_runs (
  id uuid primary key default gen_random_uuid(),
  payroll_period_id uuid not null references public.payroll_periods(id) on delete restrict,
  office_id uuid references public.offices(id) on delete set null,
  run_status public.payroll_run_status_enum not null default 'draft',
  generated_by uuid not null references public.user_accounts(id) on delete restrict,
  approved_by uuid references public.user_accounts(id) on delete set null,
  generated_at timestamptz not null default now(),
  approved_at timestamptz,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.payroll_items (
  id uuid primary key default gen_random_uuid(),
  payroll_run_id uuid not null references public.payroll_runs(id) on delete cascade,
  person_id uuid not null references public.people(id) on delete restrict,
  basic_pay numeric(14,2) not null default 0,
  overtime_pay numeric(14,2) not null default 0,
  allowances_total numeric(14,2) not null default 0,
  deductions_total numeric(14,2) not null default 0,
  gross_pay numeric(14,2) not null default 0,
  net_pay numeric(14,2) not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique(payroll_run_id, person_id)
);

create table if not exists public.payroll_adjustments (
  id uuid primary key default gen_random_uuid(),
  payroll_item_id uuid not null references public.payroll_items(id) on delete cascade,
  adjustment_type text not null,
  adjustment_code text not null,
  description text,
  amount numeric(14,2) not null,
  created_at timestamptz not null default now(),
  check (adjustment_type in ('earning','deduction'))
);

create table if not exists public.payslips (
  id uuid primary key default gen_random_uuid(),
  payroll_item_id uuid not null unique references public.payroll_items(id) on delete cascade,
  payslip_no text not null unique,
  pdf_storage_path text,
  released_at timestamptz,
  viewed_at timestamptz,
  created_at timestamptz not null default now()
);

-- =====================================================
-- 10) Performance / PRAISE / L&D / Reports
-- =====================================================
create table if not exists public.performance_cycles (
  id uuid primary key default gen_random_uuid(),
  cycle_name text not null,
  period_start date not null,
  period_end date not null,
  status public.performance_cycle_status_enum not null default 'draft',
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (period_end >= period_start)
);

create table if not exists public.performance_evaluations (
  id uuid primary key default gen_random_uuid(),
  cycle_id uuid not null references public.performance_cycles(id) on delete restrict,
  employee_person_id uuid not null references public.people(id) on delete restrict,
  evaluator_user_id uuid not null references public.user_accounts(id) on delete restrict,
  final_rating numeric(5,2),
  remarks text,
  status text not null default 'draft',
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique(cycle_id, employee_person_id, evaluator_user_id),
  check (status in ('draft','submitted','reviewed','approved'))
);

create table if not exists public.praise_awards (
  id uuid primary key default gen_random_uuid(),
  award_code text not null unique,
  award_name text not null,
  description text,
  criteria text,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.praise_nominations (
  id uuid primary key default gen_random_uuid(),
  award_id uuid not null references public.praise_awards(id) on delete restrict,
  nominee_person_id uuid not null references public.people(id) on delete restrict,
  nominated_by_user_id uuid not null references public.user_accounts(id) on delete restrict,
  cycle_id uuid references public.performance_cycles(id) on delete set null,
  justification text not null,
  status public.leave_request_status_enum not null default 'pending',
  reviewed_by uuid references public.user_accounts(id) on delete set null,
  reviewed_at timestamptz,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.training_programs (
  id uuid primary key default gen_random_uuid(),
  program_code text not null unique,
  title text not null,
  training_type text not null default 'General',
  training_category text not null default 'General',
  provider text,
  venue text not null default 'TBD',
  schedule_time time,
  start_date date not null,
  end_date date not null,
  mode text not null,
  status public.training_status_enum not null default 'planned',
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (mode in ('online','onsite','hybrid')),
  check (end_date >= start_date)
);

create table if not exists public.training_enrollments (
  id uuid primary key default gen_random_uuid(),
  program_id uuid not null references public.training_programs(id) on delete cascade,
  person_id uuid not null references public.people(id) on delete cascade,
  enrollment_status text not null,
  score numeric(5,2),
  certificate_url text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique(program_id, person_id),
  check (enrollment_status in ('enrolled','completed','failed','dropped'))
);

create table if not exists public.generated_reports (
  id uuid primary key default gen_random_uuid(),
  requested_by uuid not null references public.user_accounts(id) on delete restrict,
  report_type text not null,
  filters_json jsonb not null default '{}'::jsonb,
  file_format text not null,
  storage_path text,
  status text not null default 'queued',
  generated_at timestamptz,
  created_at timestamptz not null default now(),
  check (report_type in ('attendance','payroll','performance','documents','recruitment')),
  check (file_format in ('pdf','csv','xlsx')),
  check (status in ('queued','processing','ready','failed'))
);

-- =====================================================
-- 11) Indexes
-- =====================================================
create index if not exists idx_user_accounts_status on public.user_accounts(account_status);
create index if not exists idx_user_role_primary on public.user_role_assignments(user_id, is_primary);
create index if not exists idx_people_employee_no on public.people(agency_employee_no);
create index if not exists idx_employment_current on public.employment_records(person_id, is_current);
create index if not exists idx_applications_status on public.applications(job_posting_id, application_status);
create index if not exists idx_attendance_person_date on public.attendance_logs(person_id, attendance_date);
create index if not exists idx_leave_requests_filter on public.leave_requests(person_id, status, date_from);
create index if not exists idx_payroll_items_person on public.payroll_items(person_id);
create index if not exists idx_documents_owner_status on public.documents(owner_person_id, category_id, document_status);
create index if not exists idx_activity_logs_actor_created on public.activity_logs(actor_user_id, created_at desc);
create index if not exists idx_notifications_read_created on public.notifications(recipient_user_id, is_read, created_at desc);

-- =====================================================
-- 12) updated_at Triggers
-- =====================================================
create trigger trg_organizations_updated_at before update on public.organizations for each row execute function public.set_updated_at();
create trigger trg_offices_updated_at before update on public.offices for each row execute function public.set_updated_at();
create trigger trg_roles_updated_at before update on public.roles for each row execute function public.set_updated_at();
create trigger trg_user_accounts_updated_at before update on public.user_accounts for each row execute function public.set_updated_at();
create trigger trg_access_requests_updated_at before update on public.access_requests for each row execute function public.set_updated_at();
create trigger trg_people_updated_at before update on public.people for each row execute function public.set_updated_at();
create trigger trg_job_positions_updated_at before update on public.job_positions for each row execute function public.set_updated_at();
create trigger trg_employment_records_updated_at before update on public.employment_records for each row execute function public.set_updated_at();
create trigger trg_job_requisitions_updated_at before update on public.job_requisitions for each row execute function public.set_updated_at();
create trigger trg_job_postings_updated_at before update on public.job_postings for each row execute function public.set_updated_at();
create trigger trg_applicant_profiles_updated_at before update on public.applicant_profiles for each row execute function public.set_updated_at();
create trigger trg_applications_updated_at before update on public.applications for each row execute function public.set_updated_at();
create trigger trg_documents_updated_at before update on public.documents for each row execute function public.set_updated_at();
create trigger trg_leave_requests_updated_at before update on public.leave_requests for each row execute function public.set_updated_at();
create trigger trg_payroll_periods_updated_at before update on public.payroll_periods for each row execute function public.set_updated_at();
create trigger trg_payroll_runs_updated_at before update on public.payroll_runs for each row execute function public.set_updated_at();
create trigger trg_payroll_items_updated_at before update on public.payroll_items for each row execute function public.set_updated_at();
create trigger trg_performance_cycles_updated_at before update on public.performance_cycles for each row execute function public.set_updated_at();
create trigger trg_performance_evaluations_updated_at before update on public.performance_evaluations for each row execute function public.set_updated_at();
create trigger trg_praise_awards_updated_at before update on public.praise_awards for each row execute function public.set_updated_at();
create trigger trg_praise_nominations_updated_at before update on public.praise_nominations for each row execute function public.set_updated_at();
create trigger trg_training_programs_updated_at before update on public.training_programs for each row execute function public.set_updated_at();
create trigger trg_training_enrollments_updated_at before update on public.training_enrollments for each row execute function public.set_updated_at();

-- =====================================================
-- 13) RLS (Core)
-- =====================================================
alter table public.user_accounts enable row level security;
alter table public.people enable row level security;
alter table public.notifications enable row level security;
alter table public.applicant_profiles enable row level security;
alter table public.applications enable row level security;
alter table public.access_requests enable row level security;

-- Admin can do everything on core tables
create policy user_accounts_admin_all on public.user_accounts
for all using (public.current_user_is_admin()) with check (public.current_user_is_admin());

create policy people_admin_all on public.people
for all using (public.current_user_is_admin()) with check (public.current_user_is_admin());

create policy notifications_admin_all on public.notifications
for all using (public.current_user_is_admin()) with check (public.current_user_is_admin());

-- User can see/update own account/profile
create policy user_accounts_self_select on public.user_accounts
for select using (id = auth.uid());

create policy user_accounts_self_update on public.user_accounts
for update using (id = auth.uid()) with check (id = auth.uid());

create policy people_self_select on public.people
for select using (user_id = auth.uid());

create policy people_self_update on public.people
for update using (user_id = auth.uid()) with check (user_id = auth.uid());

create policy notifications_self_select on public.notifications
for select using (recipient_user_id = auth.uid());

create policy notifications_self_update on public.notifications
for update using (recipient_user_id = auth.uid()) with check (recipient_user_id = auth.uid());

-- Public can insert access requests (for request-access form)
create policy access_requests_public_insert on public.access_requests
for insert to anon, authenticated with check (true);

-- Applicant own data
create policy applicant_profiles_self_all on public.applicant_profiles
for all using (user_id = auth.uid() or public.current_user_is_admin())
with check (user_id = auth.uid() or public.current_user_is_admin());

create policy applications_applicant_or_admin on public.applications
for all using (
  public.current_user_is_admin()
  or exists (
    select 1
    from public.applicant_profiles ap
    where ap.id = applications.applicant_profile_id
      and ap.user_id = auth.uid()
  )
)
with check (
  public.current_user_is_admin()
  or exists (
    select 1
    from public.applicant_profiles ap
    where ap.id = applications.applicant_profile_id
      and ap.user_id = auth.uid()
  )
);

commit;
