-- Phase 17 Applicant Flow Enhancements
-- Apply in Supabase SQL Editor before enabling full Phase 17 behavior.

begin;

alter table public.job_postings
  add column if not exists plantilla_item_no text,
  add column if not exists csc_reference_url text,
  add column if not exists required_documents jsonb not null default '[]'::jsonb;

create index if not exists idx_job_postings_plantilla_item_no
  on public.job_postings(plantilla_item_no);

commit;
