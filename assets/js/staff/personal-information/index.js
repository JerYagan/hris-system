import { initFloatingActionMenus } from '../../shared/action-menu.js';

(() => {
    const searchInput = document.getElementById('personalInfoRecordsSearchInput');
    const statusFilter = document.getElementById('personalInfoRecordsStatusFilter');
    const departmentFilter = document.getElementById('personalInfoRecordsDepartmentFilter');
    const rows = Array.from(document.querySelectorAll('#personalInfoEmployeesTable tbody tr[data-profile-search]'));
    const emptyFilterRow = document.getElementById('personalInfoFilterEmpty');
    const paginationInfo = document.getElementById('personalInfoPaginationInfo');
    const prevPageButton = document.getElementById('personalInfoPrevPage');
    const nextPageButton = document.getElementById('personalInfoNextPage');
    const pageSize = 10;
    let currentPage = 1;
    let filteredRows = rows;

    const pendingSearchInput = document.getElementById('personalInfoPendingSearchInput');
    const pendingTypeFilter = document.getElementById('personalInfoPendingTypeFilter');
    const pendingRows = Array.from(document.querySelectorAll('#personalInfoPendingTable tbody tr[data-pending-row]'));
    const pendingEmptyFilterRow = document.getElementById('personalInfoPendingFilterEmpty');
    const pendingPaginationInfo = document.getElementById('personalInfoPendingPaginationInfo');
    const pendingPrevPageButton = document.getElementById('personalInfoPendingPrevPage');
    const pendingNextPageButton = document.getElementById('personalInfoPendingNextPage');
    let pendingCurrentPage = 1;
    let filteredPendingRows = pendingRows;

    const profileModal = document.getElementById('personalInfoProfileModal');
    const profileForm = document.getElementById('personalInfoProfileForm');
    const profileSubmit = document.getElementById('personalInfoProfileSubmit');

    const assignmentModal = document.getElementById('personalInfoAssignmentModal');
    const assignmentForm = document.getElementById('personalInfoAssignmentForm');
    const assignmentSubmit = document.getElementById('personalInfoAssignmentSubmit');

    const statusModal = document.getElementById('personalInfoStatusModal');
    const statusForm = document.getElementById('personalInfoStatusForm');
    const statusSubmit = document.getElementById('personalInfoStatusSubmit');

    const pendingReviewModal = document.getElementById('personalInfoPendingReviewModal');
    const pendingReviewTitle = document.getElementById('personalInfoPendingReviewTitle');
    const pendingReviewSummary = document.getElementById('personalInfoPendingReviewSummary');
    const pendingReviewPairsBody = document.getElementById('personalInfoPendingReviewPairs');
    const pendingReviewRemarks = document.getElementById('personalInfoPendingReviewRemarks');
    const pendingReviewEditButton = document.getElementById('personalInfoPendingEditRecommendationBtn');

    const profileFields = {
        personId: document.getElementById('profilePersonId'),
        employmentId: document.getElementById('profileEmploymentId'),
        employeeName: document.getElementById('personalInfoProfileEmployeeLabel'),
        firstName: document.getElementById('profileFirstName'),
        middleName: document.getElementById('profileMiddleName'),
        surname: document.getElementById('profileSurname'),
        nameExtension: document.getElementById('profileNameExtension'),
        dateOfBirth: document.getElementById('profileDateOfBirth'),
        placeOfBirth: document.getElementById('profilePlaceOfBirth'),
        sexAtBirth: document.getElementById('profileSexAtBirth'),
        civilStatus: document.getElementById('profileCivilStatus'),
        heightM: document.getElementById('profileHeightM'),
        weightKg: document.getElementById('profileWeightKg'),
        bloodType: document.getElementById('profileBloodType'),
        citizenship: document.getElementById('profileCitizenship'),
        dualCitizenshipCountry: document.getElementById('profileDualCitizenshipCountry'),
        residentialHouseNo: document.getElementById('profileResidentialHouseNo'),
        residentialStreet: document.getElementById('profileResidentialStreet'),
        residentialSubdivision: document.getElementById('profileResidentialSubdivision'),
        residentialBarangay: document.getElementById('profileResidentialBarangay'),
        residentialCity: document.getElementById('profileResidentialCity'),
        residentialProvince: document.getElementById('profileResidentialProvince'),
        residentialZipCode: document.getElementById('profileResidentialZipCode'),
        sameAsPermanentAddress: document.getElementById('profileSameAsPermanentAddress'),
        permanentHouseNo: document.getElementById('profilePermanentHouseNo'),
        permanentStreet: document.getElementById('profilePermanentStreet'),
        permanentSubdivision: document.getElementById('profilePermanentSubdivision'),
        permanentBarangay: document.getElementById('profilePermanentBarangay'),
        permanentCity: document.getElementById('profilePermanentCity'),
        permanentProvince: document.getElementById('profilePermanentProvince'),
        permanentZipCode: document.getElementById('profilePermanentZipCode'),
        umidNo: document.getElementById('profileUmidNo'),
        pagibigNo: document.getElementById('profilePagibigNo'),
        philhealthNo: document.getElementById('profilePhilhealthNo'),
        psnNo: document.getElementById('profilePsnNo'),
        tinNo: document.getElementById('profileTinNo'),
        agencyEmployeeNo: document.getElementById('profileAgencyEmployeeNo'),
        telephoneNo: document.getElementById('profileTelephoneNo'),
        email: document.getElementById('profileEmail'),
        mobile: document.getElementById('profileMobile'),
        recommendationNotes: document.getElementById('profileRecommendationNotes'),
    };

    const assignmentFields = {
        personId: document.getElementById('personalInfoAssignmentPersonId'),
        employmentId: document.getElementById('personalInfoAssignmentEmploymentId'),
        employeeDisplay: document.getElementById('personalInfoAssignmentEmployeeDisplay'),
        officeId: document.getElementById('personalInfoAssignmentOffice'),
        positionId: document.getElementById('personalInfoAssignmentPosition'),
        recommendationNotes: document.getElementById('personalInfoAssignmentRecommendationNotes'),
    };

    const statusFields = {
        personId: document.getElementById('personalInfoStatusPersonId'),
        employmentId: document.getElementById('personalInfoStatusEmploymentId'),
        employeeName: document.getElementById('personalInfoStatusEmployeeDisplay'),
        currentStatusLabel: document.getElementById('personalInfoStatusCurrentDisplay'),
        newStatus: document.getElementById('personalInfoStatusNewStatus'),
    };

    const profileFamilyFields = {
        spouseSurname: document.getElementById('profileSpouseSurname'),
        spouseFirstName: document.getElementById('profileSpouseFirstName'),
        spouseMiddleName: document.getElementById('profileSpouseMiddleName'),
        spouseExtensionName: document.getElementById('profileSpouseExtensionName'),
        spouseOccupation: document.getElementById('profileSpouseOccupation'),
        spouseEmployerBusinessName: document.getElementById('profileSpouseEmployerBusinessName'),
        spouseBusinessAddress: document.getElementById('profileSpouseBusinessAddress'),
        spouseTelephoneNo: document.getElementById('profileSpouseTelephoneNo'),
        fatherSurname: document.getElementById('profileFatherSurname'),
        fatherFirstName: document.getElementById('profileFatherFirstName'),
        fatherMiddleName: document.getElementById('profileFatherMiddleName'),
        fatherExtensionName: document.getElementById('profileFatherExtensionName'),
        motherSurname: document.getElementById('profileMotherSurname'),
        motherFirstName: document.getElementById('profileMotherFirstName'),
        motherMiddleName: document.getElementById('profileMotherMiddleName'),
        motherExtensionName: document.getElementById('profileMotherExtensionName'),
    };

    const childrenRows = document.getElementById('childrenRows');
    const childRowTemplate = document.getElementById('staffChildRowTemplate');
    const addChildButton = document.querySelector('[data-add-child-row]');

    const educationRows = document.getElementById('educationRows');
    const educationRowTemplate = document.getElementById('staffEducationRowTemplate');
    const addEducationButton = document.querySelector('[data-add-education-row]');

    const profileTabs = Array.from(document.querySelectorAll('[data-profile-tab-target]'));
    const profileSections = Array.from(document.querySelectorAll('[data-profile-section]'));
    const profilePrevButton = document.querySelector('[data-profile-prev]');
    const profileNextButton = document.querySelector('[data-profile-next]');

    const normalize = (value) => (value || '').toString().trim().toLowerCase();
    const normalizeLookup = (value) => {
        const normalized = (value || '').toString().trim().toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
        return normalized.replace(/\s+/g, ' ');
    };
    const normalizeLookupVariants = (value, isBarangay = false) => {
        const seed = normalizeLookup(value);
        if (!seed) {
            return [];
        }

        const variants = new Set();
        const queue = [seed];

        while (queue.length > 0) {
            const current = queue.shift() || '';
            if (!current || variants.has(current)) {
                continue;
            }

            variants.add(current);

            const withoutParen = current.replace(/\s*\(.*?\)\s*/g, ' ').replace(/\s+/g, ' ').trim();
            if (withoutParen && !variants.has(withoutParen)) {
                queue.push(withoutParen);
            }

            const withoutSuffix = current.replace(/\s*,\s*.*$/, '').trim();
            if (withoutSuffix && !variants.has(withoutSuffix)) {
                queue.push(withoutSuffix);
            }

            if (isBarangay) {
                const noBrgyPrefix = current.replace(/^(barangay|brgy\.?|brg\.?|bgy\.?)\s+/, '').trim();
                if (noBrgyPrefix && !variants.has(noBrgyPrefix)) {
                    queue.push(noBrgyPrefix);
                }
            } else {
                const noCityPrefix = current.replace(/^(city|city of|municipality|municipality of|mun\.?|municipio de)\s+/, '').trim();
                if (noCityPrefix && !variants.has(noCityPrefix)) {
                    queue.push(noCityPrefix);
                }

                const noCitySuffix = current.replace(/\s+city$/, '').trim();
                if (noCitySuffix && !variants.has(noCitySuffix)) {
                    queue.push(noCitySuffix);
                }
            }
        }

        return Array.from(variants);
    };
    const parseJsonAttribute = (value) => {
        if (!value) {
            return [];
        }
        try {
            const parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    };

    const escapeHtml = (value) => {
        return (value || '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const escapeSelectorValue = (value) => {
        const raw = (value || '').toString();
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(raw);
        }
        return raw.replace(/(["\\])/g, '\\$1');
    };

    const closeModal = (modal, form) => {
        if (!modal) {
            return;
        }
        modal.classList.add('hidden');
        if (form) {
            form.reset();
        }
        if (modal.id === 'personalInfoProfileModal') {
            resetProfileTabState();
            ensureMinimumRows();
            if (profileFields.sameAsPermanentAddress instanceof HTMLInputElement) {
                profileFields.sameAsPermanentAddress.checked = false;
            }
            renderAddressGroup('residential');
            renderAddressGroup('permanent');
        } else if (modal.id === 'personalInfoPendingReviewModal') {
            if (pendingReviewRemarks instanceof HTMLTextAreaElement) {
                pendingReviewRemarks.value = '';
            }
            if (pendingReviewEditButton instanceof HTMLButtonElement) {
                pendingReviewEditButton.dataset.pendingPersonId = '';
                pendingReviewEditButton.dataset.pendingAction = '';
            }
        }
    };

    const openModal = (modal) => {
        if (!modal) {
            return;
        }
        modal.classList.remove('hidden');
        if (modal.id === 'personalInfoProfileModal') {
            resetProfileTabState();
            ensureMinimumRows();
            renderAddressGroup('residential');
            renderAddressGroup('permanent');
        }
    };

    const updatePaginationUi = () => {
        const totalRows = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        if (paginationInfo) {
            paginationInfo.textContent = `Page ${currentPage} of ${totalPages}`;
        }

        if (prevPageButton) {
            const disabled = currentPage <= 1 || totalRows === 0;
            prevPageButton.disabled = disabled;
            prevPageButton.classList.toggle('opacity-60', disabled);
            prevPageButton.classList.toggle('cursor-not-allowed', disabled);
        }

        if (nextPageButton) {
            const disabled = currentPage >= totalPages || totalRows === 0;
            nextPageButton.disabled = disabled;
            nextPageButton.classList.toggle('opacity-60', disabled);
            nextPageButton.classList.toggle('cursor-not-allowed', disabled);
        }
    };

    const renderPage = () => {
        if (rows.length === 0) {
            updatePaginationUi();
            return;
        }

        rows.forEach((row) => row.classList.add('hidden'));

        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        filteredRows.slice(start, end).forEach((row) => row.classList.remove('hidden'));

        if (emptyFilterRow) {
            emptyFilterRow.classList.toggle('hidden', filteredRows.length > 0);
        }

        updatePaginationUi();
    };

    const applyFilters = () => {
        if (!searchInput || !statusFilter || rows.length === 0) {
            updatePaginationUi();
            return;
        }

        const keyword = normalize(searchInput.value);
        const status = normalize(statusFilter.value);
        const department = normalize(departmentFilter ? departmentFilter.value : '');
        filteredRows = rows.filter((row) => {
            const searchText = normalize(row.getAttribute('data-profile-search'));
            const rowStatus = normalize(row.getAttribute('data-profile-status'));
            const rowDepartment = normalize(row.getAttribute('data-profile-department'));

            return (keyword === '' || searchText.includes(keyword))
                && (status === '' || rowStatus === status)
                && (department === '' || rowDepartment === department);
        });

        currentPage = 1;
        renderPage();
    };

    const updatePendingPaginationUi = () => {
        const totalRows = filteredPendingRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
        if (pendingCurrentPage > totalPages) {
            pendingCurrentPage = totalPages;
        }

        if (pendingPaginationInfo) {
            pendingPaginationInfo.textContent = `Page ${pendingCurrentPage} of ${totalPages}`;
        }

        if (pendingPrevPageButton) {
            const disabled = pendingCurrentPage <= 1 || totalRows === 0;
            pendingPrevPageButton.disabled = disabled;
            pendingPrevPageButton.classList.toggle('opacity-60', disabled);
            pendingPrevPageButton.classList.toggle('cursor-not-allowed', disabled);
        }

        if (pendingNextPageButton) {
            const disabled = pendingCurrentPage >= totalPages || totalRows === 0;
            pendingNextPageButton.disabled = disabled;
            pendingNextPageButton.classList.toggle('opacity-60', disabled);
            pendingNextPageButton.classList.toggle('cursor-not-allowed', disabled);
        }
    };

    const renderPendingPage = () => {
        if (pendingRows.length === 0) {
            updatePendingPaginationUi();
            return;
        }

        pendingRows.forEach((row) => row.classList.add('hidden'));

        const start = (pendingCurrentPage - 1) * pageSize;
        const end = start + pageSize;
        filteredPendingRows.slice(start, end).forEach((row) => row.classList.remove('hidden'));

        if (pendingEmptyFilterRow) {
            pendingEmptyFilterRow.classList.toggle('hidden', filteredPendingRows.length > 0);
        }

        updatePendingPaginationUi();
    };

    const applyPendingFilters = () => {
        if (pendingRows.length === 0) {
            updatePendingPaginationUi();
            return;
        }

        const query = normalize(pendingSearchInput ? pendingSearchInput.value : '');
        const type = normalize(pendingTypeFilter ? pendingTypeFilter.value : '');

        filteredPendingRows = pendingRows.filter((row) => {
            const rowSearch = normalize(row.getAttribute('data-pending-search'));
            const rowType = normalize(row.getAttribute('data-pending-type'));
            return (query === '' || rowSearch.includes(query))
                && (type === '' || rowType === type);
        });

        pendingCurrentPage = 1;
        renderPendingPage();
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
    if (departmentFilter) {
        departmentFilter.addEventListener('change', applyFilters);
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

    if (pendingSearchInput) {
        pendingSearchInput.addEventListener('input', () => {
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(applyPendingFilters, 150);
        });
    }
    if (pendingTypeFilter) {
        pendingTypeFilter.addEventListener('change', applyPendingFilters);
    }
    if (pendingPrevPageButton) {
        pendingPrevPageButton.addEventListener('click', () => {
            if (pendingCurrentPage <= 1) {
                return;
            }
            pendingCurrentPage -= 1;
            renderPendingPage();
        });
    }
    if (pendingNextPageButton) {
        pendingNextPageButton.addEventListener('click', () => {
            const totalPages = Math.max(1, Math.ceil(filteredPendingRows.length / pageSize));
            if (pendingCurrentPage >= totalPages) {
                return;
            }
            pendingCurrentPage += 1;
            renderPendingPage();
        });
    }

    document.querySelectorAll('[data-pending-review-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const row = button.closest('tr[data-pending-row]');
            if (!row || !pendingReviewModal) {
                return;
            }

            const title = (row.getAttribute('data-review-title') || 'Recommendation Details').trim();
            const summary = (row.getAttribute('data-review-summary') || '').trim();
            const fallbackContent = (row.getAttribute('data-review-content') || '').trim();
            const reviewAction = (row.getAttribute('data-review-action') || '').trim();
            const personId = (row.getAttribute('data-review-person-id') || '').trim();
            const reviewPairs = parseJsonAttribute(row.getAttribute('data-review-pairs'));

            if (pendingReviewTitle) {
                pendingReviewTitle.textContent = title !== '' ? title : 'Recommendation Details';
            }
            if (pendingReviewSummary) {
                pendingReviewSummary.textContent = summary !== '' ? summary : 'No summary provided.';
            }

            if (pendingReviewPairsBody) {
                pendingReviewPairsBody.innerHTML = '';

                const normalizedPairs = reviewPairs
                    .filter((pair) => pair && typeof pair === 'object')
                    .map((pair) => ({
                        field: (pair.field || '').toString().trim(),
                        current: (pair.current || '').toString().trim(),
                        proposed: (pair.proposed || '').toString().trim(),
                    }))
                    .filter((pair) => pair.field !== '' || pair.current !== '' || pair.proposed !== '');

                if (normalizedPairs.length === 0) {
                    const rowElement = document.createElement('tr');
                    rowElement.innerHTML = `<td class="px-4 py-3 text-slate-500" colspan="3">${escapeHtml(fallbackContent || 'No field-level changes available.')}</td>`;
                    pendingReviewPairsBody.appendChild(rowElement);
                } else {
                    normalizedPairs.forEach((pair) => {
                        const rowElement = document.createElement('tr');
                        rowElement.innerHTML = `
                            <td class="px-4 py-3 text-slate-700 font-medium align-top">${escapeHtml(pair.field || 'Field')}</td>
                            <td class="px-4 py-3 text-slate-600 align-top whitespace-pre-wrap break-words">${escapeHtml(pair.current || '-')}</td>
                            <td class="px-4 py-3 text-slate-700 align-top whitespace-pre-wrap break-words">${escapeHtml(pair.proposed || '-')}</td>
                        `;
                        pendingReviewPairsBody.appendChild(rowElement);
                    });
                }
            }

            if (pendingReviewRemarks instanceof HTMLTextAreaElement) {
                pendingReviewRemarks.value = '';
            }

            if (pendingReviewEditButton instanceof HTMLButtonElement) {
                pendingReviewEditButton.dataset.pendingPersonId = personId;
                pendingReviewEditButton.dataset.pendingAction = reviewAction;
            }

            openModal(pendingReviewModal);
        });
    });

    if (pendingReviewEditButton instanceof HTMLButtonElement) {
        pendingReviewEditButton.addEventListener('click', () => {
            const actionName = (pendingReviewEditButton.dataset.pendingAction || '').trim();
            const personId = (pendingReviewEditButton.dataset.pendingPersonId || '').trim();
            if (actionName === '' || personId === '') {
                return;
            }

            const recommendationRemark = pendingReviewRemarks instanceof HTMLTextAreaElement
                ? pendingReviewRemarks.value.trim()
                : '';

            const escapedPersonId = escapeSelectorValue(personId);
            const triggerSelector = actionName === 'recommend_employee_profile_update'
                ? `[data-person-profile-open][data-person-id="${escapedPersonId}"]`
                : actionName === 'recommend_employee_status'
                    ? `[data-person-status-open][data-person-id="${escapedPersonId}"]`
                    : `[data-person-assignment-open][data-person-id="${escapedPersonId}"]`;

            closeModal(pendingReviewModal, null);

            const triggerButton = document.querySelector(triggerSelector);
            if (!(triggerButton instanceof HTMLButtonElement)) {
                return;
            }

            triggerButton.click();

            if (recommendationRemark !== '') {
                if (actionName === 'recommend_employee_profile_update' && profileFields.recommendationNotes instanceof HTMLTextAreaElement) {
                    profileFields.recommendationNotes.value = recommendationRemark;
                }
                if (actionName === 'recommend_employee_status') {
                    const notesField = document.getElementById('personalInfoStatusSpecification');
                    if (notesField instanceof HTMLTextAreaElement) {
                        notesField.value = recommendationRemark;
                    }
                }
                if (actionName === 'recommend_department_position' && assignmentFields.recommendationNotes instanceof HTMLTextAreaElement) {
                    assignmentFields.recommendationNotes.value = recommendationRemark;
                }
            }
        });
    }

    initFloatingActionMenus({
        scopeSelector: '[data-person-action-scope]',
        toggleSelector: '[data-person-action-menu-toggle]',
        menuSelector: '[data-person-action-menu]',
        initFlag: 'staffPersonalInfoActionMenusInitialized',
    });

    document.querySelectorAll('[data-action-menu-item]').forEach((menuItem) => {
        menuItem.addEventListener('click', () => {
            if (menuItem instanceof HTMLButtonElement && menuItem.disabled) {
                return;
            }

            const target = menuItem.getAttribute('data-action-target') || '';
            const scope = menuItem.closest('[data-person-action-scope]');
            if (!scope || target === '') {
                return;
            }

            const trigger = scope.querySelector(`[data-action-trigger="${target}"]`);
            if (trigger instanceof HTMLButtonElement) {
                trigger.click();
            }
        });
    });

    const profileTabOrder = profileTabs.map((tab) => tab.getAttribute('data-profile-tab-target') || '').filter(Boolean);
    let currentProfileTabIndex = 0;

    const renderProfileTabs = () => {
        const activeKey = profileTabOrder[currentProfileTabIndex] || profileTabOrder[0] || 'personal';

        profileTabs.forEach((tab) => {
            const key = tab.getAttribute('data-profile-tab-target') || '';
            const isActive = key === activeKey;
            tab.classList.toggle('bg-slate-50', isActive);
            tab.classList.toggle('text-daGreen', isActive);
            tab.classList.toggle('font-medium', isActive);
            tab.classList.toggle('border-daGreen', isActive);
            tab.classList.toggle('bg-white', !isActive);
            tab.classList.toggle('text-slate-600', !isActive);
            tab.classList.toggle('border-transparent', !isActive);
        });

        profileSections.forEach((section) => {
            const key = section.getAttribute('data-profile-section') || '';
            section.classList.toggle('hidden', key !== activeKey);
        });

        if (profilePrevButton) {
            profilePrevButton.disabled = currentProfileTabIndex === 0;
            profilePrevButton.classList.toggle('opacity-50', currentProfileTabIndex === 0);
            profilePrevButton.classList.toggle('cursor-not-allowed', currentProfileTabIndex === 0);
        }

        if (profileNextButton) {
            const isLast = currentProfileTabIndex >= profileTabOrder.length - 1;
            profileNextButton.disabled = isLast;
            profileNextButton.classList.toggle('opacity-50', isLast);
            profileNextButton.classList.toggle('cursor-not-allowed', isLast);
        }
    };

    const setProfileTab = (key) => {
        const index = profileTabOrder.indexOf(key);
        currentProfileTabIndex = index >= 0 ? index : 0;
        renderProfileTabs();
    };

    profileTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const key = tab.getAttribute('data-profile-tab-target') || 'personal';
            setProfileTab(key);
        });
    });

    profilePrevButton?.addEventListener('click', () => {
        if (currentProfileTabIndex > 0) {
            currentProfileTabIndex -= 1;
            renderProfileTabs();
        }
    });

    profileNextButton?.addEventListener('click', () => {
        if (currentProfileTabIndex < profileTabOrder.length - 1) {
            currentProfileTabIndex += 1;
            renderProfileTabs();
        }
    });

    const resetProfileTabState = () => setProfileTab('personal');

    const appendChildRow = (nameValue = '', birthDateValue = '') => {
        if (!childrenRows || !childRowTemplate) {
            return;
        }
        childrenRows.insertAdjacentHTML('beforeend', childRowTemplate.innerHTML.trim());
        const newRow = childrenRows.lastElementChild;
        if (!(newRow instanceof HTMLElement)) {
            return;
        }

        const nameInput = newRow.querySelector('[data-child-name]');
        const birthInput = newRow.querySelector('[data-child-birth-date]');
        if (nameInput instanceof HTMLInputElement) {
            nameInput.value = nameValue;
        }
        if (birthInput instanceof HTMLInputElement) {
            birthInput.value = birthDateValue;
        }
    };

    const appendEducationRow = (row = null) => {
        if (!educationRows || !educationRowTemplate) {
            return;
        }

        educationRows.insertAdjacentHTML('beforeend', educationRowTemplate.innerHTML.trim());
        const newRow = educationRows.lastElementChild;
        if (!(newRow instanceof HTMLElement)) {
            return;
        }

        const levelSelect = newRow.querySelector('[data-education-field="education_level"]');
        const setField = (name, value) => {
            const field = newRow.querySelector(`[data-education-field="${name}"]`);
            if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement) {
                field.value = value || '';
            }
        };

        if (levelSelect instanceof HTMLSelectElement) {
            const level = normalize(row?.education_level || '');
            if (level === 'vocational') {
                levelSelect.value = 'vocational_trade_course';
            } else if (level === 'graduate') {
                levelSelect.value = 'graduate_studies';
            } else if (level !== '') {
                levelSelect.value = level;
            }
        }

        setField('school_name', row?.school_name || '');
        setField('degree_course', row?.degree_course || '');
        setField('attendance_from_year', row?.attendance_from_year || '');
        setField('attendance_to_year', row?.attendance_to_year || '');
        setField('highest_level_units_earned', row?.highest_level_units_earned || '');
        setField('year_graduated', row?.year_graduated || '');
        setField('scholarship_honors_received', row?.scholarship_honors_received || '');
    };

    const ensureMinimumRows = () => {
        if (childrenRows && childrenRows.querySelectorAll('.child-row').length === 0) {
            appendChildRow();
        }
        if (educationRows && educationRows.querySelectorAll('.education-row').length === 0) {
            appendEducationRow();
        }
    };

    addChildButton?.addEventListener('click', () => appendChildRow());
    addEducationButton?.addEventListener('click', () => appendEducationRow());

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (target.matches('[data-remove-child-row]')) {
            const row = target.closest('.child-row');
            if (row && childrenRows && childrenRows.querySelectorAll('.child-row').length > 1) {
                row.remove();
            }
        }

        if (target.matches('[data-remove-education-row]')) {
            const row = target.closest('.education-row');
            if (row && educationRows && educationRows.querySelectorAll('.education-row').length > 1) {
                row.remove();
            }
        }
    });

    const hydrateChildrenRows = (children) => {
        if (!childrenRows) {
            return;
        }
        childrenRows.innerHTML = '';

        if (!Array.isArray(children) || children.length === 0) {
            appendChildRow();
            return;
        }

        children.forEach((child) => {
            appendChildRow(
                (child && child.full_name) ? child.full_name : '',
                (child && child.birth_date) ? child.birth_date : ''
            );
        });

        ensureMinimumRows();
    };

    const hydrateEducationRows = (educationRowsData) => {
        if (!educationRows) {
            return;
        }

        educationRows.innerHTML = '';

        if (!Array.isArray(educationRowsData) || educationRowsData.length === 0) {
            appendEducationRow();
            return;
        }

        educationRowsData.forEach((educationRow) => appendEducationRow(educationRow));
        ensureMinimumRows();
    };

    const addressLookupElement = document.getElementById('staffAddressLookupData');
    let addressLookup = {
        zipByCityBarangay: {},
    };

    if (addressLookupElement) {
        try {
            const parsed = JSON.parse(addressLookupElement.textContent || '{}');
            if (parsed && typeof parsed === 'object') {
                addressLookup = {
                    zipByCityBarangay: parsed.zipByCityBarangay && typeof parsed.zipByCityBarangay === 'object' ? parsed.zipByCityBarangay : {},
                };
            }
        } catch {
            addressLookup = {
                zipByCityBarangay: {},
            };
        }
    }

    const barangayDatalistByGroup = {
        residential: document.getElementById('profileResidentialBarangayList'),
        permanent: document.getElementById('profilePermanentBarangayList'),
    };

    const extractDatalistValues = (datalistElement) => {
        if (!(datalistElement instanceof HTMLDataListElement)) {
            return [];
        }

        const values = [];
        datalistElement.querySelectorAll('option').forEach((optionElement) => {
            const value = (optionElement.getAttribute('value') || '').trim();
            if (value !== '') {
                values.push(value);
            }
        });

        return values;
    };

    const defaultBarangayOptions = extractDatalistValues(barangayDatalistByGroup.residential);

    const addressControls = {
        residential: {
            city: profileFields.residentialCity,
            barangay: profileFields.residentialBarangay,
            zip: profileFields.residentialZipCode,
        },
        permanent: {
            city: profileFields.permanentCity,
            barangay: profileFields.permanentBarangay,
            zip: profileFields.permanentZipCode,
        },
    };

    const buildZipLookupMap = () => {
        const lookupMap = new Map();
        const zipMap = addressLookup.zipByCityBarangay || {};

        Object.entries(zipMap).forEach(([cityName, barangays]) => {
            if (!barangays || typeof barangays !== 'object') {
                return;
            }

            Object.entries(barangays).forEach(([barangayName, zipList]) => {
                if (!Array.isArray(zipList)) {
                    return;
                }

                const normalizedCity = normalizeLookup(cityName);
                const normalizedBarangay = normalizeLookup(barangayName);
                if (!normalizedCity || !normalizedBarangay) {
                    return;
                }

                const key = `${normalizedCity}|${normalizedBarangay}`;
                const existing = lookupMap.get(key) || new Set();

                zipList.forEach((zipValue) => {
                    const normalizedZip = (zipValue || '').toString().trim();
                    if (normalizedZip !== '') {
                        existing.add(normalizedZip);
                    }
                });

                if (existing.size > 0) {
                    lookupMap.set(key, existing);
                }
            });
        });

        return lookupMap;
    };

    const zipLookupMap = buildZipLookupMap();
    const buildCityZipLookupMap = () => {
        const lookupMap = new Map();
        const zipMap = addressLookup.zipByCityBarangay || {};

        Object.entries(zipMap).forEach(([cityName, barangays]) => {
            if (!barangays || typeof barangays !== 'object') {
                return;
            }

            const cityKey = normalizeLookup(cityName);
            if (!cityKey) {
                return;
            }

            const zipSet = lookupMap.get(cityKey) || new Set();
            Object.values(barangays).forEach((zipList) => {
                if (!Array.isArray(zipList)) {
                    return;
                }

                zipList.forEach((zipValue) => {
                    const normalizedZip = (zipValue || '').toString().trim();
                    if (normalizedZip !== '') {
                        zipSet.add(normalizedZip);
                    }
                });
            });

            if (zipSet.size > 0) {
                lookupMap.set(cityKey, zipSet);
            }
        });

        return lookupMap;
    };
    const cityZipLookupMap = buildCityZipLookupMap();
    const lastAutofilledZipByGroup = {
        residential: '',
        permanent: '',
    };
    const lastZipQueryByGroup = {
        residential: '',
        permanent: '',
    };
    const zipLookupApiPath = 'includes/personal-information/zip-lookup.php';

    const setDatalistOptions = (datalistElement, options) => {
        if (!(datalistElement instanceof HTMLDataListElement)) {
            return;
        }

        datalistElement.innerHTML = '';
        options.forEach((optionValue) => {
            const normalizedOption = (optionValue || '').toString().trim();
            if (normalizedOption === '') {
                return;
            }

            const option = document.createElement('option');
            option.value = normalizedOption;
            datalistElement.appendChild(option);
        });
    };

    const resolveBarangayOptions = () => defaultBarangayOptions;

    const requestZipFallback = async (groupKey, cityValue, barangayValue) => {
        const controls = addressControls[groupKey];
        if (!controls || !(controls.zip instanceof HTMLInputElement) || typeof window.fetch !== 'function') {
            return;
        }

        const queryKey = `${normalizeLookup(cityValue)}|${normalizeLookup(barangayValue)}`;
        if (!queryKey || lastZipQueryByGroup[groupKey] === queryKey) {
            return;
        }
        lastZipQueryByGroup[groupKey] = queryKey;

        const searchParams = new URLSearchParams({
            city: cityValue,
            barangay: barangayValue,
        });

        try {
            const response = await window.fetch(`${zipLookupApiPath}?${searchParams.toString()}`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            const zip = (payload && typeof payload.zip === 'string') ? payload.zip.trim() : '';
            if (!zip) {
                return;
            }

            const latestCity = controls.city instanceof HTMLInputElement ? controls.city.value : '';
            const latestBarangay = controls.barangay instanceof HTMLInputElement ? controls.barangay.value : '';
            const latestKey = `${normalizeLookup(latestCity)}|${normalizeLookup(latestBarangay)}`;
            if (latestKey !== queryKey) {
                return;
            }

            controls.zip.value = zip;
            lastAutofilledZipByGroup[groupKey] = zip;
        } catch {
            // noop: keep UX silent for fallback failures
        }
    };

    const autofillZip = (groupKey) => {
        const controls = addressControls[groupKey];
        if (!controls || !(controls.zip instanceof HTMLInputElement)) {
            return;
        }

        const cityValue = controls.city instanceof HTMLInputElement ? controls.city.value : '';
        const barangayValue = controls.barangay instanceof HTMLInputElement ? controls.barangay.value : '';

        if (!cityValue || !barangayValue) {
            const lastAutofilled = lastAutofilledZipByGroup[groupKey] || '';
            if (lastAutofilled !== '' && controls.zip.value === lastAutofilled) {
                controls.zip.value = '';
                lastAutofilledZipByGroup[groupKey] = '';
            }
            return;
        }

        const cityVariants = normalizeLookupVariants(cityValue, false);
        const barangayVariants = normalizeLookupVariants(barangayValue, true);

        for (const normalizedCity of cityVariants) {
            for (const normalizedBarangay of barangayVariants) {
                const lookupKey = `${normalizedCity}|${normalizedBarangay}`;
                const zipSet = zipLookupMap.get(lookupKey);
                if (zipSet && zipSet.size === 1) {
                    const resolvedZip = Array.from(zipSet)[0] || '';
                    controls.zip.value = resolvedZip;
                    lastAutofilledZipByGroup[groupKey] = resolvedZip;
                    return;
                }
            }
        }

        for (const normalizedCity of cityVariants) {
            const cityZipSet = cityZipLookupMap.get(normalizedCity);
            if (cityZipSet && cityZipSet.size === 1) {
                const resolvedZip = Array.from(cityZipSet)[0] || '';
                controls.zip.value = resolvedZip;
                lastAutofilledZipByGroup[groupKey] = resolvedZip;
                return;
            }
        }

        requestZipFallback(groupKey, cityValue, barangayValue);

        const lastAutofilled = lastAutofilledZipByGroup[groupKey] || '';
        if (lastAutofilled !== '' && controls.zip.value === lastAutofilled) {
            controls.zip.value = '';
            lastAutofilledZipByGroup[groupKey] = '';
        }
    };

    const renderAddressGroup = (groupKey) => {
        const controls = addressControls[groupKey];
        if (!controls) {
            return;
        }

        const datalistElement = barangayDatalistByGroup[groupKey];
        if (datalistElement instanceof HTMLDataListElement && datalistElement.options.length === 0) {
            setDatalistOptions(datalistElement, resolveBarangayOptions());
        }

        autofillZip(groupKey);
    };

    const createUniqueSortedOptions = (values) => {
        const unique = new Set();
        (Array.isArray(values) ? values : []).forEach((value) => {
            const normalized = (value || '').toString().trim();
            if (normalized !== '') {
                unique.add(normalized);
            }
        });
        return Array.from(unique).sort((left, right) => left.localeCompare(right));
    };

    const allZipOptions = createUniqueSortedOptions(Array.from(new Set([
        ...Array.from(zipLookupMap.values()).flatMap((zipSet) => Array.from(zipSet || [])),
        ...Array.from(cityZipLookupMap.values()).flatMap((zipSet) => Array.from(zipSet || [])),
    ])));

    const buildModernSearch = (inputElement, optionsResolver) => {
        if (!(inputElement instanceof HTMLInputElement) || typeof optionsResolver !== 'function') {
            return;
        }

        const originalListId = inputElement.getAttribute('list') || '';
        if (originalListId !== '') {
            inputElement.removeAttribute('list');
        }

        const container = document.createElement('div');
        container.className = 'absolute z-[80] mt-1 hidden w-full rounded-md border border-slate-200 bg-white shadow-lg max-h-56 overflow-auto';
        container.setAttribute('role', 'listbox');

        const parent = inputElement.parentElement;
        if (parent) {
            parent.classList.add('relative');
            parent.appendChild(container);
        }

        const legacyChevron = parent
            ? parent.querySelector('.pointer-events-none .material-symbols-outlined')
            : null;
        if (legacyChevron instanceof HTMLElement && legacyChevron.parentElement instanceof HTMLElement) {
            legacyChevron.parentElement.classList.add('hidden');
        }

        const triggerButton = document.createElement('button');
        triggerButton.type = 'button';
        triggerButton.className = 'absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-400 hover:text-slate-600';
        triggerButton.setAttribute('aria-label', 'Show options');
        triggerButton.innerHTML = '<span class="material-symbols-outlined text-[18px]">expand_more</span>';
        if (parent) {
            parent.appendChild(triggerButton);
        }

        let activeIndex = -1;
        let activeOptions = [];

        const applyActiveOption = () => {
            const optionButtons = Array.from(container.querySelectorAll('button[data-modern-search-option]'));
            optionButtons.forEach((optionButton, optionIndex) => {
                optionButton.classList.toggle('bg-slate-100', optionIndex === activeIndex);
            });
            activeOptions = optionButtons;
        };

        const renderOptions = () => {
            const query = normalize(inputElement.value || '');
            const options = createUniqueSortedOptions(optionsResolver()).filter((option) => {
                return query === '' || normalize(option).includes(query);
            }).slice(0, 30);

            container.innerHTML = '';

            if (options.length === 0) {
                container.classList.add('hidden');
                activeIndex = -1;
                activeOptions = [];
                return;
            }

            const fragment = document.createDocumentFragment();
            options.forEach((option) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.setAttribute('data-modern-search-option', '1');
                button.className = 'w-full text-left px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 border-b border-slate-100 last:border-b-0';
                button.textContent = option;
                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    inputElement.value = option;
                    inputElement.dispatchEvent(new Event('input', { bubbles: true }));
                    inputElement.dispatchEvent(new Event('change', { bubbles: true }));
                    container.classList.add('hidden');
                    activeIndex = -1;
                });
                fragment.appendChild(button);
            });

            container.appendChild(fragment);
            container.classList.remove('hidden');
            activeIndex = -1;
            applyActiveOption();
        };

        container.addEventListener('mousedown', (event) => event.stopPropagation());

        inputElement.addEventListener('focus', renderOptions);
        inputElement.addEventListener('input', renderOptions);
        inputElement.addEventListener('keydown', (event) => {
            const optionCount = activeOptions.length;

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (container.classList.contains('hidden')) {
                    renderOptions();
                }

                const nextCount = activeOptions.length;
                if (nextCount > 0) {
                    activeIndex = (activeIndex + 1 + nextCount) % nextCount;
                    applyActiveOption();
                }
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (container.classList.contains('hidden')) {
                    renderOptions();
                }

                const nextCount = activeOptions.length;
                if (nextCount > 0) {
                    activeIndex = (activeIndex - 1 + nextCount) % nextCount;
                    applyActiveOption();
                }
                return;
            }

            if (event.key === 'Enter' && !container.classList.contains('hidden') && optionCount > 0 && activeIndex >= 0 && activeIndex < optionCount) {
                event.preventDefault();
                const activeOption = activeOptions[activeIndex];
                if (activeOption instanceof HTMLButtonElement) {
                    activeOption.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
                }
                return;
            }

            if (event.key === 'Escape') {
                container.classList.add('hidden');
                activeIndex = -1;
            }
        });

        triggerButton.addEventListener('mousedown', (event) => {
            event.preventDefault();
            event.stopPropagation();
            inputElement.focus();
            if (container.classList.contains('hidden')) {
                renderOptions();
            } else {
                container.classList.add('hidden');
                activeIndex = -1;
            }
        });

        inputElement.addEventListener('blur', () => {
            window.setTimeout(() => container.classList.add('hidden'), 120);
        });
    };

    const byId = (id) => document.getElementById(id);
    const datalistOptions = (id) => extractDatalistValues(byId(id));

    let profileEnhancementsInitialized = false;
    const initializeProfileEnhancements = () => {
        if (profileEnhancementsInitialized) {
            return;
        }

        profileEnhancementsInitialized = true;

        buildModernSearch(byId('profilePlaceOfBirth'), () => datalistOptions('profilePlaceOfBirthList'));
        buildModernSearch(byId('profileCivilStatus'), () => datalistOptions('profileCivilStatusList'));
        buildModernSearch(byId('profileBloodType'), () => datalistOptions('profileBloodTypeList'));
        buildModernSearch(byId('profileResidentialBarangay'), () => datalistOptions('profileResidentialBarangayList'));
        buildModernSearch(byId('profilePermanentBarangay'), () => datalistOptions('profilePermanentBarangayList'));
        buildModernSearch(byId('profileResidentialCity'), () => datalistOptions('profileCityList'));
        buildModernSearch(byId('profilePermanentCity'), () => datalistOptions('profileCityList'));
        buildModernSearch(byId('profileResidentialProvince'), () => datalistOptions('profileProvinceList'));
        buildModernSearch(byId('profilePermanentProvince'), () => datalistOptions('profileProvinceList'));
        buildModernSearch(byId('profileResidentialZipCode'), () => allZipOptions);
        buildModernSearch(byId('profilePermanentZipCode'), () => allZipOptions);

        Object.entries(addressControls).forEach(([groupKey, controls]) => {
            if (controls.city instanceof HTMLInputElement) {
                controls.city.addEventListener('input', () => {
                    renderAddressGroup(groupKey);
                });
                controls.city.addEventListener('change', () => {
                    renderAddressGroup(groupKey);
                });
            }

            if (controls.barangay instanceof HTMLInputElement) {
                controls.barangay.addEventListener('input', () => {
                    autofillZip(groupKey);
                });
                controls.barangay.addEventListener('change', () => {
                    autofillZip(groupKey);
                });
            }
        });
    };

    const hydrateAddressSelections = (groupKey, cityValue, barangayValue, zipValue) => {
        const controls = addressControls[groupKey];
        if (!controls) {
            return;
        }

        if (controls.city instanceof HTMLInputElement) {
            controls.city.value = cityValue || '';
        }

        if (controls.barangay instanceof HTMLInputElement) {
            controls.barangay.value = barangayValue || '';
        }

        if (controls.zip instanceof HTMLInputElement) {
            controls.zip.value = zipValue || '';
        }

        autofillZip(groupKey);
    };

    const residentialAddressFields = [
        profileFields.residentialHouseNo,
        profileFields.residentialStreet,
        profileFields.residentialSubdivision,
        profileFields.residentialBarangay,
        profileFields.residentialCity,
        profileFields.residentialProvince,
        profileFields.residentialZipCode,
    ];

    const copyResidentialToPermanentAddress = () => {
        if (profileFields.residentialHouseNo instanceof HTMLInputElement && profileFields.permanentHouseNo instanceof HTMLInputElement) {
            profileFields.permanentHouseNo.value = profileFields.residentialHouseNo.value;
        }
        if (profileFields.residentialStreet instanceof HTMLInputElement && profileFields.permanentStreet instanceof HTMLInputElement) {
            profileFields.permanentStreet.value = profileFields.residentialStreet.value;
        }
        if (profileFields.residentialSubdivision instanceof HTMLInputElement && profileFields.permanentSubdivision instanceof HTMLInputElement) {
            profileFields.permanentSubdivision.value = profileFields.residentialSubdivision.value;
        }
        if (profileFields.residentialBarangay instanceof HTMLInputElement && profileFields.permanentBarangay instanceof HTMLInputElement) {
            profileFields.permanentBarangay.value = profileFields.residentialBarangay.value;
        }
        if (profileFields.residentialCity instanceof HTMLInputElement && profileFields.permanentCity instanceof HTMLInputElement) {
            profileFields.permanentCity.value = profileFields.residentialCity.value;
        }
        if (profileFields.residentialProvince instanceof HTMLInputElement && profileFields.permanentProvince instanceof HTMLInputElement) {
            profileFields.permanentProvince.value = profileFields.residentialProvince.value;
        }
        if (profileFields.residentialZipCode instanceof HTMLInputElement && profileFields.permanentZipCode instanceof HTMLInputElement) {
            profileFields.permanentZipCode.value = profileFields.residentialZipCode.value;
        }

        renderAddressGroup('permanent');
    };

    const clearPermanentAddress = () => {
        if (profileFields.permanentHouseNo instanceof HTMLInputElement) {
            profileFields.permanentHouseNo.value = '';
        }
        if (profileFields.permanentStreet instanceof HTMLInputElement) {
            profileFields.permanentStreet.value = '';
        }
        if (profileFields.permanentSubdivision instanceof HTMLInputElement) {
            profileFields.permanentSubdivision.value = '';
        }
        if (profileFields.permanentBarangay instanceof HTMLInputElement) {
            profileFields.permanentBarangay.value = '';
        }
        if (profileFields.permanentCity instanceof HTMLInputElement) {
            profileFields.permanentCity.value = '';
        }
        if (profileFields.permanentProvince instanceof HTMLInputElement) {
            profileFields.permanentProvince.value = '';
        }
        if (profileFields.permanentZipCode instanceof HTMLInputElement) {
            profileFields.permanentZipCode.value = '';
        }

        renderAddressGroup('permanent');
    };

    if (profileFields.sameAsPermanentAddress instanceof HTMLInputElement) {
        profileFields.sameAsPermanentAddress.addEventListener('change', () => {
            if (profileFields.sameAsPermanentAddress.checked) {
                copyResidentialToPermanentAddress();
            } else {
                clearPermanentAddress();
            }
        });
    }

    residentialAddressFields.forEach((field) => {
        if (!(field instanceof HTMLInputElement)) {
            return;
        }

        field.addEventListener('input', () => {
            if (profileFields.sameAsPermanentAddress instanceof HTMLInputElement && profileFields.sameAsPermanentAddress.checked) {
                copyResidentialToPermanentAddress();
            }
        });
    });

    document.querySelectorAll('[data-person-profile-open]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!profileModal) {
                return;
            }

            initializeProfileEnhancements();

            profileFields.personId.value = button.getAttribute('data-person-id') || '';
            profileFields.employmentId.value = button.getAttribute('data-employment-id') || '';
            profileFields.employeeName.textContent = button.getAttribute('data-employee-name') || 'Selected employee';
            profileFields.firstName.value = button.getAttribute('data-first-name') || '';
            profileFields.middleName.value = button.getAttribute('data-middle-name') || '';
            profileFields.surname.value = button.getAttribute('data-surname') || '';
            profileFields.nameExtension.value = button.getAttribute('data-name-extension') || '';
            profileFields.dateOfBirth.value = button.getAttribute('data-date-of-birth') || '';
            profileFields.placeOfBirth.value = button.getAttribute('data-place-of-birth') || '';
            profileFields.sexAtBirth.value = button.getAttribute('data-sex-at-birth') || '';
            profileFields.civilStatus.value = button.getAttribute('data-civil-status') || '';
            profileFields.heightM.value = button.getAttribute('data-height-m') || '';
            profileFields.weightKg.value = button.getAttribute('data-weight-kg') || '';
            profileFields.bloodType.value = button.getAttribute('data-blood-type') || '';
            profileFields.citizenship.value = button.getAttribute('data-citizenship') || '';
            profileFields.dualCitizenshipCountry.value = button.getAttribute('data-dual-citizenship-country') || '';
            profileFields.residentialHouseNo.value = button.getAttribute('data-residential-house-no') || '';
            profileFields.residentialStreet.value = button.getAttribute('data-residential-street') || '';
            profileFields.residentialSubdivision.value = button.getAttribute('data-residential-subdivision') || '';
            profileFields.residentialProvince.value = button.getAttribute('data-residential-province') || '';
            profileFields.permanentHouseNo.value = button.getAttribute('data-permanent-house-no') || '';
            profileFields.permanentStreet.value = button.getAttribute('data-permanent-street') || '';
            profileFields.permanentSubdivision.value = button.getAttribute('data-permanent-subdivision') || '';
            profileFields.permanentProvince.value = button.getAttribute('data-permanent-province') || '';
            profileFields.umidNo.value = button.getAttribute('data-umid-no') || '';
            profileFields.pagibigNo.value = button.getAttribute('data-pagibig-no') || '';
            profileFields.philhealthNo.value = button.getAttribute('data-philhealth-no') || '';
            profileFields.psnNo.value = button.getAttribute('data-psn-no') || '';
            profileFields.tinNo.value = button.getAttribute('data-tin-no') || '';
            profileFields.agencyEmployeeNo.value = button.getAttribute('data-agency-employee-no') || '';
            profileFields.telephoneNo.value = button.getAttribute('data-telephone-no') || '';
            profileFields.email.value = button.getAttribute('data-email') || '';
            profileFields.mobile.value = button.getAttribute('data-mobile') || '';
            if (profileFields.recommendationNotes instanceof HTMLTextAreaElement) {
                profileFields.recommendationNotes.value = '';
            }
            if (profileFields.sameAsPermanentAddress instanceof HTMLInputElement) {
                profileFields.sameAsPermanentAddress.checked = false;
            }

            hydrateAddressSelections(
                'residential',
                button.getAttribute('data-residential-city-municipality') || '',
                button.getAttribute('data-residential-barangay') || '',
                button.getAttribute('data-residential-zip-code') || ''
            );

            hydrateAddressSelections(
                'permanent',
                button.getAttribute('data-permanent-city-municipality') || '',
                button.getAttribute('data-permanent-barangay') || '',
                button.getAttribute('data-permanent-zip-code') || ''
            );

            profileFamilyFields.spouseSurname.value = button.getAttribute('data-spouse-surname') || '';
            profileFamilyFields.spouseFirstName.value = button.getAttribute('data-spouse-first-name') || '';
            profileFamilyFields.spouseMiddleName.value = button.getAttribute('data-spouse-middle-name') || '';
            profileFamilyFields.spouseExtensionName.value = button.getAttribute('data-spouse-extension-name') || '';
            profileFamilyFields.spouseOccupation.value = button.getAttribute('data-spouse-occupation') || '';
            profileFamilyFields.spouseEmployerBusinessName.value = button.getAttribute('data-spouse-employer-business-name') || '';
            profileFamilyFields.spouseBusinessAddress.value = button.getAttribute('data-spouse-business-address') || '';
            profileFamilyFields.spouseTelephoneNo.value = button.getAttribute('data-spouse-telephone-no') || '';
            profileFamilyFields.fatherSurname.value = button.getAttribute('data-father-surname') || '';
            profileFamilyFields.fatherFirstName.value = button.getAttribute('data-father-first-name') || '';
            profileFamilyFields.fatherMiddleName.value = button.getAttribute('data-father-middle-name') || '';
            profileFamilyFields.fatherExtensionName.value = button.getAttribute('data-father-extension-name') || '';
            profileFamilyFields.motherSurname.value = button.getAttribute('data-mother-surname') || '';
            profileFamilyFields.motherFirstName.value = button.getAttribute('data-mother-first-name') || '';
            profileFamilyFields.motherMiddleName.value = button.getAttribute('data-mother-middle-name') || '';
            profileFamilyFields.motherExtensionName.value = button.getAttribute('data-mother-extension-name') || '';

            hydrateChildrenRows(parseJsonAttribute(button.getAttribute('data-children')));
            hydrateEducationRows(parseJsonAttribute(button.getAttribute('data-educational-backgrounds')));

            openModal(profileModal);
        });
    });

    document.querySelectorAll('[data-person-assignment-open]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!assignmentModal) {
                return;
            }

            assignmentFields.personId.value = button.getAttribute('data-person-id') || '';
            assignmentFields.employmentId.value = button.getAttribute('data-employment-id') || '';
            assignmentFields.employeeDisplay.value = button.getAttribute('data-employee-name') || '';
            if (assignmentFields.officeId instanceof HTMLSelectElement) {
                assignmentFields.officeId.value = button.getAttribute('data-current-office-id') || '';
            }
            if (assignmentFields.positionId instanceof HTMLSelectElement) {
                assignmentFields.positionId.value = button.getAttribute('data-current-position-id') || '';
            }
            if (assignmentFields.recommendationNotes instanceof HTMLTextAreaElement) {
                assignmentFields.recommendationNotes.value = '';
            }
            openModal(assignmentModal);
        });
    });

    document.querySelectorAll('[data-person-status-open]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!statusModal) {
                return;
            }

            statusFields.personId.value = button.getAttribute('data-person-id') || '';
            statusFields.employmentId.value = button.getAttribute('data-employment-id') || '';
            statusFields.employeeName.value = button.getAttribute('data-employee-name') || '-';
            statusFields.currentStatusLabel.value = button.getAttribute('data-current-status') || '-';
            statusFields.newStatus.value = 'active';

            openModal(statusModal);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((closeElement) => {
        closeElement.addEventListener('click', () => {
            const modalId = closeElement.getAttribute('data-modal-close') || '';
            if (modalId === '') {
                return;
            }

            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }

            if (modalId === 'personalInfoProfileModal') {
                closeModal(modal, profileForm);
            } else if (modalId === 'personalInfoAssignmentModal') {
                closeModal(modal, assignmentForm);
            } else if (modalId === 'personalInfoStatusModal') {
                closeModal(modal, statusForm);
            } else {
                closeModal(modal, null);
            }
        });
    });

    if (profileForm) {
        profileForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (typeof profileForm.reportValidity === 'function' && !profileForm.reportValidity()) {
                return;
            }

            const employee = (profileFields.employeeName.textContent || 'employee').trim();
            if (!window.Swal || typeof window.Swal.fire !== 'function') {
                return;
            }

            const result = await window.Swal.fire({
                icon: 'warning',
                title: 'Confirm Profile Recommendation',
                html: `<div class="text-sm text-slate-600">Submit profile update recommendation for <strong>${employee}</strong>? Final approval will be done by admin.</div>`,
                showCancelButton: true,
                confirmButtonText: 'Submit Recommendation',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
            });
            const shouldContinue = !!result.isConfirmed;

            if (!shouldContinue) {
                return;
            }

            if (profileSubmit) {
                profileSubmit.disabled = true;
                profileSubmit.classList.add('opacity-60', 'cursor-not-allowed');
            }

            profileForm.submit();
        });
    }

    if (assignmentForm) {
        assignmentForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (typeof assignmentForm.reportValidity === 'function' && !assignmentForm.reportValidity()) {
                return;
            }

            const employee = (assignmentFields.employeeDisplay.value || 'employee').trim();
            if (!window.Swal || typeof window.Swal.fire !== 'function') {
                return;
            }

            const noteResult = await window.Swal.fire({
                icon: 'question',
                title: 'Recommendation Notes Required',
                input: 'textarea',
                inputLabel: 'Explain why this division/position change is recommended',
                inputPlaceholder: 'Enter recommendation notes',
                inputValue: assignmentFields.recommendationNotes instanceof HTMLTextAreaElement
                    ? assignmentFields.recommendationNotes.value
                    : '',
                inputAttributes: {
                    maxlength: '500',
                },
                showCancelButton: true,
                confirmButtonText: 'Continue',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                inputValidator: (value) => {
                    return (value || '').trim() === ''
                        ? 'Recommendation notes are required.'
                        : undefined;
                },
            });

            if (!noteResult.isConfirmed) {
                return;
            }

            const recommendationNotes = (noteResult.value || '').trim();
            if (assignmentFields.recommendationNotes instanceof HTMLTextAreaElement) {
                assignmentFields.recommendationNotes.value = recommendationNotes;
            }

            const confirmResult = await window.Swal.fire({
                icon: 'warning',
                title: 'Confirm Recommendation',
                html: `<div class="text-sm text-slate-600">Submit division/position recommendation for <strong>${employee}</strong> to admin for final approval?</div>`,
                showCancelButton: true,
                confirmButtonText: 'Submit Recommendation',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
            });

            if (!confirmResult.isConfirmed) {
                return;
            }

            if (assignmentSubmit) {
                assignmentSubmit.disabled = true;
                assignmentSubmit.classList.add('opacity-60', 'cursor-not-allowed');
            }

            assignmentForm.submit();
        });
    }

    if (statusForm) {
        statusForm.addEventListener('submit', async (event) => {
            const newStatus = normalize(statusFields.newStatus ? statusFields.newStatus.value : '');
            if (newStatus === '') {
                return;
            }

            event.preventDefault();

            const employee = (statusFields.employeeName.value || 'employee').trim();
            const oldStatus = (statusFields.currentStatusLabel.value || '-').trim();
            const newStatusLabel = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);

            if (!window.Swal || typeof window.Swal.fire !== 'function') {
                return;
            }

            const notesField = document.getElementById('personalInfoStatusSpecification');
            const notesResult = await window.Swal.fire({
                icon: 'question',
                title: 'Recommendation Notes Required',
                input: 'textarea',
                inputLabel: `State why ${employee} should be set to ${newStatusLabel}`,
                inputPlaceholder: 'Enter recommendation notes',
                inputValue: notesField instanceof HTMLTextAreaElement ? notesField.value : '',
                inputAttributes: {
                    maxlength: '500',
                },
                showCancelButton: true,
                confirmButtonText: 'Continue',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                inputValidator: (value) => {
                    return (value || '').trim() === ''
                        ? 'Recommendation notes are required.'
                        : undefined;
                },
            });

            if (!notesResult.isConfirmed) {
                return;
            }

            if (notesField instanceof HTMLTextAreaElement) {
                notesField.value = (notesResult.value || '').trim();
            }

            let shouldContinue = false;
            const result = await window.Swal.fire({
                icon: 'warning',
                title: 'Confirm Recommendation',
                html: `<div class="text-sm text-slate-600">Submit recommendation for <strong>${employee}</strong> from <strong>${oldStatus}</strong> to <strong>${newStatusLabel}</strong>? Final approval will be done by admin.</div>`,
                showCancelButton: true,
                confirmButtonText: 'Submit Recommendation',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
            });
            shouldContinue = !!result.isConfirmed;

            if (!shouldContinue) {
                return;
            }

            if (statusSubmit) {
                statusSubmit.disabled = true;
                statusSubmit.classList.add('opacity-60', 'cursor-not-allowed');
            }

            statusForm.submit();
        });
    }

    ensureMinimumRows();
    resetProfileTabState();
    updatePaginationUi();
    applyFilters();
})();
