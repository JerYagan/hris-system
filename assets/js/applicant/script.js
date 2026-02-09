document.addEventListener('DOMContentLoaded', () => {
    // Highlight status visually (future-ready)
    const status = document.querySelector('[data-app-status]');
    if (!status) return;

    const value = status.dataset.appStatus;

    if (value === 'accepted') {
        status.classList.add('bg-green-100', 'text-green-800');
    }
});

// Applicant topnav dropdown
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('applicantUserMenuBtn');
    const menu = document.getElementById('applicantUserMenu');

    if (!btn || !menu) return;

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('hidden');
    });

    document.addEventListener('click', () => {
        menu.classList.add('hidden');
    });
});
