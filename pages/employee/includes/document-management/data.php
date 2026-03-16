<?php

$state = $state ?? cleanText($_GET['state'] ?? null);
$message = $message ?? cleanText($_GET['message'] ?? null);
$dataLoadError = null;
$csrfToken = ensureCsrfToken();

$requiredDocumentCategories = [
    'Violation',
    'Memorandum Receipt',
    'GSIS',
    'Copy of SALN',
    'Service record',
    'COE',
    'PDS',
    'Resume/CV',
    'Transcript of Records',
    'Valid Government ID',
    'Eligibility (CSC/PRC)',
    'Application Letter',
    'SSS',
    'Pagibig',
    'Philhealth',
    'NBI',
    'Medical',
    'Drug Test',
    'Others',
];

$normalizeCategoryName = static function (string $value): string {
    return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
};

$toCategoryKey = static function (string $label): string {
    $key = strtolower((string)preg_replace('/[^a-z0-9]+/i', '_', $label));
    $key = trim($key, '_');
    return 'employee_201_' . $key;
};

$requiredCategoryAliasMap = [
    'violation' => 'Violation',
    'memorandum receipt' => 'Memorandum Receipt',
    'gsis instead sss' => 'GSIS',
    'gsis instead of sss' => 'GSIS',
    'gsis' => 'GSIS',
    'copy of saln' => 'Copy of SALN',
    'service record' => 'Service record',
    'coe' => 'COE',
    'certificate of employment' => 'COE',
    'pds' => 'PDS',
    'personal data sheet' => 'PDS',
    'resume/cv' => 'Resume/CV',
    'resume' => 'Resume/CV',
    'updated resume/cv' => 'Resume/CV',
    'curriculum vitae' => 'Resume/CV',
    'transcript of records' => 'Transcript of Records',
    'transcript / diploma' => 'Transcript of Records',
    'valid government id' => 'Valid Government ID',
    'government id' => 'Valid Government ID',
    'eligibility (csc/prc)' => 'Eligibility (CSC/PRC)',
    'csc/prc eligibility' => 'Eligibility (CSC/PRC)',
    'eligibility' => 'Eligibility (CSC/PRC)',
    'application letter' => 'Application Letter',
    'cover letter' => 'Application Letter',
    'sss' => 'SSS',
    'pagibig' => 'Pagibig',
    'pag-ibig' => 'Pagibig',
    'philhealth' => 'Philhealth',
    'nbi' => 'NBI',
    'medical' => 'Medical',
    'drug test' => 'Drug Test',
    'drugtest' => 'Drug Test',
    'others' => 'Others',
    'other' => 'Others',
];

$invalidCategoryNames = ['haugafia'];

$documentCategories = [];
$employeeDocuments = [];
$documentVersionsById = [];
$documentReviewsById = [];
$documentAuditTrailById = [];
$employeeDocumentRequests = [];

$hrTemplateLinkDefaults = [
    'hr_document_templates_drive_url' => 'https://drive.google.com/drive/folders/1Jq5BfHA1TROZlNCJ442yt0IRKYtG0e3O',
    'hr_document_template_coe_url' => '',
    'hr_document_template_service_record_url' => '',
    'hr_document_template_foreign_travel_url' => '',
    'hr_document_template_other_url' => '',
];

$hrTemplateLinkSettings = systemSettingLinksMap(
    $supabaseUrl,
    $headers,
    array_keys($hrTemplateLinkDefaults)
);

$sharedHrTemplateDriveUrl = (string)($hrTemplateLinkSettings['hr_document_templates_drive_url'] ?? $hrTemplateLinkDefaults['hr_document_templates_drive_url']);
$hrTemplateDownloadCards = [
    [
        'label' => 'Clearance',
        'description' => 'Approved clearance sheet from the HR template repository.',
        'url' => 'https://docs.google.com/spreadsheets/d/13byNqjj4xZLUr9v0UHOAmcq5WcOPpPR5/edit?usp=drive_link&ouid=110457973112188470700&rtpof=true&sd=true',
        'icon' => 'fact_check',
    ],
    [
        'label' => 'Work Permit for ATI CO',
        'description' => 'ATI-QF-HRMO-10 work permit form for central office use.',
        'url' => 'https://docs.google.com/document/d/158u-d-8KF0kw1wZV14yB6A01GVpiNraj/edit?usp=drive_link&ouid=110457973112188470700&rtpof=true&sd=true',
        'icon' => 'assignment',
    ],
    [
        'label' => 'Overtime Accomplishment Report',
        'description' => 'ATI-QF-HRMO-30 report of specific work accomplished during overtime.',
        'url' => 'https://docs.google.com/document/d/1jCUBlPpUIES7D5PYFMxZujEcVUkkz8kn/edit?usp=drive_link&ouid=110457973112188470700&rtpof=true&sd=true',
        'icon' => 'schedule_send',
    ],
    [
        'label' => 'Certificate of COC Earned',
        'description' => 'Certificate template for earned CTO or COC credits.',
        'url' => 'https://docs.google.com/document/d/1e8F3qIV044gvpRaCYSuQAaiseJR3viYv/edit?usp=drive_link&ouid=110457973112188470700&rtpof=true&sd=true',
        'icon' => 'workspace_premium',
    ],
    [
        'label' => 'COC Summary',
        'description' => 'Summary of earned and availed COCs for leave and timekeeping reference.',
        'url' => 'https://docs.google.com/document/d/192BWsZBJTnDmpP10VLdpru5dVreaPciF/edit?usp=drive_link&ouid=110457973112188470700&rtpof=true&sd=true',
        'icon' => 'summarize',
    ],
    [
        'label' => 'Certificate of Appearance',
        'description' => 'Updated certificate of appearance on the new letterhead.',
        'url' => 'https://docs.google.com/document/d/1sdAVklcLPXcWVPquH0i1pR0AEv6hLI2G/edit?usp=drive_link&ouid=110457973112188470700&rtpof=true&sd=true',
        'icon' => 'badge',
    ],
    [
        'label' => 'Travel Order Form',
        'description' => 'ATI-QF-HRMO-14 travel order form with QR-code-enabled layout.',
        'url' => 'https://docs.google.com/document/d/1E0odr5KFQPQTArEejwtEJGF3NtIb4gXj/edit?usp=drive_link&ouid=110457973112188470700&rtpof=true&sd=true',
        'icon' => 'flight_takeoff',
    ],
    [
        'label' => 'ATI-QF-HRMO-15 Form',
        'description' => 'Approved ATI-QF-HRMO-15 spreadsheet template from the shared repository.',
        'url' => 'https://docs.google.com/spreadsheets/d/1N3q9SeORJSuOeqR7X854_gaJvaghTMde/edit?usp=drive_link&ouid=110457973112188470700&rtpof=true&sd=true',
        'icon' => 'table_chart',
    ],
    [
        'label' => 'IPCR-DPCR Form',
        'description' => 'Blank IPCR-DPCR spreadsheet template for performance documentation.',
        'url' => 'https://docs.google.com/spreadsheets/d/1cXcsev9YwcJlrGzO0dHERc1-xP1I4Xed/edit?usp=drive_link&ouid=110457973112188470700&rtpof=true&sd=true',
        'icon' => 'bar_chart',
    ],
    [
        'label' => 'Request for JO',
        'description' => 'Blank request for JO form from the approved HR template source.',
        'url' => 'https://docs.google.com/document/d/139MRC9IxEQacdkl7huCC6O41CVHzchT3/edit?usp=drive_link&ouid=110457973112188470700&rtpof=true&sd=true',
        'icon' => 'request_quote',
    ],
    [
        'label' => 'JO Evaluation Form',
        'description' => 'QF-HRM-22 spreadsheet template for JO evaluation.',
        'url' => 'https://docs.google.com/spreadsheets/d/1vCiz7jTlXv8SxL-GxpS9OFgbrWOuk0IN/edit?usp=drive_link&ouid=110457973112188470700&rtpof=true&sd=true',
        'icon' => 'grading',
    ],
    [
        'label' => 'Other HR Templates',
        'description' => 'Open the shared Google Drive folder for the rest of the approved HR forms.',
        'url' => (string)($hrTemplateLinkSettings['hr_document_template_other_url'] ?: $sharedHrTemplateDriveUrl),
        'icon' => 'folder_open',
    ],
];

$buildAuditActorLabel = static function (array $actor): string {
    $username = trim((string)($actor['username'] ?? ''));
    if ($username !== '') {
        return $username;
    }

    $email = trim((string)($actor['email'] ?? ''));
    return $email !== '' ? $email : 'System';
};

$resolveAuditActionLabel = static function (string $actionName, array $payload): string {
    $normalizedAction = strtolower(trim($actionName));
    $statusContext = (array)($payload['status_context'] ?? []);
    $reviewStatus = strtolower(trim((string)($payload['review_status'] ?? ($statusContext['review_status'] ?? ''))));
    $statusTo = strtolower(trim((string)($payload['status_to'] ?? ($payload['status'] ?? ''))));

    return match ($normalizedAction) {
        'upload_document', 'upload_document_file' => 'Created',
        'upload_document_version' => 'Updated',
        'recommend_document' => $reviewStatus !== ''
            ? 'Reviewed: Recommend ' . ucwords(str_replace('_', ' ', $reviewStatus))
            : 'Reviewed',
        'review_document' => match ($reviewStatus !== '' ? $reviewStatus : $statusTo) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'needs_revision', 'need_revision' => 'Needs Revision',
            default => 'Reviewed',
        },
        'archive_document' => 'Archived',
        'restore_document' => 'Restored',
        default => ucwords(str_replace('_', ' ', $normalizedAction !== '' ? $normalizedAction : 'updated')),
    };
};

$resolveAuditNotes = static function (array $payload): string {
    $statusContext = (array)($payload['status_context'] ?? []);
    $candidates = [
        $payload['review_notes'] ?? null,
        $statusContext['review_notes'] ?? null,
        $payload['archive_reason'] ?? null,
        $payload['status_reason'] ?? null,
        $payload['reason'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        $value = trim((string)$candidate);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
};

$requestTypeLabels = [
    'coe' => 'COE',
    'service_record' => 'Service Record',
    'certificate_of_foreign_travel' => 'Certificate of Foreign Travel',
    'other_hr_document' => 'Other HR Document',
];

$requestPurposeLabels = [
    'employment' => 'Employment Requirement',
    'loan' => 'Loan / Financing',
    'visa_travel' => 'Visa / Travel',
    'scholarship' => 'Scholarship / Training',
    'compliance' => 'Compliance / Government Submission',
    'personal_copy' => 'Personal Copy',
    'other' => 'Other',
];

if (!(bool)($employeeContextResolved ?? false)) {
    $dataLoadError = (string)($employeeContextError ?? 'Employee context could not be resolved.');
    return;
}

$categoryResponse = apiRequest(
    'GET',
    $supabaseUrl . '/rest/v1/document_categories?select=id,category_name&order=category_name.asc&limit=200',
    $headers
);

if (isSuccessful($categoryResponse)) {
    $categoriesByRequiredLabel = [];

    $mapCategories = static function (array $rows) use (&$categoriesByRequiredLabel, $normalizeCategoryName, $requiredCategoryAliasMap): void {
        foreach ($rows as $categoryRaw) {
            $category = (array)$categoryRaw;
            $categoryId = (string)($category['id'] ?? '');
            $categoryName = (string)($category['category_name'] ?? '');
            if ($categoryId === '' || $categoryName === '') {
                continue;
            }

            $normalized = $normalizeCategoryName($categoryName);
            $mappedLabel = $requiredCategoryAliasMap[$normalized] ?? null;
            if ($mappedLabel === null || isset($categoriesByRequiredLabel[$mappedLabel])) {
                continue;
            }

            $categoriesByRequiredLabel[$mappedLabel] = [
                'id' => $categoryId,
                'category_name' => $mappedLabel,
            ];
        }
    };

    $mapCategories((array)($categoryResponse['data'] ?? []));

    $missingRequiredLabels = [];
    foreach ($requiredDocumentCategories as $requiredLabel) {
        if (!isset($categoriesByRequiredLabel[$requiredLabel])) {
            $missingRequiredLabels[] = $requiredLabel;
        }
    }

    if (!empty($missingRequiredLabels)) {
        foreach ($missingRequiredLabels as $missingLabel) {
            apiRequest(
                'POST',
                $supabaseUrl . '/rest/v1/document_categories',
                array_merge($headers, ['Prefer: return=representation']),
                [[
                    'category_key' => $toCategoryKey($missingLabel),
                    'category_name' => $missingLabel,
                    'requires_approval' => true,
                ]]
            );
        }

        $refreshCategoryResponse = apiRequest(
            'GET',
            $supabaseUrl . '/rest/v1/document_categories?select=id,category_name&order=category_name.asc&limit=300',
            $headers
        );

        if (isSuccessful($refreshCategoryResponse)) {
            $mapCategories((array)($refreshCategoryResponse['data'] ?? []));
        }
    }

    foreach ($requiredDocumentCategories as $requiredLabel) {
        if (!isset($categoriesByRequiredLabel[$requiredLabel])) {
            continue;
        }

        $documentCategories[] = $categoriesByRequiredLabel[$requiredLabel];
    }
} else {
    $dataLoadError = 'Unable to load document categories.';
}

$documentsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/documents?select=id,title,description,document_status,current_version_no,storage_bucket,storage_path,created_at,updated_at,category:document_categories(category_name)'
    . '&owner_person_id=eq.' . rawurlencode((string)$employeePersonId)
    . '&order=updated_at.desc&limit=200',
    $headers
);

if (!isSuccessful($documentsResponse)) {
    $docError = 'Unable to load your documents right now.';
    $dataLoadError = $dataLoadError ? ($dataLoadError . ' ' . $docError) : $docError;
    return;
}

$documentIds = [];
foreach ((array)($documentsResponse['data'] ?? []) as $documentRaw) {
    $document = (array)$documentRaw;
    $documentId = (string)($document['id'] ?? '');
    if ($documentId === '') {
        continue;
    }

    $documentIds[] = $documentId;
    $categoryRow = (array)($document['category'] ?? []);
    $rawCategoryName = (string)($categoryRow['category_name'] ?? '');
    $normalizedCategory = $normalizeCategoryName($rawCategoryName);
    $mappedCategory = $requiredCategoryAliasMap[$normalizedCategory] ?? null;
    $resolvedCategoryName = $mappedCategory;
    if ($resolvedCategoryName === null) {
        $resolvedCategoryName = in_array($normalizedCategory, $invalidCategoryNames, true) || $rawCategoryName === ''
            ? 'Others'
            : $rawCategoryName;
    }

    $employeeDocuments[] = [
        'id' => $documentId,
        'title' => (string)($document['title'] ?? 'Untitled Document'),
        'description' => (string)($document['description'] ?? ''),
        'document_status' => strtolower((string)($document['document_status'] ?? 'draft')),
        'current_version_no' => (int)($document['current_version_no'] ?? 1),
        'storage_bucket' => (string)($document['storage_bucket'] ?? ''),
        'storage_path' => (string)($document['storage_path'] ?? ''),
        'created_at' => (string)($document['created_at'] ?? ''),
        'updated_at' => (string)($document['updated_at'] ?? ''),
        'category_name' => $resolvedCategoryName,
        'archived_at' => '',
        'archived_label' => '-',
    ];
}

$requestLogsResponse = apiRequest(
    'GET',
    $supabaseUrl
    . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at,actor:user_accounts(username,email)'
    . '&module_name=eq.document_management'
    . '&entity_name=eq.document_requests'
    . '&order=created_at.asc&limit=2000',
    $headers
);

if (isSuccessful($requestLogsResponse)) {
    foreach ((array)($requestLogsResponse['data'] ?? []) as $requestLogRaw) {
        $requestLog = (array)$requestLogRaw;
        $payload = (array)($requestLog['new_data'] ?? []);
        $requestId = trim((string)($requestLog['entity_id'] ?? ($payload['request_id'] ?? '')));
        if ($requestId === '') {
            continue;
        }

        $requesterUserId = trim((string)($payload['requester_user_id'] ?? ''));
        if ($requesterUserId !== (string)$employeeUserId) {
            continue;
        }

        $requestTypeKey = strtolower(trim((string)($payload['request_type'] ?? 'other_hr_document')));
        $purposeKey = strtolower(trim((string)($payload['purpose_key'] ?? 'other')));
        $customRequestLabel = trim((string)($payload['custom_request_label'] ?? ''));
        $otherPurpose = trim((string)($payload['other_purpose'] ?? ''));
        $actor = (array)($requestLog['actor'] ?? []);

        $employeeDocumentRequests[] = [
            'id' => $requestId,
            'request_type_key' => $requestTypeKey,
            'request_type_label' => trim((string)($payload['request_type_label'] ?? ($requestTypeLabels[$requestTypeKey] ?? 'Other HR Document'))),
            'custom_request_label' => $customRequestLabel,
            'purpose_key' => $purposeKey,
            'purpose_label' => trim((string)($payload['purpose_label'] ?? ($requestPurposeLabels[$purposeKey] ?? 'Other'))),
            'other_purpose' => $otherPurpose,
            'notes' => trim((string)($payload['notes'] ?? '')),
            'status' => strtolower(trim((string)($payload['status'] ?? 'submitted'))),
            'status_label' => ucwords(str_replace('_', ' ', trim((string)($payload['status'] ?? 'submitted')))),
            'submitted_at' => (string)($requestLog['created_at'] ?? ''),
            'submitted_label' => formatDateTimeForPhilippines((string)($requestLog['created_at'] ?? ''), 'M d, Y g:i A'),
            'submitted_by' => $buildAuditActorLabel($actor),
        ];
    }

    usort($employeeDocumentRequests, static function (array $left, array $right): int {
        return strcmp((string)($right['submitted_at'] ?? ''), (string)($left['submitted_at'] ?? ''));
    });
}

if (!empty($documentIds)) {
    $inClause = implode(',', array_map(static fn(string $id): string => rawurlencode($id), $documentIds));

    $versionsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/document_versions?select=id,document_id,version_no,file_name,mime_type,size_bytes,uploaded_at'
        . '&document_id=in.(' . $inClause . ')'
        . '&order=version_no.desc,uploaded_at.desc&limit=1000',
        $headers
    );

    if (isSuccessful($versionsResponse)) {
        foreach ((array)($versionsResponse['data'] ?? []) as $versionRaw) {
            $version = (array)$versionRaw;
            $documentId = (string)($version['document_id'] ?? '');
            if ($documentId === '') {
                continue;
            }

            if (!isset($documentVersionsById[$documentId])) {
                $documentVersionsById[$documentId] = [];
            }

            $documentVersionsById[$documentId][] = [
                'id' => (string)($version['id'] ?? ''),
                'version_no' => (int)($version['version_no'] ?? 1),
                'file_name' => (string)($version['file_name'] ?? ''),
                'mime_type' => (string)($version['mime_type'] ?? ''),
                'size_bytes' => (int)($version['size_bytes'] ?? 0),
                'uploaded_at' => (string)($version['uploaded_at'] ?? ''),
            ];
        }
    }

    $reviewsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/document_reviews?select=id,document_id,review_status,review_notes,reviewed_at,reviewer:user_accounts(username,email)'
        . '&document_id=in.(' . $inClause . ')'
        . '&order=reviewed_at.desc,created_at.desc&limit=1000',
        $headers
    );

    if (isSuccessful($reviewsResponse)) {
        foreach ((array)($reviewsResponse['data'] ?? []) as $reviewRaw) {
            $review = (array)$reviewRaw;
            $documentId = (string)($review['document_id'] ?? '');
            if ($documentId === '') {
                continue;
            }

            if (!isset($documentReviewsById[$documentId])) {
                $documentReviewsById[$documentId] = [];
            }

            $reviewer = (array)($review['reviewer'] ?? []);
            $reviewerLabel = trim((string)($reviewer['username'] ?? ''));
            if ($reviewerLabel === '') {
                $reviewerLabel = (string)($reviewer['email'] ?? '');
            }

            $documentReviewsById[$documentId][] = [
                'id' => (string)($review['id'] ?? ''),
                'review_status' => strtolower((string)($review['review_status'] ?? 'pending')),
                'review_notes' => (string)($review['review_notes'] ?? ''),
                'reviewed_at' => (string)($review['reviewed_at'] ?? ''),
                'reviewer_label' => $reviewerLabel,
                'reviewer_email' => (string)($reviewer['email'] ?? ''),
            ];
        }
    }

    $auditLogsResponse = apiRequest(
        'GET',
        $supabaseUrl
        . '/rest/v1/activity_logs?select=entity_id,action_name,new_data,created_at,actor:user_accounts(username,email)'
        . '&entity_name=eq.documents'
        . '&entity_id=in.(' . $inClause . ')'
        . '&action_name=in.' . rawurlencode('(upload_document,upload_document_file,upload_document_version,recommend_document,review_document,archive_document,restore_document)')
        . '&order=created_at.desc&limit=4000',
        $headers
    );

    if (isSuccessful($auditLogsResponse)) {
        $archivedAtByDocumentId = [];

        foreach ((array)($auditLogsResponse['data'] ?? []) as $auditLogRaw) {
            $auditLog = (array)$auditLogRaw;
            $documentId = trim((string)($auditLog['entity_id'] ?? ''));
            if ($documentId === '') {
                continue;
            }

            $payload = (array)($auditLog['new_data'] ?? []);
            $actor = (array)($auditLog['actor'] ?? []);
            $actionName = (string)($auditLog['action_name'] ?? '');
            $notes = $resolveAuditNotes($payload);

            if (!isset($documentAuditTrailById[$documentId])) {
                $documentAuditTrailById[$documentId] = [];
            }

            $documentAuditTrailById[$documentId][] = [
                'action_label' => $resolveAuditActionLabel($actionName, $payload),
                'actor_label' => $buildAuditActorLabel($actor),
                'created_at' => (string)($auditLog['created_at'] ?? ''),
                'created_label' => formatDateTimeForPhilippines((string)($auditLog['created_at'] ?? ''), 'M d, Y g:i A'),
                'notes' => $notes,
            ];

            if (strtolower(trim($actionName)) === 'archive_document' && !isset($archivedAtByDocumentId[$documentId])) {
                $archivedAtByDocumentId[$documentId] = (string)($auditLog['created_at'] ?? '');
            }
        }

        foreach ($employeeDocuments as &$documentRow) {
            $documentId = (string)($documentRow['id'] ?? '');
            $archivedAt = (string)($archivedAtByDocumentId[$documentId] ?? '');
            $documentRow['archived_at'] = $archivedAt;
            $documentRow['archived_label'] = $archivedAt !== ''
                ? formatDateTimeForPhilippines($archivedAt, 'M d, Y g:i A')
                : '-';
        }
        unset($documentRow);
    }
}
