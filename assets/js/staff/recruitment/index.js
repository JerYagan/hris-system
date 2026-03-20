(() => {
    const MIN_SKELETON_MS = 250;
    const normalize = (value) => (value || '').toString().trim().toLowerCase();

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

    const escapeHtml = (value) => {
        const text = (value || '').toString();
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const fetchJsonResponse = async (url) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.redirected) {
            window.location.href = response.url;
            return null;
        }

        let payload = null;
        try {
            payload = await response.json();
        } catch (_error) {
            payload = null;
        }

        if (!response.ok) {
            const message = payload && typeof payload.error === 'string' && payload.error.trim() !== ''
                ? payload.error.trim()
                : `Recruitment JSON request failed with status ${response.status}`;
            throw new Error(message);
        }

        if (!payload || typeof payload !== 'object') {
            throw new Error('Recruitment detail request returned an invalid payload.');
        }

        if (typeof payload.error === 'string' && payload.error.trim() !== '') {
            throw new Error(payload.error.trim());
        }

        return payload;
    };

    const showConfirmation = async (message, title = 'Please confirm') => {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            const result = await window.Swal.fire({
                title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, continue',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            });
            return Boolean(result && result.isConfirmed);
        }

        return window.confirm(message);
    };

    const showFlashMessage = () => {
        const flashNode = document.getElementById('recruitmentFlashState');
        if (!flashNode || !(window.Swal && typeof window.Swal.fire === 'function')) {
            return;
        }

        const state = normalize(flashNode.getAttribute('data-state'));
        const message = (flashNode.getAttribute('data-message') || '').trim();
        if (!state || !message) {
            return;
        }

        const icon = state === 'success' ? 'success' : state === 'error' ? 'error' : 'info';
        window.Swal.fire({
            icon,
            text: message,
            confirmButtonText: 'OK',
        });
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

    const setupTableFilters = ({ reloadListings } = {}) => {
        const filtersForm = document.getElementById('recruitmentListingsFilters');
        const pageInput = document.getElementById('recruitmentPageInput');
        const searchInput = document.getElementById('recruitmentSearchInput');
        const statusFilter = document.getElementById('recruitmentStatusFilter');
        const prevButton = document.getElementById('recruitmentPrevPage');
        const nextButton = document.getElementById('recruitmentNextPage');

        if (!filtersForm || !pageInput || !searchInput || !statusFilter || typeof reloadListings !== 'function') {
            return;
        }

        let debounceTimer = 0;
        const submitCurrentState = () => {
            reloadListings(new FormData(filtersForm));
        };

        searchInput.addEventListener('input', () => {
            pageInput.value = '1';
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(() => {
                submitCurrentState();
            }, 200);
        });

        statusFilter.addEventListener('change', () => {
            pageInput.value = '1';
            submitCurrentState();
        });

        prevButton?.addEventListener('click', () => {
            const page = prevButton.getAttribute('data-recruitment-page') || '';
            if (page === '') {
                return;
            }

            pageInput.value = page;
            submitCurrentState();
        });

        nextButton?.addEventListener('click', () => {
            const page = nextButton.getAttribute('data-recruitment-page') || '';
            if (page === '') {
                return;
            }

            pageInput.value = page;
            submitCurrentState();
        });
    };

    const initAsyncRecruitmentPage = () => {
        const region = document.getElementById('staffRecruitmentAsyncRegion');
        if (!region) {
            return false;
        }

        const summaryUrl = region.getAttribute('data-recruitment-summary-url') || '';
        const listingsUrl = region.getAttribute('data-recruitment-listings-url') || '';
        const secondaryUrl = region.getAttribute('data-recruitment-secondary-url') || '';

        const summarySkeleton = document.getElementById('staffRecruitmentSummarySkeleton');
        const summaryContent = document.getElementById('staffRecruitmentSummaryContent');
        const summaryError = document.getElementById('staffRecruitmentSummaryError');
        const summaryRetry = document.getElementById('staffRecruitmentSummaryRetry');

        const listingsSkeleton = document.getElementById('staffRecruitmentListingsSkeleton');
        const listingsContent = document.getElementById('staffRecruitmentListingsContent');
        const listingsError = document.getElementById('staffRecruitmentListingsError');
        const listingsRetry = document.getElementById('staffRecruitmentListingsRetry');

        const secondarySkeleton = document.getElementById('staffRecruitmentSecondarySkeleton');
        const secondaryContent = document.getElementById('staffRecruitmentSecondaryContent');
        const secondaryError = document.getElementById('staffRecruitmentSecondaryError');
        const secondaryRetry = document.getElementById('staffRecruitmentSecondaryRetry');

        if (!summaryUrl || !listingsUrl || !secondaryUrl || !summarySkeleton || !summaryContent || !summaryError || !summaryRetry || !listingsSkeleton || !listingsContent || !listingsError || !listingsRetry || !secondarySkeleton || !secondaryContent || !secondaryError || !secondaryRetry) {
            return false;
        }

        const summaryState = { requestId: 0 };
        const listingsState = { requestId: 0 };
        const secondaryState = { requestId: 0 };

        const buildSectionUrl = (baseUrl, params) => {
            const url = new URL(baseUrl, window.location.href);
            if (!(params instanceof FormData)) {
                return url.toString();
            }

            Array.from(params.entries()).forEach(([key, value]) => {
                url.searchParams.set(key, String(value));
            });

            return url.toString();
        };

        const setLoadingState = ({ skeleton, content, errorBox, retryButton }, isLoading) => {
            skeleton.classList.toggle('hidden', !isLoading);
            content.classList.toggle('hidden', isLoading);
            if (isLoading) {
                errorBox.classList.add('hidden');
            }
            retryButton.disabled = isLoading;
        };

        const showErrorState = ({ skeleton, content, errorBox, retryButton }) => {
            skeleton.classList.add('hidden');
            content.classList.add('hidden');
            errorBox.classList.remove('hidden');
            retryButton.disabled = false;
        };

        const fetchPartialHtml = async (url) => {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.redirected) {
                window.location.href = response.url;
                return '';
            }

            if (!response.ok) {
                throw new Error(`Recruitment partial request failed with status ${response.status}`);
            }

            const html = await response.text();
            if (html.trim() === '') {
                throw new Error('Recruitment partial returned an empty response.');
            }

            return html;
        };

        const loadSection = async ({ url, params, skeleton, content, errorBox, retryButton, onSuccess, state }) => {
            setLoadingState({ skeleton, content, errorBox, retryButton }, true);
            state.requestId += 1;
            const requestId = state.requestId;
            const startedAt = window.performance?.now?.() ?? Date.now();

            try {
                const html = await fetchPartialHtml(buildSectionUrl(url, params));
                const elapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
                const remaining = Math.max(0, MIN_SKELETON_MS - elapsed);

                window.setTimeout(() => {
                    if (requestId !== state.requestId) {
                        return;
                    }

                    content.innerHTML = html;
                    content.classList.remove('hidden');
                    skeleton.classList.add('hidden');
                    errorBox.classList.add('hidden');
                    retryButton.disabled = false;
                    if (typeof onSuccess === 'function') {
                        onSuccess();
                    }
                }, remaining);
            } catch (error) {
                console.error(error);
                showErrorState({ skeleton, content, errorBox, retryButton });
            }
        };

        const loadListings = (params) => loadSection({
            url: listingsUrl,
            params,
            skeleton: listingsSkeleton,
            content: listingsContent,
            errorBox: listingsError,
            retryButton: listingsRetry,
            state: listingsState,
            onSuccess: () => {
                setupTableFilters({ reloadListings: loadListings });
            },
        });

        summaryRetry.addEventListener('click', () => {
            loadSection({
                url: summaryUrl,
                skeleton: summarySkeleton,
                content: summaryContent,
                errorBox: summaryError,
                retryButton: summaryRetry,
                state: summaryState,
            }).catch(console.error);
        });

        listingsRetry.addEventListener('click', () => {
            loadListings().catch(console.error);
        });

        secondaryRetry.addEventListener('click', () => {
            loadSection({
                url: secondaryUrl,
                skeleton: secondarySkeleton,
                content: secondaryContent,
                errorBox: secondaryError,
                retryButton: secondaryRetry,
                state: secondaryState,
                onSuccess: () => {
                    setupUnarchiveForms();
                },
            }).catch(console.error);
        });

        region.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const refreshButton = target.closest('[data-recruitment-refresh]');
            if (!(refreshButton instanceof HTMLElement)) {
                return;
            }

            const refreshTarget = (refreshButton.getAttribute('data-recruitment-refresh') || '').trim().toLowerCase();
            if (refreshTarget === 'listings') {
                const filtersForm = document.getElementById('recruitmentListingsFilters');
                loadListings(filtersForm instanceof HTMLFormElement ? new FormData(filtersForm) : undefined).catch(console.error);
                return;
            }

            if (refreshTarget === 'secondary') {
                loadSection({
                    url: secondaryUrl,
                    skeleton: secondarySkeleton,
                    content: secondaryContent,
                    errorBox: secondaryError,
                    retryButton: secondaryRetry,
                    state: secondaryState,
                    onSuccess: () => {
                        setupUnarchiveForms();
                    },
                }).catch(console.error);
            }
        });

        loadSection({
            url: summaryUrl,
            skeleton: summarySkeleton,
            content: summaryContent,
            errorBox: summaryError,
            retryButton: summaryRetry,
            state: summaryState,
        }).catch(console.error);

        loadListings().catch(console.error);

        loadSection({
            url: secondaryUrl,
            skeleton: secondarySkeleton,
            content: secondaryContent,
            errorBox: secondaryError,
            retryButton: secondaryRetry,
            state: secondaryState,
            onSuccess: () => {
                setupUnarchiveForms();
            },
        }).catch(console.error);

        return true;
    };

    const setupPostingStatusModal = () => {
        const modal = document.getElementById('postingStatusModal');
        const openButtons = Array.from(document.querySelectorAll('[data-open-posting-status-modal]'));
        const closeButton = document.getElementById('postingStatusModalClose');
        const cancelButton = document.getElementById('postingStatusModalCancel');
        const form = document.getElementById('postingStatusForm');
        const submitButton = document.getElementById('postingStatusSubmit');

        const postingIdInput = document.getElementById('postingStatusPostingId');
        const postingTitle = document.getElementById('postingStatusTitle');
        const currentStatus = document.getElementById('postingStatusCurrent');
        const newStatusInput = document.getElementById('postingStatusNew');

        if (!modal || !form || !postingIdInput || !newStatusInput) {
            return;
        }

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
            form.reset();
            form.dataset.confirmed = '0';
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => {
            postingIdInput.value = button.getAttribute('data-posting-id') || '';
                if (postingTitle) {
                    postingTitle.textContent = button.getAttribute('data-posting-title') || 'Position';
                }
                if (currentStatus) {
                    currentStatus.textContent = button.getAttribute('data-current-status-label') || '-';
                }
            newStatusInput.value = button.getAttribute('data-current-status') || '';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (form) {
            form.addEventListener('submit', async (event) => {
                if (form.dataset.confirmed === '1') {
                    return;
                }

                event.preventDefault();
            const title = (postingTitle ? postingTitle.textContent : 'posting') || 'posting';
            const oldLabel = (currentStatus ? currentStatus.textContent : '-') || '-';
            const newValue = normalize(newStatusInput ? newStatusInput.value : '');
            if (newValue === '') {
                return;
            }

            const newLabel = newValue.replace('_', ' ');
                const shouldContinue = await showConfirmation(`Confirm status change for "${title}" from "${oldLabel}" to "${newLabel}"?`, 'Update Posting Status');
            if (!shouldContinue) {
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

                form.dataset.confirmed = '1';
                form.submit();
        });
    }
    };

    const setupCreatePostingModal = () => {
        const modal = document.getElementById('createJobPostingModal');
        const openButton = document.getElementById('openCreateJobPostingModal');
        const closeButton = document.getElementById('createJobPostingModalClose');
        const cancelButton = document.getElementById('createJobPostingModalCancel');
        const form = document.getElementById('createJobPostingForm');
        const submitButton = document.getElementById('createJobPostingSubmit');
        const employmentTypeSelect = document.getElementById('createJobPostingEmploymentType');
        const positionSelect = document.getElementById('createJobPostingPosition');

        if (!modal || !openButton || !form) {
            return;
        }

        const applyPositionFilter = () => {
            if (!employmentTypeSelect || !positionSelect) {
                return;
            }

            const selectedEmploymentType = normalize(employmentTypeSelect.value);
            const options = Array.from(positionSelect.options || []);
            options.forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const optionEmploymentType = normalize(option.getAttribute('data-employment-type'));
                const isMatch = selectedEmploymentType === '' || optionEmploymentType === selectedEmploymentType;
                option.hidden = !isMatch;
                option.disabled = !isMatch;
            });

            const selectedOption = positionSelect.options[positionSelect.selectedIndex];
            if (selectedOption && selectedOption.disabled) {
                positionSelect.value = '';
            }
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            form.reset();
            form.dataset.confirmed = '0';
            applyPositionFilter();
        };

        openButton.addEventListener('click', () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            applyPositionFilter();
        });

        if (employmentTypeSelect) {
            employmentTypeSelect.addEventListener('change', applyPositionFilter);
        }

        if (closeButton) {
            closeButton.addEventListener('click', closeModal);
        }
        if (cancelButton) {
            cancelButton.addEventListener('click', closeModal);
        }

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const shouldContinue = await showConfirmation('Create this job posting?', 'Create Job Posting');
            if (!shouldContinue) {
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

            form.dataset.confirmed = '1';
            form.submit();
        });
    };

    const setupPostingViewModal = () => {
        const region = document.getElementById('staffRecruitmentAsyncRegion');
        const modal = document.getElementById('postingViewModal');
        const closeButton = document.getElementById('postingViewModalClose');
        const cancelButton = document.getElementById('postingViewModalCancel');
        const feedbackBox = document.getElementById('postingViewFeedback');
        const applicantModal = document.getElementById('staffApplicantDecisionModal');
        const applicantModalCloseButtons = Array.from(document.querySelectorAll('[data-staff-applicant-modal-close="staffApplicantDecisionModal"]'));
        const applicantDecisionForm = document.getElementById('staffApplicantDecisionForm');
        const staffDecisionApplicationId = document.getElementById('staffDecisionApplicationId');
        const applicantProfileName = document.getElementById('staffApplicantProfileName');
        const applicantProfileMeta = document.getElementById('staffApplicantProfileMeta');
        const applicantEmployeeActionEl = document.getElementById('staffApplicantEmployeeAction');
        const applicantDocumentsBody = document.getElementById('staffApplicantDocumentsBody');
        const applicantStatusBox = document.getElementById('staffApplicantProfileStatus');
        const applicantFeedbackList = document.getElementById('staffApplicantFeedbackList');
        const applicantInterviewList = document.getElementById('staffApplicantInterviewList');

        if (!region || !modal) {
            return;
        }

        const postingViewBaseUrl = region.getAttribute('data-recruitment-posting-view-url') || '';
        const applicantViewBaseUrl = region.getAttribute('data-recruitment-applicant-view-url') || '';
        if (!postingViewBaseUrl || !applicantViewBaseUrl) {
            return;
        }

        const positionEl = document.getElementById('postingViewPosition');
        const officeEl = document.getElementById('postingViewOffice');
        const employmentTypeEl = document.getElementById('postingViewEmploymentType');
        const statusEl = document.getElementById('postingViewStatus');
        const openDateEl = document.getElementById('postingViewOpenDate');
        const closeDateEl = document.getElementById('postingViewCloseDate');
        const descriptionEl = document.getElementById('postingViewDescription');
        const qualificationsEl = document.getElementById('postingViewQualifications');
        const responsibilitiesEl = document.getElementById('postingViewResponsibilities');
        const requirementsEl = document.getElementById('postingViewRequirements');
        const applicantsBodyEl = document.getElementById('postingViewApplicantsBody');

        const postingCache = new Map();
        const applicantCache = new Map();
        let postingRequestId = 0;
        let applicantRequestId = 0;

        const setText = (element, text) => {
            if (element) {
                element.textContent = text || '-';
            }
        };

        const setStatusMessage = (element, message, isVisible = true) => {
            if (!element) {
                return;
            }

            element.textContent = message || '';
            element.classList.toggle('hidden', !isVisible || !message);
        };

        const buildDetailUrl = (baseUrl, paramKey, paramValue) => {
            const url = new URL(baseUrl, window.location.href);
            url.searchParams.set(paramKey, paramValue);
            return url.toString();
        };

        const renderRequirements = (requirements) => {
            if (!requirementsEl) {
                return;
            }

            const items = Array.isArray(requirements) ? requirements : [];
            requirementsEl.innerHTML = items.length > 0
                ? items.map((item) => `<li>${escapeHtml(item)}</li>`).join('')
                : '<li>-</li>';
        };

        const renderApplicants = (applicants) => {
            if (!applicantsBodyEl) {
                return;
            }

            if (!Array.isArray(applicants) || applicants.length === 0) {
                applicantsBodyEl.innerHTML = '<tr><td colspan="6" class="px-3 py-3 text-gray-500">No applicants yet.</td></tr>';
                return;
            }

            applicantsBodyEl.innerHTML = applicants.map((applicant) => {
                const applicantName = escapeHtml(applicant && applicant.applicant_name ? applicant.applicant_name : 'Applicant');
                const applicantEmail = escapeHtml(applicant && applicant.applicant_email ? applicant.applicant_email : '-');
                const appliedPosition = escapeHtml(applicant && applicant.applied_position ? applicant.applied_position : '-');
                const submittedLabel = escapeHtml(applicant && applicant.submitted_label ? applicant.submitted_label : '-');
                const screeningLabel = escapeHtml(applicant && applicant.initial_screening_label ? applicant.initial_screening_label : 'For Review');
                const screeningClass = applicant && applicant.initial_screening_class ? applicant.initial_screening_class : 'bg-slate-100 text-slate-700';
                const basis = escapeHtml(applicant && applicant.basis ? applicant.basis : '-');
                const applicationId = escapeHtml(applicant && applicant.application_id ? applicant.application_id : '');

                return `
                    <tr>
                        <td class="px-3 py-2">
                            <div class="font-medium text-slate-800">${applicantName}</div>
                            <div class="text-xs text-slate-500">${applicantEmail}</div>
                        </td>
                        <td class="px-3 py-2 text-slate-700">${appliedPosition}</td>
                        <td class="px-3 py-2 text-slate-700">${submittedLabel}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex px-2 py-1 text-xs rounded-full ${screeningClass}">${screeningLabel}</span>
                        </td>
                        <td class="px-3 py-2 text-slate-700">${basis}</td>
                        <td class="px-3 py-2">
                            <button type="button" data-staff-applicant-open data-application-id="${applicationId}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[16px]">person_search</span>View Profile</button>
                        </td>
                    </tr>
                `;
            }).join('');
        };

        const renderDocuments = (documents) => {
            if (!applicantDocumentsBody) {
                return;
            }

            if (!Array.isArray(documents) || documents.length === 0) {
                applicantDocumentsBody.innerHTML = '<tr><td class="px-3 py-3 text-slate-500" colspan="4">No submitted documents found.</td></tr>';
                return;
            }

            applicantDocumentsBody.innerHTML = documents.map((documentRow) => {
                const label = escapeHtml(documentRow && documentRow.document_label ? documentRow.document_label : 'Document');
                const fileName = escapeHtml(documentRow && documentRow.file_name ? documentRow.file_name : '-');
                const uploaded = escapeHtml(documentRow && documentRow.uploaded_label ? documentRow.uploaded_label : '-');
                const fileUrl = documentRow && documentRow.file_url ? documentRow.file_url.toString() : '';
                const isAvailable = Boolean(documentRow && documentRow.is_available && fileUrl.trim() !== '');
                const safeUrl = escapeHtml(fileUrl);

                const actionHtml = isAvailable
                    ? `
                        <div class="flex items-center gap-2">
                            <a href="${safeUrl}" target="_blank" rel="noopener noreferrer" class="px-2 py-1 text-xs rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">View</a>
                            <a href="${safeUrl}" download class="px-2 py-1 text-xs rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100">Download</a>
                        </div>
                    `
                    : '<span class="text-xs text-slate-500">File unavailable</span>';

                return `
                    <tr>
                        <td class="px-3 py-2 text-slate-700">${label}</td>
                        <td class="px-3 py-2 text-slate-700">${fileName}</td>
                        <td class="px-3 py-2 text-slate-700">${uploaded}</td>
                        <td class="px-3 py-2">${actionHtml}</td>
                    </tr>
                `;
            }).join('');
        };

        const renderFeedbackHistory = (items) => {
            if (!applicantFeedbackList) {
                return;
            }

            if (!Array.isArray(items) || items.length === 0) {
                applicantFeedbackList.innerHTML = '<p class="text-slate-500">No feedback history recorded.</p>';
                return;
            }

            applicantFeedbackList.innerHTML = items.map((item) => `
                <article class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-medium text-slate-800">${escapeHtml(item && item.decision_label ? item.decision_label : 'Recorded')}</p>
                        <p class="text-xs text-slate-500">${escapeHtml(item && item.provided_label ? item.provided_label : '-')}</p>
                    </div>
                    <p class="mt-2 text-slate-700">${escapeHtml(item && item.summary ? item.summary : 'No remarks recorded.')}</p>
                </article>
            `).join('');
        };

        const renderInterviewHistory = (items) => {
            if (!applicantInterviewList) {
                return;
            }

            if (!Array.isArray(items) || items.length === 0) {
                applicantInterviewList.innerHTML = '<p class="text-slate-500">No interview history recorded.</p>';
                return;
            }

            applicantInterviewList.innerHTML = items.map((item) => `
                <article class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="font-medium text-slate-800">${escapeHtml(item && item.result_label ? item.result_label : 'Pending')}</p>
                    <p class="mt-1 text-slate-600">${escapeHtml(item && item.scheduled_label ? item.scheduled_label : '-')}</p>
                </article>
            `).join('');
        };

        const resetPostingModal = () => {
            setText(positionEl, '-');
            setText(officeEl, '-');
            setText(employmentTypeEl, '-');
            setText(statusEl, '-');
            setText(openDateEl, '-');
            setText(closeDateEl, '-');
            setText(descriptionEl, '-');
            setText(qualificationsEl, '-');
            setText(responsibilitiesEl, '-');
            renderRequirements([]);
            if (applicantsBodyEl) {
                applicantsBodyEl.innerHTML = '<tr><td colspan="6" class="px-3 py-3 text-gray-500">Select a posting to load applicants.</td></tr>';
            }
            setStatusMessage(feedbackBox, '', false);
        };

        const resetApplicantModal = () => {
            if (staffDecisionApplicationId) {
                staffDecisionApplicationId.value = '';
            }
            setText(applicantProfileName, '-');
            setText(applicantProfileMeta, '-');
            if (applicantEmployeeActionEl) {
                applicantEmployeeActionEl.innerHTML = '';
            }
            if (applicantDocumentsBody) {
                applicantDocumentsBody.innerHTML = '<tr><td class="px-3 py-3 text-slate-500" colspan="4">No document selected.</td></tr>';
            }
            renderFeedbackHistory([]);
            renderInterviewHistory([]);
            setStatusMessage(applicantStatusBox, '', false);
            if (applicantDecisionForm) {
                applicantDecisionForm.dataset.confirmed = '0';
            }
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            resetPostingModal();
        };

        const closeApplicantModal = () => {
            if (!applicantModal) {
                return;
            }

            applicantModal.classList.add('hidden');
            resetApplicantModal();
        };

        const openPostingModal = async (postingId) => {
            if (!postingId) {
                return;
            }

            postingRequestId += 1;
            const requestId = postingRequestId;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            resetPostingModal();
            setStatusMessage(feedbackBox, 'Loading posting details and applicant list...');

            try {
                const payload = postingCache.has(postingId)
                    ? postingCache.get(postingId)
                    : await fetchJsonResponse(buildDetailUrl(postingViewBaseUrl, 'posting_id', postingId));
                if (!postingCache.has(postingId) && payload) {
                    postingCache.set(postingId, payload);
                }

                if (requestId !== postingRequestId || !payload) {
                    return;
                }

                setText(positionEl, payload.position_title || payload.posting_title || '-');
                setText(officeEl, payload.office_name || '-');
                setText(employmentTypeEl, payload.employment_type || '-');
                setText(statusEl, payload.status_label || '-');
                setText(openDateEl, payload.open_date_label || '-');
                setText(closeDateEl, payload.close_date_label || '-');
                setText(descriptionEl, payload.description || '-');
                setText(qualificationsEl, payload.qualifications || '-');
                setText(responsibilitiesEl, payload.responsibilities || '-');
                renderRequirements(payload.requirements || []);
                renderApplicants(payload.applicants || []);
                setStatusMessage(feedbackBox, '', false);
            } catch (error) {
                console.error(error);
                if (requestId !== postingRequestId) {
                    return;
                }
                setStatusMessage(feedbackBox, error instanceof Error ? error.message : 'Posting details could not be loaded.');
                if (applicantsBodyEl) {
                    applicantsBodyEl.innerHTML = '<tr><td colspan="6" class="px-3 py-3 text-rose-700">Posting details could not be loaded.</td></tr>';
                }
            }
        };

        const openApplicantModal = async (applicationId) => {
            if (!applicantModal || !applicationId) {
                return;
            }

            applicantRequestId += 1;
            const requestId = applicantRequestId;
            applicantModal.classList.remove('hidden');
            resetApplicantModal();
            setStatusMessage(applicantStatusBox, 'Loading applicant profile, documents, feedback, and interview history...');

            try {
                const payload = applicantCache.has(applicationId)
                    ? applicantCache.get(applicationId)
                    : await fetchJsonResponse(buildDetailUrl(applicantViewBaseUrl, 'application_id', applicationId));
                if (!applicantCache.has(applicationId) && payload) {
                    applicantCache.set(applicationId, payload);
                }

                if (requestId !== applicantRequestId || !payload) {
                    return;
                }

                if (staffDecisionApplicationId) {
                    staffDecisionApplicationId.value = payload.application_id || applicationId;
                }

                const applicantName = payload.applicant_name || 'Applicant';
                const applicantEmail = payload.applicant_email || '-';
                const appliedPosition = payload.applied_position || '-';
                const submittedLabel = payload.submitted_label || '-';
                const screening = payload.initial_screening_label || '-';
                const basis = payload.basis || '-';
                setText(applicantProfileName, applicantName);
                setText(applicantProfileMeta, `${appliedPosition} • ${applicantEmail} • Submitted ${submittedLabel} • ${screening}`);

                if (applicantEmployeeActionEl) {
                    if (payload.already_employee) {
                        applicantEmployeeActionEl.innerHTML = '<span class="px-2 py-1 text-xs rounded-full bg-slate-200 text-slate-700">Already Employee</span>';
                    } else {
                        applicantEmployeeActionEl.innerHTML = `<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">Read-only review</span><p class="mt-2 text-xs text-slate-600">Screening basis: ${escapeHtml(basis)}</p>`;
                    }
                }

                renderDocuments(payload.documents || []);
                renderFeedbackHistory(payload.feedback_history || []);
                renderInterviewHistory(payload.interview_history || []);
                setStatusMessage(applicantStatusBox, '', false);
            } catch (error) {
                console.error(error);
                if (requestId !== applicantRequestId) {
                    return;
                }
                setStatusMessage(applicantStatusBox, error instanceof Error ? error.message : 'Applicant details could not be loaded.');
            }
        };

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const postingButton = target.closest('[data-open-posting-view-modal]');
            if (postingButton instanceof HTMLElement) {
                openPostingModal((postingButton.getAttribute('data-posting-id') || '').trim()).catch(console.error);
                return;
            }

            const applicantButton = target.closest('[data-staff-applicant-open]');
            if (applicantButton instanceof HTMLElement) {
                openApplicantModal((applicantButton.getAttribute('data-application-id') || '').trim()).catch(console.error);
            }
        });

        if (closeButton) {
            closeButton.addEventListener('click', closeModal);
        }
        if (cancelButton) {
            cancelButton.addEventListener('click', closeModal);
        }
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        applicantModalCloseButtons.forEach((button) => {
            button.addEventListener('click', closeApplicantModal);
        });

        if (applicantModal) {
            applicantModal.addEventListener('click', (event) => {
                if (event.target === applicantModal) {
                    closeApplicantModal();
                }
            });
        }
    };

    const setupAddEmployeeForms = () => {
        document.addEventListener('submit', async (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.classList.contains('add-employee-form')) {
                return;
            }

            if (form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const applicantName = (form.getAttribute('data-applicant-name') || 'this hired applicant').trim();
            const shouldContinue = await showConfirmation(
                `Add "${applicantName}" as an employee now?`,
                'Add as Employee'
            );
            if (!shouldContinue) {
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

            form.dataset.confirmed = '1';
            form.submit();
        });
    };

    const setupUnarchiveForms = () => {
        const forms = Array.from(document.querySelectorAll('.recruitment-unarchive-form'));
        forms.forEach((form) => {
            form.addEventListener('submit', async (event) => {
                if (form.dataset.confirmed === '1') {
                    return;
                }

                event.preventDefault();
                const positionTitle = (form.getAttribute('data-position-title') || 'this position').trim();
                const shouldContinue = await showConfirmation(
                    `Unarchive "${positionTitle}" and restore it to active job listings?`,
                    'Unarchive Job Posting'
                );
                if (!shouldContinue) {
                    return;
                }

                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-60', 'cursor-not-allowed');
                }

                form.dataset.confirmed = '1';
                form.submit();
            });
        });
    };

    showFlashMessage();
    initDatePickers();
    setupPostingStatusModal();
    setupCreatePostingModal();
    setupPostingViewModal();
    setupAddEmployeeForms();

    if (!initAsyncRecruitmentPage()) {
        setupUnarchiveForms();
    }
})();