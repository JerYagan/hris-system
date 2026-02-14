-- DA HRIS MVP Seed Data
-- Run this AFTER SUPABASE_SCHEMA.sql

begin;

-- =====================================================
-- 1) Organization and Offices
-- =====================================================
insert into public.organizations (code, name, is_active)
values ('DA-ATI', 'Department of Agriculture - Agricultural Training Institute', true)
on conflict (code) do update
set name = excluded.name,
    is_active = excluded.is_active,
    updated_at = now();

with org as (
  select id from public.organizations where code = 'DA-ATI'
)
insert into public.offices (organization_id, office_code, office_name, office_type, is_active)
select org.id, seed.office_code, seed.office_name, seed.office_type, true
from org
join (
  values
    ('DA-ATI-CENTRAL', 'ATI Central Office', 'central'),
    ('DA-ATI-HR', 'HR Division', 'division'),
    ('DA-ATI-IT', 'IT Unit', 'unit')
) as seed(office_code, office_name, office_type) on true
on conflict (office_code) do update
set office_name = excluded.office_name,
    office_type = excluded.office_type,
    is_active = excluded.is_active,
    updated_at = now();

-- =====================================================
-- 2) Roles
-- =====================================================
insert into public.roles (role_key, role_name, description, is_system)
values
  ('admin', 'Administrator', 'Full system access', true),
  ('hr_officer', 'HR Officer', 'HR operations and approvals', true),
  ('supervisor', 'Supervisor', 'Supervisory approvals and review', true),
  ('staff', 'Staff', 'Operational HR support', true),
  ('employee', 'Employee', 'Employee self-service access', true),
  ('applicant', 'Applicant', 'Applicant portal access', true)
on conflict (role_key) do update
set role_name = excluded.role_name,
    description = excluded.description,
    is_system = excluded.is_system,
    updated_at = now();

-- =====================================================
-- 3) Permissions
-- =====================================================
insert into public.permissions (permission_key, module_name, action_name, description)
values
  ('users.read', 'users', 'read', 'View users'),
  ('users.manage', 'users', 'manage', 'Manage user accounts and roles'),
  ('settings.manage', 'settings', 'manage', 'Manage system settings'),

  ('recruitment.read', 'recruitment', 'read', 'View recruitment data'),
  ('recruitment.create', 'recruitment', 'create', 'Create requisitions and postings'),
  ('recruitment.update', 'recruitment', 'update', 'Update recruitment records'),
  ('recruitment.approve', 'recruitment', 'approve', 'Approve recruitment decisions'),

  ('applications.read', 'applications', 'read', 'View applications'),
  ('applications.update', 'applications', 'update', 'Update application status'),
  ('applications.submit', 'applications', 'submit', 'Submit job applications'),

  ('documents.read', 'documents', 'read', 'View documents'),
  ('documents.upload', 'documents', 'upload', 'Upload documents'),
  ('documents.review', 'documents', 'review', 'Review and approve documents'),

  ('timekeeping.read', 'timekeeping', 'read', 'View attendance and leave records'),
  ('timekeeping.request_leave', 'timekeeping', 'request_leave', 'Submit leave request'),
  ('timekeeping.approve_leave', 'timekeeping', 'approve_leave', 'Approve leave requests'),

  ('payroll.read', 'payroll', 'read', 'View payroll information'),
  ('payroll.manage', 'payroll', 'manage', 'Manage payroll runs and items'),

  ('performance.read', 'performance', 'read', 'View evaluations and PRAISE data'),
  ('performance.evaluate', 'performance', 'evaluate', 'Create and update evaluations'),

  ('reports.read', 'reports', 'read', 'View generated reports'),
  ('reports.export', 'reports', 'export', 'Generate and export reports')
on conflict (permission_key) do update
set module_name = excluded.module_name,
    action_name = excluded.action_name,
    description = excluded.description;

-- =====================================================
-- 4) Role-Permission Mapping
-- =====================================================
insert into public.role_permissions (role_id, permission_id)
select r.id, p.id
from public.roles r
join (
  values
    ('admin', 'users.read'),
    ('admin', 'users.manage'),
    ('admin', 'settings.manage'),
    ('admin', 'recruitment.read'),
    ('admin', 'recruitment.create'),
    ('admin', 'recruitment.update'),
    ('admin', 'recruitment.approve'),
    ('admin', 'applications.read'),
    ('admin', 'applications.update'),
    ('admin', 'documents.read'),
    ('admin', 'documents.upload'),
    ('admin', 'documents.review'),
    ('admin', 'timekeeping.read'),
    ('admin', 'timekeeping.request_leave'),
    ('admin', 'timekeeping.approve_leave'),
    ('admin', 'payroll.read'),
    ('admin', 'payroll.manage'),
    ('admin', 'performance.read'),
    ('admin', 'performance.evaluate'),
    ('admin', 'reports.read'),
    ('admin', 'reports.export'),

    ('hr_officer', 'users.read'),
    ('hr_officer', 'recruitment.read'),
    ('hr_officer', 'recruitment.create'),
    ('hr_officer', 'recruitment.update'),
    ('hr_officer', 'applications.read'),
    ('hr_officer', 'applications.update'),
    ('hr_officer', 'documents.read'),
    ('hr_officer', 'documents.review'),
    ('hr_officer', 'timekeeping.read'),
    ('hr_officer', 'timekeeping.approve_leave'),
    ('hr_officer', 'payroll.read'),
    ('hr_officer', 'performance.read'),
    ('hr_officer', 'performance.evaluate'),
    ('hr_officer', 'reports.read'),
    ('hr_officer', 'reports.export'),

    ('staff', 'recruitment.read'),
    ('staff', 'recruitment.create'),
    ('staff', 'applications.read'),
    ('staff', 'applications.update'),
    ('staff', 'documents.read'),
    ('staff', 'documents.review'),
    ('staff', 'timekeeping.read'),
    ('staff', 'timekeeping.approve_leave'),
    ('staff', 'reports.read'),
    ('staff', 'reports.export'),

    ('supervisor', 'applications.read'),
    ('supervisor', 'documents.read'),
    ('supervisor', 'documents.review'),
    ('supervisor', 'timekeeping.read'),
    ('supervisor', 'timekeeping.approve_leave'),
    ('supervisor', 'performance.read'),
    ('supervisor', 'performance.evaluate'),

    ('employee', 'documents.read'),
    ('employee', 'documents.upload'),
    ('employee', 'timekeeping.read'),
    ('employee', 'timekeeping.request_leave'),
    ('employee', 'payroll.read'),
    ('employee', 'performance.read'),
    ('employee', 'reports.read'),

    ('applicant', 'applications.submit'),
    ('applicant', 'applications.read')
) as map(role_key, permission_key) on r.role_key = map.role_key
  join public.permissions p on p.permission_key = map.permission_key
on conflict (role_id, permission_id) do nothing;

-- =====================================================
-- 5) Baseline Leave Types
-- =====================================================
insert into public.leave_types (leave_code, leave_name, default_annual_credits, requires_attachment, is_active)
values
  ('VL', 'Vacation Leave', 15, false, true),
  ('SL', 'Sick Leave', 15, false, true),
  ('ML', 'Maternity Leave', 105, true, true),
  ('PL', 'Paternity Leave', 7, true, true),
  ('SPL', 'Special Privilege Leave', 3, false, true),
  ('LWOP', 'Leave Without Pay', 0, false, true)
on conflict (leave_code) do update
set leave_name = excluded.leave_name,
    default_annual_credits = excluded.default_annual_credits,
    requires_attachment = excluded.requires_attachment,
    is_active = excluded.is_active;

-- =====================================================
-- 6) Baseline Document Categories
-- =====================================================
insert into public.document_categories (category_key, category_name, requires_approval, retention_years)
values
  ('pds', 'Personal Data Sheet', true, 10),
  ('service-record', 'Service Record', true, 10),
  ('leave-form', 'Leave Form', true, 5),
  ('payroll-support', 'Payroll Supporting Document', true, 7),
  ('training-cert', 'Training Certificate', false, 5)
on conflict (category_key) do update
set category_name = excluded.category_name,
    requires_approval = excluded.requires_approval,
    retention_years = excluded.retention_years;

commit;

-- =====================================================
-- 7) First Admin Bootstrap (run after creating Auth user)
-- =====================================================
-- Steps:
-- 1) Create your admin in Supabase Authentication first.
-- 2) Replace admin email below and run this block.

with admin_auth as (
  select id, email
  from auth.users
  where email = 'admin@da.gov.ph'
  limit 1
), upsert_account as (
  insert into public.user_accounts (id, email, account_status, email_verified_at)
  select id, email::citext, 'active', now()
  from admin_auth
  on conflict (id) do update
  set email = excluded.email,
      account_status = excluded.account_status,
      email_verified_at = coalesce(public.user_accounts.email_verified_at, excluded.email_verified_at),
      updated_at = now()
  returning id
)
insert into public.user_role_assignments (user_id, role_id, office_id, is_primary, assigned_at)
select ua.id, r.id, o.id, true, now()
from upsert_account ua
join public.roles r on r.role_key = 'admin'
join public.offices o on o.office_code = 'DA-ATI-CENTRAL'
on conflict do nothing;
