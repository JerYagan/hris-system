(() => {
const closeTopnavNotifications = () => {
  document.dispatchEvent(new CustomEvent("hris:close-topnav-notifications", { detail: { source: "employee-shell" } }));
};

document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("sidebar");
  const toggle = document.getElementById("sidebarToggle");
  const main = document.getElementById("mainContent");
  const close = document.getElementById("sidebarClose");
  const backdrop = document.getElementById("sidebarBackdrop");
  const isOverlayMode = sidebar?.dataset.sidebarMode === "overlay";
  const profileMenu = document.getElementById("profileMenu");

  // Exit early if layout elements are missing
  if (!sidebar || !toggle || !main) return;

  let isOpen = isOverlayMode ? false : window.innerWidth >= 768;

  const closeProfileMenu = () => {
    if (profileMenu) {
      profileMenu.classList.add("hidden");
    }
  };

  function applyLayout() {
    if (isOpen) {
      closeProfileMenu();
      closeTopnavNotifications();
      sidebar.classList.remove("-translate-x-full");
      if (!isOverlayMode) {
        main.classList.add("md:ml-64");
      }
      backdrop?.classList.remove("hidden");
      document.body.classList.add("overflow-hidden");
      document.dispatchEvent(new CustomEvent("hris:sidebar-opened", { detail: { source: "employee-sidebar" } }));
    } else {
      sidebar.classList.add("-translate-x-full");
      if (!isOverlayMode) {
        main.classList.remove("md:ml-64");
      }
      backdrop?.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }

    if (!isOverlayMode && window.innerWidth >= 768) {
      backdrop?.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  }

  // Initial render
  applyLayout();

  // Toggle handler
  toggle.addEventListener("click", () => {
    isOpen = !isOpen;
    applyLayout();
  });

  close?.addEventListener("click", () => {
    isOpen = false;
    applyLayout();
  });

  document.addEventListener("hris:request-close-sidebar", () => {
    if (!isOpen) {
      return;
    }

    isOpen = false;
    applyLayout();
  });

  backdrop?.addEventListener("click", () => {
    isOpen = false;
    applyLayout();
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && isOpen) {
      isOpen = false;
      applyLayout();
    }
  });

  // Handle resize edge cases
  window.addEventListener("resize", () => {
    if (isOverlayMode) {
      isOpen = false;
    } else if (window.innerWidth < 768) {
      isOpen = false;
    }
    applyLayout();
  });
});

// ===== AUTO-HIDE TOPNAV ON SCROLL =====
const topnav = document.getElementById("topnav");

if (topnav) {
  let lastScrollY = window.scrollY;
  const navHeight = topnav.offsetHeight;

  window.addEventListener("scroll", () => {
    const currentScrollY = window.scrollY;

    // Ignore tiny scroll movements
    if (Math.abs(currentScrollY - lastScrollY) < 10) return;

    if (currentScrollY > lastScrollY && currentScrollY > navHeight) {
      // Scrolling down → hide
      topnav.classList.add("-translate-y-full");
    } else {
      // Scrolling up → show
      topnav.classList.remove("-translate-y-full");
    }

    lastScrollY = currentScrollY;
  });
}

const ctx = document.getElementById("leaveChart");

new Chart(ctx, {
  type: "doughnut",
  data: {
    labels: ["Approved", "Pending", "Rejected"],
    datasets: [
      {
        data: [4, 2, 1],
        backgroundColor: ["#B7F7A3", "#F9F871", "#FF9A9A"],
        borderWidth: 0,
      },
    ],
  },
  options: {
    plugins: {
      legend: {
        position: "bottom",
      },
    },
  },
});

/* ===============================
   REPORT CHART PLACEHOLDERS
   =============================== */
function initReportCharts() {

  // Attendance Chart
  const attendanceCtx = document.getElementById("attendanceChart");
  if (attendanceCtx && !attendanceCtx.chart) {
    attendanceCtx.chart = new Chart(attendanceCtx, {
      type: "bar",
      data: {
        labels: ["Present", "Leave", "Late"],
        datasets: [{
          data: [22, 2, 1],
          borderWidth: 1
        }]
      },
      options: { responsive: true }
    });
  }

  // Payroll Chart
  const payrollCtx = document.getElementById("payrollChart");
  if (payrollCtx && !payrollCtx.chart) {
    payrollCtx.chart = new Chart(payrollCtx, {
      type: "line",
      data: {
        labels: ["Nov", "Dec", "Jan"],
        datasets: [{
          data: [18450, 18450, 18450],
          tension: 0.3
        }]
      },
      options: { responsive: true }
    });
  }

  // Performance Chart
  const performanceCtx = document.getElementById("performanceChart");
  if (performanceCtx && !performanceCtx.chart) {
    performanceCtx.chart = new Chart(performanceCtx, {
      type: "radar",
      data: {
        labels: ["Self", "Supervisor", "Peer"],
        datasets: [{
          data: [4.5, 4.7, 4.3],
        }]
      },
      options: { responsive: true }
    });
  }
}

/* ===============================
   REPORT EXPORT HANDLER
   =============================== */
document.querySelectorAll("[data-export]").forEach(btn => {
  btn.addEventListener("click", () => {
    const type = btn.dataset.export;

    // For now: redirect to PHP export stub
    window.open(`../export/${type}.php`, "_blank");
  });
});

/* ===============================
   COMPACT SIDEBAR MODE
   =============================== */
const compactToggle = document.getElementById("compactSidebarToggle");
const sidebar = document.getElementById("sidebar");

if (compactToggle && sidebar) {
  compactToggle.addEventListener("click", () => {
    sidebar.classList.toggle("sidebar-compact");

    // Auto-collapse all sections in compact mode
    document
      .querySelectorAll("[data-collapse-content]")
      .forEach(section => {
        section.classList.toggle(
          "hidden",
          sidebar.classList.contains("sidebar-compact")
        );
      });
  });
}

/* ===============================
   SIDEBAR COLLAPSIBLE SECTIONS
   =============================== */
document.querySelectorAll("[data-collapse-toggle]").forEach(toggle => {
  toggle.addEventListener("click", () => {
    const key = toggle.dataset.collapseToggle;
    const content = document.querySelector(
      `[data-collapse-content="${key}"]`
    );
    const icon = toggle.querySelector(".material-icons");

    if (!content) return;

    content.classList.toggle("hidden");
    icon.classList.toggle("rotate-180");
  });
});

/* ===============================
   SUPPORT PAGE MODALS
   =============================== */
const supportMap = [
  ['alerts', 'alertsModal'],
  ['ticket', 'ticketModal'],
  ['updates', 'updatesModal'],
];

supportMap.forEach(([key, modalId]) => {
  const modal = document.getElementById(modalId);

  document.querySelectorAll(`[data-open-${key}]`).forEach(btn =>
    btn.addEventListener("click", () => modal?.classList.remove("hidden"))
  );

  document.querySelectorAll(`[data-close-${key}]`).forEach(btn =>
    btn.addEventListener("click", () => modal?.classList.add("hidden"))
  );

  modal?.addEventListener("click", e => {
    if (e.target === modal) modal.classList.add("hidden");
  });
});

/* ===============================
   PROFILE DROPDOWN
   =============================== */
const profileToggle = document.getElementById("profileToggle");
const profileMenu = document.getElementById("profileMenu");

if (profileToggle && profileMenu) {
  profileToggle.addEventListener("click", (e) => {
    e.stopPropagation();
    closeTopnavNotifications();
    profileMenu.classList.toggle("hidden");

    if (!profileMenu.classList.contains("hidden")) {
      document.dispatchEvent(new CustomEvent("hris:profile-menu-opened", { detail: { source: "employee-profile" } }));
    }
  });

  document.addEventListener("click", () => {
    profileMenu.classList.add("hidden");
  });
}

/* ===============================
   SETTINGS MODALS
   =============================== */
const settingsMap = [
  ['password', 'passwordModal'],
  ['logout', 'logoutModal'],
];

settingsMap.forEach(([key, modalId]) => {
  const modal = document.getElementById(modalId);

  document.querySelectorAll(`[data-open-${key}]`).forEach(btn =>
    btn.addEventListener("click", () => modal?.classList.remove("hidden"))
  );

  document.querySelectorAll(`[data-close-${key}]`).forEach(btn =>
    btn.addEventListener("click", () => modal?.classList.add("hidden"))
  );

  modal?.addEventListener("click", e => {
    if (e.target === modal) modal.classList.add("hidden");
  });
});

/* ===============================
   REAL-TIME CLOCK
   =============================== */
function updateClock() {
  const el = document.getElementById("currentTime");
  if (!el) return;

  const now = new Date();
  el.textContent = now.toLocaleString("en-PH", {
    weekday: "short",
    hour: "2-digit",
    minute: "2-digit",
    hour12: true
  });
}

setInterval(updateClock, 1000);
updateClock();

/* ===============================
   SETTINGS TAB SWITCHING
   =============================== */
document.addEventListener("DOMContentLoaded", () => {
  const tabs = document.querySelectorAll(".settings-tab");
  const panels = document.querySelectorAll("[data-tab-content]");

  if (!tabs.length || !panels.length) return;

  tabs.forEach(tab => {
    tab.addEventListener("click", () => {
      const target = tab.dataset.tab;

      // Reset tabs
      tabs.forEach(t =>
        t.classList.remove("bg-daGreen/10", "text-daGreen", "font-medium")
      );

      // Hide panels
      panels.forEach(panel => panel.classList.add("hidden"));

      // Activate tab
      tab.classList.add("bg-daGreen/10", "text-daGreen", "font-medium");

      // Show panel
      document
        .querySelector(`[data-tab-content="${target}"]`)
        ?.classList.remove("hidden");
    });
  });
});
})();
