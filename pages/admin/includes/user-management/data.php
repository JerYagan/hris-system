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

$officesDirectoryResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/offices?select=id,office_name,office_code,is_active&order=office_name.asc&limit=2000',
    $headers
);

$positionsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/job_positions?select=id,position_title,is_active&order=position_title.asc&limit=2000',
    $headers
);

$organizationsResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/organizations?select=id,name,code,is_active&is_active=eq.true&order=name.asc&limit=200',
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
$officesDirectory = isSuccessful($officesDirectoryResponse) ? $officesDirectoryResponse['data'] : [];
$positions = isSuccessful($positionsResponse) ? $positionsResponse['data'] : [];
$organizations = isSuccessful($organizationsResponse) ? $organizationsResponse['data'] : [];
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
