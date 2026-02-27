(() => {
    const searchInput = document.getElementById('documentSearchInput');
    const statusFilter = document.getElementById('documentStatusFilter');
    const categoryFilter = document.getElementById('documentCategoryFilter');
    const tableRows = Array.from(document.querySelectorAll('[data-doc-row]'));
    const filterEmptyRow = document.getElementById('documentFilterEmptyRow');
    const pendingSearchInput = document.getElementById('pendingStaffReviewSearch');
    const pendingCategoryFilter = document.getElementById('pendingStaffReviewCategory');
    const pendingRows = Array.from(document.querySelectorAll('[data-pending-row]'));
    const pendingFilterEmptyRow = document.getElementById('pendingStaffReviewFilterEmpty');
    const archivedSearchInput = document.getElementById('archivedDocumentSearchInput');
    const archivedCategoryFilter = document.getElementById('archivedDocumentCategoryFilter');
    const archivedRows = Array.from(document.querySelectorAll('[data-archived-row]'));
    const archivedFilterEmptyRow = document.getElementById('archivedDocumentFilterEmpty');
    const uploaderTableSearch = document.getElementById('uploaderTableSearch');
    const uploaderTabButtons = Array.from(document.querySelectorAll('[data-uploader-tab]'));
    const uploaderRows = Array.from(document.querySelectorAll('[data-uploader-row]'));

    const reviewModal = document.getElementById('documentReviewModal');
    const reviewOpenButtons = Array.from(document.querySelectorAll('[data-open-review-modal]'));
    const reviewCloseButton = document.getElementById('documentReviewModalClose');
    const reviewCancelButton = document.getElementById('documentReviewModalCancel');
    const reviewForm = document.getElementById('documentReviewForm');
    const reviewSubmitButton = document.getElementById('documentReviewSubmit');

    const reviewDocumentId = document.getElementById('reviewDocumentId');
    const reviewDocumentTitle = document.getElementById('reviewDocumentTitle');
    const reviewDocumentCurrentStatus = document.getElementById('reviewDocumentCurrentStatus');
    const reviewStatusSelect = document.getElementById('reviewStatusSelect');
    const reviewViewDocumentButton = document.getElementById('reviewViewDocumentButton');

    const archiveModal = document.getElementById('documentArchiveModal');
    const archiveOpenButtons = Array.from(document.querySelectorAll('[data-open-archive-modal]'));
    const archiveCloseButton = document.getElementById('documentArchiveModalClose');
    const archiveCancelButton = document.getElementById('documentArchiveModalCancel');
    const archiveForm = document.getElementById('documentArchiveForm');
    const archiveSubmitButton = document.getElementById('documentArchiveSubmit');

    const archiveDocumentId = document.getElementById('archiveDocumentId');
    const archiveDocumentTitle = document.getElementById('archiveDocumentTitle');
    const archiveDocumentCurrentStatus = document.getElementById('archiveDocumentCurrentStatus');
    const archiveReasonInput = document.getElementById('archiveReasonInput');

    const uploaderModal = document.getElementById('uploaderDocumentsModal');
    const uploaderOpenButtons = Array.from(document.querySelectorAll('[data-open-uploader-modal]'));
    const uploaderModalClose = document.getElementById('uploaderDocumentsModalClose');
    const uploaderModalCancel = document.getElementById('uploaderDocumentsModalCancel');
    const uploaderTitle = document.getElementById('uploaderDocumentsTitle');
    const uploaderMeta = document.getElementById('uploaderDocumentsMeta');
    const uploaderBody = document.getElementById('uploaderDocumentsBody');
    const uploaderSearch = document.getElementById('uploaderModalSearch');
    const uploaderCategoryFilter = document.getElementById('uploaderModalCategoryFilter');

    const normalize = (value) => (value || '').toString().trim().toLowerCase();
    const PER_PAGE = 10;

    const paginationByTable = new Map();
    Array.from(document.querySelectorAll('[data-pagination-controls]')).forEach((container) => {
        const tableId = container.getAttribute('data-pagination-controls');
        if (!tableId) {
            return;
        }

        const prevButton = container.querySelector('[data-pagination-prev]');
        const nextButton = container.querySelector('[data-pagination-next]');
        const pageLabel = container.querySelector('[data-pagination-page]');
        const countLabel = container.querySelector('[data-pagination-label]');

        paginationByTable.set(tableId, {
            page: 1,
            prevButton,
            nextButton,
            pageLabel,
            countLabel,
        });
    });

    const setPage = (tableId, page) => {
        const state = paginationByTable.get(tableId);
        if (!state) {
            return;
        }
        state.page = Math.max(1, page);
    };

    const renderPaginatedRows = (tableId, allRows, filteredRows, emptyRow) => {
        const state = paginationByTable.get(tableId);
        if (!state) {
            allRows.forEach((row) => row.classList.toggle('hidden', !filteredRows.includes(row)));
            if (emptyRow) {
                emptyRow.classList.toggle('hidden', filteredRows.length > 0);
            }
            return;
        }

        const totalRows = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / PER_PAGE));
        if (state.page > totalPages) {
            state.page = totalPages;
        }

        const start = (state.page - 1) * PER_PAGE;
        const end = start + PER_PAGE;
        const pageRows = filteredRows.slice(start, end);

        allRows.forEach((row) => {
            row.classList.toggle('hidden', !pageRows.includes(row));
        });

        if (emptyRow) {
            emptyRow.classList.toggle('hidden', totalRows > 0);
        }

        if (state.pageLabel) {
            state.pageLabel.textContent = `Page ${state.page} of ${totalPages}`;
        }
        if (state.countLabel) {
            const shown = totalRows === 0 ? 0 : Math.min(end, totalRows);
            state.countLabel.textContent = `Showing ${start + (totalRows === 0 ? 0 : 1)}-${shown} of ${totalRows}`;
        }
        if (state.prevButton) {
            state.prevButton.disabled = state.page <= 1;
            state.prevButton.classList.toggle('opacity-60', state.page <= 1);
            state.prevButton.classList.toggle('cursor-not-allowed', state.page <= 1);
        }
        if (state.nextButton) {
            state.nextButton.disabled = state.page >= totalPages;
            state.nextButton.classList.toggle('opacity-60', state.page >= totalPages);
            state.nextButton.classList.toggle('cursor-not-allowed', state.page >= totalPages);
        }
    };

    paginationByTable.forEach((state, tableId) => {
        if (state.prevButton) {
            state.prevButton.addEventListener('click', () => {
                if (state.page <= 1) {
                    return;
                }
                state.page -= 1;
                applyAllFilters();
            });
        }

        if (state.nextButton) {
            state.nextButton.addEventListener('click', () => {
                state.page += 1;
                applyAllFilters();
            });
        }
    });

    const escapeHtml = (value) => (value || '')
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const applyRegistryFilters = () => {
        if (!searchInput || !statusFilter || !categoryFilter) {
            return;
        }

        const query = normalize(searchInput.value);
        const status = normalize(statusFilter.value);
        const category = normalize(categoryFilter.value);
        const filteredRows = tableRows.filter((row) => {
            const haystack = normalize(row.getAttribute('data-doc-search'));
            const rowStatus = normalize(row.getAttribute('data-doc-status'));
            const rowCategory = normalize(row.getAttribute('data-doc-category'));

            const matchesSearch = query === '' || haystack.includes(query);
            const matchesStatus = status === '' || rowStatus === status;
            const matchesCategory = category === '' || rowCategory === category;
            return matchesSearch && matchesStatus && matchesCategory;
        });

        renderPaginatedRows('staffDocumentTable', tableRows, filteredRows, filterEmptyRow);
    };

    const applyPendingFilters = () => {
        if (!pendingSearchInput || !pendingCategoryFilter) {
            return;
        }

        const query = normalize(pendingSearchInput.value);
        const category = normalize(pendingCategoryFilter.value);

        const filteredRows = pendingRows.filter((row) => {
            const haystack = normalize(row.getAttribute('data-pending-search'));
            const rowCategory = normalize(row.getAttribute('data-pending-category'));
            const matchesSearch = query === '' || haystack.includes(query);
            const matchesCategory = category === '' || rowCategory === category;
            return matchesSearch && matchesCategory;
        });

        renderPaginatedRows('pendingStaffReviewTable', pendingRows, filteredRows, pendingFilterEmptyRow);
    };

    const applyArchivedFilters = () => {
        if (!archivedSearchInput || !archivedCategoryFilter) {
            return;
        }

        const query = normalize(archivedSearchInput.value);
        const category = normalize(archivedCategoryFilter.value);

        const filteredRows = archivedRows.filter((row) => {
            const haystack = normalize(row.getAttribute('data-archived-search'));
            const rowCategory = normalize(row.getAttribute('data-archived-category'));
            const matchesSearch = query === '' || haystack.includes(query);
            const matchesCategory = category === '' || rowCategory === category;
            return matchesSearch && matchesCategory;
        });

        renderPaginatedRows('archivedDocumentTable', archivedRows, filteredRows, archivedFilterEmptyRow);
    };

    let activeUploaderTypeTab = 'all';
    const applyUploaderTableFilters = () => {
        const query = normalize(uploaderTableSearch ? uploaderTableSearch.value : '');

        const filteredRows = uploaderRows.filter((row) => {
            const rowType = normalize(row.getAttribute('data-uploader-type'));
            const rowSearch = normalize(row.getAttribute('data-uploader-search'));
            const matchesType = activeUploaderTypeTab === 'all' || rowType === activeUploaderTypeTab;
            const matchesSearch = query === '' || rowSearch.includes(query);
            return matchesType && matchesSearch;
        });

        renderPaginatedRows('staffDocumentUploadersTable', uploaderRows, filteredRows, null);
    };

    const applyAllFilters = () => {
        applyRegistryFilters();
        applyPendingFilters();
        applyArchivedFilters();
        applyUploaderTableFilters();
    };

    if (searchInput && statusFilter && categoryFilter && tableRows.length > 0) {
        let debounceTimer = null;
        searchInput.addEventListener('input', () => {
            setPage('staffDocumentTable', 1);
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(applyRegistryFilters, 150);
        });

        statusFilter.addEventListener('change', () => {
            setPage('staffDocumentTable', 1);
            applyRegistryFilters();
        });
        categoryFilter.addEventListener('change', () => {
            setPage('staffDocumentTable', 1);
            applyRegistryFilters();
        });
        applyRegistryFilters();
    }

    if (pendingSearchInput && pendingCategoryFilter && pendingRows.length > 0) {
        let debounceTimer = null;
        pendingSearchInput.addEventListener('input', () => {
            setPage('pendingStaffReviewTable', 1);
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(applyPendingFilters, 150);
        });
        pendingCategoryFilter.addEventListener('change', () => {
            setPage('pendingStaffReviewTable', 1);
            applyPendingFilters();
        });
        applyPendingFilters();
    }

    if (archivedSearchInput && archivedCategoryFilter && archivedRows.length > 0) {
        let debounceTimer = null;
        archivedSearchInput.addEventListener('input', () => {
            setPage('archivedDocumentTable', 1);
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(applyArchivedFilters, 150);
        });
        archivedCategoryFilter.addEventListener('change', () => {
            setPage('archivedDocumentTable', 1);
            applyArchivedFilters();
        });
        applyArchivedFilters();
    }

    if (uploaderTableSearch && uploaderRows.length > 0) {
        let debounceTimer = null;
        uploaderTableSearch.addEventListener('input', () => {
            setPage('staffDocumentUploadersTable', 1);
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(applyUploaderTableFilters, 150);
        });
    }

    if (uploaderTabButtons.length > 0) {
        uploaderTabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeUploaderTypeTab = normalize(button.getAttribute('data-uploader-tab')) || 'all';
                uploaderTabButtons.forEach((tabButton) => {
                    const isActive = normalize(tabButton.getAttribute('data-uploader-tab')) === activeUploaderTypeTab;
                    tabButton.classList.toggle('bg-green-700', isActive);
                    tabButton.classList.toggle('text-white', isActive);
                    tabButton.classList.toggle('bg-white', !isActive);
                    tabButton.classList.toggle('text-gray-700', !isActive);
                });
                setPage('staffDocumentUploadersTable', 1);
                applyUploaderTableFilters();
            });
        });
        applyUploaderTableFilters();
    }

    const openModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const closeModal = (modal) => {
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    reviewOpenButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!reviewDocumentId || !reviewDocumentTitle || !reviewDocumentCurrentStatus) {
                return;
            }

            reviewDocumentId.value = button.getAttribute('data-document-id') || '';
            reviewDocumentTitle.textContent = button.getAttribute('data-document-title') || 'Document';
            reviewDocumentCurrentStatus.textContent = button.getAttribute('data-current-status') || '-';

            const viewUrl = (button.getAttribute('data-document-view-url') || '').trim();
            if (reviewViewDocumentButton) {
                reviewViewDocumentButton.href = viewUrl || '#';
                const viewEnabled = viewUrl !== '';
                reviewViewDocumentButton.classList.toggle('pointer-events-none', !viewEnabled);
                reviewViewDocumentButton.classList.toggle('opacity-60', !viewEnabled);
            }

            const previousRecommendation = normalize(button.getAttribute('data-previous-recommendation') || '');
            const previousNotes = (button.getAttribute('data-previous-notes') || '').toString();

            if (reviewStatusSelect) {
                if (previousRecommendation === 'approved' || previousRecommendation === 'rejected') {
                    reviewStatusSelect.value = previousRecommendation;
                } else {
                    reviewStatusSelect.value = '';
                }
            }

            const notesInput = document.getElementById('reviewNotesInput');
            if (notesInput) {
                notesInput.value = previousNotes;
            }

            openModal(reviewModal);
        });
    });

    if (reviewCloseButton) {
        reviewCloseButton.addEventListener('click', () => closeModal(reviewModal));
    }

    if (reviewCancelButton) {
        reviewCancelButton.addEventListener('click', () => closeModal(reviewModal));
    }

    if (reviewModal) {
        reviewModal.addEventListener('click', (event) => {
            if (event.target === reviewModal) {
                closeModal(reviewModal);
            }
        });
    }

    if (reviewForm) {
        reviewForm.addEventListener('submit', (event) => {
            if (reviewForm.dataset.confirmed === '1') {
                reviewForm.dataset.confirmed = '0';
                return;
            }

            const statusValue = normalize(reviewStatusSelect ? reviewStatusSelect.value : '');
            if (statusValue === '') {
                return;
            }

            const decisionLabel = statusValue.replace('_', ' ');
            const title = (reviewDocumentTitle ? reviewDocumentTitle.textContent : 'Document') || 'Document';
            const oldStatus = (reviewDocumentCurrentStatus ? reviewDocumentCurrentStatus.textContent : '-') || '-';
            const notesInput = document.getElementById('reviewNotesInput');
            const notesValue = normalize(notesInput ? notesInput.value : '');

            if (notesValue === '') {
                event.preventDefault();
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'warning',
                        title: 'Recommendation notes required',
                        text: 'Please provide reason/notes before sending to admin.',
                        confirmButtonColor: '#166534',
                    });
                }
                return;
            }

            event.preventDefault();

            const continueSubmit = () => {
                if (reviewSubmitButton) {
                    reviewSubmitButton.disabled = true;
                    reviewSubmitButton.classList.add('opacity-60', 'cursor-not-allowed');
                }

                reviewForm.dataset.confirmed = '1';
                reviewForm.requestSubmit();
            };

            const confirmText = `Send recommendation for "${title}" from current status "${oldStatus}" to "${decisionLabel}"? Admin will make the final decision.`;

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'Send recommendation?',
                    text: confirmText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, send',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#166534',
                }).then((result) => {
                    if (result.isConfirmed) {
                        continueSubmit();
                    }
                });
                return;
            }
        });
    }

    archiveOpenButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!archiveDocumentId || !archiveDocumentTitle || !archiveDocumentCurrentStatus) {
                return;
            }

            archiveDocumentId.value = button.getAttribute('data-document-id') || '';
            archiveDocumentTitle.textContent = button.getAttribute('data-document-title') || 'Document';
            archiveDocumentCurrentStatus.textContent = button.getAttribute('data-current-status') || '-';
            if (archiveReasonInput) {
                archiveReasonInput.value = '';
            }

            openModal(archiveModal);
        });
    });

    if (archiveCloseButton) {
        archiveCloseButton.addEventListener('click', () => closeModal(archiveModal));
    }

    if (archiveCancelButton) {
        archiveCancelButton.addEventListener('click', () => closeModal(archiveModal));
    }

    if (archiveModal) {
        archiveModal.addEventListener('click', (event) => {
            if (event.target === archiveModal) {
                closeModal(archiveModal);
            }
        });
    }

    if (archiveForm) {
        archiveForm.addEventListener('submit', (event) => {
            if (archiveForm.dataset.confirmed === '1') {
                archiveForm.dataset.confirmed = '0';
                return;
            }

            const title = (archiveDocumentTitle ? archiveDocumentTitle.textContent : 'Document') || 'Document';
            const reasonValue = normalize(archiveReasonInput ? archiveReasonInput.value : '');
            event.preventDefault();

            if (reasonValue === '') {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'warning',
                        title: 'Archive reason required',
                        text: 'Please provide archive notes before continuing.',
                        confirmButtonColor: '#1f2937',
                    });
                }
                return;
            }

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'Archive document?',
                    text: `"${title}" will be moved to archived records and view/download will be disabled.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, archive',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#1f2937',
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    if (archiveSubmitButton) {
                        archiveSubmitButton.disabled = true;
                        archiveSubmitButton.classList.add('opacity-60', 'cursor-not-allowed');
                    }

                    archiveForm.dataset.confirmed = '1';
                    archiveForm.requestSubmit();
                });

                return;
            }
        });
    }

    const restoreForms = Array.from(document.querySelectorAll('form input[name="form_action"][value="restore_document"]')).map((input) => input.closest('form')).filter(Boolean);
    restoreForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === '1') {
                form.dataset.confirmed = '0';
                return;
            }

            event.preventDefault();
            if (!window.Swal || typeof window.Swal.fire !== 'function') {
                return;
            }

            window.Swal.fire({
                title: 'Restore document?',
                text: 'Provide reason before restoring this archived document.',
                icon: 'warning',
                input: 'textarea',
                inputPlaceholder: 'Enter restore reason',
                inputAttributes: {
                    maxlength: 500,
                    'aria-label': 'Restore reason',
                },
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'Restore reason is required.';
                    }
                    return null;
                },
                showCancelButton: true,
                confirmButtonText: 'Yes, restore',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#4338ca',
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const reasonInput = form.querySelector('[data-restore-reason]');
                if (reasonInput) {
                    reasonInput.value = (result.value || '').toString().trim();
                }
                form.dataset.confirmed = '1';
                form.requestSubmit();
            });
        });
    });

    const resolveTypeMeta = (document) => {
        const icon = document.file_type_icon || 'draft';
        const label = document.file_type_label || 'File';
        const cssClass = document.file_type_class || 'bg-slate-100 text-slate-700';

        return { icon, label, cssClass };
    };

    let uploaderDocuments = [];

    const renderUploaderRows = () => {
        if (!uploaderBody) {
            return;
        }

        const searchValue = normalize(uploaderSearch ? uploaderSearch.value : '');
        const categoryValue = normalize(uploaderCategoryFilter ? uploaderCategoryFilter.value : '');

        const filtered = uploaderDocuments.filter((document) => {
            const searchText = normalize(`${document.title || ''} ${document.category || ''} ${document.status || ''} ${document.source || ''}`);
            const category = normalize(document.category || '');

            const matchesSearch = searchValue === '' || searchText.includes(searchValue);
            const matchesCategory = categoryValue === '' || category === categoryValue;

            return matchesSearch && matchesCategory;
        });

        if (filtered.length === 0) {
            uploaderBody.innerHTML = '<tr><td class="px-4 py-3 text-gray-500" colspan="6">No documents match your current search/filter criteria.</td></tr>';
            return;
        }

        uploaderBody.innerHTML = filtered.map((document) => {
            const title = escapeHtml(document.title || 'Document');
            const category = escapeHtml(document.category || '-');
            const status = escapeHtml(document.status || '-');
            const updated = escapeHtml(document.updated || '-');
            const source = escapeHtml(document.source || '-');
            const viewUrl = (document.view_url || '').toString().trim();
            const downloadUrl = (document.download_url || '').toString().trim();

            const viewEnabled = viewUrl !== '';
            const downloadEnabled = downloadUrl !== '';
            const viewClass = viewEnabled ? 'border-gray-300 text-gray-700 hover:bg-gray-50' : 'border-gray-200 text-gray-400 pointer-events-none bg-gray-50';
            const downloadClass = downloadEnabled ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' : 'border-gray-200 text-gray-400 pointer-events-none bg-gray-50';

            const typeMeta = resolveTypeMeta(document);

            return `
                <tr>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs ${escapeHtml(typeMeta.cssClass)}">
                            <span class="material-symbols-outlined text-[14px]">${escapeHtml(typeMeta.icon)}</span>
                            ${escapeHtml(typeMeta.label)}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800">${title}</p>
                        <p class="text-xs text-gray-500 mt-1">${source}</p>
                    </td>
                    <td class="px-4 py-3 text-gray-700">${category}</td>
                    <td class="px-4 py-3 text-gray-700">${status}</td>
                    <td class="px-4 py-3 text-gray-700">${updated}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="${escapeHtml(viewUrl || '#')}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border ${viewClass}">
                                <span class="material-symbols-outlined text-[14px]">visibility</span>View
                            </a>
                            <a href="${escapeHtml(downloadUrl || '#')}" target="_blank" rel="noopener noreferrer" download class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-md border ${downloadClass}">
                                <span class="material-symbols-outlined text-[14px]">download</span>Download
                            </a>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    };

    uploaderOpenButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!uploaderModal || !uploaderTitle || !uploaderMeta || !uploaderBody) {
                return;
            }

            const uploaderName = button.getAttribute('data-uploader-name') || 'Uploader';
            const uploaderType = button.getAttribute('data-uploader-type') || 'User';
            const rawDocuments = button.getAttribute('data-uploader-documents') || '[]';

            try {
                const parsed = JSON.parse(rawDocuments);
                uploaderDocuments = Array.isArray(parsed) ? parsed : [];
            } catch (_error) {
                uploaderDocuments = [];
            }

            if (uploaderSearch) {
                uploaderSearch.value = '';
            }
            if (uploaderCategoryFilter) {
                uploaderCategoryFilter.value = '';
            }

            uploaderTitle.textContent = `${uploaderName} - Uploaded Documents`;
            uploaderMeta.textContent = `${uploaderType} · ${uploaderDocuments.length} record(s)`;

            renderUploaderRows();
            openModal(uploaderModal);
        });
    });

    if (uploaderSearch) {
        let debounceTimer = null;
        uploaderSearch.addEventListener('input', () => {
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(renderUploaderRows, 150);
        });
    }

    if (uploaderCategoryFilter) {
        uploaderCategoryFilter.addEventListener('change', renderUploaderRows);
    }

    if (uploaderModalClose) {
        uploaderModalClose.addEventListener('click', () => closeModal(uploaderModal));
    }

    if (uploaderModalCancel) {
        uploaderModalCancel.addEventListener('click', () => closeModal(uploaderModal));
    }

    if (uploaderModal) {
        uploaderModal.addEventListener('click', (event) => {
            if (event.target === uploaderModal) {
                closeModal(uploaderModal);
            }
        });
    }
})();
