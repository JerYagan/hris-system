(() => {
    const searchInput = document.getElementById('staffEmployeeSearch');
    const statusFilter = document.getElementById('staffEmployeeStatusFilter');
    const rows = Array.from(document.querySelectorAll('[data-employee-row]'));
    const emptyFilterRow = document.getElementById('staffEmployeeFilterEmpty');

    const profileModal = document.getElementById('employeeProfileModal');
    const profileModalClose = document.getElementById('employeeProfileModalClose');
    const profileModalCancel = document.getElementById('employeeProfileModalCancel');
    const profileForm = document.getElementById('employeeProfileForm');
    const profileSubmit = document.getElementById('employeeProfileSubmit');

    const statusModal = document.getElementById('employeeStatusModal');
    const statusModalClose = document.getElementById('employeeStatusModalClose');
    const statusModalCancel = document.getElementById('employeeStatusModalCancel');
    const statusForm = document.getElementById('employeeStatusForm');
    const statusSubmit = document.getElementById('employeeStatusSubmit');

    const profileFields = {
        personId: document.getElementById('profileModalPersonId'),
        employmentId: document.getElementById('profileModalEmploymentId'),
        employeeName: document.getElementById('profileModalEmployeeName'),
        firstName: document.getElementById('profileModalFirstName'),
        middleName: document.getElementById('profileModalMiddleName'),
        surname: document.getElementById('profileModalSurname'),
        nameExtension: document.getElementById('profileModalNameExtension'),
        personalEmail: document.getElementById('profileModalPersonalEmail'),
        mobileNo: document.getElementById('profileModalMobileNo'),
    };

    const statusFields = {
        personId: document.getElementById('statusModalPersonId'),
        employmentId: document.getElementById('statusModalEmploymentId'),
        employeeName: document.getElementById('statusModalEmployeeName'),
        currentStatusLabel: document.getElementById('statusModalCurrentStatus'),
        currentStatusRaw: '',
        newStatus: document.getElementById('statusModalNewStatus'),
    };

    const normalize = (value) => (value || '').toString().trim().toLowerCase();

    const closeModal = (modal, form) => {
        if (!modal) {
            return;
        }
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (form) {
            form.reset();
        }
    };

    const openModal = (modal) => {
        if (!modal) {
            return;
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const applyFilters = () => {
        if (!searchInput || !statusFilter || rows.length === 0) {
            return;
        }

        const keyword = normalize(searchInput.value);
        const status = normalize(statusFilter.value);
        let visibleCount = 0;

        rows.forEach((row) => {
            const searchText = normalize(row.getAttribute('data-employee-search'));
            const rowStatus = normalize(row.getAttribute('data-employee-status'));

            const matched = (keyword === '' || searchText.includes(keyword)) && (status === '' || rowStatus === status);
            row.classList.toggle('hidden', !matched);
            if (matched) {
                visibleCount += 1;
            }
        });

        if (emptyFilterRow) {
            emptyFilterRow.classList.toggle('hidden', visibleCount > 0);
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

    document.querySelectorAll('[data-open-employee-profile-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!profileModal) {
                return;
            }

            profileFields.personId.value = button.getAttribute('data-person-id') || '';
            profileFields.employmentId.value = button.getAttribute('data-employment-id') || '';
            profileFields.employeeName.textContent = button.getAttribute('data-employee-name') || '-';
            profileFields.firstName.value = button.getAttribute('data-first-name') || '';
            profileFields.middleName.value = button.getAttribute('data-middle-name') || '';
            profileFields.surname.value = button.getAttribute('data-surname') || '';
            profileFields.nameExtension.value = button.getAttribute('data-name-extension') || '';
            profileFields.personalEmail.value = button.getAttribute('data-personal-email') || '';
            profileFields.mobileNo.value = button.getAttribute('data-mobile-no') || '';

            openModal(profileModal);
        });
    });

    document.querySelectorAll('[data-open-status-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!statusModal) {
                return;
            }

            statusFields.personId.value = button.getAttribute('data-person-id') || '';
            statusFields.employmentId.value = button.getAttribute('data-employment-id') || '';
            statusFields.employeeName.textContent = button.getAttribute('data-employee-name') || '-';
            statusFields.currentStatusLabel.textContent = button.getAttribute('data-current-status-label') || '-';
            statusFields.currentStatusRaw = normalize(button.getAttribute('data-current-status') || '');
            statusFields.newStatus.value = '';

            openModal(statusModal);
        });
    });

    if (profileModalClose) {
        profileModalClose.addEventListener('click', () => closeModal(profileModal, profileForm));
    }
    if (profileModalCancel) {
        profileModalCancel.addEventListener('click', () => closeModal(profileModal, profileForm));
    }
    if (profileModal) {
        profileModal.addEventListener('click', (event) => {
            if (event.target === profileModal) {
                closeModal(profileModal, profileForm);
            }
        });
    }

    if (statusModalClose) {
        statusModalClose.addEventListener('click', () => closeModal(statusModal, statusForm));
    }
    if (statusModalCancel) {
        statusModalCancel.addEventListener('click', () => closeModal(statusModal, statusForm));
    }
    if (statusModal) {
        statusModal.addEventListener('click', (event) => {
            if (event.target === statusModal) {
                closeModal(statusModal, statusForm);
            }
        });
    }

    if (profileForm) {
        profileForm.addEventListener('submit', (event) => {
            const employee = (profileFields.employeeName.textContent || 'employee').trim();
            const shouldContinue = window.confirm(`Save updated profile information for ${employee}?`);
            if (!shouldContinue) {
                event.preventDefault();
                return;
            }

            if (profileSubmit) {
                profileSubmit.disabled = true;
                profileSubmit.classList.add('opacity-60', 'cursor-not-allowed');
            }
        });
    }

    if (statusForm) {
        statusForm.addEventListener('submit', (event) => {
            const newStatus = normalize(statusFields.newStatus ? statusFields.newStatus.value : '');
            if (newStatus === '') {
                return;
            }

            const employee = (statusFields.employeeName.textContent || 'employee').trim();
            const oldStatus = (statusFields.currentStatusLabel.textContent || '-').trim();
            const newStatusLabel = newStatus.replace('_', ' ');
            const shouldContinue = window.confirm(`Confirm status change for ${employee} from ${oldStatus} to ${newStatusLabel}?`);
            if (!shouldContinue) {
                event.preventDefault();
                return;
            }

            if (statusSubmit) {
                statusSubmit.disabled = true;
                statusSubmit.classList.add('opacity-60', 'cursor-not-allowed');
            }
        });
    }

    applyFilters();
})();