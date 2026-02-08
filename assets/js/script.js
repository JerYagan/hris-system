document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("sidebar");
  const toggle = document.getElementById("sidebarToggle");
  const main = document.getElementById("mainContent");

  // Exit early if layout elements are missing
  if (!sidebar || !toggle || !main) return;

  let isOpen = window.innerWidth >= 768; // desktop default open

  function applyLayout() {
    if (isOpen) {
      sidebar.classList.remove("-translate-x-full");
      main.classList.add("md:ml-64");
    } else {
      sidebar.classList.add("-translate-x-full");
      main.classList.remove("md:ml-64");
    }
  }

  // Initial render
  applyLayout();

  // Toggle handler
  toggle.addEventListener("click", () => {
    isOpen = !isOpen;
    applyLayout();
  });

  // Handle resize edge cases
  window.addEventListener("resize", () => {
    if (window.innerWidth < 768) {
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
    profileMenu.classList.toggle("hidden");
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
