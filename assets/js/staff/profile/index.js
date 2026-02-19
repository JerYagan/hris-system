(() => {
    const form = document.getElementById('staffProfileForm');
    if (!form) {
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', (event) => {
        const shouldContinue = window.confirm('Save your updated profile information?');
        if (!shouldContinue) {
            event.preventDefault();
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('opacity-60', 'cursor-not-allowed');
        }
    });
})();