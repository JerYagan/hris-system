(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const closeButton = document.getElementById('sidebarClose');
    const overlay = document.getElementById('sidebarOverlay');
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');
    const topnav = document.getElementById('topnav');
    const categoryToggles = document.querySelectorAll('[data-cat-toggle]');

    if (!sidebar || !toggle || !overlay) return;

    let isOpen = false;

    function applySidebarState() {
      if (isOpen) {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100');
        sidebar.setAttribute('aria-hidden', 'false');
      } else {
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        overlay.classList.remove('opacity-100');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        sidebar.setAttribute('aria-hidden', 'true');
      }
    }

    toggle.addEventListener('click', () => {
      isOpen = !isOpen;
      applySidebarState();
    });

    if (closeButton) {
      closeButton.addEventListener('click', () => {
        isOpen = false;
        applySidebarState();
      });
    }

    overlay.addEventListener('click', () => {
      isOpen = false;
      applySidebarState();
    });

    document.addEventListener('click', (event) => {
      if (!isOpen) return;
      const clickedInsideSidebar = sidebar.contains(event.target);
      const clickedToggle = toggle.contains(event.target);
      const clickedOverlay = overlay.contains(event.target);

      if (!clickedInsideSidebar && !clickedToggle && !clickedOverlay) {
        isOpen = false;
        applySidebarState();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && isOpen) {
        isOpen = false;
        applySidebarState();
      }
    });

    if (profileToggle && profileMenu) {
      profileToggle.addEventListener('click', (event) => {
        event.stopPropagation();
        profileMenu.classList.toggle('hidden');
      });

      document.addEventListener('click', (event) => {
        if (!profileMenu.classList.contains('hidden') && !profileMenu.contains(event.target) && !profileToggle.contains(event.target)) {
          profileMenu.classList.add('hidden');
        }
      });
    }

    if (topnav) {
      let lastScrollY = window.scrollY;
      const navHeight = topnav.offsetHeight;

      window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;
        const delta = currentScrollY - lastScrollY;

        if (Math.abs(delta) < 8) return;

        if (delta > 0 && currentScrollY > navHeight) {
          topnav.classList.add('-translate-y-full');
        } else {
          topnav.classList.remove('-translate-y-full');
        }

        lastScrollY = currentScrollY;
      });
    }

    if (categoryToggles.length) {
      categoryToggles.forEach((toggleButton) => {
        const key = toggleButton.getAttribute('data-cat-toggle');
        const content = document.querySelector(`[data-cat-content="${key}"]`);
        if (!content) return;

        toggleButton.addEventListener('click', () => {
          const expanded = toggleButton.getAttribute('aria-expanded') === 'true';
          toggleButton.setAttribute('aria-expanded', expanded ? 'false' : 'true');
          content.classList.toggle('hidden', expanded);
        });
      });
    }

    applySidebarState();
  });
})();
