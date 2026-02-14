-- DA HRIS Demo Seed (Admin - Document Management)
-- Purpose: temporary mock data for Document Management page validation
-- Safe to rerun: yes (update-then-insert pattern + constrained upserts)
--
-- Prerequisites:
-- 1) Run SUPABASE_SCHEMA.sql
-- 2) Run SUPABASE_SEED_MVP.sql
-- 3) Recommended: run SUPABASE_SEED_DEMO_USER_MANAGEMENT.sql first
--
-- Note:
-- - Physical file upload to Supabase Storage is NOT required for table/listing UI.
-- - Actual preview/download links require real files in the configured bucket/path.

begin;

-- -----------------------------------------------------
-- 0) Minimum guardrail: requires at least one user + person
-- -----------------------------------------------------
do $$
begin
  if not exists (select 1 from public.user_accounts) then
    raise exception using
      message = 'Document demo seed prerequisites missing.',
      detail = 'No rows in public.user_accounts.',
      hint = 'Create at least one user account (or run your user demo seed) before this file.';
  end if;

  if not exists (select 1 from public.people) then
    raise exception using
      message = 'Document demo seed prerequisites missing.',
      detail = 'No rows in public.people.',
      hint = 'Create at least one people profile linked to user_accounts before this file.';
  end if;
end
$$;

-- -----------------------------------------------------
-- A) Document categories
-- -----------------------------------------------------
insert into public.document_categories (
  category_key,
  category_name,
  requires_approval,
  retention_years
)
values
  ('pds', 'Personal Data Sheet', true, 5),
  ('service_record', 'Service Record', true, 10),
  ('training_certificate', 'Training Certificate', true, 5),
  ('eligibility_certificate', 'Eligibility Certificate', true, 10)
on conflict (category_key) do update
set
  category_name = excluded.category_name,
  requires_approval = excluded.requires_approval,
  retention_years = excluded.retention_years;

-- -----------------------------------------------------
-- B) Documents (update existing by storage_path, insert missing)
-- -----------------------------------------------------
with seed as (
  select *
  from (
    values
      (
        'doc-pds-ana',
        'Personal Data Sheet - Ana Dela Cruz',
        'pds',
        'employee.one@da.gov.ph',
        'hr.staff@da.gov.ph',
        'submitted',
        'hris-documents',
        'documents/employee.one/pds-2026-v1.pdf',
        'Updated PDS for annual profile validation.',
        1,
        'pds-ana-dela-cruz-v1.pdf',
        'application/pdf',
        524288,
        'a34c51535059f8bd72fd5ef4a379f5af8de5f3c8c0f5d1888b4a30ebcc840101'
      ),
      (
        'doc-service-mark',
        'Service Record - Mark Villanueva',
        'service_record',
        'employee.two@da.gov.ph',
        'hr.staff@da.gov.ph',
        'approved',
        'hris-documents',
        'documents/employee.two/service-record-2026.pdf',
        'Latest service record for internal verification.',
        1,
        'service-record-mark-villanueva-2026.pdf',
        'application/pdf',
        432112,
        'b10f3a731ec8f42f2eb0d6f4f2284ac2d5e4872ad962914f3af83a4efba24d22'
      ),
      (
        'doc-training-ana',
        'Training Certificate - Ana Dela Cruz',
        'training_certificate',
        'employee.one@da.gov.ph',
        'admin@da.gov.ph',
        'rejected',
        'hris-documents',
        'documents/employee.one/training-certificate-ict-2026.pdf',
        'Certificate upload for HR records completion.',
        1,
        'training-certificate-ict-ana-2026.pdf',
        'application/pdf',
        289004,
        'ec2258c7c454af71f3ce7fc38cc0f34f2fb3f7552aa0877f2f877f0c9630a333'
      ),
      (
        'doc-eligibility-lea',
        'Eligibility Certificate - Lea Ramos',
        'eligibility_certificate',
        'archived.user@da.gov.ph',
        'admin@da.gov.ph',
        'archived',
        'hris-documents',
        'documents/archived.user/eligibility-certificate-2024.pdf',
        'Archived copy retained for audit reference.',
        2,
        'eligibility-certificate-lea-ramos-2024.pdf',
        'application/pdf',
        315220,
        'dc3da074f4f93de6bd68051bfb8d12af2c043128cc4c58fdb39e8f90a40f022b'
      )
  ) as t(
    doc_key,
    title,
    category_key,
    owner_email,
    uploader_email,
    document_status,
    storage_bucket,
    storage_path,
    description,
    version_no,
    file_name,
    mime_type,
    size_bytes,
    checksum_sha256
  )
), resolved as (
  select
    s.*,
    dc.id as category_id,
    coalesce(owner_people.id, fallback_owner.person_id) as owner_person_id,
    coalesce(uploader_user.id, fallback_uploader.user_id) as uploaded_by
  from seed s
  join public.document_categories dc
    on dc.category_key = s.category_key
  left join public.user_accounts owner_user
    on lower(owner_user.email::text) = lower(s.owner_email)
  left join public.people owner_people
    on owner_people.user_id = owner_user.id
  left join public.user_accounts uploader_user
    on lower(uploader_user.email::text) = lower(s.uploader_email)
  cross join lateral (
    select p.id as person_id
    from public.people p
    order by p.created_at asc
    limit 1
  ) fallback_owner
  cross join lateral (
    select ua.id as user_id
    from public.user_accounts ua
    order by ua.created_at asc
    limit 1
  ) fallback_uploader
), update_existing as (
  update public.documents d
  set
    owner_person_id = r.owner_person_id,
    category_id = r.category_id,
    title = r.title,
    description = r.description,
    storage_bucket = r.storage_bucket,
    current_version_no = r.version_no,
    document_status = r.document_status::public.doc_status_enum,
    uploaded_by = r.uploaded_by,
    updated_at = now()
  from resolved r
  where d.storage_path = r.storage_path
  returning d.id
)
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
  r.owner_person_id,
  r.category_id,
  r.title,
  r.description,
  r.storage_bucket,
  r.storage_path,
  r.version_no,
  r.document_status::public.doc_status_enum,
  r.uploaded_by
from resolved r
where not exists (
  select 1
  from public.documents d
  where d.storage_path = r.storage_path
);

-- -----------------------------------------------------
-- C) Document versions (upsert by unique(document_id, version_no))
-- -----------------------------------------------------
with seed as (
  select *
  from (
    values
      ('documents/employee.one/pds-2026-v1.pdf', 1, 'pds-ana-dela-cruz-v1.pdf', 'application/pdf', 524288, 'a34c51535059f8bd72fd5ef4a379f5af8de5f3c8c0f5d1888b4a30ebcc840101', 'hr.staff@da.gov.ph'),
      ('documents/employee.two/service-record-2026.pdf', 1, 'service-record-mark-villanueva-2026.pdf', 'application/pdf', 432112, 'b10f3a731ec8f42f2eb0d6f4f2284ac2d5e4872ad962914f3af83a4efba24d22', 'hr.staff@da.gov.ph'),
      ('documents/employee.one/training-certificate-ict-2026.pdf', 1, 'training-certificate-ict-ana-2026.pdf', 'application/pdf', 289004, 'ec2258c7c454af71f3ce7fc38cc0f34f2fb3f7552aa0877f2f877f0c9630a333', 'admin@da.gov.ph'),
      ('documents/archived.user/eligibility-certificate-2024.pdf', 2, 'eligibility-certificate-lea-ramos-2024.pdf', 'application/pdf', 315220, 'dc3da074f4f93de6bd68051bfb8d12af2c043128cc4c58fdb39e8f90a40f022b', 'admin@da.gov.ph')
  ) as t(storage_path, version_no, file_name, mime_type, size_bytes, checksum_sha256, uploader_email)
), resolved as (
  select
    d.id as document_id,
    s.version_no,
    s.file_name,
    s.mime_type,
    s.size_bytes,
    s.checksum_sha256,
    d.storage_path,
    coalesce(uploader.id, fallback_uploader.user_id) as uploaded_by
  from seed s
  join public.documents d on d.storage_path = s.storage_path
  left join public.user_accounts uploader on lower(uploader.email::text) = lower(s.uploader_email)
  cross join lateral (
    select ua.id as user_id
    from public.user_accounts ua
    order by ua.created_at asc
    limit 1
  ) fallback_uploader
)
insert into public.document_versions (
  document_id,
  version_no,
  file_name,
  mime_type,
  size_bytes,
  checksum_sha256,
  storage_path,
  uploaded_by,
  uploaded_at
)
select
  r.document_id,
  r.version_no,
  r.file_name,
  r.mime_type,
  r.size_bytes,
  r.checksum_sha256,
  r.storage_path,
  r.uploaded_by,
  now() - interval '2 days'
from resolved r
on conflict (document_id, version_no) do update
set
  file_name = excluded.file_name,
  mime_type = excluded.mime_type,
  size_bytes = excluded.size_bytes,
  checksum_sha256 = excluded.checksum_sha256,
  storage_path = excluded.storage_path,
  uploaded_by = excluded.uploaded_by;

-- -----------------------------------------------------
-- D) Review workflow records (insert if missing)
-- -----------------------------------------------------
with review_seed as (
  select *
  from (
    values
      ('documents/employee.one/pds-2026-v1.pdf', 'approved', 'Validated against required profile fields.', 'hr.staff@da.gov.ph', now() - interval '1 day'),
      ('documents/employee.one/training-certificate-ict-2026.pdf', 'needs_revision', 'Upload a clearer scan of the training signature section.', 'admin@da.gov.ph', now() - interval '12 hours'),
      ('documents/archived.user/eligibility-certificate-2024.pdf', 'approved', 'Archived after completion of retention workflow.', 'admin@da.gov.ph', now() - interval '8 days')
  ) as t(storage_path, review_status, review_notes, reviewer_email, reviewed_at)
), resolved as (
  select
    d.id as document_id,
    coalesce(reviewer.id, fallback_reviewer.user_id) as reviewer_user_id,
    rs.review_status,
    rs.review_notes,
    rs.reviewed_at
  from review_seed rs
  join public.documents d on d.storage_path = rs.storage_path
  left join public.user_accounts reviewer on lower(reviewer.email::text) = lower(rs.reviewer_email)
  cross join lateral (
    select ua.id as user_id
    from public.user_accounts ua
    order by ua.created_at asc
    limit 1
  ) fallback_reviewer
)
insert into public.document_reviews (
  document_id,
  reviewer_user_id,
  review_status,
  review_notes,
  reviewed_at,
  created_at
)
select
  r.document_id,
  r.reviewer_user_id,
  r.review_status::public.approval_status_enum,
  r.review_notes,
  r.reviewed_at,
  r.reviewed_at
from resolved r
where not exists (
  select 1
  from public.document_reviews dr
  where dr.document_id = r.document_id
    and dr.reviewer_user_id = r.reviewer_user_id
    and dr.review_status = r.review_status::public.approval_status_enum
    and coalesce(dr.review_notes, '') = coalesce(r.review_notes, '')
);

-- -----------------------------------------------------
-- E) Access log proof (insert if missing)
-- -----------------------------------------------------
with access_seed as (
  select *
  from (
    values
      ('documents/employee.one/pds-2026-v1.pdf', 'admin@da.gov.ph', 'view'),
      ('documents/employee.two/service-record-2026.pdf', 'hr.staff@da.gov.ph', 'download'),
      ('documents/archived.user/eligibility-certificate-2024.pdf', 'admin@da.gov.ph', 'view')
  ) as t(storage_path, viewer_email, access_type)
), resolved as (
  select
    d.id as document_id,
    coalesce(viewer.id, fallback_viewer.user_id) as viewer_user_id,
    a.access_type
  from access_seed a
  join public.documents d on d.storage_path = a.storage_path
  left join public.user_accounts viewer on lower(viewer.email::text) = lower(a.viewer_email)
  cross join lateral (
    select ua.id as user_id
    from public.user_accounts ua
    order by ua.created_at asc
    limit 1
  ) fallback_viewer
)
insert into public.document_access_logs (
  document_id,
  viewer_user_id,
  access_type,
  accessed_at
)
select
  r.document_id,
  r.viewer_user_id,
  r.access_type,
  now() - interval '6 hours'
from resolved r
where not exists (
  select 1
  from public.document_access_logs dal
  where dal.document_id = r.document_id
    and dal.viewer_user_id = r.viewer_user_id
    and dal.access_type = r.access_type
);

-- -----------------------------------------------------
-- F) Activity log proof for document module
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
  admin_user.id,
  'document_management',
  'documents',
  d.id,
  'seed_demo_document_management',
  null,
  jsonb_build_object(
    'title', d.title,
    'document_status', d.document_status,
    'storage_path', d.storage_path
  ),
  '127.0.0.1'::inet
from public.documents d
join lateral (
  select ua.id
  from public.user_accounts ua
  where lower(ua.email::text) = 'admin@da.gov.ph'
  order by ua.created_at asc
  limit 1
) admin_user on true
where d.storage_path in (
  'documents/employee.one/pds-2026-v1.pdf',
  'documents/employee.two/service-record-2026.pdf',
  'documents/employee.one/training-certificate-ict-2026.pdf',
  'documents/archived.user/eligibility-certificate-2024.pdf'
)
and not exists (
  select 1
  from public.activity_logs al
  where al.entity_id = d.id
    and al.module_name = 'document_management'
    and al.action_name = 'seed_demo_document_management'
);

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
  fallback_admin.id,
  'document_management',
  'documents',
  d.id,
  'seed_demo_document_management',
  null,
  jsonb_build_object(
    'title', d.title,
    'document_status', d.document_status,
    'storage_path', d.storage_path
  ),
  '127.0.0.1'::inet
from public.documents d
join lateral (
  select ua.id
  from public.user_accounts ua
  order by ua.created_at asc
  limit 1
) fallback_admin on true
where d.storage_path in (
  'documents/employee.one/pds-2026-v1.pdf',
  'documents/employee.two/service-record-2026.pdf',
  'documents/employee.one/training-certificate-ict-2026.pdf',
  'documents/archived.user/eligibility-certificate-2024.pdf'
)
and not exists (
  select 1
  from public.activity_logs al
  where al.entity_id = d.id
    and al.module_name = 'document_management'
    and al.action_name = 'seed_demo_document_management'
);

commit;

-- -----------------------------------------------------
-- Verification 1: document listing summary
-- -----------------------------------------------------
select
  d.title,
  dc.category_name,
  d.document_status,
  p.first_name || ' ' || p.surname as owner_name,
  uploader.email as uploaded_by,
  d.updated_at
from public.documents d
join public.document_categories dc on dc.id = d.category_id
join public.people p on p.id = d.owner_person_id
join public.user_accounts uploader on uploader.id = d.uploaded_by
where d.storage_path like 'documents/%'
order by d.updated_at desc;

-- -----------------------------------------------------
-- Verification 2: latest review per seeded document
-- -----------------------------------------------------
select
  d.title,
  dr.review_status,
  dr.review_notes,
  reviewer.email as reviewer_email,
  dr.reviewed_at
from public.document_reviews dr
join public.documents d on d.id = dr.document_id
join public.user_accounts reviewer on reviewer.id = dr.reviewer_user_id
where d.storage_path in (
  'documents/employee.one/pds-2026-v1.pdf',
  'documents/employee.two/service-record-2026.pdf',
  'documents/employee.one/training-certificate-ict-2026.pdf',
  'documents/archived.user/eligibility-certificate-2024.pdf'
)
order by dr.reviewed_at desc;

-- -----------------------------------------------------
-- Verification 3: access log proof
-- -----------------------------------------------------
select
  d.title,
  dal.access_type,
  viewer.email as viewer_email,
  dal.accessed_at
from public.document_access_logs dal
join public.documents d on d.id = dal.document_id
join public.user_accounts viewer on viewer.id = dal.viewer_user_id
where d.storage_path in (
  'documents/employee.one/pds-2026-v1.pdf',
  'documents/employee.two/service-record-2026.pdf',
  'documents/employee.one/training-certificate-ict-2026.pdf',
  'documents/archived.user/eligibility-certificate-2024.pdf'
)
order by dal.accessed_at desc;
