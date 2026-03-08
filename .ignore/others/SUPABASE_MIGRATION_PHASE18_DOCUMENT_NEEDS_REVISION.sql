-- Phase 18: Enable explicit needs_revision state for documents
-- Run in Supabase SQL editor (or migration runner) once.

begin;

alter type public.doc_status_enum add value if not exists 'needs_revision';

commit;
