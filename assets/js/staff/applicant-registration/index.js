document.addEventListener('DOMContentLoaded', () => {
    const asyncRegion = document.getElementById('staffApplicantRegistrationAsyncRegion');
    const listSkeleton = document.getElementById('staffApplicantRegistrationListSkeleton');
    const listError = document.getElementById('staffApplicantRegistrationListError');
    const listRetryButton = document.getElementById('staffApplicantRegistrationListRetry');
    const listContent = document.getElementById('staffApplicantRegistrationListContent');
    const modal = document.getElementById('registrationModal');
    const closeButtons = Array.from(document.querySelectorAll('[data-registration-modal-close="registrationModal"]'));
    const applicationIdInput = document.getElementById('registrationApplicationId');
    const applicantNameLabel = document.getElementById('registrationApplicantName');
    const applicantMetaLabel = document.getElementById('registrationApplicantMeta');
    const applicantContactLabel = document.getElementById('registrationApplicantContact');
    const applicationRefLabel = document.getElementById('registrationApplicationRef');
    const documentsBody = document.getElementById('registrationDocumentsBody');

    if (!asyncRegion || !listContent || !modal || !documentsBody) {
        return;
    }

    const listUrl = asyncRegion.dataset.registrationListUrl || 'applicant-registration.php?partial=registration-list';
    const detailUrl = asyncRegion.dataset.registrationDetailUrl || 'applicant-registration.php?partial=registration-detail';

    let activeListAbortController = null;
    let activeDetailAbortController = null;
    let searchDebounceTimer = null;

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const setListState = ({ loading = false, error = false }) => {
        listSkeleton?.classList.toggle('hidden', !loading);
        listContent.classList.toggle('hidden', loading || error);
        listError?.classList.toggle('hidden', !error);
    };

    const resetModal = () => {
        if (applicationIdInput) {
            applicationIdInput.value = '';
        }
        if (applicantNameLabel) {
            applicantNameLabel.textContent = '-';
        }
        if (applicantMetaLabel) {
            applicantMetaLabel.textContent = '-';
        }
        if (applicantContactLabel) {
            applicantContactLabel.textContent = '-';
        }
        if (applicationRefLabel) {
            applicationRefLabel.textContent = '-';
        }
        documentsBody.innerHTML = '<tr><td class="px-3 py-3 text-slate-500" colspan="3">No document selected.</td></tr>';
    };

    const openModal = () => {
        modal.classList.remove('hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        resetModal();
    };

    const renderDocuments = (documents) => {
        if (!Array.isArray(documents) || documents.length === 0) {
            documentsBody.innerHTML = '<tr><td class="px-3 py-3 text-slate-500" colspan="3">No submitted documents found.</td></tr>';
            return;
        }

        documentsBody.innerHTML = documents.map((documentItem) => {
            const fileName = escapeHtml(documentItem?.file_name || '-');
            const uploadedLabel = escapeHtml(documentItem?.uploaded_label || '-');
            const previewUrl = escapeHtml(documentItem?.preview_url || documentItem?.file_url || '');
            const downloadUrl = escapeHtml(documentItem?.download_url || documentItem?.preview_url || documentItem?.file_url || '');
            const hasFile = Boolean(documentItem?.is_available && previewUrl !== '');

            return `
                <tr>
                    <td class="px-3 py-2 text-slate-700">${fileName}</td>
                    <td class="px-3 py-2 text-slate-700">${uploadedLabel}</td>
                    <td class="px-3 py-2">
                        ${hasFile ? `
                            <div class="flex items-center gap-2">
                                <a href="${previewUrl}" target="_blank" rel="noopener noreferrer" class="px-2 py-1 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">View</a>
                                <a href="${downloadUrl}" class="px-2 py-1 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100">Download</a>
                            </div>
                        ` : '<span class="text-xs text-slate-500">File unavailable</span>'}
                    </td>
                </tr>
            `;
        }).join('');
    };

    const renderProfile = (payload) => {
        if (applicationIdInput) {
            applicationIdInput.value = payload.application_id || '';
        }
        if (applicantNameLabel) {
            applicantNameLabel.textContent = payload.applicant_name || 'Applicant';
        }
        if (applicantMetaLabel) {
            applicantMetaLabel.textContent = `${payload.posting_title || '-'} • ${payload.applicant_email || '-'} • Submitted ${payload.submitted_label || '-'} • ${payload.screening_label || '-'}`;
        }
        if (applicantContactLabel) {
            applicantContactLabel.textContent = `Mobile: ${payload.applicant_mobile || '-'} • Address: ${payload.applicant_address || '-'}`;
        }
        if (applicationRefLabel) {
            applicationRefLabel.textContent = `Application Ref: ${payload.application_ref_no || '-'} • Basis: ${payload.basis || '-'}`;
        }

        renderDocuments(payload.documents || []);
    };

    const bindListInteractions = () => {
        const searchInput = listContent.querySelector('#registrationSearchInput');
        const statusFilter = listContent.querySelector('#registrationStatusFilter');
        const paginationButtons = Array.from(listContent.querySelectorAll('[data-registration-page]'));
        const profileButtons = Array.from(listContent.querySelectorAll('[data-open-registration-modal]'));

        const queueListReload = () => {
            if (searchDebounceTimer !== null) {
                window.clearTimeout(searchDebounceTimer);
            }

            searchDebounceTimer = window.setTimeout(() => {
                loadList({
                    page: 1,
                    search: (searchInput?.value || '').trim(),
                    status: (statusFilter?.value || '').trim(),
                });
            }, 180);
        };

        searchInput?.addEventListener('input', queueListReload);
        searchInput?.addEventListener('search', queueListReload);
        statusFilter?.addEventListener('change', () => {
            loadList({
                page: 1,
                search: (searchInput?.value || '').trim(),
                status: (statusFilter?.value || '').trim(),
            });
        });

        paginationButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled) {
                    return;
                }

                const page = Number.parseInt(button.dataset.registrationPage || '1', 10);
                if (!Number.isFinite(page) || page < 1) {
                    return;
                }

                loadList({
                    page,
                    search: (searchInput?.value || '').trim(),
                    status: (statusFilter?.value || '').trim(),
                });
            });
        });

        profileButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const applicationId = (button.dataset.applicationId || '').trim();
                if (applicationId === '') {
                    return;
                }

                void loadDetail(applicationId);
            });
        });
    };

    const loadList = async ({ page = 1, search = '', status = '' } = {}) => {
        activeListAbortController?.abort();
        activeListAbortController = new AbortController();

        setListState({ loading: true, error: false });

        const requestUrl = new URL(listUrl, window.location.href);
        requestUrl.searchParams.set('page', String(page));
        if (search !== '') {
            requestUrl.searchParams.set('search', search);
        }
        if (status !== '') {
            requestUrl.searchParams.set('status', status);
        }

        try {
            const response = await fetch(requestUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeListAbortController.signal,
            });

            if (!response.ok) {
                throw new Error(`List request failed with status ${response.status}`);
            }

            listContent.innerHTML = await response.text();
            setListState({ loading: false, error: false });
            bindListInteractions();
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }

            setListState({ loading: false, error: true });
        }
    };

    const loadDetail = async (applicationId) => {
        activeDetailAbortController?.abort();
        activeDetailAbortController = new AbortController();

        resetModal();
        if (applicantNameLabel) {
            applicantNameLabel.textContent = 'Loading applicant profile...';
        }
        openModal();

        const requestUrl = new URL(detailUrl, window.location.href);
        requestUrl.searchParams.set('application_id', applicationId);

        try {
            const response = await fetch(requestUrl.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeDetailAbortController.signal,
            });

            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload || payload.error) {
                throw new Error(payload?.error || `Detail request failed with status ${response.status}`);
            }

            renderProfile(payload);
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }

            if (applicantNameLabel) {
                applicantNameLabel.textContent = 'Applicant profile unavailable';
            }
            if (applicantMetaLabel) {
                applicantMetaLabel.textContent = 'The selected profile could not be loaded right now.';
            }
            documentsBody.innerHTML = '<tr><td class="px-3 py-3 text-amber-700" colspan="3">Applicant documents could not be loaded right now.</td></tr>';
        }
    };

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    listRetryButton?.addEventListener('click', () => {
        void loadList();
    });

    resetModal();
    void loadList();
});