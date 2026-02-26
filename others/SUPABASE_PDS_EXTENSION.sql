-- DA HRIS PDS Extension (CS Form 212 alignment)
-- Run after SUPABASE_SCHEMA.sql

begin;

-- =====================================================
-- 1) Additional PDS tables
-- =====================================================

create table if not exists public.person_civil_service_eligibilities (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  eligibility_name text not null,
  rating text,
  exam_date date,
  exam_place text,
  license_no text,
  license_validity date,
  sequence_no int not null default 1,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (license_validity is null or exam_date is null or license_validity >= exam_date)
);

create table if not exists public.person_educational_backgrounds (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  education_level text not null,
  school_name text,
  degree_course text,
  attendance_from_year int,
  attendance_to_year int,
  highest_level_units_earned text,
  year_graduated int,
  scholarship_honors_received text,
  sequence_no int not null default 1,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (person_id, education_level),
  check (education_level in ('elementary','secondary','vocational_trade_course','college','graduate_studies')),
  check (attendance_from_year is null or attendance_from_year between 1900 and 2100),
  check (attendance_to_year is null or attendance_to_year between 1900 and 2100),
  check (year_graduated is null or year_graduated between 1900 and 2100),
  check (attendance_to_year is null or attendance_from_year is null or attendance_to_year >= attendance_from_year)
);

create table if not exists public.person_work_experiences (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  inclusive_date_from date not null,
  inclusive_date_to date,
  position_title text not null,
  office_company text not null,
  monthly_salary numeric(12,2),
  salary_grade_step text,
  appointment_status text,
  is_government_service boolean,
  separation_reason text,
  achievements text,
  sequence_no int not null default 1,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (inclusive_date_to is null or inclusive_date_to >= inclusive_date_from),
  check (monthly_salary is null or monthly_salary >= 0)
);

create table if not exists public.person_voluntary_works (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  organization_name text not null,
  organization_address text,
  date_from date,
  date_to date,
  no_of_hours int,
  position_nature_of_work text,
  sequence_no int not null default 1,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (date_to is null or date_from is null or date_to >= date_from),
  check (no_of_hours is null or no_of_hours >= 0)
);

create table if not exists public.person_learning_development (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  title_of_program text not null,
  date_from date,
  date_to date,
  number_of_hours int,
  learning_type text,
  conducted_by text,
  sequence_no int not null default 1,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (date_to is null or date_from is null or date_to >= date_from),
  check (number_of_hours is null or number_of_hours >= 0)
);

create table if not exists public.person_other_information (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  info_type text not null,
  info_value text not null,
  sequence_no int not null default 1,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (info_type in ('special_skill','non_academic_distinction','membership'))
);

create table if not exists public.person_references (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null references public.people(id) on delete cascade,
  reference_name text not null,
  reference_address text,
  telephone_no text,
  sequence_no int not null default 1,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.person_pds_declarations (
  id uuid primary key default gen_random_uuid(),
  person_id uuid not null unique references public.people(id) on delete cascade,

  q34_a_criminal_conviction boolean,
  q34_a_details text,
  q34_b_admin_offense boolean,
  q34_b_details text,

  q35_pending_case boolean,
  q35_details text,

  q36_separated_service boolean,
  q36_details text,

  q37_candidate_in_election boolean,
  q37_candidate_details text,
  q37_campaigning boolean,
  q37_campaigning_details text,

  q38_related_within_third_degree boolean,
  q38_details text,

  q39_related_within_fourth_degree boolean,
  q39_details text,

  q40_disability boolean,
  q40_disability_details text,

  q41_solo_parent boolean,
  q41_solo_parent_id_no text,

  government_issued_id_name text,
  government_issued_id_no text,
  government_issued_id_date date,
  government_issued_id_place text,

  e_signature_url text,
  date_accomplished date,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

-- =====================================================
-- 2) Indexes
-- =====================================================

create index if not exists idx_person_civil_service_person on public.person_civil_service_eligibilities(person_id);
create index if not exists idx_person_educational_background_person on public.person_educational_backgrounds(person_id, sequence_no asc);
create index if not exists idx_person_work_experience_person on public.person_work_experiences(person_id, inclusive_date_from desc);
create index if not exists idx_person_voluntary_work_person on public.person_voluntary_works(person_id);
create index if not exists idx_person_learning_development_person on public.person_learning_development(person_id);
create index if not exists idx_person_other_information_person on public.person_other_information(person_id, info_type);
create index if not exists idx_person_references_person on public.person_references(person_id);

-- =====================================================
-- 3) updated_at triggers
-- =====================================================

create trigger trg_person_civil_service_updated_at before update on public.person_civil_service_eligibilities for each row execute function public.set_updated_at();
create trigger trg_person_educational_background_updated_at before update on public.person_educational_backgrounds for each row execute function public.set_updated_at();
create trigger trg_person_work_experience_updated_at before update on public.person_work_experiences for each row execute function public.set_updated_at();
create trigger trg_person_voluntary_work_updated_at before update on public.person_voluntary_works for each row execute function public.set_updated_at();
create trigger trg_person_learning_development_updated_at before update on public.person_learning_development for each row execute function public.set_updated_at();
create trigger trg_person_other_information_updated_at before update on public.person_other_information for each row execute function public.set_updated_at();
create trigger trg_person_references_updated_at before update on public.person_references for each row execute function public.set_updated_at();
create trigger trg_person_pds_declarations_updated_at before update on public.person_pds_declarations for each row execute function public.set_updated_at();

-- =====================================================
-- 4) RLS
-- =====================================================

alter table public.person_civil_service_eligibilities enable row level security;
alter table public.person_educational_backgrounds enable row level security;
alter table public.person_work_experiences enable row level security;
alter table public.person_voluntary_works enable row level security;
alter table public.person_learning_development enable row level security;
alter table public.person_other_information enable row level security;
alter table public.person_references enable row level security;
alter table public.person_pds_declarations enable row level security;

-- Admin all-access policies
create policy person_civil_service_admin_all on public.person_civil_service_eligibilities
for all using (public.current_user_is_admin()) with check (public.current_user_is_admin());

create policy person_educational_background_admin_all on public.person_educational_backgrounds
for all using (public.current_user_is_admin()) with check (public.current_user_is_admin());

create policy person_work_experience_admin_all on public.person_work_experiences
for all using (public.current_user_is_admin()) with check (public.current_user_is_admin());

create policy person_voluntary_work_admin_all on public.person_voluntary_works
for all using (public.current_user_is_admin()) with check (public.current_user_is_admin());

create policy person_learning_development_admin_all on public.person_learning_development
for all using (public.current_user_is_admin()) with check (public.current_user_is_admin());

create policy person_other_information_admin_all on public.person_other_information
for all using (public.current_user_is_admin()) with check (public.current_user_is_admin());

create policy person_references_admin_all on public.person_references
for all using (public.current_user_is_admin()) with check (public.current_user_is_admin());

create policy person_pds_declarations_admin_all on public.person_pds_declarations
for all using (public.current_user_is_admin()) with check (public.current_user_is_admin());

-- Self-access policies (for owner-linked records)
create policy person_civil_service_self_all on public.person_civil_service_eligibilities
for all
using (
  exists (
    select 1 from public.people p
    where p.id = person_civil_service_eligibilities.person_id
      and p.user_id = auth.uid()
  )
)
with check (
  exists (
    select 1 from public.people p
    where p.id = person_civil_service_eligibilities.person_id
      and p.user_id = auth.uid()
  )
);

create policy person_educational_background_self_all on public.person_educational_backgrounds
for all
using (
  exists (
    select 1 from public.people p
    where p.id = person_educational_backgrounds.person_id
      and p.user_id = auth.uid()
  )
)
with check (
  exists (
    select 1 from public.people p
    where p.id = person_educational_backgrounds.person_id
      and p.user_id = auth.uid()
  )
);

create policy person_work_experience_self_all on public.person_work_experiences
for all
using (
  exists (
    select 1 from public.people p
    where p.id = person_work_experiences.person_id
      and p.user_id = auth.uid()
  )
)
with check (
  exists (
    select 1 from public.people p
    where p.id = person_work_experiences.person_id
      and p.user_id = auth.uid()
  )
);

create policy person_voluntary_work_self_all on public.person_voluntary_works
for all
using (
  exists (
    select 1 from public.people p
    where p.id = person_voluntary_works.person_id
      and p.user_id = auth.uid()
  )
)
with check (
  exists (
    select 1 from public.people p
    where p.id = person_voluntary_works.person_id
      and p.user_id = auth.uid()
  )
);

create policy person_learning_development_self_all on public.person_learning_development
for all
using (
  exists (
    select 1 from public.people p
    where p.id = person_learning_development.person_id
      and p.user_id = auth.uid()
  )
)
with check (
  exists (
    select 1 from public.people p
    where p.id = person_learning_development.person_id
      and p.user_id = auth.uid()
  )
);

create policy person_other_information_self_all on public.person_other_information
for all
using (
  exists (
    select 1 from public.people p
    where p.id = person_other_information.person_id
      and p.user_id = auth.uid()
  )
)
with check (
  exists (
    select 1 from public.people p
    where p.id = person_other_information.person_id
      and p.user_id = auth.uid()
  )
);

create policy person_references_self_all on public.person_references
for all
using (
  exists (
    select 1 from public.people p
    where p.id = person_references.person_id
      and p.user_id = auth.uid()
  )
)
with check (
  exists (
    select 1 from public.people p
    where p.id = person_references.person_id
      and p.user_id = auth.uid()
  )
);

create policy person_pds_declarations_self_all on public.person_pds_declarations
for all
using (
  exists (
    select 1 from public.people p
    where p.id = person_pds_declarations.person_id
      and p.user_id = auth.uid()
  )
)
with check (
  exists (
    select 1 from public.people p
    where p.id = person_pds_declarations.person_id
      and p.user_id = auth.uid()
  )
);

commit;
