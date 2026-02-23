(() => {
    const bindConfirmSubmit = (formId, confirmMessage) => {
        const form = document.getElementById(formId);
        if (!form) {
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === '1') {
                form.dataset.confirmed = '0';
                return;
            }

            event.preventDefault();

            const continueSubmit = () => {
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-60', 'cursor-not-allowed');
                }

                form.dataset.confirmed = '1';
                form.requestSubmit();
            };

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'Please confirm',
                    text: confirmMessage,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Confirm',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#166534',
                }).then((result) => {
                    if (result.isConfirmed) {
                        continueSubmit();
                    }
                });
                return;
            }

            if (window.confirm(confirmMessage)) {
                continueSubmit();
            }
        });
    };

    bindConfirmSubmit('staffProfileForm', 'Save your updated profile information?');
    bindConfirmSubmit('staffProfilePhotoForm', 'Upload this profile photo?');
})();