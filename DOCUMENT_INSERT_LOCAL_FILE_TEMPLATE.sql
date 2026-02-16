-- Document insert template for files stored in local path:
-- hris-system/storage/document/
--
-- 1) Place your file in: /hris-system/storage/document/<stored_file_name>
-- 2) Replace the placeholders below.
-- 3) Run this SQL in Supabase SQL editor.

begin;

with new_document as (
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
  values (
    '55460f39-6a65-4e5e-934b-373d07a1faef', -- owner_person_id
    '29359d1f-da93-48e9-b064-939762ee631c', -- category_id
    'Sample Document Title',
    'Optional description',
    'local_documents',
    'report-payroll-20260215-074318-86a94334.pdf', -- file name inside storage/document/
    1,
    'submitted',
    '6628d9ad-12f2-4890-bfc6-dda925272200' -- uploaded_by (user_accounts.id)
  )
  returning id
)
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
  nd.id,
  1,
  'sample-document.pdf',
  'application/pdf',
  0, -- optional: replace with actual file size
  null, -- optional: replace with SHA-256 checksum
  'report-payroll-20260215-074318-86a94334.pdf',
  '6628d9ad-12f2-4890-bfc6-dda925272200'
from new_document nd;

commit;
