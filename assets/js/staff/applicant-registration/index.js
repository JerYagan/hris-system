(() => {
    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const escapeHtml = (value) => {
        const text = (value || '').toString();
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const parseJsonScriptNode = (id) => {
        const node = document.getElementById(id);
        if (!node) {
            return {};
        }

        try {
            const parsed = JSON.parse(node.textContent || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (_error) {
            return {};
        }
    };

    const initDatePickers = () => {
        if (!window.flatpickr) {
            return;
        }

        const dateInputs = document.querySelectorAll('main input[type="date"]:not([data-flatpickr="off"])');
        dateInputs.forEach((input) => {
            if (input.dataset.flatpickrInitialized === 'true') {
                return;
            }

            window.flatpickr(input, {
                dateFormat: 'Y-m-d',
                allowInput: true,
            });
            input.dataset.flatpickrInitialized = 'true';
        });
    };

    const searchInput = document.getElementById('registrationSearchInput');
    const statusFilter = document.getElementById('registrationStatusFilter');
    const rows = Array.from(document.querySelectorAll('[data-registration-row]'));
    const emptyRow = document.getElementById('registrationFilterEmptyRow');
    const registrationViewData = parseJsonScriptNode('registrationViewData');

    const modal = document.getElementById('registrationModal');
    const closeButtons = Array.from(document.querySelectorAll('[data-registration-modal-close="registrationModal"]'));
    const form = document.getElementById('registrationForm');
    const submitBtn = document.getElementById('registrationSubmit');

    const applicationIdInput = document.getElementById('registrationApplicationId');
    const applicantNameLabel = document.getElementById('registrationApplicantName');
    const applicantMetaLabel = document.getElementById('registrationApplicantMeta');
    const applicantContactLabel = document.getElementById('registrationApplicantContact');
    const applicationRefLabel = document.getElementById('registrationApplicationRef');
    const decisionInput = document.getElementById('registrationDecision');
    const documentsBody = document.getElementById('registrationDocumentsBody');

    const applyFilters = () => {
        if (!searchInput || !statusFilter) {
            return;
        }

        const query = normalize(searchInput.value);
        const status = normalize(statusFilter.value);
        let visible = 0;

        rows.forEach((row) => {
            const haystack = normalize(row.getAttribute('data-registration-search'));
            const rowStatus = normalize(row.getAttribute('data-registration-status'));
            const isMatch = (query === '' || haystack.includes(query)) && (status === '' || rowStatus === status);
            row.classList.toggle('hidden', !isMatch);
            if (isMatch) {
                visible += 1;
            }
        });

        if (emptyRow) {
            emptyRow.classList.toggle('hidden', visible > 0);
        }
    };

    let debounceTimer = null;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(applyFilters, 150);
        });
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }

    const closeModal = () => {
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        if (form) {
            form.dataset.confirmed = '0';
        }
        if (documentsBody) {
            documentsBody.innerHTML = '<tr><td class="px-3 py-3 text-slate-500" colspan="3">No document selected.</td></tr>';
        }
    };

    const renderDocuments = (documents) => {
        if (!documentsBody) {
            return;
        }

        if (!Array.isArray(documents) || documents.length === 0) {
            documentsBody.innerHTML = '<tr><td class="px-3 py-3 text-slate-500" colspan="3">No submitted documents found.</td></tr>';
            return;
        }

        documentsBody.innerHTML = documents.map((documentRow) => {
            const fileName = escapeHtml(documentRow && documentRow.file_name ? documentRow.file_name : '-');
            const uploaded = escapeHtml(documentRow && documentRow.uploaded_label ? documentRow.uploaded_label : '-');
            const previewUrl = documentRow && documentRow.preview_url ? documentRow.preview_url.toString() : ((documentRow && documentRow.file_url ? documentRow.file_url.toString() : ''));
            const downloadUrl = documentRow && documentRow.download_url ? documentRow.download_url.toString() : previewUrl;
            const hasFile = Boolean(documentRow && documentRow.is_available && previewUrl.trim() !== '');
            const safePreviewUrl = escapeHtml(previewUrl);
            const safeDownloadUrl = escapeHtml(downloadUrl);

            const actionHtml = hasFile
                ? `
                    <div class="flex items-center gap-2">
                        <a href="${safePreviewUrl}" target="_blank" rel="noopener noreferrer" class="px-2 py-1 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">View</a>
                        <a href="${safeDownloadUrl}" class="px-2 py-1 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100">Download</a>
                    </div>
                `
                : '<span class="text-xs text-slate-500">File unavailable</span>';

            return `
                <tr>
                    <td class="px-3 py-2 text-slate-700">${fileName}</td>
                    <td class="px-3 py-2 text-slate-700">${uploaded}</td>
                    <td class="px-3 py-2">${actionHtml}</td>
                </tr>
            `;
        }).join('');
    };

    document.querySelectorAll('[data-open-registration-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!modal || !applicationIdInput || !applicantNameLabel) {
                return;
            }

            const applicationId = button.getAttribute('data-application-id') || '';
            const payload = registrationViewData && typeof registrationViewData === 'object'
                ? registrationViewData[applicationId]
                : null;
            if (!payload || typeof payload !== 'object') {
                return;
            }

            applicationIdInput.value = applicationId;
            applicantNameLabel.textContent = payload.applicant_name || 'Applicant';
            if (applicantMetaLabel) {
                applicantMetaLabel.textContent = `${payload.posting_title || '-'} • ${payload.applicant_email || '-'} • Submitted ${payload.submitted_label || '-'} • ${payload.screening_label || '-'}`;
            }
            if (applicantContactLabel) {
                applicantContactLabel.textContent = `Mobile: ${payload.applicant_mobile || '-'} • Address: ${payload.applicant_address || '-'}`;
            }
            if (applicationRefLabel) {
                applicationRefLabel.textContent = `Application Ref: ${payload.application_ref_no || '-'}`;
            }
            if (decisionInput) {
                decisionInput.value = 'approve_for_next_stage';
            }
            renderDocuments(payload.documents || []);
            modal.classList.remove('hidden');
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    initDatePickers();
    applyFilters();
})();