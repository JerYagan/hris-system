-- Insert / promote admin account for: skywarrior.sw@gmail.com
-- Run this in Supabase SQL Editor.

begin;

do $$
declare
  v_user_id uuid;
  v_admin_role_id uuid;
begin
  select u.id
  into v_user_id
  from auth.users u
  where lower(u.email) = lower('skywarrior.sw@gmail.com')
  limit 1;

  if v_user_id is null then
    raise exception 'No auth.users record found for email: %', 'skywarrior.sw@gmail.com';
  end if;

  select r.id
  into v_admin_role_id
  from public.roles r
  where r.role_key = 'admin'
  limit 1;

  if v_admin_role_id is null then
    raise exception 'Admin role not found in public.roles (role_key = admin).';
  end if;

  insert into public.user_accounts (
    id,
    email,
    account_status,
    email_verified_at,
    created_at,
    updated_at
  )
  values (
    v_user_id,
    'skywarrior.sw@gmail.com',
    'active',
    now(),
    now(),
    now()
  )
  on conflict (id)
  do update
  set
    email = excluded.email,
    account_status = 'active',
    updated_at = now();

  insert into public.user_role_assignments (
    user_id,
    role_id,
    office_id,
    assigned_at,
    is_primary,
    created_at
  )
  select
    v_user_id,
    v_admin_role_id,
    null,
    now(),
    true,
    now()
  where not exists (
    select 1
    from public.user_role_assignments ura
    where ura.user_id = v_user_id
      and ura.role_id = v_admin_role_id
      and ura.office_id is null
      and ura.expires_at is null
  );
end $$;

commit;
