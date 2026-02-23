(() => {
    const byId = (id) => document.getElementById(id);

    const params = new URLSearchParams(window.location.search);
    const state = (params.get('state') || '').toLowerCase();
    const message = params.get('message') || '';
    if (message && typeof window.Swal !== 'undefined' && typeof window.Swal.fire === 'function') {
        window.Swal.fire({
            icon: state === 'success' ? 'success' : 'error',
            title: state === 'success' ? 'Success' : 'Update Failed',
            text: message,
        });
    }

    const bindTableFilters = ({
        searchId,
        statusId,
        rowSelector,
        emptyId,
        searchAttr,
        statusAttr,
        pageId,
        prevId,
        nextId,
        summaryId,
        pageSize = 10,
    }) => {
        const searchInput = byId(searchId);
        const statusFilter = byId(statusId);
        const rows = Array.from(document.querySelectorAll(rowSelector));
        const emptyRow = byId(emptyId);
        const pageLabel = byId(pageId);
        const prevButton = byId(prevId);
        const nextButton = byId(nextId);
        const summaryLabel = byId(summaryId);

        let currentPage = 1;

        const updatePagination = (matchedRows) => {
            const total = matchedRows.length;
            const totalPages = Math.max(1, Math.ceil(total / pageSize));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }
            if (currentPage < 1) {
                currentPage = 1;
            }

            const start = total === 0 ? 0 : ((currentPage - 1) * pageSize) + 1;
            const end = total === 0 ? 0 : Math.min(total, currentPage * pageSize);

            matchedRows.forEach((row, index) => {
                const inPage = index >= (currentPage - 1) * pageSize && index < currentPage * pageSize;
                row.classList.toggle('hidden', !inPage);
            });

            if (emptyRow) {
                emptyRow.classList.toggle('hidden', total > 0);
            }

            if (pageLabel) {
                pageLabel.textContent = `Page ${total === 0 ? 1 : currentPage} of ${totalPages}`;
            }
            if (summaryLabel) {
                summaryLabel.textContent = `Showing ${start}-${end} of ${total}`;
            }

            if (prevButton instanceof HTMLButtonElement) {
                prevButton.disabled = total === 0 || currentPage <= 1;
            }
            if (nextButton instanceof HTMLButtonElement) {
                nextButton.disabled = total === 0 || currentPage >= totalPages;
            }
        };

        const apply = (resetPage = false) => {
            if (resetPage) {
                currentPage = 1;
            }

            if (!rows.length) {
                if (emptyRow) {
                    emptyRow.classList.add('hidden');
                }
                if (pageLabel) {
                    pageLabel.textContent = 'Page 1 of 1';
                }
                if (summaryLabel) {
                    summaryLabel.textContent = 'Showing 0-0 of 0';
                }
                if (prevButton instanceof HTMLButtonElement) {
                    prevButton.disabled = true;
                }
                if (nextButton instanceof HTMLButtonElement) {
                    nextButton.disabled = true;
                }
                return;
            }

            const needle = (searchInput?.value || '').trim().toLowerCase();
            const status = (statusFilter?.value || '').trim().toLowerCase();
            const matchedRows = [];

            rows.forEach((row) => {
                const rowSearch = (row.getAttribute(searchAttr) || '').toLowerCase();
                const rowStatus = (row.getAttribute(statusAttr) || '').toLowerCase();

                const visible = (needle === '' || rowSearch.includes(needle))
                    && (status === '' || rowStatus === status);

                if (visible) {
                    matchedRows.push(row);
                } else {
                    row.classList.add('hidden');
                }
            });

            updatePagination(matchedRows);
        };

        searchInput?.addEventListener('input', () => apply(true));
        statusFilter?.addEventListener('change', () => apply(true));

        if (prevButton instanceof HTMLButtonElement) {
            prevButton.addEventListener('click', () => {
                currentPage -= 1;
                apply(false);
            });
        }
        if (nextButton instanceof HTMLButtonElement) {
            nextButton.addEventListener('click', () => {
                currentPage += 1;
                apply(false);
            });
        }

        apply(true);
    };

    bindTableFilters({
        searchId: 'staffLearningCourseSearch',
        statusId: 'staffLearningCourseStatusFilter',
        rowSelector: '[data-learning-course-row]',
        emptyId: 'staffLearningCourseFilterEmpty',
        searchAttr: 'data-learning-course-search',
        statusAttr: 'data-learning-course-status',
        pageId: 'staffLearningCoursePage',
        prevId: 'staffLearningCoursePrev',
        nextId: 'staffLearningCourseNext',
        summaryId: 'staffLearningCoursePaginationSummary',
        pageSize: 10,
    });

    bindTableFilters({
        searchId: 'staffLearningEnrollmentSearch',
        statusId: 'staffLearningEnrollmentStatusFilter',
        rowSelector: '[data-learning-enrollment-row]',
        emptyId: 'staffLearningEnrollmentFilterEmpty',
        searchAttr: 'data-learning-enrollment-search',
        statusAttr: 'data-learning-enrollment-status',
        pageId: 'staffLearningEnrollmentPage',
        prevId: 'staffLearningEnrollmentPrev',
        nextId: 'staffLearningEnrollmentNext',
        summaryId: 'staffLearningEnrollmentPaginationSummary',
        pageSize: 10,
    });

    const closeModal = (modalId) => {
        const modal = byId(modalId);
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    };

    const openModal = (modalId) => {
        const modal = byId(modalId);
        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
    };

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal-open') || '';
            if (modalId !== '') {
                openModal(modalId);
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal-close') || '';
            if (modalId !== '') {
                closeModal(modalId);
            }
        });
    });

    const courseViewModalId = 'staffLearningCourseViewModal';
    const courseViewTitle = byId('staffLearningCourseViewTitle');
    const courseViewStatus = byId('staffLearningCourseViewStatus');
    const courseViewProvider = byId('staffLearningCourseViewProvider');
    const courseViewType = byId('staffLearningCourseViewType');
    const courseViewCategory = byId('staffLearningCourseViewCategory');
    const courseViewMode = byId('staffLearningCourseViewMode');
    const courseViewSchedule = byId('staffLearningCourseViewSchedule');
    const courseViewVenue = byId('staffLearningCourseViewVenue');
    const courseViewEnrolleesBody = byId('staffLearningCourseViewEnrolleesBody');

    const setText = (element, value) => {
        if (element) {
            element.textContent = value && String(value).trim() !== '' ? String(value) : '-';
        }
    };

    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    document.querySelectorAll('[data-course-view-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const payloadRaw = button.getAttribute('data-course-view-payload') || '{}';
            let payload = {};
            try {
                payload = JSON.parse(payloadRaw);
            } catch {
                payload = {};
            }

            setText(courseViewTitle, payload.title);
            setText(courseViewStatus, payload.status_label);
            setText(courseViewProvider, payload.provider);
            setText(courseViewType, payload.training_type);
            setText(courseViewCategory, payload.training_category);
            setText(courseViewMode, payload.mode);
            setText(courseViewSchedule, payload.schedule);
            setText(courseViewVenue, payload.venue);

            if (courseViewEnrolleesBody) {
                const enrollees = Array.isArray(payload.enrollees) ? payload.enrollees : [];
                if (!enrollees.length) {
                    courseViewEnrolleesBody.innerHTML = '<tr><td colspan="3" class="px-4 py-3 text-slate-500">No enrolled employees found for this course.</td></tr>';
                } else {
                    courseViewEnrolleesBody.innerHTML = enrollees.map((enrollee) => {
                        const employeeName = escapeHtml(enrollee.employee_name || 'Unknown Employee');
                        const department = escapeHtml(enrollee.department || '-');
                        const statusLabel = escapeHtml(enrollee.status_label || '-');

                        return `<tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-2">${employeeName}</td>
                            <td class="px-4 py-2">${department}</td>
                            <td class="px-4 py-2">${statusLabel}</td>
                        </tr>`;
                    }).join('');
                }
            }

            openModal(courseViewModalId);
        });
    });

    const attendanceModalId = 'staffLearningAttendanceUpdateModal';
    const attendanceIdInput = byId('staffLearningAttendanceUpdateId');
    const attendanceEmployee = byId('staffLearningAttendanceUpdateEmployee');
    const attendanceCourse = byId('staffLearningAttendanceUpdateCourse');
    const attendanceCurrent = byId('staffLearningAttendanceUpdateCurrent');
    const attendanceNotes = byId('staffLearningAttendanceUpdateNotes');
    const attendanceNew = byId('staffLearningAttendanceUpdateNew');

    document.querySelectorAll('[data-attendance-update-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const enrollmentId = button.getAttribute('data-attendance-enrollment-id') || '';
            const employee = button.getAttribute('data-attendance-employee') || '';
            const course = button.getAttribute('data-attendance-course') || '';
            const currentStatus = (button.getAttribute('data-attendance-current-status') || 'enrolled').toLowerCase();
            const notes = button.getAttribute('data-attendance-notes') || '-';

            if (attendanceIdInput) {
                attendanceIdInput.value = enrollmentId;
            }
            if (attendanceEmployee) {
                attendanceEmployee.value = employee;
            }
            if (attendanceCourse) {
                attendanceCourse.value = course;
            }
            if (attendanceCurrent) {
                attendanceCurrent.value = currentStatus.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
            }
            if (attendanceNotes instanceof HTMLTextAreaElement) {
                attendanceNotes.value = notes;
            }
            if (attendanceNew instanceof HTMLSelectElement) {
                const allowed = ['enrolled', 'present', 'absent', 'dropped'];
                attendanceNew.value = allowed.includes(currentStatus) ? currentStatus : 'enrolled';
            }

            openModal(attendanceModalId);
        });
    });

    const bindConfirmSubmit = (formId, messageBuilder) => {
        const form = byId(formId);
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        form.addEventListener('submit', (event) => {
            const confirmed = window.confirm(messageBuilder());
            if (!confirmed) {
                event.preventDefault();
            }
        });
    };

    bindConfirmSubmit('staffLearningCourseCreateForm', () => {
        const form = byId('staffLearningCourseCreateForm');
        const status = form instanceof HTMLFormElement
            ? ((new FormData(form).get('course_status') || 'draft').toString())
            : 'draft';
        return `Create this learning course as ${status}?`;
    });
    bindConfirmSubmit('staffLearningEnrollmentCreateForm', () => 'Create this enrollment request as Pending?');
    const attendanceUpdateForm = byId('staffLearningAttendanceUpdateForm');
    if (attendanceUpdateForm instanceof HTMLFormElement) {
        attendanceUpdateForm.addEventListener('submit', (event) => {
            event.preventDefault();

            const employee = (attendanceEmployee instanceof HTMLInputElement ? attendanceEmployee.value : 'employee');
            const status = (attendanceNew instanceof HTMLSelectElement ? attendanceNew.value : 'enrolled').replace(/_/g, ' ');
            const title = 'Confirm Attendance Update';
            const text = `Update attendance status for ${employee} to ${status}?`;

            if (typeof window.Swal !== 'undefined' && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title,
                    text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Submit Update',
                    cancelButtonText: 'Cancel',
                }).then((result) => {
                    if (result.isConfirmed) {
                        attendanceUpdateForm.submit();
                    }
                });
                return;
            }

            if (window.confirm(text)) {
                attendanceUpdateForm.submit();
            }
        });
    }
})();
