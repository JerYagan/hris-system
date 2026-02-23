(() => {
    const searchInput = document.getElementById('documentSearchInput');
    const statusFilter = document.getElementById('documentStatusFilter');
    const categoryFilter = document.getElementById('documentCategoryFilter');
    const tableRows = Array.from(document.querySelectorAll('[data-doc-row]'));
    const filterEmptyRow = document.getElementById('documentFilterEmptyRow');

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
        let visibleCount = 0;

        tableRows.forEach((row) => {
            const haystack = normalize(row.getAttribute('data-doc-search'));
            const rowStatus = normalize(row.getAttribute('data-doc-status'));
            const rowCategory = normalize(row.getAttribute('data-doc-category'));

            const matchesSearch = query === '' || haystack.includes(query);
            const matchesStatus = status === '' || rowStatus === status;
            const matchesCategory = category === '' || rowCategory === category;
            const visible = matchesSearch && matchesStatus && matchesCategory;

            row.classList.toggle('hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        if (filterEmptyRow) {
            filterEmptyRow.classList.toggle('hidden', visibleCount > 0);
        }
    };

    if (searchInput && statusFilter && categoryFilter && tableRows.length > 0) {
        let debounceTimer = null;
        searchInput.addEventListener('input', () => {
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }
            debounceTimer = window.setTimeout(applyRegistryFilters, 150);
        });

        statusFilter.addEventListener('change', applyRegistryFilters);
        categoryFilter.addEventListener('change', applyRegistryFilters);
        applyRegistryFilters();
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

            if (reviewStatusSelect) {
                reviewStatusSelect.value = '';
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

            if (window.confirm(confirmText)) {
                continueSubmit();
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
            event.preventDefault();

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

            const shouldContinue = window.confirm(`Archive "${title}"? Archived documents can no longer be viewed/downloaded.`);
            if (!shouldContinue) {
                return;
            }

            if (archiveSubmitButton) {
                archiveSubmitButton.disabled = true;
                archiveSubmitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }

            archiveForm.dataset.confirmed = '1';
            archiveForm.requestSubmit();
        });
    }

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
