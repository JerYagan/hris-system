-- DA HRIS minimal baseline seed for a clean Supabase project.
-- Run this after .ignore/revisions/SUPABASE_SCHEMA.sql.

begin;

create extension if not exists pgcrypto;

insert into public.organizations (
  code,
  name,
  is_active
)
values (
  'DA-ATI',
  'Agricultural Training Institute',
  true
)
on conflict (code) do update
set
  name = excluded.name,
  is_active = excluded.is_active,
  updated_at = now();

insert into public.offices (
  organization_id,
  office_code,
  office_name,
  office_type,
  is_active
)
select
  o.id,
  'DA-ATI-CENTRAL',
  'ATI Central Office',
  'central',
  true
from public.organizations o
where o.code = 'DA-ATI'
on conflict (office_code) do update
set
  organization_id = excluded.organization_id,
  office_name = excluded.office_name,
  office_type = excluded.office_type,
  is_active = excluded.is_active,
  updated_at = now();

insert into public.roles (
  role_key,
  role_name,
  description,
  is_system
)
values
  ('admin', 'Admin', 'System administrator role.', true),
  ('staff', 'Staff', 'HR operations and recommendation role.', true),
  ('employee', 'Employee', 'Regular employee role.', true),
  ('applicant', 'Applicant', 'External applicant role.', true),
  ('hr_officer', 'HR Officer', 'Legacy HR officer role used by some policies.', true),
  ('supervisor', 'Supervisor', 'Legacy supervisor role used by some policies.', true)
on conflict (role_key) do update
set
  role_name = excluded.role_name,
  description = excluded.description,
  is_system = excluded.is_system,
  updated_at = now();

insert into public.job_positions (
  position_code,
  position_title,
  salary_grade,
  employment_classification,
  is_supervisory,
  is_active
)
values
  ('SYS-ADMIN', 'System Administrator', 'SG-24', 'regular', true, true),
  ('HR-STAFF', 'HR Staff', 'SG-11', 'regular', false, true),
  ('EMP-GEN', 'Administrative Aide', 'SG-06', 'regular', false, true)
on conflict (position_code) do update
set
  position_title = excluded.position_title,
  salary_grade = excluded.salary_grade,
  employment_classification = excluded.employment_classification,
  is_supervisory = excluded.is_supervisory,
  is_active = excluded.is_active,
  updated_at = now();

insert into public.system_settings (
  setting_key,
  setting_value
)
values
  ('employee_id_prefix', jsonb_build_object('value', 'DA-EMP-')),
  ('password_min_length', jsonb_build_object('value', '10')),
  ('login_lockout_threshold', jsonb_build_object('value', '5')),
  ('session_timeout_minutes', jsonb_build_object('value', '30'))
on conflict (setting_key) do update
set
  setting_value = excluded.setting_value,
  updated_at = now();

insert into storage.buckets (
  id,
  name,
  public
)
values
  ('hris-documents', 'hris-documents', false),
  ('hris-applications', 'hris-applications', false),
  ('hris-payslips', 'hris-payslips', false)
on conflict (id) do nothing;

do $$
declare
  seed_user record;
  resolved_user_id uuid;
  resolved_role_id uuid;
  resolved_office_id uuid;
  resolved_person_id uuid;
  resolved_position_id uuid;
  scoped_office_id uuid;
begin
  select id
  into resolved_office_id
  from public.offices
  where office_code = 'DA-ATI-CENTRAL'
  limit 1;

  if resolved_office_id is null then
    raise exception 'Seed aborted: office DA-ATI-CENTRAL was not created.';
  end if;

  for seed_user in
    select *
    from (
      values
        ('admin@hris.local', 'Admin123!', 'admin', 'System', 'Administrator', 'DA-EMP-0001', 'SYS-ADMIN', true, 'organization'),
        ('staff@hris.local', 'Password123!', 'staff', 'HR', 'Staff', 'DA-EMP-0002', 'HR-STAFF', true, 'office'),
        ('employee@hris.local', 'Employee123!', 'employee', 'Sample', 'Employee', 'DA-EMP-0003', 'EMP-GEN', true, 'self'),
        ('applicant@hris.local', 'Applicant123!', 'applicant', 'Sample', 'Applicant', null, null, false, null)
    ) as seeded(
      email,
      password,
      role_key,
      first_name,
      surname,
      agency_employee_no,
      position_code,
      with_employment,
      scope_type
    )
  loop
    resolved_user_id := null;
    resolved_role_id := null;
    resolved_person_id := null;
    resolved_position_id := null;
    scoped_office_id := null;

    if seed_user.role_key in ('admin', 'staff', 'employee') then
      scoped_office_id := resolved_office_id;
    end if;

    select u.id
    into resolved_user_id
    from auth.users u
    where lower(u.email) = lower(seed_user.email)
    limit 1;

    if resolved_user_id is null then
      resolved_user_id := gen_random_uuid();

      insert into auth.users (
        instance_id,
        id,
        aud,
        role,
        email,
        encrypted_password,
        email_confirmed_at,
        raw_app_meta_data,
        raw_user_meta_data,
        created_at,
        updated_at,
        confirmation_token,
        email_change,
        email_change_token_new,
        recovery_token
      )
      values (
        '00000000-0000-0000-0000-000000000000',
        resolved_user_id,
        'authenticated',
        'authenticated',
        lower(seed_user.email),
        crypt(seed_user.password, gen_salt('bf')),
        now(),
        jsonb_build_object('provider', 'email', 'providers', jsonb_build_array('email')),
        jsonb_build_object(
          'full_name', trim(seed_user.first_name || ' ' || seed_user.surname),
          'role_requested', seed_user.role_key
        ),
        now(),
        now(),
        '',
        '',
        '',
        ''
      );
    else
      update auth.users
      set
        email = lower(seed_user.email),
        encrypted_password = crypt(seed_user.password, gen_salt('bf')),
        email_confirmed_at = coalesce(email_confirmed_at, now()),
        aud = 'authenticated',
        role = 'authenticated',
        raw_app_meta_data = jsonb_build_object('provider', 'email', 'providers', jsonb_build_array('email')),
        raw_user_meta_data = coalesce(raw_user_meta_data, '{}'::jsonb) || jsonb_build_object(
          'full_name', trim(seed_user.first_name || ' ' || seed_user.surname),
          'role_requested', seed_user.role_key
        ),
        updated_at = now()
      where id = resolved_user_id;
    end if;

    insert into public.user_accounts (
      id,
      email,
      username,
      mobile_no,
      account_status,
      email_verified_at,
      must_change_password
    )
    values (
      resolved_user_id,
      lower(seed_user.email),
      split_part(lower(seed_user.email), '@', 1),
      '09170000000',
      'active',
      now(),
      true
    )
    on conflict (id) do update
    set
      email = excluded.email,
      username = excluded.username,
      mobile_no = excluded.mobile_no,
      account_status = excluded.account_status,
      email_verified_at = coalesce(public.user_accounts.email_verified_at, excluded.email_verified_at),
      must_change_password = excluded.must_change_password,
      updated_at = now();

    select r.id
    into resolved_role_id
    from public.roles r
    where r.role_key = seed_user.role_key
    limit 1;

    if resolved_role_id is null then
      raise exception 'Seed aborted: role % is missing.', seed_user.role_key;
    end if;

    if not exists (
      select 1
      from public.user_role_assignments ura
      where ura.user_id = resolved_user_id
        and ura.role_id = resolved_role_id
        and coalesce(ura.office_id, '00000000-0000-0000-0000-000000000000'::uuid) = coalesce(scoped_office_id, '00000000-0000-0000-0000-000000000000'::uuid)
    ) then
      insert into public.user_role_assignments (
        user_id,
        role_id,
        office_id,
        is_primary,
        assigned_at
      )
      values (
        resolved_user_id,
        resolved_role_id,
        scoped_office_id,
        true,
        now()
      );
    end if;

    insert into public.people (
      user_id,
      surname,
      first_name,
      personal_email,
      mobile_no,
      citizenship,
      civil_status,
      agency_employee_no
    )
    values (
      resolved_user_id,
      seed_user.surname,
      seed_user.first_name,
      lower(seed_user.email),
      '09170000000',
      'Filipino',
      'Single',
      seed_user.agency_employee_no
    )
    on conflict (user_id) do update
    set
      surname = excluded.surname,
      first_name = excluded.first_name,
      personal_email = excluded.personal_email,
      mobile_no = excluded.mobile_no,
      citizenship = excluded.citizenship,
      civil_status = excluded.civil_status,
      agency_employee_no = excluded.agency_employee_no,
      updated_at = now();

    select p.id
    into resolved_person_id
    from public.people p
    where p.user_id = resolved_user_id
    limit 1;

    if resolved_person_id is null then
      raise exception 'Seed aborted: person record for % was not created.', seed_user.email;
    end if;

    if seed_user.with_employment then
      select jp.id
      into resolved_position_id
      from public.job_positions jp
      where jp.position_code = seed_user.position_code
      limit 1;

      if resolved_position_id is null then
        raise exception 'Seed aborted: position % is missing.', seed_user.position_code;
      end if;

      insert into public.employment_records (
        person_id,
        office_id,
        position_id,
        hire_date,
        employment_status,
        is_current
      )
      values (
        resolved_person_id,
        resolved_office_id,
        resolved_position_id,
        current_date,
        'active',
        true
      )
      on conflict (person_id) where is_current = true do update
      set
        office_id = excluded.office_id,
        position_id = excluded.position_id,
        hire_date = excluded.hire_date,
        employment_status = excluded.employment_status,
        is_current = true,
        separation_date = null,
        separation_reason = null,
        updated_at = now();

      if seed_user.scope_type is not null and not exists (
        select 1
        from public.user_office_scopes uos
        where uos.user_id = resolved_user_id
          and uos.office_id = resolved_office_id
          and uos.scope_type = seed_user.scope_type
      ) then
        insert into public.user_office_scopes (
          user_id,
          office_id,
          scope_type
        )
        values (
          resolved_user_id,
          resolved_office_id,
          seed_user.scope_type
        );
      end if;
    else
      insert into public.applicant_profiles (
        user_id,
        full_name,
        email,
        mobile_no,
        current_address,
        training_hours_completed
      )
      values (
        resolved_user_id,
        trim(seed_user.first_name || ' ' || seed_user.surname),
        lower(seed_user.email),
        '09170000000',
        'ATI Central Office',
        0
      )
      on conflict (user_id) do update
      set
        full_name = excluded.full_name,
        email = excluded.email,
        mobile_no = excluded.mobile_no,
        current_address = excluded.current_address,
        training_hours_completed = excluded.training_hours_completed,
        updated_at = now();
    end if;
  end loop;
end
$$;

commit;

-- Seeded credentials
-- admin@hris.local / P@ssw0rd!Admin123
-- staff@hris.local / P@ssw0rd!Staff123
-- employee@hris.local / P@ssw0rd!Employee123
-- applicant@hris.local / P@ssw0rd!Applicant123