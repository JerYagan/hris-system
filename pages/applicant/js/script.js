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

// Applied Indicator
$alreadyApplied = true; // simulate DB result

document.addEventListener('DOMContentLoaded', () => {
    const headers = document.querySelectorAll('[data-sort]');
    const tbody = document.querySelector('tbody');
    let direction = 1;

    headers.forEach(header => {
        header.addEventListener('click', () => {
            const key = header.dataset.sort;
            const rows = Array.from(tbody.querySelectorAll('tr'));

            direction *= -1;

            rows.sort((a, b) => {
                const valA = a.dataset[key];
                const valB = b.dataset[key];

                if (!isNaN(Date.parse(valA))) {
                    return direction * (new Date(valA) - new Date(valB));
                }

                return direction * valA.localeCompare(valB);
            });

            rows.forEach(row => tbody.appendChild(row));
        });
    }); 
});

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');

    if (!sidebar || !toggle) return;

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('sidebar-collapsed');
        console.log(
            sidebar.classList.contains('sidebar-collapsed')
                ? 'Sidebar closed'
                : 'Sidebar open'
        );
    });
});

