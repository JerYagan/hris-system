-- Sample employee seed with an active employment record.
-- Run this after the base schema, and preferably after the minimal baseline seed.
-- Re-runnable: existing records for the same sample email/user are updated instead of duplicated.

begin;

create extension if not exists pgcrypto;

do $$
declare
  sample_email constant citext := 'employee.sample@da-ati.local';
  sample_password constant text := 'Employee123!';
  sample_first_name constant text := 'Elena';
  sample_surname constant text := 'Marquez';
  sample_employee_no constant text := 'DA-EMP-1001';
  sample_office_code constant text := 'DA-ATI-CENTRAL';
  sample_position_code constant text := 'EMP-SAMPLE';
  sample_position_title constant text := 'Administrative Officer I';

  resolved_org_id uuid;
  resolved_office_id uuid;
  resolved_role_id uuid;
  resolved_position_id uuid;
  resolved_user_id uuid;
  resolved_person_id uuid;
  leave_type_sl_id uuid;
  leave_type_vl_id uuid;
  leave_type_cto_id uuid;
begin
  insert into public.organizations (
    code,
    name,
    is_active
  )
  values (
    'DA-ATI',
    'Department of Agriculture - Agricultural Training Institute',
    true
  )
  on conflict (code) do update
  set
    name = excluded.name,
    is_active = excluded.is_active,
    updated_at = now();

  select id
  into resolved_org_id
  from public.organizations
  where code = 'DA-ATI'
  limit 1;

  if resolved_org_id is null then
    raise exception 'Sample seed aborted: organization DA-ATI could not be resolved.';
  end if;

  insert into public.offices (
    organization_id,
    office_code,
    office_name,
    office_type,
    is_active
  )
  values (
    resolved_org_id,
    sample_office_code,
    'ATI Central Office',
    'central',
    true
  )
  on conflict (office_code) do update
  set
    organization_id = excluded.organization_id,
    office_name = excluded.office_name,
    office_type = excluded.office_type,
    is_active = excluded.is_active,
    updated_at = now();

  select id
  into resolved_office_id
  from public.offices
  where office_code = sample_office_code
  limit 1;

  if resolved_office_id is null then
    raise exception 'Sample seed aborted: office % could not be resolved.', sample_office_code;
  end if;

  insert into public.roles (
    role_key,
    role_name,
    description,
    is_system
  )
  values (
    'employee',
    'Employee',
    'Regular employee role.',
    true
  )
  on conflict (role_key) do update
  set
    role_name = excluded.role_name,
    description = excluded.description,
    is_system = excluded.is_system,
    updated_at = now();

  select id
  into resolved_role_id
  from public.roles
  where role_key = 'employee'
  limit 1;

  if resolved_role_id is null then
    raise exception 'Sample seed aborted: employee role could not be resolved.';
  end if;

  insert into public.job_positions (
    position_code,
    position_title,
    salary_grade,
    employment_classification,
    is_supervisory,
    is_active
  )
  values (
    sample_position_code,
    sample_position_title,
    'SG-10',
    'regular',
    false,
    true
  )
  on conflict (position_code) do update
  set
    position_title = excluded.position_title,
    salary_grade = excluded.salary_grade,
    employment_classification = excluded.employment_classification,
    is_supervisory = excluded.is_supervisory,
    is_active = excluded.is_active,
    updated_at = now();

  select id
  into resolved_position_id
  from public.job_positions
  where position_code = sample_position_code
  limit 1;

  if resolved_position_id is null then
    raise exception 'Sample seed aborted: position % could not be resolved.', sample_position_code;
  end if;

  select id
  into resolved_user_id
  from auth.users
  where lower(email) = lower(sample_email)
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
      lower(sample_email),
      crypt(sample_password, gen_salt('bf')),
      now(),
      jsonb_build_object('provider', 'email', 'providers', jsonb_build_array('email')),
      jsonb_build_object(
        'full_name', trim(sample_first_name || ' ' || sample_surname),
        'role_requested', 'employee'
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
      email = lower(sample_email),
      encrypted_password = crypt(sample_password, gen_salt('bf')),
      email_confirmed_at = coalesce(email_confirmed_at, now()),
      aud = 'authenticated',
      role = 'authenticated',
      raw_app_meta_data = jsonb_build_object('provider', 'email', 'providers', jsonb_build_array('email')),
      raw_user_meta_data = coalesce(raw_user_meta_data, '{}'::jsonb) || jsonb_build_object(
        'full_name', trim(sample_first_name || ' ' || sample_surname),
        'role_requested', 'employee'
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
    lower(sample_email),
    'employee.sample',
    '09171234567',
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

  insert into public.people (
    user_id,
    surname,
    first_name,
    middle_name,
    date_of_birth,
    sex_at_birth,
    civil_status,
    citizenship,
    mobile_no,
    personal_email,
    agency_employee_no
  )
  values (
    resolved_user_id,
    sample_surname,
    sample_first_name,
    'Santos',
    date '1994-05-16',
    'female',
    'Single',
    'Filipino',
    '09171234567',
    lower(sample_email),
    sample_employee_no
  )
  on conflict (user_id) do update
  set
    surname = excluded.surname,
    first_name = excluded.first_name,
    middle_name = excluded.middle_name,
    date_of_birth = excluded.date_of_birth,
    sex_at_birth = excluded.sex_at_birth,
    civil_status = excluded.civil_status,
    citizenship = excluded.citizenship,
    mobile_no = excluded.mobile_no,
    personal_email = excluded.personal_email,
    agency_employee_no = excluded.agency_employee_no,
    updated_at = now();

  select id
  into resolved_person_id
  from public.people
  where user_id = resolved_user_id
  limit 1;

  if resolved_person_id is null then
    raise exception 'Sample seed aborted: person record for % could not be resolved.', sample_email;
  end if;

  update public.user_role_assignments
  set is_primary = false
  where user_id = resolved_user_id
    and is_primary = true;

  insert into public.user_role_assignments (
    user_id,
    role_id,
    office_id,
    assigned_at,
    is_primary
  )
  values (
    resolved_user_id,
    resolved_role_id,
    resolved_office_id,
    now(),
    true
  )
  on conflict (user_id, role_id, coalesce(office_id, '00000000-0000-0000-0000-000000000000'::uuid)) do update
  set
    assigned_at = excluded.assigned_at,
    is_primary = true,
    expires_at = null;

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
    current_date - 120,
    'active',
    true
  )
  on conflict (person_id) where is_current = true do update
  set
    office_id = excluded.office_id,
    position_id = excluded.position_id,
    hire_date = excluded.hire_date,
    employment_status = excluded.employment_status,
    separation_date = null,
    separation_reason = null,
    is_current = true,
    updated_at = now();

  insert into public.user_office_scopes (
    user_id,
    office_id,
    scope_type
  )
  values (
    resolved_user_id,
    resolved_office_id,
    'self'
  )
  on conflict (user_id, office_id, scope_type) do nothing;

  insert into public.leave_types (
    leave_code,
    leave_name,
    default_annual_credits,
    requires_attachment,
    is_active
  )
  values
    ('SL', 'Sick Leave', 15, false, true),
    ('VL', 'Vacation Leave', 15, false, true),
    ('CTO', 'Compensatory Time Off', 0, false, true)
  on conflict (leave_code) do update
  set
    leave_name = excluded.leave_name,
    default_annual_credits = excluded.default_annual_credits,
    requires_attachment = excluded.requires_attachment,
    is_active = excluded.is_active;

  select id into leave_type_sl_id from public.leave_types where upper(leave_code) = 'SL' limit 1;
  select id into leave_type_vl_id from public.leave_types where upper(leave_code) = 'VL' limit 1;
  select id into leave_type_cto_id from public.leave_types where upper(leave_code) = 'CTO' limit 1;

  if leave_type_sl_id is not null then
    insert into public.leave_balances (
      person_id,
      leave_type_id,
      year,
      earned_credits,
      used_credits,
      remaining_credits,
      updated_at
    )
    values (
      resolved_person_id,
      leave_type_sl_id,
      extract(year from current_date)::int,
      15,
      2,
      13,
      now()
    )
    on conflict (person_id, leave_type_id, year) do update
    set
      earned_credits = excluded.earned_credits,
      used_credits = excluded.used_credits,
      remaining_credits = excluded.remaining_credits,
      updated_at = now();
  end if;

  if leave_type_vl_id is not null then
    insert into public.leave_balances (
      person_id,
      leave_type_id,
      year,
      earned_credits,
      used_credits,
      remaining_credits,
      updated_at
    )
    values (
      resolved_person_id,
      leave_type_vl_id,
      extract(year from current_date)::int,
      15,
      4,
      11,
      now()
    )
    on conflict (person_id, leave_type_id, year) do update
    set
      earned_credits = excluded.earned_credits,
      used_credits = excluded.used_credits,
      remaining_credits = excluded.remaining_credits,
      updated_at = now();
  end if;

  if leave_type_cto_id is not null then
    insert into public.leave_balances (
      person_id,
      leave_type_id,
      year,
      earned_credits,
      used_credits,
      remaining_credits,
      updated_at
    )
    values (
      resolved_person_id,
      leave_type_cto_id,
      extract(year from current_date)::int,
      8,
      1,
      7,
      now()
    )
    on conflict (person_id, leave_type_id, year) do update
    set
      earned_credits = excluded.earned_credits,
      used_credits = excluded.used_credits,
      remaining_credits = excluded.remaining_credits,
      updated_at = now();
  end if;

  insert into public.attendance_logs (
    person_id,
    attendance_date,
    time_in,
    time_out,
    hours_worked,
    undertime_hours,
    late_minutes,
    attendance_status,
    source,
    recorded_by
  )
  values (
    resolved_person_id,
    current_date,
    (current_date::text || ' 08:05:00+08')::timestamptz,
    null,
    null,
    null,
    5,
    'late',
    'manual',
    resolved_user_id
  )
  on conflict (person_id, attendance_date) do update
  set
    time_in = excluded.time_in,
    time_out = excluded.time_out,
    hours_worked = excluded.hours_worked,
    undertime_hours = excluded.undertime_hours,
    late_minutes = excluded.late_minutes,
    attendance_status = excluded.attendance_status,
    source = excluded.source,
    recorded_by = excluded.recorded_by;

  if not exists (
    select 1
    from public.notifications
    where recipient_user_id = resolved_user_id
      and title = 'Welcome to DA-ATI HRIS'
  ) then
    insert into public.notifications (
      recipient_user_id,
      category,
      title,
      body,
      link_url,
      is_read
    )
    values (
      resolved_user_id,
      'general',
      'Welcome to DA-ATI HRIS',
      'Your employee account is active and linked to a current employment record.',
      'dashboard.php',
      false
    );
  end if;
end
$$;

commit;

-- Sample employee credentials
-- Email: employee.sample@da-ati.local
-- Password: P@ssw0rd!Employee123