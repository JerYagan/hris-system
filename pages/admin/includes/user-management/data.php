<?php

$usersResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_accounts?select=id,email,account_status,created_at,people(first_name,surname)&order=created_at.desc&limit=1000',
    $headers
);

$rolesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/roles?select=id,role_key,role_name&order=role_name.asc',
    $headers
);

$officesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name,office_code,is_active&is_active=eq.true&order=office_name.asc',
    $headers
);

$primaryRolesResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/user_role_assignments?select=user_id,role:roles(role_name,role_key)&is_primary=eq.true&expires_at=is.null&limit=2000',
    $headers
);

$users = isSuccessful($usersResponse) ? $usersResponse['data'] : [];
$roles = isSuccessful($rolesResponse) ? $rolesResponse['data'] : [];
$offices = isSuccessful($officesResponse) ? $officesResponse['data'] : [];
$primaryRoles = isSuccessful($primaryRolesResponse) ? $primaryRolesResponse['data'] : [];

$primaryRoleMap = [];
foreach ($primaryRoles as $assignment) {
    $userId = (string)($assignment['user_id'] ?? '');
    if ($userId === '' || isset($primaryRoleMap[$userId])) {
        continue;
    }

    $roleName = (string)($assignment['role']['role_name'] ?? $assignment['role']['role_key'] ?? '');
    $primaryRoleMap[$userId] = $roleName !== '' ? $roleName : 'Unassigned';
}
