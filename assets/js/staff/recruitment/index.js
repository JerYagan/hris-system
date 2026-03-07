(() => {
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

    const setupTableFilters = () => {
    const searchInput = document.getElementById('recruitmentSearchInput');
    const statusFilter = document.getElementById('recruitmentStatusFilter');
    const tableRows = Array.from(document.querySelectorAll('[data-recruitment-row]'));
    const filterEmptyRow = document.getElementById('recruitmentFilterEmptyRow');

        if (!searchInput || !statusFilter) {
            return;
        }

    const applyFilters = () => {
        const query = normalize(searchInput.value);
        const status = normalize(statusFilter.value);
        let visibleCount = 0;

        tableRows.forEach((row) => {
            const haystack = normalize(row.getAttribute('data-recruitment-search'));
            const rowStatus = normalize(row.getAttribute('data-recruitment-status'));
            const visible = (query === '' || haystack.includes(query)) && (status === '' || rowStatus === status);
            row.classList.toggle('hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        if (filterEmptyRow) {
            filterEmptyRow.classList.toggle('hidden', visibleCount > 0);
        }
    };

    let debounceTimer = null;
    searchInput.addEventListener('input', () => {
        if (debounceTimer) {
            window.clearTimeout(debounceTimer);
        }
        debounceTimer = window.setTimeout(applyFilters, 150);
    });
        statusFilter.addEventListener('change', applyFilters);
        applyFilters();
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
        const postingViewData = parseJsonScriptNode('recruitmentPostingViewData');
        const modal = document.getElementById('postingViewModal');
        const openButtons = Array.from(document.querySelectorAll('[data-open-posting-view-modal]'));
        const closeButton = document.getElementById('postingViewModalClose');
        const cancelButton = document.getElementById('postingViewModalCancel');
        const applicantModal = document.getElementById('staffApplicantDecisionModal');
        const applicantModalCloseButtons = Array.from(document.querySelectorAll('[data-staff-applicant-modal-close="staffApplicantDecisionModal"]'));
        const applicantDecisionForm = document.getElementById('staffApplicantDecisionForm');
        const staffDecisionApplicationId = document.getElementById('staffDecisionApplicationId');
        const applicantProfileName = document.getElementById('staffApplicantProfileName');
        const applicantProfileMeta = document.getElementById('staffApplicantProfileMeta');
        const applicantEmployeeActionEl = document.getElementById('staffApplicantEmployeeAction');
        const applicantDocumentsBody = document.getElementById('staffApplicantDocumentsBody');

        if (!modal || openButtons.length === 0) {
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
        let currentApplicants = [];

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (requirementsEl) {
                requirementsEl.innerHTML = '';
            }
            if (applicantsBodyEl) {
                applicantsBodyEl.innerHTML = '';
            }
            currentApplicants = [];
        };

        const closeApplicantModal = () => {
            if (!applicantModal) {
                return;
            }

            applicantModal.classList.add('hidden');
            if (applicantDocumentsBody) {
                applicantDocumentsBody.innerHTML = '<tr><td class="px-3 py-3 text-slate-500" colspan="4">No document selected.</td></tr>';
            }
            if (applicantEmployeeActionEl) {
                applicantEmployeeActionEl.innerHTML = '';
            }
            if (applicantDecisionForm) {
                applicantDecisionForm.dataset.confirmed = '0';
            }
        };

        const setText = (element, text) => {
            if (element) {
                element.textContent = text || '-';
            }
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

        const openApplicantModal = (applicant) => {
            if (!applicantModal || !applicant || typeof applicant !== 'object') {
                return;
            }

            const applicationId = applicant.application_id ? applicant.application_id.toString() : '';
            if (staffDecisionApplicationId) {
                staffDecisionApplicationId.value = applicationId;
            }

            const applicantName = applicant.applicant_name ? applicant.applicant_name.toString() : 'Applicant';
            const applicantEmail = applicant.applicant_email ? applicant.applicant_email.toString() : '-';
            const appliedPosition = applicant.applied_position ? applicant.applied_position.toString() : '-';
            const submittedLabel = applicant.submitted_label ? applicant.submitted_label.toString() : '-';
            const screening = applicant.initial_screening_label ? applicant.initial_screening_label.toString() : '-';
            const alreadyEmployee = Boolean(applicant.already_employee);

            setText(applicantProfileName, applicantName);
            setText(applicantProfileMeta, `${appliedPosition} • ${applicantEmail} • Submitted ${submittedLabel} • ${screening}`);
            if (applicantEmployeeActionEl) {
                if (alreadyEmployee) {
                    applicantEmployeeActionEl.innerHTML = '<span class="px-2 py-1 text-xs rounded-full bg-slate-200 text-slate-700">Already Employee</span>';
                } else {
                    applicantEmployeeActionEl.innerHTML = '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">Read-only review</span>';
                }
            }
            renderDocuments(Array.isArray(applicant.documents) ? applicant.documents : []);

            applicantModal.classList.remove('hidden');
        };

        const renderApplicants = (applicants) => {
            if (!applicantsBodyEl) {
                return;
            }

            if (!Array.isArray(applicants) || applicants.length === 0) {
                applicantsBodyEl.innerHTML = '<tr><td colspan="6" class="px-3 py-3 text-gray-500">No applicants yet.</td></tr>';
                return;
            }

            currentApplicants = applicants;

            applicantsBodyEl.innerHTML = applicants.map((applicant, index) => {
                const applicantName = escapeHtml(applicant && applicant.applicant_name ? applicant.applicant_name : 'Applicant');
                const applicantEmail = escapeHtml(applicant && applicant.applicant_email ? applicant.applicant_email : '-');
                const appliedPosition = escapeHtml(applicant && applicant.applied_position ? applicant.applied_position : '-');
                const submittedLabel = escapeHtml(applicant && applicant.submitted_label ? applicant.submitted_label : '-');
                const screeningLabel = escapeHtml(applicant && applicant.initial_screening_label ? applicant.initial_screening_label : 'For Review');
                const screeningClass = applicant && applicant.initial_screening_class ? applicant.initial_screening_class : 'bg-slate-100 text-slate-700';
                const basis = escapeHtml(applicant && applicant.basis ? applicant.basis : '-');

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
                            <button type="button" data-staff-applicant-open data-applicant-index="${index}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-sm"><span class="material-symbols-outlined text-[16px]">person_search</span>View Profile</button>
                        </td>
                    </tr>
                `;
            }).join('');
        };

        const renderRequirements = (requirements) => {
            if (!requirementsEl) {
                return;
            }

            const items = Array.isArray(requirements) ? requirements : [];
            if (items.length === 0) {
                requirementsEl.innerHTML = '<li>-</li>';
                return;
            }

            requirementsEl.innerHTML = items
                .map((item) => `<li>${item}</li>`)
                .join('');
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const postingId = (button.getAttribute('data-posting-id') || '').trim();
                const posting = postingViewData && typeof postingViewData === 'object' ? postingViewData[postingId] : null;
                if (!posting || typeof posting !== 'object') {
                    return;
                }

                setText(positionEl, posting.position_title || posting.posting_title || '-');
                setText(officeEl, posting.office_name || '-');
                setText(employmentTypeEl, posting.employment_type || '-');
                setText(statusEl, posting.status_label || '-');
                setText(openDateEl, posting.open_date_label || '-');
                setText(closeDateEl, posting.close_date_label || '-');
                setText(descriptionEl, posting.description || '-');
                setText(qualificationsEl, posting.qualifications || '-');
                setText(responsibilitiesEl, posting.responsibilities || '-');
                renderRequirements(posting.requirements || []);
                renderApplicants(posting.applicants || []);

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        });

        if (applicantsBodyEl) {
            applicantsBodyEl.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof Element)) {
                    return;
                }

                const button = target.closest('[data-staff-applicant-open]');
                if (!(button instanceof HTMLElement)) {
                    return;
                }

                const index = Number(button.getAttribute('data-applicant-index') || '-1');
                if (!Number.isInteger(index) || index < 0 || index >= currentApplicants.length) {
                    return;
                }

                openApplicantModal(currentApplicants[index]);
            });
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
    setupTableFilters();
    setupPostingStatusModal();
    setupCreatePostingModal();
    setupPostingViewModal();
    setupUnarchiveForms();
    setupAddEmployeeForms();
})();