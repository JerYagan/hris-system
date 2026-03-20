document.addEventListener('DOMContentLoaded', () => {
    const asyncRegion = document.getElementById('staffEvaluationAsyncRegion');
    const listSkeleton = document.getElementById('staffEvaluationListSkeleton');
    const listError = document.getElementById('staffEvaluationListError');
    const listRetry = document.getElementById('staffEvaluationListRetry');
    const listContent = document.getElementById('staffEvaluationListContent');
    const modal = document.getElementById('ruleEvaluationDetailModal');
    const closeButtons = Array.from(document.querySelectorAll('[data-evaluation-modal-close="ruleEvaluationDetailModal"]'));
    const applicantNameLabel = document.getElementById('ruleEvaluationDetailApplicantName');
    const applicantMetaLabel = document.getElementById('ruleEvaluationDetailMeta');
    const applicantContactLabel = document.getElementById('ruleEvaluationDetailContact');
    const criteriaList = document.getElementById('ruleEvaluationCriteriaList');
    const scoreList = document.getElementById('ruleEvaluationScoreList');
    const recommendationBox = document.getElementById('ruleEvaluationRecommendation');
    const evidenceBox = document.getElementById('ruleEvaluationEvidence');
    const interviewBox = document.getElementById('ruleEvaluationInterview');
    const qualifiedCount = document.getElementById('staffEvaluationQualifiedCount');
    const notQualifiedCount = document.getElementById('staffEvaluationNotQualifiedCount');
    const totalCount = document.getElementById('staffEvaluationTotalCount');
    const summaryState = document.getElementById('staffEvaluationSummaryState');

    if (!asyncRegion || !listContent || !modal || !criteriaList || !scoreList || !recommendationBox || !evidenceBox || !interviewBox) {
        return;
    }

    const flashNode = document.getElementById('evaluationFlashState');
    const listUrl = asyncRegion.dataset.evaluationListUrl || 'evaluation.php?partial=list';
    const detailUrl = asyncRegion.dataset.evaluationDetailUrl || 'evaluation.php?partial=detail';

    let activeListAbortController = null;
    let activeDetailAbortController = null;
    let searchDebounceTimer = null;
    let evaluationState = {
        page: 1,
        search: '',
        status: '',
        position: '',
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

    const setListState = ({ loading = false, error = false }) => {
        listSkeleton?.classList.toggle('hidden', !loading);
        listContent.classList.toggle('hidden', loading || error);
        listError?.classList.toggle('hidden', !error);
    };

    const setSummaryState = ({ qualified = '--', notQualified = '--', total = '--', message = '' }) => {
        if (qualifiedCount) {
            qualifiedCount.textContent = String(qualified);
        }
        if (notQualifiedCount) {
            notQualifiedCount.textContent = String(notQualified);
        }
        if (totalCount) {
            totalCount.textContent = String(total);
        }
        if (summaryState) {
            summaryState.textContent = message;
        }
    };

    const hydrateSummaryFromList = () => {
        const summaryNode = listContent.querySelector('[data-evaluation-summary]');
        if (!summaryNode) {
            return;
        }

        setSummaryState({
            qualified: summaryNode.getAttribute('data-qualified') || '0',
            notQualified: summaryNode.getAttribute('data-not-qualified') || '0',
            total: summaryNode.getAttribute('data-total') || '0',
            message: 'Summary hydrated from the deferred scoring queue.',
        });
    };

    const resetModal = () => {
        applicantNameLabel.textContent = '-';
        applicantMetaLabel.textContent = '-';
        applicantContactLabel.textContent = '-';
        criteriaList.innerHTML = '<p class="text-slate-500">No candidate selected.</p>';
        scoreList.innerHTML = '<p class="text-slate-500">No candidate selected.</p>';
        recommendationBox.innerHTML = '<p class="text-slate-500">No recommendation detail loaded.</p>';
        evidenceBox.innerHTML = '<p class="text-slate-500">No evidence loaded.</p>';
        interviewBox.innerHTML = '<p class="text-slate-500">No interview detail loaded.</p>';
    };

    const openModal = () => {
        modal.classList.remove('hidden');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        resetModal();
    };

    const renderCriteria = (payload) => {
        const criteria = payload.criteria || {};
        const profile = payload.profile || {};
        const criteriaMet = payload.criteria_met || {};

        const rows = [
            ['Eligibility', profile.eligibility || 'n/a', criteria.eligibility || 'n/a', Boolean(criteriaMet.eligibility)],
            ['Education', `${profile.education_years || 0} yrs`, `${criteria.education_years || 0} yrs`, Boolean(criteriaMet.education)],
            ['Training', `${profile.training_hours || 0} hrs`, `${criteria.training_hours || 0} hrs`, Boolean(criteriaMet.training)],
            ['Experience', `${profile.experience_years || 0} yrs`, `${criteria.experience_years || 0} yrs`, Boolean(criteriaMet.experience)],
        ];

        criteriaList.innerHTML = rows.map(([label, actual, required, met]) => `
            <article class="rounded-lg border border-slate-200 bg-white p-3">
                <div class="flex items-center justify-between gap-3">
                    <p class="font-medium text-slate-800">${escapeHtml(label)}</p>
                    <span class="px-2 py-1 text-xs rounded-full ${met ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}">${met ? 'Met' : 'Missing'}</span>
                </div>
                <p class="mt-2 text-slate-700">Candidate: ${escapeHtml(actual)}</p>
                <p class="mt-1 text-slate-500">Required: ${escapeHtml(required)}</p>
            </article>
        `).join('');
    };

    const renderScores = (payload) => {
        const scores = payload.scores || {};
        const failedCriteria = Array.isArray(payload.failed_criteria) ? payload.failed_criteria : [];

        scoreList.innerHTML = `
            <article class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="font-medium text-slate-800">Eligibility Score</p>
                <p class="mt-2 text-slate-700">${escapeHtml(scores.eligibility || 0)}%</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="font-medium text-slate-800">Education Score</p>
                <p class="mt-2 text-slate-700">${escapeHtml(scores.education || 0)}%</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="font-medium text-slate-800">Training Score</p>
                <p class="mt-2 text-slate-700">${escapeHtml(scores.training || 0)}%</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="font-medium text-slate-800">Experience Score</p>
                <p class="mt-2 text-slate-700">${escapeHtml(scores.experience || 0)}%</p>
            </article>
            <article class="rounded-lg border border-slate-200 bg-slate-50 p-3 md:col-span-2">
                <p class="font-medium text-slate-800">Total Score</p>
                <p class="mt-2 text-slate-700">${escapeHtml(payload.total_score || 0)}% of ${escapeHtml(payload.threshold || 75)}%</p>
                <p class="mt-1 text-slate-500">${failedCriteria.length > 0 ? `Failed criteria: ${escapeHtml(failedCriteria.join(', '))}` : 'All required criteria were satisfied.'}</p>
            </article>
        `;
    };

    const renderRecommendation = (payload) => {
        const feedback = payload.latest_feedback || {};
        recommendationBox.innerHTML = `
            <p class="font-medium text-slate-800">${escapeHtml(feedback.decision_label || payload.status_label || 'No recommendation')}</p>
            <p class="mt-2 text-slate-700">${escapeHtml(feedback.feedback_text || 'No recommendation note recorded.')}</p>
            <p class="mt-2 text-xs text-slate-500">Provided ${escapeHtml(feedback.provided_label || '-')} by ${escapeHtml(feedback.provider_email || '-')}</p>
        `;
    };

    const renderEvidence = (payload) => {
        const evidence = payload.evidence_signals || {};
        const documents = Array.isArray(evidence.documents) ? evidence.documents : [];
        evidenceBox.innerHTML = `
            <p class="text-slate-700">Resume: ${escapeHtml(evidence.resume_available ? 'Available' : 'Unavailable')}</p>
            <p class="mt-1 text-slate-700">Portfolio: ${escapeHtml(evidence.portfolio_available ? 'Available' : 'Unavailable')}</p>
            <p class="mt-2 text-slate-500">Supporting documents: ${documents.length > 0 ? escapeHtml(documents.join(', ')) : 'None recorded'}</p>
        `;
    };

    const renderInterview = (payload) => {
        const interview = payload.latest_interview || {};
        interviewBox.innerHTML = `
            <p class="font-medium text-slate-800">${escapeHtml(interview.result_label || 'Pending')}</p>
            <p class="mt-2 text-slate-700">Stage: ${escapeHtml(interview.stage || '-')}</p>
            <p class="mt-1 text-slate-700">Scheduled: ${escapeHtml(interview.scheduled_label || '-')}</p>
            <p class="mt-1 text-slate-700">Score: ${escapeHtml(interview.score || '-')}</p>
            <p class="mt-1 text-slate-700">Remarks: ${escapeHtml(interview.remarks || '-')}</p>
            <p class="mt-2 text-xs text-slate-500">Interviewer: ${escapeHtml(interview.interviewer_email || '-')}</p>
        `;
    };

    const renderDetail = (payload) => {
        applicantNameLabel.textContent = payload.applicant_name || 'Candidate';
        applicantMetaLabel.textContent = `${payload.position_title || '-'} • ${payload.application_status_label || '-'} • ${payload.status_label || '-'} • Ref ${payload.application_ref_no || '-'}`;
        applicantContactLabel.textContent = `${payload.applicant_email || '-'} • Submitted ${payload.submitted_at_label || '-'}`;

        renderCriteria(payload);
        renderScores(payload);
        renderRecommendation(payload);
        renderEvidence(payload);
        renderInterview(payload);
    };

    const buildListUrl = () => {
        const requestUrl = new URL(listUrl, window.location.href);
        requestUrl.searchParams.set('rule_page', String(evaluationState.page));
        if (evaluationState.search !== '') {
            requestUrl.searchParams.set('rule_search', evaluationState.search);
        }
        if (evaluationState.status !== '') {
            requestUrl.searchParams.set('rule_status', evaluationState.status);
        }
        if (evaluationState.position !== '') {
            requestUrl.searchParams.set('position_title', evaluationState.position);
        }

        return requestUrl.toString();
    };

    const bindListInteractions = () => {
        const filtersForm = listContent.querySelector('#ruleEvaluationFilters');
        const pageInput = listContent.querySelector('#ruleEvaluationPageInput');
        const searchInput = listContent.querySelector('#ruleEvalSearchInput');
        const statusFilter = listContent.querySelector('#ruleEvalStatusFilter');
        const positionFilter = listContent.querySelector('#ruleEvalPositionFilter');
        const refreshButton = listContent.querySelector('[data-evaluation-refresh="list"]');
        const paginationButtons = Array.from(listContent.querySelectorAll('[data-rule-evaluation-page]'));
        const detailButtons = Array.from(listContent.querySelectorAll('[data-open-rule-evaluation-detail]'));

        const submitFilters = () => {
            evaluationState.page = 1;
            evaluationState.search = (searchInput?.value || '').trim();
            evaluationState.status = (statusFilter?.value || '').trim();
            evaluationState.position = (positionFilter?.value || '').trim();
            if (pageInput) {
                pageInput.value = '1';
            }
            void loadList();
        };

        searchInput?.addEventListener('input', () => {
            if (searchDebounceTimer !== null) {
                window.clearTimeout(searchDebounceTimer);
            }
            searchDebounceTimer = window.setTimeout(submitFilters, 180);
        });

        statusFilter?.addEventListener('change', submitFilters);
        positionFilter?.addEventListener('change', submitFilters);

        paginationButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled) {
                    return;
                }

                const page = Number.parseInt(button.dataset.ruleEvaluationPage || '1', 10);
                if (!Number.isFinite(page) || page < 1) {
                    return;
                }

                evaluationState.page = page;
                evaluationState.search = (searchInput?.value || '').trim();
                evaluationState.status = (statusFilter?.value || '').trim();
                evaluationState.position = (positionFilter?.value || '').trim();
                if (pageInput) {
                    pageInput.value = String(page);
                }
                void loadList();
            });
        });

        detailButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const applicationId = (button.dataset.applicationId || '').trim();
                if (applicationId === '') {
                    return;
                }

                void loadDetail(applicationId);
            });
        });

        refreshButton?.addEventListener('click', () => {
            evaluationState.search = (searchInput?.value || '').trim();
            evaluationState.status = (statusFilter?.value || '').trim();
            evaluationState.position = (positionFilter?.value || '').trim();
            void loadList();
        });

        filtersForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            submitFilters();
        });
    };

    const loadList = async () => {
        activeListAbortController?.abort();
        activeListAbortController = new AbortController();
        setListState({ loading: true, error: false });
        setSummaryState({ message: 'Loading deferred evaluation summary...' });

        try {
            const response = await fetch(buildListUrl(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeListAbortController.signal,
            });

            if (!response.ok) {
                throw new Error(`Evaluation list request failed with status ${response.status}`);
            }

            listContent.innerHTML = await response.text();
            hydrateSummaryFromList();
            setListState({ loading: false, error: false });
            bindListInteractions();
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }

            setSummaryState({ message: 'Evaluation summary is unavailable right now.' });
            setListState({ loading: false, error: true });
        }
    };

    const loadDetail = async (applicationId) => {
        activeDetailAbortController?.abort();
        activeDetailAbortController = new AbortController();

        resetModal();
        applicantNameLabel.textContent = 'Loading candidate detail...';
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
                throw new Error(payload?.error || `Evaluation detail request failed with status ${response.status}`);
            }

            renderDetail(payload);
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }

            applicantNameLabel.textContent = 'Candidate detail could not be loaded.';
            applicantMetaLabel.textContent = error instanceof Error ? error.message : 'Unexpected detail error.';
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

    listRetry?.addEventListener('click', () => {
        void loadList();
    });

    showFlashMessage();
    resetModal();
    void loadList();
});
