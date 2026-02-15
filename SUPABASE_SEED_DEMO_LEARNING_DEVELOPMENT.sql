-- DA HRIS Demo Seed: Learning & Development
-- Run AFTER:
-- 1) SUPABASE_SCHEMA.sql
-- 2) SUPABASE_MIGRATION_LD_TRAINING_FIELDS.sql

begin;

-- -----------------------------------------------------
-- 0) Guardrails
-- -----------------------------------------------------
do $$
begin
  if not exists (select 1 from public.training_programs limit 1) then
    raise notice 'training_programs is empty. Seed will still proceed and create records.';
  end if;

  if not exists (
    select 1
    from public.employment_records er
    where er.is_current = true
      and er.employment_status = 'active'
  ) then
    raise exception using
      message = 'Learning & Development seed prerequisites missing.',
      detail = 'No active current employment records found.',
      hint = 'Seed employees and employment_records first.';
  end if;
end
$$;

-- -----------------------------------------------------
-- 1) Seed training programs (rerunnable by program_code)
-- -----------------------------------------------------
insert into public.training_programs (
  program_code,
  title,
  training_type,
  training_category,
  provider,
  venue,
  schedule_time,
  start_date,
  end_date,
  mode,
  status
)
values
  (
    'LND-2026-101',
    'Technical - Data Privacy and Cybersecurity',
    'Technical',
    'Data Privacy and Cybersecurity',
    'ATI Learning Office',
    'DA Central Office - Conference Room A',
    time '09:00',
    date '2026-03-10',
    date '2026-03-10',
    'hybrid',
    'planned'
  ),
  (
    'LND-2026-102',
    'Leadership - Performance Coaching Essentials',
    'Leadership',
    'Performance Coaching Essentials',
    'Civil Service Institute',
    'Online (MS Teams)',
    time '13:30',
    date '2026-03-18',
    date '2026-03-18',
    'online',
    'open'
  ),
  (
    'LND-2026-103',
    'Compliance - Records Management and Archiving',
    'Compliance',
    'Records Management and Archiving',
    'Records and Archives Unit',
    'DA Regional Office - Training Hall',
    time '08:30',
    date '2026-02-25',
    date '2026-02-25',
    'onsite',
    'ongoing'
  )
on conflict (program_code) do update
set
  title = excluded.title,
  training_type = excluded.training_type,
  training_category = excluded.training_category,
  provider = excluded.provider,
  venue = excluded.venue,
  schedule_time = excluded.schedule_time,
  start_date = excluded.start_date,
  end_date = excluded.end_date,
  mode = excluded.mode,
  status = excluded.status,
  updated_at = now();

-- -----------------------------------------------------
-- 2) Assign participants to each training
-- -----------------------------------------------------
with participant_pool as (
  select distinct er.person_id, row_number() over (order by er.created_at asc, er.person_id asc) as rn
  from public.employment_records er
  where er.is_current = true
    and er.employment_status = 'active'
  limit 12
),
training_targets as (
  select id, program_code
  from public.training_programs
  where program_code in ('LND-2026-101', 'LND-2026-102', 'LND-2026-103')
),
assignment_matrix as (
  select tt.id as program_id, pp.person_id,
    case
      when tt.program_code = 'LND-2026-101' and pp.rn <= 6 then 'enrolled'
      when tt.program_code = 'LND-2026-102' and pp.rn between 3 and 9 then 'completed'
      when tt.program_code = 'LND-2026-103' and pp.rn between 1 and 7 then 'enrolled'
      else null
    end as enrollment_status
  from training_targets tt
  cross join participant_pool pp
)
insert into public.training_enrollments (
  program_id,
  person_id,
  enrollment_status,
  score,
  certificate_url
)
select
  am.program_id,
  am.person_id,
  am.enrollment_status,
  case when am.enrollment_status = 'completed' then 92.50 else null end,
  case when am.enrollment_status = 'completed' then 'certificates/' || am.program_id::text || '/' || am.person_id::text || '.pdf' else null end
from assignment_matrix am
where am.enrollment_status is not null
on conflict (program_id, person_id) do update
set
  enrollment_status = excluded.enrollment_status,
  score = excluded.score,
  certificate_url = excluded.certificate_url,
  updated_at = now();

commit;
