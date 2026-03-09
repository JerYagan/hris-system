-- =====================================================
-- Phase 19: Applicant Training Hours Source Alignment
-- =====================================================
-- Purpose:
--   Add applicant-specific training hours field used by Applicant Profile,
--   Apply flow, and Admin Applicant Profile snapshot.

begin;

alter table if exists public.applicant_profiles
  add column if not exists training_hours_completed numeric(8,2) not null default 0;

do $$
begin
  if not exists (
    select 1
    from pg_constraint
    where conname = 'applicant_profiles_training_hours_completed_nonnegative'
      and conrelid = 'public.applicant_profiles'::regclass
  ) then
    alter table public.applicant_profiles
      add constraint applicant_profiles_training_hours_completed_nonnegative
      check (training_hours_completed >= 0);
  end if;
end;
$$;

commit;
