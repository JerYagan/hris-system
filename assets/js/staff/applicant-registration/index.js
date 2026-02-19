(() => {
    const searchInput = document.getElementById('registrationSearchInput');
    const statusFilter = document.getElementById('registrationStatusFilter');
    const rows = Array.from(document.querySelectorAll('[data-registration-row]'));
    const emptyRow = document.getElementById('registrationFilterEmptyRow');

    const modal = document.getElementById('registrationModal');
    const closeBtn = document.getElementById('registrationModalClose');
    const cancelBtn = document.getElementById('registrationModalCancel');
    const form = document.getElementById('registrationForm');
    const submitBtn = document.getElementById('registrationSubmit');

    const applicationIdInput = document.getElementById('registrationApplicationId');
    const applicantNameLabel = document.getElementById('registrationApplicantName');
    const postingTitleLabel = document.getElementById('registrationPostingTitle');
    const currentStatusLabel = document.getElementById('registrationCurrentStatus');
    const newStatusInput = document.getElementById('registrationNewStatus');

    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const applyFilters = () => {
        if (!searchInput || !statusFilter || rows.length === 0) {
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
        modal.classList.remove('flex');
        if (form) {
            form.reset();
        }
    };

    document.querySelectorAll('[data-open-registration-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!modal || !applicationIdInput || !applicantNameLabel || !postingTitleLabel || !currentStatusLabel || !newStatusInput) {
                return;
            }

            applicationIdInput.value = button.getAttribute('data-application-id') || '';
            applicantNameLabel.textContent = button.getAttribute('data-applicant-name') || 'Applicant';
            postingTitleLabel.textContent = button.getAttribute('data-posting-title') || 'Job Posting';
            currentStatusLabel.textContent = button.getAttribute('data-current-status-label') || '-';
            newStatusInput.value = '';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }
    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (form) {
        form.addEventListener('submit', (event) => {
            const applicant = (applicantNameLabel ? applicantNameLabel.textContent : 'Applicant') || 'Applicant';
            const oldStatus = (currentStatusLabel ? currentStatusLabel.textContent : '-') || '-';
            const newStatus = normalize(newStatusInput ? newStatusInput.value : '');
            if (newStatus === '') {
                return;
            }

            const shouldContinue = window.confirm(`Confirm registration decision for ${applicant} from ${oldStatus} to ${newStatus.replace('_', ' ')}?`);
            if (!shouldContinue) {
                event.preventDefault();
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
            }
        });
    }

    applyFilters();
})();