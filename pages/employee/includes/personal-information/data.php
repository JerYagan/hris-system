<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;
$csrfToken = ensureCsrfToken();

$pdsEducationLevels = ['elementary', 'secondary', 'vocational', 'college', 'graduate'];
$civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'];
$bloodTypeOptions = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$placeOfBirthOptions = [];
$placeOfBirthOptionMap = [];
$cityMunicipalityOptions = [];
$provinceOptions = [];
$barangayOptions = [];
$barangayByCityLookup = [];
$zipLookupByCityBarangay = [];
$cityLabelByLookup = [];
$provinceByCityLookup = [];

$normalizeLookup = static function (string $value): string {
    return strtolower(trim((string)preg_replace('/\s+/', ' ', $value)));
};

$normalizeLookupVariants = static function (string $value, bool $isBarangay = false) use ($normalizeLookup): array {
    $seed = $normalizeLookup($value);
    if ($seed === '') {
        return [];
    }

    $variants = [];
    $queue = [$seed];

    while (!empty($queue)) {
        $current = array_shift($queue);
        if (!is_string($current) || $current === '' || isset($variants[$current])) {
            continue;
        }

        $variants[$current] = true;

        $withoutParen = $normalizeLookup((string)preg_replace('/\s*\(.*?\)\s*/', ' ', $current));
        if ($withoutParen !== '' && !isset($variants[$withoutParen])) {
            $queue[] = $withoutParen;
        }

        $withoutSuffix = $normalizeLookup((string)preg_replace('/\s*,\s*.*$/', '', $current));
        if ($withoutSuffix !== '' && !isset($variants[$withoutSuffix])) {
            $queue[] = $withoutSuffix;
        }

        if ($isBarangay) {
            $withoutBarangayPrefix = $normalizeLookup((string)preg_replace('/^(barangay|brgy\.?|brg\.?|bgy\.?)\s+/i', '', $current));
            if ($withoutBarangayPrefix !== '' && !isset($variants[$withoutBarangayPrefix])) {
                $queue[] = $withoutBarangayPrefix;
            }
        } else {
            $withoutCityPrefix = $normalizeLookup((string)preg_replace('/^(city|city of|municipality|municipality of|mun\.?|municipio de)\s+/i', '', $current));
            if ($withoutCityPrefix !== '' && !isset($variants[$withoutCityPrefix])) {
                $queue[] = $withoutCityPrefix;
            }

            $withoutCitySuffix = $normalizeLookup((string)preg_replace('/\s+city$/i', '', $current));
            if ($withoutCitySuffix !== '' && !isset($variants[$withoutCitySuffix])) {
                $queue[] = $withoutCitySuffix;
            }
        }
    }

    return array_keys($variants);
};

$resolveCanonicalMappedValue = static function (string $value, array $lookup, bool $isBarangay = false) use ($normalizeLookupVariants): ?string {
    $valueVariants = $normalizeLookupVariants($value, $isBarangay);
    if (empty($valueVariants)) {
        return null;
    }

    foreach ($valueVariants as $variant) {
        if (isset($lookup[$variant]) && is_string($lookup[$variant])) {
            return $lookup[$variant];
        }
    }

    foreach ($lookup as $lookupValue) {
        if (!is_string($lookupValue)) {
            continue;
        }

        foreach ($normalizeLookupVariants($lookupValue, $isBarangay) as $lookupVariant) {
            if (in_array($lookupVariant, $valueVariants, true)) {
                return $lookupValue;
            }
        }
    }

    return null;
};

$resolveCanonicalOption = static function (string $value, array $options, bool $isBarangay = false) use ($normalizeLookupVariants): ?string {
    $valueVariants = $normalizeLookupVariants($value, $isBarangay);
    if (empty($valueVariants)) {
        return null;
    }

    foreach ($options as $option) {
        $optionText = cleanText($option);
        if ($optionText === null) {
            continue;
        }

        foreach ($normalizeLookupVariants($optionText, $isBarangay) as $optionVariant) {
            if (in_array($optionVariant, $valueVariants, true)) {
                return $optionText;
            }
        }
    }

    return null;
};

$resolveZipByCityBarangay = static function (string $cityValue, string $barangayValue) use ($normalizeLookupVariants, $zipLookupByCityBarangay): ?string {
    $cityVariants = $normalizeLookupVariants($cityValue, false);
    $barangayVariants = $normalizeLookupVariants($barangayValue, true);

    foreach ($cityVariants as $cityVariant) {
        $barangayGroup = $zipLookupByCityBarangay[$cityVariant] ?? null;
        if (!is_array($barangayGroup)) {
            continue;
        }

        foreach ($barangayVariants as $barangayVariant) {
            $zipOptions = $barangayGroup[$barangayVariant] ?? null;
            if (is_array($zipOptions) && count($zipOptions) === 1) {
                $zipValue = cleanText($zipOptions[0] ?? null);
                if ($zipValue !== null) {
                    return $zipValue;
                }
            }
        }
    }

    return null;
};

$formatPersonalInfoTimestamp = static function (?string $value, string $format = 'M d, Y h:i A'): string {
    return function_exists('formatDateTimeForPhilippines')
        ? formatDateTimeForPhilippines($value, $format)
        : (($value !== null && $value !== '' && strtotime($value) !== false) ? date($format, strtotime($value)) : '-');
};

$excludePlaceholderLookupValue = static function (string $value): bool {
    return strtolower(trim($value)) === 'haugafia';
};

$assetRoot = dirname(__DIR__, 5) . '/assets';
$municipalitiesPath = $assetRoot . '/psgc/municipalities.json';
if (is_file($municipalitiesPath)) {
    $municipalitiesRaw = file_get_contents($municipalitiesPath);
    $municipalitiesData = is_string($municipalitiesRaw) ? json_decode($municipalitiesRaw, true) : null;
    if (is_array($municipalitiesData)) {
        $citySet = [];
        $provinceSet = [];
        foreach ($municipalitiesData as $municipalityRaw) {
            $municipality = (array)$municipalityRaw;
            $name = cleanText($municipality['name'] ?? null);
            $province = cleanText($municipality['province'] ?? null);

            if ($name !== null && $name !== '') {
                $cityKey = $normalizeLookup($name);
                $placeLabel = $province !== null && $province !== ''
                    ? $name . ', ' . $province
                    : $name;
                $placeOfBirthOptionMap[$normalizeLookup($placeLabel)] = $placeLabel;
                $placeOfBirthOptionMap[$cityKey] = $placeLabel;
                $citySet[$name] = true;
                $cityLabelByLookup[$cityKey] = $name;
            }

            if ($province !== null && $province !== '') {
                $provinceSet[$province] = true;
                if ($name !== null && $name !== '') {
                    $provinceByCityLookup[$normalizeLookup($name)] = $province;
                }
            }
        }

        $cityMunicipalityOptions = array_keys($citySet);
        $provinceOptions = array_keys($provinceSet);
        $placeOfBirthOptions = array_values(array_unique(array_values($placeOfBirthOptionMap)));
        sort($placeOfBirthOptions, SORT_NATURAL | SORT_FLAG_CASE);
    }
}

$zipCodesPath = $assetRoot . '/zip-codes.json';
if (is_file($zipCodesPath)) {
    $zipCodesRaw = file_get_contents($zipCodesPath);
    $zipCodesData = is_string($zipCodesRaw) ? json_decode($zipCodesRaw, true) : null;
    if (is_array($zipCodesData)) {
        $barangaySet = [];
        foreach ($zipCodesData as $cityName => $barangayGroupRaw) {
            $cityKey = $normalizeLookup((string)$cityName);
            if ($cityKey === '') {
                continue;
            }

            if (!isset($zipLookupByCityBarangay[$cityKey])) {
                $zipLookupByCityBarangay[$cityKey] = [];
            }
            foreach ((array)$barangayGroupRaw as $barangayName => $zipListRaw) {
                $barangayKey = $normalizeLookup((string)$barangayName);
                if ($barangayKey === '') {
                    continue;
                }

                $barangayLabel = trim((string)$barangayName);
                if ($barangayLabel !== '') {
                    $barangaySet[strtolower($barangayLabel)] = $barangayLabel;
                }

                $existingZips = $zipLookupByCityBarangay[$cityKey][$barangayKey] ?? [];
                $zips = is_array($existingZips) ? array_values($existingZips) : [];
                foreach ((array)$zipListRaw as $zipValue) {
                    $zipText = trim((string)$zipValue);
                    if ($zipText !== '' && !in_array($zipText, $zips, true)) {
                        $zips[] = $zipText;
                    }
                }

                if (!empty($zips)) {
                    $zipLookupByCityBarangay[$cityKey][$barangayKey] = $zips;
                }
            }
        }

        $barangayOptions = array_values($barangaySet);
        sort($barangayOptions, SORT_NATURAL | SORT_FLAG_CASE);
    }
}

$barangaysPath = $assetRoot . '/psgc/barangays.json';
if (is_file($barangaysPath)) {
    $barangaysRaw = file_get_contents($barangaysPath);
    $barangaysData = is_string($barangaysRaw) ? json_decode($barangaysRaw, true) : null;
    if (is_array($barangaysData)) {
        $barangaySet = [];
        foreach ($barangaysData as $barangayRaw) {
            $barangay = (array)$barangayRaw;
            $barangayName = cleanText($barangay['name'] ?? null);
            $cityName = cleanText($barangay['citymun'] ?? null);
            $zipCode = cleanText($barangay['zip_code'] ?? null);

            if ($barangayName === null || $barangayName === '') {
                continue;
            }

            $barangaySet[strtolower($barangayName)] = $barangayName;

            $cityKey = $normalizeLookup((string)$cityName);
            if ($cityKey === '') {
                continue;
            }

            if (!isset($barangayByCityLookup[$cityKey])) {
                $barangayByCityLookup[$cityKey] = [];
            }

            $barangayByCityLookup[$cityKey][strtolower($barangayName)] = $barangayName;

            if ($zipCode !== null && $zipCode !== '') {
                if (!isset($zipLookupByCityBarangay[$cityKey])) {
                    $zipLookupByCityBarangay[$cityKey] = [];
                }
                $zipLookupByCityBarangay[$cityKey][strtolower($barangayName)] = [$zipCode];
            }
        }

        foreach ($barangayByCityLookup as $cityKey => $cityBarangays) {
            $cityValues = array_values($cityBarangays);
            sort($cityValues, SORT_NATURAL | SORT_FLAG_CASE);
            $barangayByCityLookup[$cityKey] = $cityValues;
        }

        $barangayOptions = array_values($barangaySet);
        sort($barangayOptions, SORT_NATURAL | SORT_FLAG_CASE);
    }
}

$civilStatusOptions = array_values(array_filter($civilStatusOptions, static fn(string $value): bool => !$excludePlaceholderLookupValue($value)));
$bloodTypeOptions = array_values(array_filter($bloodTypeOptions, static fn(string $value): bool => !$excludePlaceholderLookupValue($value)));
$placeOfBirthOptions = array_values(array_filter($placeOfBirthOptions, static fn(string $value): bool => !$excludePlaceholderLookupValue($value)));
$cityMunicipalityOptions = array_values(array_filter($cityMunicipalityOptions, static fn(string $value): bool => !$excludePlaceholderLookupValue($value)));
$provinceOptions = array_values(array_filter($provinceOptions, static fn(string $value): bool => !$excludePlaceholderLookupValue($value)));
$barangayOptions = array_values(array_filter($barangayOptions, static fn(string $value): bool => !$excludePlaceholderLookupValue($value)));

$personal201Categories = [
    'violation',
    'memorandum receipt',
    'gsis instead sss',
    'copy of saln',
    'service record',
    'coe',
    'pds',
    'sss',
    'pagibig',
    'philhealth',
    'nbi',
    'medical',
    'drug test',
    'others',
];

$employeeProfile = [
    'person_id' => (string)($employeePersonId ?? ''),
    'first_name' => '',
    'last_name' => '',
    'middle_name' => '',
    'name_extension' => '',
    'sex_at_birth' => '',
    'height_m' => '',
    'weight_kg' => '',
    'blood_type' => '',
    'citizenship' => '',
    'dual_citizenship' => '0',
    'dual_citizenship_country' => '',
    'telephone_no' => '',
    'personal_email' => '',
    'mobile_no' => '',
    'date_of_birth' => '',
    'profile_photo_url' => '',
    'profile_photo_public_url' => '',
    'place_of_birth' => '',
    'civil_status' => '',
    'agency_employee_no' => '',
    'address_id' => '',
    'house_no' => '',
    'street' => '',
    'subdivision' => '',
    'barangay' => '',
    'city_municipality' => '',
    'province' => '',
    'zip_code' => '',
    'country' => 'Philippines',
    'permanent_address_id' => '',
    'permanent_house_no' => '',
    'permanent_street' => '',
    'permanent_subdivision' => '',
    'permanent_barangay' => '',
    'permanent_city_municipality' => '',
    'permanent_province' => '',
    'permanent_zip_code' => '',
    'permanent_country' => 'Philippines',
    'permanent_same_as_residential' => false,
    'umid_no' => '',
    'pagibig_no' => '',
    'philhealth_no' => '',
    'psn_no' => '',
    'tin_no' => '',
    'address_line' => '-',
    'employment_position_title' => (string)($employeePositionTitle ?? ''),
    'employment_office_name' => (string)($employeeOfficeName ?? ''),
    'employment_type' => '',
    'employment_status' => (string)($employeeEmploymentStatus ?? ''),
    'has_contractual_application' => false,
    'contractual_application_label' => '',
    'contractual_application_job_title' => '',
    'supervisor_name' => '',
];

$employeeSpouse = [
    'id' => '',
    'surname' => '',
    'first_name' => '',
    'middle_name' => '',
    'extension_name' => '',
    'occupation' => '',
    'employer_business_name' => '',
    'business_address' => '',
    'telephone_no' => '',
];
$employeeSpouses = [];

$employeeFather = [
    'id' => '',
    'surname' => '',
    'first_name' => '',
    'middle_name' => '',
    'extension_name' => '',
];

$employeeMother = [
    'id' => '',
    'surname' => '',
    'first_name' => '',
    'middle_name' => '',
    'extension_name' => '',
];

$employeeChildren = [];
$employeeEducationRows = [];
$employeeDocuments = [];
$employeePersonal201Files = [];
$employeeApprovedEvaluations = [];
$spouseRequestHistory = [];
$passwordChangeStatus = [
    'is_pending' => false,
    'expires_at' => '-',
    'email' => '',
];
$loginHistoryRows = [];
$loginEventOptions = [];
$loginDeviceOptions = [];
$loginSearchQuery = strtolower(trim((string)($_GET['login_search'] ?? '')));
$loginEventFilter = trim((string)($_GET['login_event'] ?? ''));
$loginDeviceFilter = trim((string)($_GET['login_device'] ?? ''));
$loginPage = max(1, (int)($_GET['login_page'] ?? 1));
$loginPerPage = 10;
$loginHistoryTotal = 0;
$loginTotalPages = 1;

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$personResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/people?select=id,first_name,surname,middle_name,name_extension,date_of_birth,place_of_birth,sex_at_birth,civil_status,height_m,weight_kg,blood_type,citizenship,dual_citizenship,dual_citizenship_country,telephone_no,mobile_no,personal_email,agency_employee_no,profile_photo_url'
    . '&id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=1',
    $headers
);

if (!isSuccessful($personResponse) || empty((array)($personResponse['data'] ?? []))) {
    $dataLoadError = 'Unable to load your profile information at the moment.';
    return;
}

$personRow = (array)$personResponse['data'][0];
$employeeProfile['first_name'] = (string)($personRow['first_name'] ?? '');
$employeeProfile['last_name'] = (string)($personRow['surname'] ?? '');
$employeeProfile['middle_name'] = (string)($personRow['middle_name'] ?? '');
$employeeProfile['name_extension'] = (string)($personRow['name_extension'] ?? '');
$employeeProfile['sex_at_birth'] = (string)($personRow['sex_at_birth'] ?? '');
$employeeProfile['height_m'] = (string)($personRow['height_m'] ?? '');
$employeeProfile['weight_kg'] = (string)($personRow['weight_kg'] ?? '');
$employeeProfile['blood_type'] = (string)($personRow['blood_type'] ?? '');
$employeeProfile['citizenship'] = (string)($personRow['citizenship'] ?? '');
$employeeProfile['dual_citizenship'] = (bool)($personRow['dual_citizenship'] ?? false) ? '1' : '0';
$employeeProfile['dual_citizenship_country'] = (string)($personRow['dual_citizenship_country'] ?? '');
$employeeProfile['telephone_no'] = (string)($personRow['telephone_no'] ?? '');
$employeeProfile['personal_email'] = (string)($personRow['personal_email'] ?? '');
$employeeProfile['mobile_no'] = (string)($personRow['mobile_no'] ?? '');
$employeeProfile['date_of_birth'] = (string)($personRow['date_of_birth'] ?? '');
$employeeProfile['profile_photo_url'] = (string)($personRow['profile_photo_url'] ?? '');
$employeeProfile['place_of_birth'] = (string)($personRow['place_of_birth'] ?? '');
$canonicalPlaceOfBirth = $resolveCanonicalMappedValue($employeeProfile['place_of_birth'], $placeOfBirthOptionMap, false);
if ($canonicalPlaceOfBirth !== null) {
    $employeeProfile['place_of_birth'] = $canonicalPlaceOfBirth;
}
$employeeProfile['civil_status'] = (string)($personRow['civil_status'] ?? '');
$employeeProfile['agency_employee_no'] = (string)($personRow['agency_employee_no'] ?? '');

$rawProfilePhotoPath = trim((string)$employeeProfile['profile_photo_url']);
if ($rawProfilePhotoPath !== '') {
    if (preg_match('#^https?://#i', $rawProfilePhotoPath) === 1 || str_starts_with($rawProfilePhotoPath, '/')) {
        $employeeProfile['profile_photo_public_url'] = $rawProfilePhotoPath;
    } else {
        $employeeProfile['profile_photo_public_url'] = '/hris-system/storage/document/' . ltrim($rawProfilePhotoPath, '/');
    }
}

$addressResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_addresses?select=id,address_type,house_no,street,subdivision,barangay,city_municipality,province,zip_code,country,is_primary'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=is_primary.desc,created_at.desc&limit=20',
    $headers
);

if (isSuccessful($addressResponse)) {
    foreach ((array)($addressResponse['data'] ?? []) as $addressRaw) {
        $addressRow = (array)$addressRaw;
        $addressType = strtolower((string)($addressRow['address_type'] ?? ''));

        if ($addressType === 'residential' || ($addressType === '' && $employeeProfile['address_id'] === '')) {
            $employeeProfile['address_id'] = (string)($addressRow['id'] ?? '');
            $employeeProfile['house_no'] = (string)($addressRow['house_no'] ?? '');
            $employeeProfile['street'] = (string)($addressRow['street'] ?? '');
            $employeeProfile['subdivision'] = (string)($addressRow['subdivision'] ?? '');
            $employeeProfile['barangay'] = (string)($addressRow['barangay'] ?? '');
            $employeeProfile['city_municipality'] = (string)($addressRow['city_municipality'] ?? '');
            $employeeProfile['province'] = (string)($addressRow['province'] ?? '');
            $employeeProfile['zip_code'] = (string)($addressRow['zip_code'] ?? '');
            $employeeProfile['country'] = (string)($addressRow['country'] ?? 'Philippines');
        }

        if ($addressType === 'permanent') {
            $employeeProfile['permanent_address_id'] = (string)($addressRow['id'] ?? '');
            $employeeProfile['permanent_house_no'] = (string)($addressRow['house_no'] ?? '');
            $employeeProfile['permanent_street'] = (string)($addressRow['street'] ?? '');
            $employeeProfile['permanent_subdivision'] = (string)($addressRow['subdivision'] ?? '');
            $employeeProfile['permanent_barangay'] = (string)($addressRow['barangay'] ?? '');
            $employeeProfile['permanent_city_municipality'] = (string)($addressRow['city_municipality'] ?? '');
            $employeeProfile['permanent_province'] = (string)($addressRow['province'] ?? '');
            $employeeProfile['permanent_zip_code'] = (string)($addressRow['zip_code'] ?? '');
            $employeeProfile['permanent_country'] = (string)($addressRow['country'] ?? 'Philippines');
        }
    }

    $normalizeEmployeeAddress = static function (array &$address) use ($normalizeLookupVariants, $resolveCanonicalMappedValue, $resolveCanonicalOption, $resolveZipByCityBarangay, $cityLabelByLookup, $provinceByCityLookup, $barangayByCityLookup): void {
        $canonicalCity = $resolveCanonicalMappedValue((string)($address['city_municipality'] ?? ''), $cityLabelByLookup, false);
        if ($canonicalCity !== null) {
            $address['city_municipality'] = $canonicalCity;
        }

        $cityVariants = $normalizeLookupVariants((string)($address['city_municipality'] ?? ''), false);
        foreach ($cityVariants as $cityVariant) {
            if (isset($provinceByCityLookup[$cityVariant])) {
                $address['province'] = $provinceByCityLookup[$cityVariant];
                break;
            }
        }

        $barangayOptionsForCity = [];
        foreach ($cityVariants as $cityVariant) {
            $cityBarangays = $barangayByCityLookup[$cityVariant] ?? null;
            if (is_array($cityBarangays)) {
                $barangayOptionsForCity = array_merge($barangayOptionsForCity, $cityBarangays);
            }
        }

        $canonicalBarangay = $resolveCanonicalOption((string)($address['barangay'] ?? ''), $barangayOptionsForCity, true);
        if ($canonicalBarangay !== null) {
            $address['barangay'] = $canonicalBarangay;
        }

        if (trim((string)($address['zip_code'] ?? '')) === '') {
            $resolvedZip = $resolveZipByCityBarangay((string)($address['city_municipality'] ?? ''), (string)($address['barangay'] ?? ''));
            if ($resolvedZip !== null) {
                $address['zip_code'] = $resolvedZip;
            }
        }
    };

    $normalizeEmployeeAddress($employeeProfile);

    $permanentAddress = [
        'barangay' => $employeeProfile['permanent_barangay'],
        'city_municipality' => $employeeProfile['permanent_city_municipality'],
        'province' => $employeeProfile['permanent_province'],
        'zip_code' => $employeeProfile['permanent_zip_code'],
    ];
    $normalizeEmployeeAddress($permanentAddress);
    $employeeProfile['permanent_barangay'] = $permanentAddress['barangay'];
    $employeeProfile['permanent_city_municipality'] = $permanentAddress['city_municipality'];
    $employeeProfile['permanent_province'] = $permanentAddress['province'];
    $employeeProfile['permanent_zip_code'] = $permanentAddress['zip_code'];

    $parts = [
        cleanText($employeeProfile['house_no']),
        cleanText($employeeProfile['street']),
        cleanText($employeeProfile['subdivision']),
        cleanText($employeeProfile['barangay']),
        cleanText($employeeProfile['city_municipality']),
        cleanText($employeeProfile['province']),
        cleanText($employeeProfile['zip_code']),
        cleanText($employeeProfile['country']),
    ];

    $addressParts = [];
    foreach ($parts as $part) {
        if ($part !== null) {
            $addressParts[] = $part;
        }
    }

    $employeeProfile['address_line'] = !empty($addressParts) ? implode(', ', $addressParts) : '-';

    $residentialComparable = [
        $employeeProfile['house_no'],
        $employeeProfile['street'],
        $employeeProfile['subdivision'],
        $employeeProfile['barangay'],
        $employeeProfile['city_municipality'],
        $employeeProfile['province'],
        $employeeProfile['zip_code'],
        $employeeProfile['country'],
    ];
    $permanentComparable = [
        $employeeProfile['permanent_house_no'],
        $employeeProfile['permanent_street'],
        $employeeProfile['permanent_subdivision'],
        $employeeProfile['permanent_barangay'],
        $employeeProfile['permanent_city_municipality'],
        $employeeProfile['permanent_province'],
        $employeeProfile['permanent_zip_code'],
        $employeeProfile['permanent_country'],
    ];

    $employeeProfile['permanent_same_as_residential'] = $residentialComparable === $permanentComparable;
}

$govIdResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_government_ids?select=id,id_type,id_value_encrypted'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=20',
    $headers
);

if (isSuccessful($govIdResponse)) {
    foreach ((array)($govIdResponse['data'] ?? []) as $govIdRaw) {
        $govId = (array)$govIdRaw;
        $type = strtolower((string)($govId['id_type'] ?? ''));
        $value = (string)($govId['id_value_encrypted'] ?? '');

        if ($type === 'umid') {
            $employeeProfile['umid_no'] = $value;
        } elseif ($type === 'pagibig') {
            $employeeProfile['pagibig_no'] = $value;
        } elseif ($type === 'philhealth') {
            $employeeProfile['philhealth_no'] = $value;
        } elseif ($type === 'psn') {
            $employeeProfile['psn_no'] = $value;
        } elseif ($type === 'tin') {
            $employeeProfile['tin_no'] = $value;
        }
    }
}

$spouseResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_family_spouses?select=id,surname,first_name,middle_name,extension_name,occupation,employer_business_name,business_address,telephone_no'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=sequence_no.asc,created_at.asc&limit=20',
    $headers
);

if (isSuccessful($spouseResponse) && !empty((array)($spouseResponse['data'] ?? []))) {
    foreach ((array)($spouseResponse['data'] ?? []) as $spouseRaw) {
        $spouseRow = (array)$spouseRaw;
        $mappedSpouse = [
            'id' => (string)($spouseRow['id'] ?? ''),
            'surname' => (string)($spouseRow['surname'] ?? ''),
            'first_name' => (string)($spouseRow['first_name'] ?? ''),
            'middle_name' => (string)($spouseRow['middle_name'] ?? ''),
            'extension_name' => (string)($spouseRow['extension_name'] ?? ''),
            'occupation' => (string)($spouseRow['occupation'] ?? ''),
            'employer_business_name' => (string)($spouseRow['employer_business_name'] ?? ''),
            'business_address' => (string)($spouseRow['business_address'] ?? ''),
            'telephone_no' => (string)($spouseRow['telephone_no'] ?? ''),
        ];

        $employeeSpouses[] = $mappedSpouse;
    }

    $employeeSpouse = $employeeSpouses[0];
}

$parentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_parents?select=id,parent_type,surname,first_name,middle_name,extension_name'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&limit=5',
    $headers
);

if (isSuccessful($parentsResponse)) {
    foreach ((array)($parentsResponse['data'] ?? []) as $parentRaw) {
        $parent = (array)$parentRaw;
        $mapped = [
            'id' => (string)($parent['id'] ?? ''),
            'surname' => (string)($parent['surname'] ?? ''),
            'first_name' => (string)($parent['first_name'] ?? ''),
            'middle_name' => (string)($parent['middle_name'] ?? ''),
            'extension_name' => (string)($parent['extension_name'] ?? ''),
        ];

        if ((string)($parent['parent_type'] ?? '') === 'father') {
            $employeeFather = $mapped;
        }
        if ((string)($parent['parent_type'] ?? '') === 'mother') {
            $employeeMother = $mapped;
        }
    }
}

$childrenResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_family_children?select=id,full_name,birth_date,sequence_no'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=sequence_no.asc,created_at.asc&limit=200',
    $headers
);

if (isSuccessful($childrenResponse)) {
    foreach ((array)($childrenResponse['data'] ?? []) as $childRaw) {
        $child = (array)$childRaw;
        $employeeChildren[] = [
            'id' => (string)($child['id'] ?? ''),
            'full_name' => (string)($child['full_name'] ?? ''),
            'birth_date' => (string)($child['birth_date'] ?? ''),
        ];
    }
}

$educationResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/person_educations?select=id,education_level,school_name,course_degree,period_from,period_to,highest_level_units,year_graduated,honors_received,sequence_no'
    . '&person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=education_level.asc,sequence_no.asc,created_at.asc&limit=400',
    $headers
);

if (isSuccessful($educationResponse)) {
    foreach ((array)($educationResponse['data'] ?? []) as $educationRaw) {
        $education = (array)$educationRaw;
        $employeeEducationRows[] = [
            'id' => (string)($education['id'] ?? ''),
            'education_level' => strtolower((string)($education['education_level'] ?? '')),
            'school_name' => (string)($education['school_name'] ?? ''),
            'course_degree' => (string)($education['course_degree'] ?? ''),
            'period_from' => (string)($education['period_from'] ?? ''),
            'period_to' => (string)($education['period_to'] ?? ''),
            'highest_level_units' => (string)($education['highest_level_units'] ?? ''),
            'year_graduated' => (string)($education['year_graduated'] ?? ''),
            'honors_received' => (string)($education['honors_received'] ?? ''),
            'sequence_no' => (int)($education['sequence_no'] ?? 1),
        ];
    }
}

if (empty($employeeEducationRows)) {
    foreach ($pdsEducationLevels as $level) {
        $employeeEducationRows[] = [
            'id' => '',
            'education_level' => $level,
            'school_name' => '',
            'course_degree' => '',
            'period_from' => '',
            'period_to' => '',
            'highest_level_units' => '',
            'year_graduated' => '',
            'honors_received' => '',
            'sequence_no' => 1,
        ];
    }
}

$employmentResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/employment_records?select=id,employment_status,employment_type,office:offices(office_name),position:job_positions(position_title,employment_classification),supervisor:people!employment_records_immediate_supervisor_person_id_fkey(first_name,surname)'
    . '&id=eq.' . rawurlencode((string)$employeeEmploymentId)
    . '&limit=1',
    $headers
);

if (isSuccessful($employmentResponse) && !empty((array)($employmentResponse['data'] ?? []))) {
    $employmentRow = (array)$employmentResponse['data'][0];
    $officeRow = (array)($employmentRow['office'] ?? []);
    $positionRow = (array)($employmentRow['position'] ?? []);
    $supervisorRow = (array)($employmentRow['supervisor'] ?? []);

    $employmentTypeRaw = strtolower(trim((string)($employmentRow['employment_type'] ?? '')));
    $positionClassification = strtolower(trim((string)($positionRow['employment_classification'] ?? '')));
    $employeeProfile['employment_type'] = $employmentTypeRaw !== ''
        ? $employmentTypeRaw
        : (in_array($positionClassification, ['regular', 'coterminous'], true) ? 'permanent' : ($positionClassification !== '' ? 'contractual' : ''));

    $employeeProfile['employment_status'] = (string)($employmentRow['employment_status'] ?? $employeeProfile['employment_status']);
    $employeeProfile['employment_office_name'] = (string)($officeRow['office_name'] ?? $employeeProfile['employment_office_name']);
    $employeeProfile['employment_position_title'] = (string)($positionRow['position_title'] ?? $employeeProfile['employment_position_title']);

    $supervisorFirstName = cleanText($supervisorRow['first_name'] ?? null);
    $supervisorLastName = cleanText($supervisorRow['surname'] ?? null);
    if ($supervisorFirstName !== null || $supervisorLastName !== null) {
        $employeeProfile['supervisor_name'] = trim(((string)$supervisorFirstName . ' ' . (string)$supervisorLastName));
    }
}

$applicantProfileResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/applicant_profiles?select=id'
    . '&user_id=eq.' . rawurlencode((string)$employeeUserId)
    . '&limit=1',
    $headers
);

if (isSuccessful($applicantProfileResponse) && !empty((array)($applicantProfileResponse['data'] ?? []))) {
    $applicantProfileId = cleanText($applicantProfileResponse['data'][0]['id'] ?? null) ?? '';
    if (isValidUuid($applicantProfileId)) {
        $applicationsResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/applications?select=job_posting_id,application_status,submitted_at'
            . '&applicant_profile_id=eq.' . rawurlencode($applicantProfileId)
            . '&order=submitted_at.desc&limit=100',
            $headers
        );

        if (isSuccessful($applicationsResponse)) {
            $applicationRows = (array)($applicationsResponse['data'] ?? []);
            $postingIds = [];
            foreach ($applicationRows as $applicationRow) {
                $postingId = cleanText($applicationRow['job_posting_id'] ?? null) ?? '';
                if (isValidUuid($postingId)) {
                    $postingIds[$postingId] = true;
                }
            }

            if (!empty($postingIds)) {
                $postingIdFilter = implode(',', array_map('rawurlencode', array_keys($postingIds)));
                $jobPostingsResponse = apiRequest(
                    'GET',
                    $supabaseUrl
                    . '/rest/v1/job_postings?select=id,title,position:job_positions(position_title,employment_classification)'
                    . '&id=in.(' . $postingIdFilter . ')&limit=100',
                    $headers
                );

                $jobPostingById = [];
                if (isSuccessful($jobPostingsResponse)) {
                    foreach ((array)($jobPostingsResponse['data'] ?? []) as $postingRow) {
                        $postingId = cleanText($postingRow['id'] ?? null) ?? '';
                        if (!isValidUuid($postingId)) {
                            continue;
                        }

                        $jobPostingById[$postingId] = (array)$postingRow;
                    }
                }

                foreach ($applicationRows as $applicationRow) {
                    $postingId = cleanText($applicationRow['job_posting_id'] ?? null) ?? '';
                    $status = strtolower(trim((string)(cleanText($applicationRow['application_status'] ?? null) ?? 'submitted')));
                    $posting = (array)($jobPostingById[$postingId] ?? []);
                    $position = is_array($posting['position'] ?? null) ? (array)$posting['position'] : [];
                    $classification = strtolower(trim((string)(cleanText($position['employment_classification'] ?? null) ?? '')));

                    if (in_array($status, ['withdrawn', 'rejected', 'hired'], true)) {
                        continue;
                    }

                    if (in_array($classification, ['regular', 'coterminous', ''], true)) {
                        continue;
                    }

                    $employeeProfile['has_contractual_application'] = true;
                    $employeeProfile['contractual_application_label'] = 'Applied to Contractual Job';
                    $employeeProfile['contractual_application_job_title'] = cleanText($posting['title'] ?? null)
                        ?? cleanText($position['position_title'] ?? null)
                        ?? 'Contractual Job';
                    break;
                }
            }
        }
    }
}

$documentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/documents?select=id,title,description,document_status,storage_path,current_version_no,created_at,updated_at,category:document_categories(category_name)'
    . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&document_status=neq.archived'
    . '&order=updated_at.desc&limit=25',
    $headers
);

if (isSuccessful($documentsResponse)) {
    foreach ((array)($documentsResponse['data'] ?? []) as $documentRaw) {
        $document = (array)$documentRaw;
        $category = (array)($document['category'] ?? []);
        $categoryName = (string)($category['category_name'] ?? 'Uncategorized');
        $status = strtolower((string)($document['document_status'] ?? 'draft'));
        $documentId = (string)($document['id'] ?? '');
        $is201Category = in_array($normalizeLookup($categoryName), $personal201Categories, true);

        $employeeDocuments[] = [
            'id' => $documentId,
            'title' => (string)($document['title'] ?? 'Untitled Document'),
            'description' => (string)($document['description'] ?? ''),
            'document_status' => $status,
            'storage_path' => (string)($document['storage_path'] ?? ''),
            'current_version_no' => (int)($document['current_version_no'] ?? 1),
            'created_at' => cleanText($document['created_at'] ?? null),
            'updated_at' => cleanText($document['updated_at'] ?? null),
            'category_name' => $categoryName,
            'is_201_category' => $is201Category,
            'download_allowed' => $documentId !== '' && $status !== 'draft',
            'download_url' => $documentId !== '' ? ('download-document.php?document_id=' . rawurlencode($documentId)) : '',
        ];
    }

    $employeePersonal201Files = array_values(array_filter(
        $employeeDocuments,
        static fn(array $documentRow): bool => (bool)($documentRow['is_201_category'] ?? false)
    ));
}

$spouseRequestResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/activity_logs?select=id,created_at,new_data'
    . '&actor_user_id=eq.' . rawurlencode((string)$employeeUserId)
    . '&entity_name=eq.person_family_spouses_request'
    . '&action_name=eq.submit_spouse_addition_request'
    . '&order=created_at.desc&limit=15',
    $headers
);

if (isSuccessful($spouseRequestResponse)) {
    $requestLogIdList = [];
    foreach ((array)($spouseRequestResponse['data'] ?? []) as $requestRaw) {
        $requestRow = (array)$requestRaw;
        $newData = is_array($requestRow['new_data'] ?? null) ? (array)$requestRow['new_data'] : [];
        $requestLogId = (string)($requestRow['id'] ?? '');
        if ($requestLogId !== '') {
            $requestLogIdList[] = $requestLogId;
        }

        $spouseRequestHistory[] = [
            'id' => $requestLogId,
            'created_at' => cleanText($requestRow['created_at'] ?? null),
            'status' => (string)($newData['status'] ?? 'pending_admin_approval'),
            'spouse_name' => trim((string)($newData['spouse_first_name'] ?? '') . ' ' . (string)($newData['spouse_surname'] ?? '')),
            'attachment_name' => (string)($newData['supporting_document_name'] ?? ''),
            'notes' => (string)($newData['request_notes'] ?? ''),
            'admin_remarks' => '',
        ];
    }

    if (!empty($requestLogIdList)) {
        $decisionResponse = apiRequest(
            'GET',
            $supabaseUrl
            . '/rest/v1/activity_logs?select=id,created_at,new_data,action_name'
            . '&module_name=eq.personal_information'
            . '&entity_name=eq.person_family_spouses_request'
            . '&entity_id=eq.' . rawurlencode((string)$employeePersonId)
            . '&action_name=in.(approve_spouse_addition_request,reject_spouse_addition_request)'
            . '&order=created_at.desc&limit=100',
            $headers
        );

        $decisionByRequestId = [];
        if (isSuccessful($decisionResponse)) {
            foreach ((array)($decisionResponse['data'] ?? []) as $decisionRaw) {
                $decisionRow = (array)$decisionRaw;
                $decisionData = is_array($decisionRow['new_data'] ?? null) ? (array)$decisionRow['new_data'] : [];
                $sourceRequestId = (string)($decisionData['request_log_id'] ?? '');
                if ($sourceRequestId === '' || isset($decisionByRequestId[$sourceRequestId])) {
                    continue;
                }

                $decisionByRequestId[$sourceRequestId] = [
                    'status' => (string)($decisionData['status'] ?? ''),
                    'remarks' => (string)($decisionData['remarks'] ?? ''),
                ];
            }
        }

        foreach ($spouseRequestHistory as $index => $requestRow) {
            $requestId = (string)($requestRow['id'] ?? '');
            if ($requestId === '' || !isset($decisionByRequestId[$requestId])) {
                continue;
            }

            $resolvedStatus = strtolower((string)($decisionByRequestId[$requestId]['status'] ?? ''));
            if ($resolvedStatus === 'approved' || $resolvedStatus === 'rejected') {
                $spouseRequestHistory[$index]['status'] = $resolvedStatus;
            }
            $spouseRequestHistory[$index]['admin_remarks'] = (string)($decisionByRequestId[$requestId]['remarks'] ?? '');
        }
    }
}

$personalInfoRequestRows = [];
$pendingPersonalInfoRequestCount = 0;
$nearestPersonalInfoDueLabel = '-';
$personalInfoRequestResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/activity_logs?select=id,created_at,new_data'
    . '&module_name=eq.personal_information'
    . '&entity_name=eq.people'
    . '&action_name=eq.submit_employee_profile_update_request'
    . '&entity_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=created_at.desc&limit=25',
    $headers
);

if (isSuccessful($personalInfoRequestResponse)) {
    $requestRows = (array)($personalInfoRequestResponse['data'] ?? []);
    $decisionResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=id,actor_user_id,created_at,new_data,action_name'
        . '&module_name=eq.personal_information'
        . '&entity_name=eq.people'
        . '&entity_id=eq.' . rawurlencode((string)$employeePersonId)
        . '&action_name=in.(approve_employee_profile_recommendation,reject_employee_profile_recommendation)'
        . '&order=created_at.desc&limit=100',
        $headers
    );

    $decisionByRequestId = [];
    $reviewerIds = [];
    if (isSuccessful($decisionResponse)) {
        foreach ((array)($decisionResponse['data'] ?? []) as $decisionRaw) {
            $decisionRow = (array)$decisionRaw;
            $decisionData = is_array($decisionRow['new_data'] ?? null) ? (array)$decisionRow['new_data'] : [];
            $requestId = (string)($decisionData['recommendation_log_id'] ?? '');
            if ($requestId === '' || isset($decisionByRequestId[$requestId])) {
                continue;
            }

            $reviewerId = (string)($decisionRow['actor_user_id'] ?? '');
            if ($reviewerId !== '') {
                $reviewerIds[$reviewerId] = true;
            }

            $decisionByRequestId[$requestId] = [
                'decision' => strtolower((string)($decisionData['decision'] ?? '')),
                'remarks' => (string)($decisionData['remarks'] ?? ''),
                'reviewed_at' => (string)($decisionRow['created_at'] ?? ''),
                'reviewer_id' => $reviewerId,
                'updated_live_record' => (bool)($decisionData['updated_live_record'] ?? false),
            ];
        }
    }

    $reviewerLabelById = [];
    if (!empty($reviewerIds)) {
        $reviewerResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/user_accounts?select=id,username,email&id=in.(' . implode(',', array_map('rawurlencode', array_keys($reviewerIds))) . ')&limit=100',
            $headers
        );

        if (isSuccessful($reviewerResponse)) {
            foreach ((array)($reviewerResponse['data'] ?? []) as $reviewerRaw) {
                $reviewerRow = (array)$reviewerRaw;
                $reviewerId = (string)($reviewerRow['id'] ?? '');
                if ($reviewerId === '') {
                    continue;
                }

                $reviewerLabelById[$reviewerId] = (string)(cleanText($reviewerRow['username'] ?? null) ?? cleanText($reviewerRow['email'] ?? null) ?? 'Admin');
            }
        }
    }

    $nowManila = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    $nearestDueDate = null;

    foreach ($requestRows as $requestRaw) {
        $requestRow = (array)$requestRaw;
        $requestId = (string)($requestRow['id'] ?? '');
        $newData = is_array($requestRow['new_data'] ?? null) ? (array)$requestRow['new_data'] : [];
        $requestDueAt = (string)($newData['request_due_at'] ?? '');
        $decisionRow = $decisionByRequestId[$requestId] ?? null;
        $status = 'pending_admin_review';
        if (is_array($decisionRow)) {
            $status = (string)($decisionRow['decision'] ?? $status);
        }

        $summaryParts = [];
        $recommendedProfile = is_array($newData['recommended_profile'] ?? null) ? (array)$newData['recommended_profile'] : [];
        $recommendedAddresses = is_array($newData['recommended_addresses'] ?? null) ? (array)$newData['recommended_addresses'] : [];
        $recommendedGovernmentIds = is_array($newData['recommended_government_ids'] ?? null) ? (array)$newData['recommended_government_ids'] : [];
        $recommendedFamily = is_array($newData['recommended_family'] ?? null) ? (array)$newData['recommended_family'] : [];
        $recommendedEducation = is_array($newData['recommended_educational_backgrounds'] ?? null) ? (array)$newData['recommended_educational_backgrounds'] : [];

        if (!empty($recommendedProfile)) {
            $summaryParts[] = count($recommendedProfile) . ' profile field(s)';
        }
        $addressDetailCount = 0;
        foreach ($recommendedAddresses as $addressRow) {
            if (!is_array($addressRow)) {
                continue;
            }
            foreach ($addressRow as $value) {
                if (trim((string)$value) !== '') {
                    $addressDetailCount++;
                }
            }
        }
        if ($addressDetailCount > 0) {
            $summaryParts[] = $addressDetailCount . ' address detail(s)';
        }
        $governmentDetailCount = 0;
        foreach ($recommendedGovernmentIds as $value) {
            if (trim((string)$value) !== '') {
                $governmentDetailCount++;
            }
        }
        if ($governmentDetailCount > 0) {
            $summaryParts[] = $governmentDetailCount . ' government ID detail(s)';
        }
        if (!empty($recommendedFamily)) {
            $summaryParts[] = 'family information';
        }
        if (!empty($recommendedEducation)) {
            $summaryParts[] = count($recommendedEducation) . ' education row(s)';
        }

        $deadlineStatusLabel = 'Pending';
        $deadlineStatusClass = 'bg-amber-100 text-amber-800';
        if ($status === 'approve' || $status === 'approved') {
            $deadlineStatusLabel = 'Approved';
            $deadlineStatusClass = 'bg-emerald-100 text-emerald-800';
        } elseif ($status === 'reject' || $status === 'rejected') {
            $deadlineStatusLabel = 'Rejected';
            $deadlineStatusClass = 'bg-rose-100 text-rose-800';
        } elseif ($requestDueAt !== '' && strtotime($requestDueAt) !== false) {
            $dueAtDate = new DateTimeImmutable($requestDueAt);
            if ($dueAtDate < $nowManila) {
                $deadlineStatusLabel = 'Overdue';
                $deadlineStatusClass = 'bg-rose-100 text-rose-800';
            } elseif ($dueAtDate <= $nowManila->modify('+2 days')) {
                $deadlineStatusLabel = 'Reminder Window';
                $deadlineStatusClass = 'bg-orange-100 text-orange-800';
            }

            if ($status === 'pending_admin_review' && ($nearestDueDate === null || $dueAtDate < $nearestDueDate)) {
                $nearestDueDate = $dueAtDate;
            }
        }

        if ($status === 'pending_admin_review') {
            $pendingPersonalInfoRequestCount++;
        }

        $reviewerLabel = '';
        if (is_array($decisionRow)) {
            $reviewerLabel = (string)($reviewerLabelById[(string)($decisionRow['reviewer_id'] ?? '')] ?? 'Admin');
        }

        $personalInfoRequestRows[] = [
            'request_id' => $requestId,
            'submitted_at_label' => $formatPersonalInfoTimestamp((string)($requestRow['created_at'] ?? '')),
            'due_at_label' => $requestDueAt !== '' ? $formatPersonalInfoTimestamp($requestDueAt) . ' PST' : '-',
            'status_label' => $deadlineStatusLabel,
            'status_class' => $deadlineStatusClass,
            'summary' => !empty($summaryParts) ? implode(', ', $summaryParts) : 'Personal information update request',
            'reviewed_at_label' => is_array($decisionRow) ? $formatPersonalInfoTimestamp((string)($decisionRow['reviewed_at'] ?? '')) : '-',
            'reviewed_by' => $reviewerLabel !== '' ? $reviewerLabel : '-',
            'review_remarks' => is_array($decisionRow) ? (string)($decisionRow['remarks'] ?? '') : '',
            'updated_live_record' => is_array($decisionRow) ? (bool)($decisionRow['updated_live_record'] ?? false) : false,
        ];
    }

    if ($nearestDueDate instanceof DateTimeImmutable) {
        $nearestPersonalInfoDueLabel = $formatPersonalInfoTimestamp($nearestDueDate->format(DATE_ATOM)) . ' PST';
    }
}

$passwordChangePending = (array)($_SESSION['employee_profile_password_change'] ?? []);
$pendingExpiresAt = (int)($passwordChangePending['expires_at'] ?? 0);
if ($pendingExpiresAt > time()) {
    $passwordChangeStatus['is_pending'] = true;
    $passwordChangeStatus['expires_at'] = formatUnixTimestampForPhilippines($pendingExpiresAt, 'M d, Y h:i A') . ' PST';
    $passwordChangeStatus['email'] = (string)($passwordChangePending['email'] ?? '');
}

$approvedEvaluationsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/performance_evaluations?select=id,final_rating,remarks,status,updated_at,cycle:cycle_id(cycle_name,period_start,period_end),evaluator:evaluator_user_id(email)'
    . '&employee_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&status=eq.approved'
    . '&order=updated_at.desc&limit=100',
    $headers
);

if (isSuccessful($approvedEvaluationsResponse)) {
    foreach ((array)($approvedEvaluationsResponse['data'] ?? []) as $evaluationRaw) {
        $evaluation = (array)$evaluationRaw;
        $cycle = is_array($evaluation['cycle'] ?? null) ? (array)$evaluation['cycle'] : [];
        $evaluator = is_array($evaluation['evaluator'] ?? null) ? (array)$evaluation['evaluator'] : [];

        $employeeApprovedEvaluations[] = [
            'id' => (string)($evaluation['id'] ?? ''),
            'cycle_name' => (string)(cleanText($cycle['cycle_name'] ?? null) ?? 'Evaluation Cycle'),
            'period_start' => cleanText($cycle['period_start'] ?? null),
            'period_end' => cleanText($cycle['period_end'] ?? null),
            'final_rating' => is_numeric($evaluation['final_rating'] ?? null) ? number_format((float)$evaluation['final_rating'], 2) . ' / 5.00' : '-',
            'remarks' => (string)(cleanText($evaluation['remarks'] ?? null) ?? '-'),
            'approved_at' => cleanText($evaluation['updated_at'] ?? null),
            'evaluator_email' => (string)(cleanText($evaluator['email'] ?? null) ?? '-'),
        ];
    }
}

$loginHistoryResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/login_audit_logs?select=id,event_type,auth_provider,ip_address,user_agent,created_at&user_id=eq.' . rawurlencode((string)$employeeUserId) . '&order=created_at.desc&limit=500',
    $headers
);

$loginHistoryRowsRaw = isSuccessful($loginHistoryResponse) ? (array)($loginHistoryResponse['data'] ?? []) : [];

$resolveDeviceLabel = static function (string $userAgent): string {
    $agent = strtolower(trim($userAgent));
    if ($agent === '' || $agent === '-') {
        return 'Unknown Device';
    }

    if (str_contains($agent, 'bot') || str_contains($agent, 'spider') || str_contains($agent, 'crawler')) {
        return 'Bot / Script';
    }

    if (str_contains($agent, 'ipad') || str_contains($agent, 'tablet')) {
        return 'Tablet';
    }

    if (str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone')) {
        return 'Mobile';
    }

    return 'Desktop';
};

foreach ($loginHistoryRowsRaw as $entry) {
    $eventType = (string)($entry['event_type'] ?? 'unknown');
    $eventLabel = ucwords(str_replace('_', ' ', $eventType));
    $createdAt = (string)($entry['created_at'] ?? '');
    $userAgent = (string)($entry['user_agent'] ?? '-');
    $deviceLabel = $resolveDeviceLabel($userAgent);

    if ($eventLabel !== '') {
        $loginEventOptions[$eventLabel] = true;
    }
    $loginDeviceOptions[$deviceLabel] = true;

    $loginHistoryRows[] = [
        'event_label' => $eventLabel,
        'auth_provider' => (string)($entry['auth_provider'] ?? 'password'),
        'ip_address' => (string)($entry['ip_address'] ?? 'unknown'),
        'user_agent' => $userAgent,
        'device_label' => $deviceLabel,
        'created_at' => $createdAt !== '' ? $formatPersonalInfoTimestamp($createdAt) . ' PST' : '-',
        'search_text' => strtolower(trim($eventLabel . ' ' . ((string)($entry['auth_provider'] ?? '')) . ' ' . ((string)($entry['ip_address'] ?? '')) . ' ' . $userAgent . ' ' . $deviceLabel)),
    ];
}

$loginHistoryRowsFiltered = array_values(array_filter(
    $loginHistoryRows,
    static function (array $row) use ($loginSearchQuery, $loginEventFilter, $loginDeviceFilter): bool {
        if ($loginEventFilter !== '' && (string)($row['event_label'] ?? '') !== $loginEventFilter) {
            return false;
        }

        if ($loginDeviceFilter !== '' && (string)($row['device_label'] ?? '') !== $loginDeviceFilter) {
            return false;
        }

        if ($loginSearchQuery !== '' && !str_contains((string)($row['search_text'] ?? ''), $loginSearchQuery)) {
            return false;
        }

        return true;
    }
));

$loginHistoryTotal = count($loginHistoryRowsFiltered);
$loginTotalPages = max(1, (int)ceil($loginHistoryTotal / $loginPerPage));
$loginPage = min($loginPage, $loginTotalPages);
$loginOffset = ($loginPage - 1) * $loginPerPage;
$loginHistoryRows = array_slice($loginHistoryRowsFiltered, $loginOffset, $loginPerPage);
$loginEventOptions = array_keys($loginEventOptions);
sort($loginEventOptions);
$loginDeviceOptions = array_keys($loginDeviceOptions);
sort($loginDeviceOptions);