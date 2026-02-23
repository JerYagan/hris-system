(() => {
    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const nextStatusFromCurrent = (current) => {
        const key = normalize(current);
        const map = {
            submitted: 'screening',
            screening: 'shortlisted',
            shortlisted: 'interview',
            interview: 'offer',
            offer: 'hired',
            hired: 'hired',
            rejected: 'rejected',
            withdrawn: 'withdrawn',
        };

        return map[key] || 'screening';
    };

    const showConfirmation = async (title, text) => {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            const result = await window.Swal.fire({
                title,
                text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, continue',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            });

            return Boolean(result && result.isConfirmed);
        }

        return true;
    };

    const showFlashMessage = () => {
        const flashNode = document.getElementById('trackingFlashState');
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

        const timeInputs = document.querySelectorAll('main input[type="time"]:not([data-flatpickr="off"])');
        timeInputs.forEach((input) => {
            if (input.dataset.flatpickrInitialized === 'true') {
                return;
            }

            window.flatpickr(input, {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: false,
                allowInput: true,
            });
            input.dataset.flatpickrInitialized = 'true';
        });
    };

    const bindTableFilters = ({
        searchId,
        statusId,
        rowSelector,
        emptyRowId,
        searchAttr,
        statusAttr,
        paginationInfoId,
        prevPageId,
        nextPageId,
    }) => {
        const searchInput = searchId ? document.getElementById(searchId) : null;
        const statusFilter = statusId ? document.getElementById(statusId) : null;
        const rows = Array.from(document.querySelectorAll(rowSelector));
        const emptyRow = document.getElementById(emptyRowId);
        const paginationInfo = document.getElementById(paginationInfoId);
        const prevPageButton = document.getElementById(prevPageId);
        const nextPageButton = document.getElementById(nextPageId);

        const pageSize = 10;
        let currentPage = 1;
        let filteredRows = rows;

        const updatePaginationUi = (total) => {
            const totalPages = Math.max(1, Math.ceil(total / pageSize));
            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            if (paginationInfo) {
                paginationInfo.textContent = `Page ${currentPage} of ${totalPages}`;
            }

            if (prevPageButton) {
                const disabled = currentPage <= 1 || total === 0;
                prevPageButton.disabled = disabled;
                prevPageButton.classList.toggle('opacity-60', disabled);
                prevPageButton.classList.toggle('cursor-not-allowed', disabled);
            }

            if (nextPageButton) {
                const disabled = currentPage >= totalPages || total === 0;
                nextPageButton.disabled = disabled;
                nextPageButton.classList.toggle('opacity-60', disabled);
                nextPageButton.classList.toggle('cursor-not-allowed', disabled);
            }
        };

        const renderPage = () => {
            const total = filteredRows.length;
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;

            rows.forEach((row) => row.classList.add('hidden'));
            filteredRows.slice(start, end).forEach((row) => row.classList.remove('hidden'));

            if (emptyRow) {
                emptyRow.classList.toggle('hidden', total > 0);
            }

            updatePaginationUi(total);
        };

        const applyFilters = () => {
            const query = normalize(searchInput ? searchInput.value : '');
            const status = normalize(statusFilter ? statusFilter.value : '');

            filteredRows = rows.filter((row) => {
                const rowSearch = normalize(searchAttr ? row.getAttribute(searchAttr) : '');
                const rowStatus = normalize(statusAttr ? row.getAttribute(statusAttr) : '');
                const matchesSearch = searchInput ? (query === '' || rowSearch.includes(query)) : true;
                const matchesStatus = statusFilter ? (status === '' || rowStatus === status) : true;
                return matchesSearch && matchesStatus;
            });

            currentPage = 1;
            renderPage();
        };

        if (rows.length === 0) {
            updatePaginationUi(0);
            return;
        }

        if (searchInput) {
            let debounceTimer;
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

        if (prevPageButton) {
            prevPageButton.addEventListener('click', () => {
                if (currentPage <= 1) {
                    return;
                }
                currentPage -= 1;
                renderPage();
            });
        }

        if (nextPageButton) {
            nextPageButton.addEventListener('click', () => {
                const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
                if (currentPage >= totalPages) {
                    return;
                }
                currentPage += 1;
                renderPage();
            });
        }

        applyFilters();
    };

    bindTableFilters({
        searchId: 'trackingSearchInput',
        statusId: 'trackingStatusFilter',
        rowSelector: '[data-tracking-row]',
        emptyRowId: 'trackingFilterEmptyRow',
        searchAttr: 'data-tracking-search',
        statusAttr: 'data-tracking-status',
        paginationInfoId: 'trackingPaginationInfo',
        prevPageId: 'trackingPrevPage',
        nextPageId: 'trackingNextPage',
    });

    bindTableFilters({
        searchId: 'queueSearchInput',
        statusId: 'queueStatusFilter',
        rowSelector: '[data-queue-row]',
        emptyRowId: 'queueFilterEmptyRow',
        searchAttr: 'data-queue-search',
        statusAttr: 'data-queue-status',
        paginationInfoId: 'queuePaginationInfo',
        prevPageId: 'queuePrevPage',
        nextPageId: 'queueNextPage',
    });

    bindTableFilters({
        searchId: 'hiredSearchInput',
        statusId: null,
        rowSelector: '[data-hired-row]',
        emptyRowId: 'hiredFilterEmptyRow',
        searchAttr: 'data-hired-search',
        statusAttr: null,
        paginationInfoId: 'hiredPaginationInfo',
        prevPageId: 'hiredPrevPage',
        nextPageId: 'hiredNextPage',
    });

    const scheduleModal = document.getElementById('scheduleInterviewModal');
    const statusModal = document.getElementById('updateStatusModal');

    const scheduleForm = document.getElementById('scheduleInterviewForm');
    const scheduleSubmit = document.getElementById('scheduleInterviewSubmit');
    const scheduleApplicationId = document.getElementById('scheduleApplicationId');
    const scheduleApplicantName = document.getElementById('scheduleApplicantName');

    const statusForm = document.getElementById('updateStatusForm');
    const statusSubmit = document.getElementById('updateStatusSubmit');
    const statusApplicationId = document.getElementById('statusApplicationId');
    const statusApplicantName = document.getElementById('statusApplicantName');
    const statusCurrentStatus = document.getElementById('statusCurrentStatus');
    const statusNewStatus = document.getElementById('statusNewStatus');

    const openModal = (modalNode) => {
        if (!modalNode) {
            return;
        }

        modalNode.classList.remove('hidden');
    };

    const closeModal = (modalNode) => {
        if (!modalNode) {
            return;
        }

        modalNode.classList.add('hidden');

        if (modalNode === scheduleModal && scheduleForm) {
            scheduleForm.reset();
            scheduleForm.dataset.confirmed = '0';
        }

        if (modalNode === statusModal && statusForm) {
            statusForm.reset();
            statusForm.dataset.confirmed = '0';
        }
    };

    document.querySelectorAll('[data-track-schedule]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!scheduleApplicationId || !scheduleApplicantName) {
                return;
            }

            scheduleApplicationId.value = button.getAttribute('data-application-id') || '';
            scheduleApplicantName.value = button.getAttribute('data-applicant-name') || 'Applicant';
            openModal(scheduleModal);
        });
    });

    document.querySelectorAll('[data-track-status-update]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!statusApplicationId || !statusApplicantName || !statusCurrentStatus || !statusNewStatus) {
                return;
            }

            statusApplicationId.value = button.getAttribute('data-application-id') || '';
            statusApplicantName.value = button.getAttribute('data-applicant-name') || 'Applicant';
            statusCurrentStatus.value = button.getAttribute('data-current-status-label') || '-';
            statusNewStatus.value = nextStatusFromCurrent(button.getAttribute('data-current-status') || 'submitted');
            openModal(statusModal);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-modal-close') || '';
            if (targetId === 'scheduleInterviewModal') {
                closeModal(scheduleModal);
            } else if (targetId === 'updateStatusModal') {
                closeModal(statusModal);
            }
        });
    });

    if (scheduleModal) {
        scheduleModal.addEventListener('click', (event) => {
            if (event.target === scheduleModal) {
                closeModal(scheduleModal);
            }
        });
    }

    if (statusModal) {
        statusModal.addEventListener('click', (event) => {
            if (event.target === statusModal) {
                closeModal(statusModal);
            }
        });
    }

    if (scheduleForm) {
        scheduleForm.addEventListener('submit', async (event) => {
            if (scheduleForm.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const applicant = (scheduleApplicantName ? scheduleApplicantName.value : 'Applicant') || 'Applicant';
            const shouldContinue = await showConfirmation('Schedule Interview', `Confirm interview schedule for ${applicant}?`);
            if (!shouldContinue) {
                return;
            }

            if (scheduleSubmit) {
                scheduleSubmit.disabled = true;
                scheduleSubmit.classList.add('opacity-60', 'cursor-not-allowed');
            }

            scheduleForm.dataset.confirmed = '1';
            scheduleForm.submit();
        });
    }

    if (statusForm) {
        statusForm.addEventListener('submit', async (event) => {
            if (statusForm.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const applicant = (statusApplicantName ? statusApplicantName.value : 'Applicant') || 'Applicant';
            const oldStatus = (statusCurrentStatus ? statusCurrentStatus.value : '-') || '-';
            const nextStatusRaw = normalize(statusNewStatus ? statusNewStatus.value : '');
            const nextStatusLabel = nextStatusRaw.replace('_', ' ');
            const shouldContinue = await showConfirmation(
                'Update Application Status',
                `Confirm status change for ${applicant} from ${oldStatus} to ${nextStatusLabel}?`
            );
            if (!shouldContinue) {
                return;
            }

            if (statusSubmit) {
                statusSubmit.disabled = true;
                statusSubmit.classList.add('opacity-60', 'cursor-not-allowed');
            }

            statusForm.dataset.confirmed = '1';
            statusForm.submit();
        });
    }

    document.querySelectorAll('[data-add-employee-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            const applicant = button ? (button.getAttribute('data-applicant-name') || 'Applicant') : 'Applicant';
            const shouldContinue = await showConfirmation('Add as Employee', `Create an employee record for ${applicant}?`);
            if (!shouldContinue) {
                return;
            }

            if (button) {
                button.disabled = true;
                button.classList.add('opacity-60', 'cursor-not-allowed');
            }

            form.dataset.confirmed = '1';
            form.submit();
        });
    });

    initDatePickers();
    showFlashMessage();
})();
