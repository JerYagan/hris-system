document.addEventListener('DOMContentLoaded', () => {
    const asyncRegion = document.getElementById('staffApplicantTrackingAsyncRegion');
    const postingsSkeleton = document.getElementById('staffApplicantTrackingPostingsSkeleton');
    const postingsError = document.getElementById('staffApplicantTrackingPostingsError');
    const postingsRetry = document.getElementById('staffApplicantTrackingPostingsRetry');
    const postingsContent = document.getElementById('staffApplicantTrackingPostingsContent');
    const applicantsSkeleton = document.getElementById('staffApplicantTrackingApplicantsSkeleton');
    const applicantsError = document.getElementById('staffApplicantTrackingApplicantsError');
    const applicantsRetry = document.getElementById('staffApplicantTrackingApplicantsRetry');
    const applicantsContent = document.getElementById('staffApplicantTrackingApplicantsContent');
    const modal = document.getElementById('trackingProfileModal');
    const closeButtons = Array.from(document.querySelectorAll('[data-tracking-modal-close="trackingProfileModal"]'));
    const applicationIdInput = document.getElementById('trackingProfileApplicationId');
    const applicantNameLabel = document.getElementById('trackingProfileApplicantName');
    const applicantMetaLabel = document.getElementById('trackingProfileApplicantMeta');
    const applicantContactLabel = document.getElementById('trackingProfileApplicantContact');
    const applicationRefLabel = document.getElementById('trackingProfileApplicationRef');
    const documentsBody = document.getElementById('trackingProfileDocumentsBody');
    const feedbackList = document.getElementById('trackingProfileFeedbackList');
    const interviewList = document.getElementById('trackingProfileInterviewList');

    if (!asyncRegion || !postingsContent || !applicantsContent || !modal || !documentsBody || !feedbackList || !interviewList) {
        return;
    }

    const flashNode = document.getElementById('trackingFlashState');
    const postingsUrl = asyncRegion.dataset.trackingPostingsUrl || 'applicant-tracking.php?partial=tracking-postings';
    const applicantsUrl = asyncRegion.dataset.trackingApplicantsUrl || 'applicant-tracking.php?partial=tracking-applicants';
    const detailUrl = asyncRegion.dataset.trackingDetailUrl || 'applicant-tracking.php?partial=tracking-detail';

    let activePostingsAbortController = null;
    let activeApplicantsAbortController = null;
    let activeDetailAbortController = null;
    let searchDebounceTimer = null;
    let trackingState = {
        postingId: '',
        postingPage: 1,
        applicantPage: 1,
        search: '',
        status: '',
    };

    const normalize = (value) => (value || '').toString().trim().toLowerCase();
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const showFlashMessage = () => {
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

    const setSectionState = ({ skeleton, content, errorBox }, { loading = false, error = false }) => {
        skeleton?.classList.toggle('hidden', !loading);
        content.classList.toggle('hidden', loading || error);
        errorBox?.classList.toggle('hidden', !error);
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
        documentsBody.innerHTML = '<tr><td class="px-3 py-3 text-slate-500" colspan="4">No document selected.</td></tr>';
        feedbackList.innerHTML = '<p class="text-slate-500">No feedback selected.</p>';
        interviewList.innerHTML = '<p class="text-slate-500">No interview history selected.</p>';
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
            documentsBody.innerHTML = '<tr><td class="px-3 py-3 text-slate-500" colspan="4">No submitted documents found.</td></tr>';
            return;
        }

        documentsBody.innerHTML = documents.map((documentItem) => {
            const documentLabel = escapeHtml(documentItem?.document_label || 'Document');
            const fileName = escapeHtml(documentItem?.file_name || '-');
            const uploadedLabel = escapeHtml(documentItem?.uploaded_label || '-');
            const previewUrl = escapeHtml(documentItem?.preview_url || '');
            const downloadUrl = escapeHtml(documentItem?.download_url || documentItem?.preview_url || '');
            const hasFile = Boolean(documentItem?.is_available && previewUrl !== '');

            return `
                <tr>
                    <td class="px-3 py-2 text-slate-700">${documentLabel}</td>
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

    const renderFeedback = (entries) => {
        if (!Array.isArray(entries) || entries.length === 0) {
            feedbackList.innerHTML = '<p class="text-slate-500">No feedback history recorded.</p>';
            return;
        }

        feedbackList.innerHTML = entries.map((entry) => `
            <article class="rounded-lg border border-slate-200 p-3">
                <p class="font-medium text-slate-800">${escapeHtml(entry?.decision_label || '-')}</p>
                <p class="mt-1 text-slate-700">${escapeHtml(entry?.feedback_text || '-')}</p>
                <p class="mt-2 text-xs text-slate-500">${escapeHtml(entry?.provided_label || '-')} • ${escapeHtml(entry?.provider_email || '-')}</p>
            </article>
        `).join('');
    };

    const renderInterviews = (entries) => {
        if (!Array.isArray(entries) || entries.length === 0) {
            interviewList.innerHTML = '<p class="text-slate-500">No interview history recorded.</p>';
            return;
        }

        interviewList.innerHTML = entries.map((entry) => `
            <article class="rounded-lg border border-slate-200 p-3">
                <p class="font-medium text-slate-800">${escapeHtml(entry?.stage_label || 'Interview')}</p>
                <p class="mt-1 text-slate-700">${escapeHtml(entry?.scheduled_label || '-')} • ${escapeHtml(entry?.result_label || 'Pending')}</p>
                <p class="mt-1 text-slate-700">${escapeHtml(entry?.remarks || '-')}</p>
                <p class="mt-2 text-xs text-slate-500">Score: ${escapeHtml(entry?.score || '-')} • ${escapeHtml(entry?.interviewer_email || '-')}</p>
            </article>
        `).join('');
    };

    const renderProfile = (payload) => {
        if (applicationIdInput) {
            applicationIdInput.value = payload.application_id || '';
        }
        if (applicantNameLabel) {
            applicantNameLabel.textContent = payload.applicant_name || 'Applicant';
        }
        if (applicantMetaLabel) {
            applicantMetaLabel.textContent = `${payload.posting_title || '-'} • Submitted ${payload.submitted_label || '-'} • ${payload.current_stage_label || '-'} • ${payload.status_label || '-'}`;
        }
        if (applicantContactLabel) {
            applicantContactLabel.textContent = `Email: ${payload.applicant_email || '-'} • Mobile: ${payload.applicant_mobile || '-'} • Address: ${payload.applicant_address || '-'}`;
        }
        if (applicationRefLabel) {
            applicationRefLabel.textContent = `Application Ref: ${payload.application_ref_no || '-'}`;
        }

        renderDocuments(payload.documents || []);
        renderFeedback(payload.feedback || []);
        renderInterviews(payload.interviews || []);
    };

    const buildApplicantsUrl = () => {
        const requestUrl = new URL(applicantsUrl, window.location.href);
        requestUrl.searchParams.set('tracking_page', String(trackingState.applicantPage));
        if (trackingState.postingId !== '') {
            requestUrl.searchParams.set('posting_id', trackingState.postingId);
        }
        if (trackingState.search !== '') {
            requestUrl.searchParams.set('search', trackingState.search);
        }
        if (trackingState.status !== '') {
            requestUrl.searchParams.set('status', trackingState.status);
        }

        return requestUrl.toString();
    };

    const bindPostingsInteractions = () => {
        const postingButtons = Array.from(postingsContent.querySelectorAll('[data-tracking-posting-id]'));
        const paginationButtons = Array.from(postingsContent.querySelectorAll('[data-tracking-postings-page]'));
        const refreshButton = postingsContent.querySelector('[data-tracking-refresh="postings"]');

        postingButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const postingId = (button.dataset.trackingPostingId || '').trim();
                trackingState.postingId = postingId;
                trackingState.applicantPage = 1;
                void Promise.all([loadPostings(), loadApplicants()]);
            });
        });

        paginationButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled) {
                    return;
                }

                const page = Number.parseInt(button.dataset.trackingPostingsPage || '1', 10);
                if (!Number.isFinite(page) || page < 1) {
                    return;
                }

                trackingState.postingPage = page;
                void loadPostings();
            });
        });

        refreshButton?.addEventListener('click', () => {
            void loadPostings();
        });
    };

    const bindApplicantsInteractions = () => {
        const filtersForm = applicantsContent.querySelector('#trackingApplicantsFilters');
        const pageInput = applicantsContent.querySelector('#trackingApplicantsPageInput');
        const postingInput = applicantsContent.querySelector('#trackingApplicantsPostingInput');
        const searchInput = applicantsContent.querySelector('#trackingApplicantsSearchInput');
        const statusFilter = applicantsContent.querySelector('#trackingApplicantsStatusFilter');
        const clearPostingButton = applicantsContent.querySelector('#trackingClearPostingFilter');
        const paginationButtons = Array.from(applicantsContent.querySelectorAll('[data-tracking-applicants-page]'));
        const profileButtons = Array.from(applicantsContent.querySelectorAll('[data-open-tracking-profile]'));
        const refreshButton = applicantsContent.querySelector('[data-tracking-refresh="applicants"]');

        if (postingInput) {
            postingInput.value = trackingState.postingId;
        }

        const submitFilters = () => {
            trackingState.applicantPage = 1;
            trackingState.search = (searchInput?.value || '').trim();
            trackingState.status = (statusFilter?.value || '').trim();
            if (pageInput) {
                pageInput.value = '1';
            }
            void loadApplicants();
        };

        searchInput?.addEventListener('input', () => {
            if (searchDebounceTimer !== null) {
                window.clearTimeout(searchDebounceTimer);
            }

            searchDebounceTimer = window.setTimeout(submitFilters, 180);
        });

        statusFilter?.addEventListener('change', submitFilters);

        clearPostingButton?.addEventListener('click', () => {
            trackingState.postingId = '';
            trackingState.applicantPage = 1;
            void Promise.all([loadPostings(), loadApplicants()]);
        });

        paginationButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled) {
                    return;
                }

                const page = Number.parseInt(button.dataset.trackingApplicantsPage || '1', 10);
                if (!Number.isFinite(page) || page < 1) {
                    return;
                }

                trackingState.applicantPage = page;
                trackingState.search = (searchInput?.value || '').trim();
                trackingState.status = (statusFilter?.value || '').trim();
                if (pageInput) {
                    pageInput.value = String(page);
                }
                void loadApplicants();
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

        refreshButton?.addEventListener('click', () => {
            trackingState.search = (searchInput?.value || '').trim();
            trackingState.status = (statusFilter?.value || '').trim();
            void loadApplicants();
        });

        filtersForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            submitFilters();
        });
    };

    const loadPostings = async () => {
        activePostingsAbortController?.abort();
        activePostingsAbortController = new AbortController();
        setSectionState({ skeleton: postingsSkeleton, content: postingsContent, errorBox: postingsError }, { loading: true, error: false });

        const requestUrl = new URL(postingsUrl, window.location.href);
        requestUrl.searchParams.set('tracking_postings_page', String(trackingState.postingPage));
        if (trackingState.postingId !== '') {
            requestUrl.searchParams.set('posting_id', trackingState.postingId);
        }

        try {
            const response = await fetch(requestUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activePostingsAbortController.signal,
            });

            if (!response.ok) {
                throw new Error(`Postings request failed with status ${response.status}`);
            }

            postingsContent.innerHTML = await response.text();
            setSectionState({ skeleton: postingsSkeleton, content: postingsContent, errorBox: postingsError }, { loading: false, error: false });
            bindPostingsInteractions();
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }

            setSectionState({ skeleton: postingsSkeleton, content: postingsContent, errorBox: postingsError }, { loading: false, error: true });
        }
    };

    const loadApplicants = async () => {
        activeApplicantsAbortController?.abort();
        activeApplicantsAbortController = new AbortController();
        setSectionState({ skeleton: applicantsSkeleton, content: applicantsContent, errorBox: applicantsError }, { loading: true, error: false });

        try {
            const response = await fetch(buildApplicantsUrl(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeApplicantsAbortController.signal,
            });

            if (!response.ok) {
                throw new Error(`Applicants request failed with status ${response.status}`);
            }

            applicantsContent.innerHTML = await response.text();
            setSectionState({ skeleton: applicantsSkeleton, content: applicantsContent, errorBox: applicantsError }, { loading: false, error: false });
            bindApplicantsInteractions();
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }

            setSectionState({ skeleton: applicantsSkeleton, content: applicantsContent, errorBox: applicantsError }, { loading: false, error: true });
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
                throw new Error(payload?.error || `Tracking detail request failed with status ${response.status}`);
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
                applicantMetaLabel.textContent = 'The selected applicant history could not be loaded right now.';
            }
            documentsBody.innerHTML = '<tr><td class="px-3 py-3 text-amber-700" colspan="4">Applicant documents could not be loaded right now.</td></tr>';
            feedbackList.innerHTML = '<p class="text-amber-700">Feedback history could not be loaded right now.</p>';
            interviewList.innerHTML = '<p class="text-amber-700">Interview history could not be loaded right now.</p>';
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

    postingsRetry?.addEventListener('click', () => {
        void loadPostings();
    });

    applicantsRetry?.addEventListener('click', () => {
        void loadApplicants();
    });

    showFlashMessage();
    resetModal();
    void Promise.all([loadPostings(), loadApplicants()]);
});