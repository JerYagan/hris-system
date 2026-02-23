(() => {
    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const searchInput = document.getElementById('evaluationSearchInput');
    const statusFilter = document.getElementById('evaluationStatusFilter');
    const rows = Array.from(document.querySelectorAll('[data-evaluation-row]'));
    const emptyRow = document.getElementById('evaluationFilterEmptyRow');

    const ruleSearchInput = document.getElementById('ruleEvalSearchInput');
    const ruleStatusFilter = document.getElementById('ruleEvalStatusFilter');
    const rulePositionFilter = document.getElementById('ruleEvalPositionFilter');
    const ruleRows = Array.from(document.querySelectorAll('[data-rule-eval-row]'));
    const ruleEmptyRow = document.getElementById('ruleEvalFilterEmptyRow');

    const applicantFinalEvalModal = document.getElementById('applicantFinalEvalModal');
    const applicantFinalEvalOpenButtons = Array.from(document.querySelectorAll('[data-open-applicant-final-eval]'));
    const applicantFinalEvalClose = document.getElementById('applicantFinalEvalClose');
    const applicantFinalEvalCancel = document.getElementById('applicantFinalEvalCancel');
    const applicantFinalEvalForm = document.getElementById('applicantFinalEvalForm');
    const appFinalEvalSubmit = document.getElementById('appFinalEvalSubmit');

    const appFinalEvalApplicationId = document.getElementById('appFinalEvalApplicationId');
    const appFinalEvalApplicantName = document.getElementById('appFinalEvalApplicantName');
    const appFinalEvalPositionTitle = document.getElementById('appFinalEvalPositionTitle');
    const appFinalEvalCurrentStatus = document.getElementById('appFinalEvalCurrentStatus');
    const appFinalEvalInterviewResult = document.getElementById('appFinalEvalInterviewResult');
    const appFinalEvalInterviewScore = document.getElementById('appFinalEvalInterviewScore');
    const appFinalEvalRecommendation = document.getElementById('appFinalEvalRecommendation');
    const appFinalEvalRemarks = document.getElementById('appFinalEvalRemarks');

    const showConfirm = async (title, text) => {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            const result = await window.Swal.fire({
                title,
                text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, submit',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            });
            return Boolean(result && result.isConfirmed);
        }

        return window.confirm(text);
    };

    const modal = document.getElementById('evaluationReviewModal');
    const modalOpenButtons = Array.from(document.querySelectorAll('[data-open-evaluation-modal]'));
    const modalCloseButton = document.getElementById('evaluationModalClose');
    const modalCancelButton = document.getElementById('evaluationModalCancel');
    const reviewForm = document.getElementById('evaluationReviewForm');
    const submitButton = document.getElementById('evaluationSubmit');

    const evaluationIdInput = document.getElementById('evaluationIdInput');
    const employeeLabel = document.getElementById('evaluationEmployeeLabel');
    const cycleLabel = document.getElementById('evaluationCycleLabel');
    const currentStatusLabel = document.getElementById('evaluationCurrentStatusLabel');
    const decisionSelect = document.getElementById('evaluationDecisionSelect');

    const applyFilters = () => {
        if (!searchInput || !statusFilter || rows.length === 0) {
            return;
        }

        const query = normalize(searchInput.value);
        const status = normalize(statusFilter.value);
        let visibleCount = 0;

        rows.forEach((row) => {
            const rowSearch = normalize(row.getAttribute('data-evaluation-search'));
            const rowStatus = normalize(row.getAttribute('data-evaluation-status'));
            const visible = (query === '' || rowSearch.includes(query)) && (status === '' || rowStatus === status);

            row.classList.toggle('hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        if (emptyRow) {
            emptyRow.classList.toggle('hidden', visibleCount > 0);
        }
    };

    if (searchInput && statusFilter && rows.length > 0) {
        let debounceTimer = null;
        searchInput.addEventListener('input', () => {
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(applyFilters, 150);
        });
        statusFilter.addEventListener('change', applyFilters);
        applyFilters();
    }

    const applyRuleFilters = () => {
        if (ruleRows.length === 0) {
            return;
        }

        const query = normalize(ruleSearchInput ? ruleSearchInput.value : '');
        const status = normalize(ruleStatusFilter ? ruleStatusFilter.value : '');
        const position = normalize(rulePositionFilter ? rulePositionFilter.value : '');
        let visibleCount = 0;

        ruleRows.forEach((row) => {
            const rowSearch = normalize(row.getAttribute('data-rule-eval-search'));
            const rowStatus = normalize(row.getAttribute('data-rule-eval-status'));
            const rowPosition = normalize(row.getAttribute('data-rule-eval-position'));

            const visible = (query === '' || rowSearch.includes(query))
                && (status === '' || rowStatus === status)
                && (position === '' || rowPosition === position);

            row.classList.toggle('hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        if (ruleEmptyRow) {
            ruleEmptyRow.classList.toggle('hidden', visibleCount > 0);
        }
    };

    if (ruleRows.length > 0) {
        let ruleDebounceTimer = null;
        if (ruleSearchInput) {
            ruleSearchInput.addEventListener('input', () => {
                if (ruleDebounceTimer) {
                    window.clearTimeout(ruleDebounceTimer);
                }
                ruleDebounceTimer = window.setTimeout(applyRuleFilters, 150);
            });
        }

        if (ruleStatusFilter) {
            ruleStatusFilter.addEventListener('change', applyRuleFilters);
        }
        if (rulePositionFilter) {
            rulePositionFilter.addEventListener('change', applyRuleFilters);
        }

        applyRuleFilters();
    }

    const closeApplicantFinalEvalModal = () => {
        if (!applicantFinalEvalModal) {
            return;
        }

        applicantFinalEvalModal.classList.add('hidden');
        applicantFinalEvalModal.classList.remove('flex');

        if (applicantFinalEvalForm) {
            applicantFinalEvalForm.reset();
            applicantFinalEvalForm.dataset.confirmed = '0';
        }

        if (appFinalEvalSubmit) {
            appFinalEvalSubmit.disabled = false;
            appFinalEvalSubmit.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    };

    const openApplicantFinalEvalModal = (button) => {
        if (
            !applicantFinalEvalModal
            || !appFinalEvalApplicationId
            || !appFinalEvalApplicantName
            || !appFinalEvalPositionTitle
            || !appFinalEvalCurrentStatus
            || !appFinalEvalInterviewResult
            || !appFinalEvalInterviewScore
            || !appFinalEvalRecommendation
            || !appFinalEvalRemarks
        ) {
            return;
        }

        appFinalEvalApplicationId.value = button.getAttribute('data-application-id') || '';
        appFinalEvalApplicantName.value = button.getAttribute('data-applicant-name') || 'Applicant';
        appFinalEvalPositionTitle.value = button.getAttribute('data-position-title') || '-';
        appFinalEvalCurrentStatus.value = button.getAttribute('data-current-status-label') || '-';

        const interviewResult = normalize(button.getAttribute('data-interview-result') || 'pending');
        appFinalEvalInterviewResult.value = ['pass', 'fail', 'pending'].includes(interviewResult) ? interviewResult : 'pending';
        appFinalEvalInterviewScore.value = (button.getAttribute('data-interview-score') || '').trim();

        const feedbackDecision = normalize(button.getAttribute('data-feedback-decision') || '');
        appFinalEvalRecommendation.value = feedbackDecision === 'on_hold'
            ? 'not_recommended'
            : 'recommend_for_approval';

        appFinalEvalRemarks.value = (button.getAttribute('data-feedback-text') || '').trim();

        applicantFinalEvalModal.classList.remove('hidden');
        applicantFinalEvalModal.classList.add('flex');
    };

    applicantFinalEvalOpenButtons.forEach((button) => {
        button.addEventListener('click', () => openApplicantFinalEvalModal(button));
    });

    if (applicantFinalEvalClose) {
        applicantFinalEvalClose.addEventListener('click', closeApplicantFinalEvalModal);
    }
    if (applicantFinalEvalCancel) {
        applicantFinalEvalCancel.addEventListener('click', closeApplicantFinalEvalModal);
    }

    if (applicantFinalEvalModal) {
        applicantFinalEvalModal.addEventListener('click', (event) => {
            if (event.target === applicantFinalEvalModal) {
                closeApplicantFinalEvalModal();
            }
        });
    }

    if (applicantFinalEvalForm) {
        applicantFinalEvalForm.addEventListener('submit', async (event) => {
            if (applicantFinalEvalForm.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const applicantName = appFinalEvalApplicantName ? appFinalEvalApplicantName.value || 'Applicant' : 'Applicant';
            const shouldProceed = await showConfirm(
                'Submit Final Evaluation',
                `Submit final evaluation for ${applicantName} to admin approval queue?`
            );
            if (!shouldProceed) {
                return;
            }

            if (appFinalEvalSubmit) {
                appFinalEvalSubmit.disabled = true;
                appFinalEvalSubmit.classList.add('opacity-60', 'cursor-not-allowed');
            }

            applicantFinalEvalForm.dataset.confirmed = '1';
            applicantFinalEvalForm.submit();
        });
    }

    const closeModal = () => {
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (reviewForm) {
            reviewForm.reset();
        }
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    };

    const openModal = (button) => {
        if (!modal || !evaluationIdInput) {
            return;
        }

        const evaluationId = button.getAttribute('data-evaluation-id') || '';
        const employeeName = button.getAttribute('data-employee-name') || '-';
        const cycleName = button.getAttribute('data-cycle-name') || '-';
        const currentStatus = button.getAttribute('data-current-status') || '';
        const currentStatusLabelText = button.getAttribute('data-current-status-label') || '-';

        evaluationIdInput.value = evaluationId;
        if (employeeLabel) {
            employeeLabel.textContent = employeeName;
        }
        if (cycleLabel) {
            cycleLabel.textContent = cycleName;
        }
        if (currentStatusLabel) {
            currentStatusLabel.textContent = currentStatusLabelText;
        }
        if (decisionSelect) {
            decisionSelect.value = currentStatus;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    modalOpenButtons.forEach((button) => {
        button.addEventListener('click', () => openModal(button));
    });

    if (modalCloseButton) {
        modalCloseButton.addEventListener('click', closeModal);
    }
    if (modalCancelButton) {
        modalCancelButton.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (reviewForm) {
        reviewForm.addEventListener('submit', (event) => {
            const employee = employeeLabel ? employeeLabel.textContent || 'Employee' : 'Employee';
            const oldStatus = currentStatusLabel ? currentStatusLabel.textContent || '-' : '-';
            const decision = normalize(decisionSelect ? decisionSelect.value : '');

            if (decision === '') {
                return;
            }

            const decisionLabel = decision.replaceAll('_', ' ');
            const shouldContinue = window.confirm(`Confirm evaluation status change for "${employee}" from "${oldStatus}" to "${decisionLabel}"?`);
            if (!shouldContinue) {
                event.preventDefault();
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }
        });
    }
})();
