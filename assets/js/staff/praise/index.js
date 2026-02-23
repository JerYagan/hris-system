(() => {
    const PAGE_SIZE = 10;
    const normalize = (value) => (value || '').toString().trim().toLowerCase();
    const debounce = (fn, wait = 200) => {
        let timer = null;
        return (...args) => {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(() => fn(...args), wait);
        };
    };

    const toTitleLabel = (value) => value.toString().replace(/_/g, ' ').trim();

    const confirmAction = async ({ title, text, confirmButtonText }) => {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            const result = await window.Swal.fire({
                title,
                text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText,
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
            });
            return !!result.isConfirmed;
        }

        return true;
    };

    const flashNode = document.getElementById('praiseFlashMessage');
    if (flashNode && window.Swal && typeof window.Swal.fire === 'function') {
        const flashState = normalize(flashNode.getAttribute('data-state'));
        const flashMessage = (flashNode.getAttribute('data-message') || '').toString().trim();
        if (flashMessage !== '') {
            window.Swal.fire({
                icon: flashState === 'success' ? 'success' : 'error',
                title: flashState === 'success' ? 'Success' : 'Action Failed',
                text: flashMessage,
                confirmButtonText: 'OK',
            });
            flashNode.classList.add('hidden');
        }
    }

    const buildPagedTable = ({ rows, emptyRow, pageLabel, prevBtn, nextBtn, rowIsVisible }) => {
        const state = {
            currentPage: 1,
            pageSize: PAGE_SIZE,
        };

        const render = () => {
            const visibleRows = rows.filter((row) => rowIsVisible(row));
            const totalPages = Math.max(1, Math.ceil(visibleRows.length / state.pageSize));
            if (state.currentPage > totalPages) {
                state.currentPage = totalPages;
            }
            const start = (state.currentPage - 1) * state.pageSize;
            const end = start + state.pageSize;

            rows.forEach((row) => {
                if (!rowIsVisible(row)) {
                    row.classList.add('hidden');
                    return;
                }

                const visibleIndex = visibleRows.indexOf(row);
                const inPage = visibleIndex >= start && visibleIndex < end;
                row.classList.toggle('hidden', !inPage);
            });

            if (emptyRow) {
                emptyRow.classList.toggle('hidden', visibleRows.length > 0);
            }

            if (pageLabel) {
                pageLabel.textContent = `Page ${state.currentPage} of ${totalPages}`;
            }

            if (prevBtn) {
                prevBtn.disabled = state.currentPage <= 1;
            }

            if (nextBtn) {
                nextBtn.disabled = state.currentPage >= totalPages;
            }
        };

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (state.currentPage <= 1) {
                    return;
                }
                state.currentPage -= 1;
                render();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                state.currentPage += 1;
                render();
            });
        }

        return {
            resetPage() {
                state.currentPage = 1;
            },
            render,
        };
    };

    const searchInput = document.getElementById('praiseNominationSearchInput');
    const statusFilter = document.getElementById('praiseNominationStatusFilter');
    const rows = Array.from(document.querySelectorAll('[data-praise-nomination-row]'));
    const emptyRow = document.getElementById('praiseNominationFilterEmptyRow');

    const nominationPrevPage = document.getElementById('praiseNominationPrevPage');
    const nominationNextPage = document.getElementById('praiseNominationNextPage');
    const nominationPageLabel = document.getElementById('praiseNominationPageLabel');

    const nominationVisibility = new Map();
    rows.forEach((row) => nominationVisibility.set(row, true));

    const nominationsPager = buildPagedTable({
        rows,
        emptyRow,
        pageLabel: nominationPageLabel,
        prevBtn: nominationPrevPage,
        nextBtn: nominationNextPage,
        rowIsVisible: (row) => nominationVisibility.get(row) === true,
    });

    const awardsRows = Array.from(document.querySelectorAll('[data-praise-award-row]'));
    const awardsPager = buildPagedTable({
        rows: awardsRows,
        emptyRow: null,
        pageLabel: document.getElementById('praiseAwardsPageLabel'),
        prevBtn: document.getElementById('praiseAwardsPrevPage'),
        nextBtn: document.getElementById('praiseAwardsNextPage'),
        rowIsVisible: () => true,
    });

    const publishSearchInput = document.getElementById('praisePublishSearchInput');
    const publishRows = Array.from(document.querySelectorAll('[data-praise-publish-row]'));
    const publishEmptyRow = document.getElementById('praisePublishFilterEmptyRow');
    const publishVisibility = new Map();
    publishRows.forEach((row) => publishVisibility.set(row, true));
    const publishPager = buildPagedTable({
        rows: publishRows,
        emptyRow: publishEmptyRow,
        pageLabel: document.getElementById('praisePublishPageLabel'),
        prevBtn: document.getElementById('praisePublishPrevPage'),
        nextBtn: document.getElementById('praisePublishNextPage'),
        rowIsVisible: (row) => publishVisibility.get(row) === true,
    });

    const modal = document.getElementById('praiseNominationReviewModal');
    const modalOpenButtons = Array.from(document.querySelectorAll('[data-open-praise-nomination-modal]'));
    const modalCloseButton = document.getElementById('praiseNominationModalClose');
    const modalCancelButton = document.getElementById('praiseNominationModalCancel');
    const reviewForm = document.getElementById('praiseNominationReviewForm');
    const submitButton = document.getElementById('praiseNominationSubmit');

    const nominationIdInput = document.getElementById('praiseNominationIdInput');
    const nomineeLabel = document.getElementById('praiseNomineeLabel');
    const awardLabel = document.getElementById('praiseAwardLabel');
    const currentStatusLabel = document.getElementById('praiseNominationCurrentStatusLabel');
    const decisionSelect = document.getElementById('praiseNominationDecisionSelect');

    const createModal = document.getElementById('praiseCreateNominationModal');
    const createModalOpenButton = document.querySelector('[data-open-praise-create-modal]');
    const createModalCloseButton = document.getElementById('praiseCreateNominationModalClose');
    const createModalCancelButton = document.getElementById('praiseCreateNominationModalCancel');
    const createForm = document.getElementById('praiseCreateNominationForm');
    const createSubmitButton = document.getElementById('praiseCreateNominationSubmit');

    const publishModal = document.getElementById('praisePublishAwardeeModal');
    const publishOpenButtons = Array.from(document.querySelectorAll('[data-open-praise-publish-modal]'));
    const publishModalCloseButton = document.getElementById('praisePublishModalClose');
    const publishModalCancelButton = document.getElementById('praisePublishModalCancel');
    const publishForm = document.getElementById('praisePublishAwardeeForm');
    const publishSubmitButton = document.getElementById('praisePublishSubmit');
    const publishNominationIdInput = document.getElementById('praisePublishNominationIdInput');
    const publishNomineeLabel = document.getElementById('praisePublishNomineeLabel');
    const publishAwardLabel = document.getElementById('praisePublishAwardLabel');

    const quickEvaluationReportForm = document.getElementById('praiseQuickEvaluationReportForm');
    const quickEvaluationReportButton = document.getElementById('praiseQuickEvaluationReportButton');

    const applyFilters = () => {
        if (!searchInput || !statusFilter || rows.length === 0) {
            nominationsPager.render();
            return;
        }

        const query = normalize(searchInput.value);
        const status = normalize(statusFilter.value);

        rows.forEach((row) => {
            const rowSearch = normalize(row.getAttribute('data-praise-nomination-search'));
            const rowStatus = normalize(row.getAttribute('data-praise-nomination-status'));
            const visible = (query === '' || rowSearch.includes(query)) && (status === '' || rowStatus === status);
            nominationVisibility.set(row, visible);
        });

        nominationsPager.resetPage();
        nominationsPager.render();
    };

    if (searchInput && statusFilter && rows.length > 0) {
        searchInput.addEventListener('input', debounce(applyFilters, 200));
        statusFilter.addEventListener('change', applyFilters);
        applyFilters();
    } else {
        nominationsPager.render();
    }

    const applyPublishFilters = () => {
        if (!publishSearchInput || publishRows.length === 0) {
            publishPager.render();
            return;
        }

        const query = normalize(publishSearchInput.value);
        publishRows.forEach((row) => {
            const haystack = normalize(row.getAttribute('data-praise-publish-search'));
            publishVisibility.set(row, query === '' || haystack.includes(query));
        });

        publishPager.resetPage();
        publishPager.render();
    };

    if (publishSearchInput && publishRows.length > 0) {
        publishSearchInput.addEventListener('input', debounce(applyPublishFilters, 200));
        applyPublishFilters();
    } else {
        publishPager.render();
    }

    awardsPager.render();

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

    const closeCreateModal = () => {
        if (!createModal) {
            return;
        }

        createModal.classList.add('hidden');
        createModal.classList.remove('flex');
        if (createForm) {
            createForm.reset();
        }
        if (createSubmitButton) {
            createSubmitButton.disabled = false;
            createSubmitButton.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    };

    const openCreateModal = () => {
        if (!createModal) {
            return;
        }

        createModal.classList.remove('hidden');
        createModal.classList.add('flex');
    };

    const closePublishModal = () => {
        if (!publishModal) {
            return;
        }

        publishModal.classList.add('hidden');
        publishModal.classList.remove('flex');
        if (publishForm) {
            publishForm.reset();
        }
        if (publishSubmitButton) {
            publishSubmitButton.disabled = false;
            publishSubmitButton.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    };

    const openPublishModal = (button) => {
        if (!publishModal || !publishNominationIdInput) {
            return;
        }

        publishNominationIdInput.value = button.getAttribute('data-nomination-id') || '';
        if (publishNomineeLabel) {
            publishNomineeLabel.textContent = button.getAttribute('data-nominee-name') || '-';
        }
        if (publishAwardLabel) {
            publishAwardLabel.textContent = button.getAttribute('data-award-name') || '-';
        }

        publishModal.classList.remove('hidden');
        publishModal.classList.add('flex');
    };

    const openModal = (button) => {
        if (!modal || !nominationIdInput) {
            return;
        }

        const nominationId = button.getAttribute('data-nomination-id') || '';
        const nomineeName = button.getAttribute('data-nominee-name') || '-';
        const awardName = button.getAttribute('data-award-name') || '-';
        const currentStatus = button.getAttribute('data-current-status') || '';
        const currentStatusText = button.getAttribute('data-current-status-label') || '-';

        nominationIdInput.value = nominationId;
        if (nomineeLabel) {
            nomineeLabel.textContent = nomineeName;
        }
        if (awardLabel) {
            awardLabel.textContent = awardName;
        }
        if (currentStatusLabel) {
            currentStatusLabel.textContent = currentStatusText;
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

    if (createModalOpenButton) {
        createModalOpenButton.addEventListener('click', openCreateModal);
    }
    if (createModalCloseButton) {
        createModalCloseButton.addEventListener('click', closeCreateModal);
    }
    if (createModalCancelButton) {
        createModalCancelButton.addEventListener('click', closeCreateModal);
    }
    if (createModal) {
        createModal.addEventListener('click', (event) => {
            if (event.target === createModal) {
                closeCreateModal();
            }
        });
    }

    publishOpenButtons.forEach((button) => {
        button.addEventListener('click', () => openPublishModal(button));
    });

    if (publishModalCloseButton) {
        publishModalCloseButton.addEventListener('click', closePublishModal);
    }
    if (publishModalCancelButton) {
        publishModalCancelButton.addEventListener('click', closePublishModal);
    }
    if (publishModal) {
        publishModal.addEventListener('click', (event) => {
            if (event.target === publishModal) {
                closePublishModal();
            }
        });
    }

    if (reviewForm) {
        reviewForm.addEventListener('submit', async (event) => {
            const nominee = nomineeLabel ? nomineeLabel.textContent || 'Nominee' : 'Nominee';
            const oldStatus = currentStatusLabel ? currentStatusLabel.textContent || '-' : '-';
            const decision = normalize(decisionSelect ? decisionSelect.value : '');

            if (decision === '') {
                return;
            }

            event.preventDefault();

            const decisionLabel = toTitleLabel(decision);
            const shouldContinue = await confirmAction({
                title: 'Confirm Nomination Decision',
                text: `Change nomination status for "${nominee}" from "${oldStatus}" to "${decisionLabel}"?`,
                confirmButtonText: `Set ${decisionLabel}`,
            });
            if (!shouldContinue) {
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

            reviewForm.submit();
        });
    }

    if (createForm) {
        createForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const nomineeSelect = document.getElementById('praiseNomineePersonSelect');
            const awardSelect = document.getElementById('praiseAwardSelect');
            const nomineeLabelText = nomineeSelect && nomineeSelect.selectedOptions[0]
                ? nomineeSelect.selectedOptions[0].textContent || 'Employee'
                : 'Employee';
            const awardLabelText = awardSelect && awardSelect.selectedOptions[0]
                ? awardSelect.selectedOptions[0].textContent || 'Award'
                : 'Award';

            const shouldContinue = await confirmAction({
                title: 'Submit PRAISE Nomination',
                text: `Submit nomination for "${nomineeLabelText}" under "${awardLabelText}"?`,
                confirmButtonText: 'Submit Nomination',
            });

            if (!shouldContinue) {
                return;
            }

            if (createSubmitButton) {
                createSubmitButton.disabled = true;
                createSubmitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

            createForm.submit();
        });
    }

    if (publishForm) {
        publishForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const awardee = publishNomineeLabel ? publishNomineeLabel.textContent || 'Employee' : 'Employee';
            const award = publishAwardLabel ? publishAwardLabel.textContent || 'PRAISE Award' : 'PRAISE Award';

            const shouldContinue = await confirmAction({
                title: 'Publish Awardee Recognition',
                text: `Publish "${awardee}" as awardee for "${award}"?`,
                confirmButtonText: 'Publish Awardee',
            });

            if (!shouldContinue) {
                return;
            }

            if (publishSubmitButton) {
                publishSubmitButton.disabled = true;
                publishSubmitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

            publishForm.submit();
        });
    }

    if (quickEvaluationReportForm && quickEvaluationReportButton) {
        quickEvaluationReportForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const shouldContinue = await confirmAction({
                title: 'Generate Employee Evaluation Report',
                text: 'Generate an organization-wide performance evaluation report using the current cutoff window?',
                confirmButtonText: 'Generate Report',
            });

            if (!shouldContinue) {
                return;
            }

            quickEvaluationReportButton.disabled = true;
            quickEvaluationReportButton.classList.add('opacity-60', 'cursor-not-allowed');
            quickEvaluationReportForm.submit();
        });
    }

    document.addEventListener('click', (event) => {
        const createOpenTrigger = event.target.closest('[data-open-praise-create-modal]');
        if (createOpenTrigger) {
            event.preventDefault();
            openCreateModal();
            return;
        }

        const reviewOpenTrigger = event.target.closest('[data-open-praise-nomination-modal]');
        if (reviewOpenTrigger) {
            event.preventDefault();
            openModal(reviewOpenTrigger);
            return;
        }

        const publishOpenTrigger = event.target.closest('[data-open-praise-publish-modal]');
        if (publishOpenTrigger) {
            event.preventDefault();
            openPublishModal(publishOpenTrigger);
        }
    });
})();
