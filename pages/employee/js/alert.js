document.addEventListener("DOMContentLoaded", () => {

  /* ===============================
     ACTION MENU (3 DOTS)
     =============================== */
  document.addEventListener("click", (e) => {
    const toggleBtn = e.target.closest("[data-action-toggle]");
    const menus = document.querySelectorAll(".action-menu");

    // Close all menus first
    menus.forEach(menu => menu.classList.add("hidden"));

    // Open the clicked one
    if (toggleBtn) {
      const menu = toggleBtn.nextElementSibling;
      if (menu) {
        menu.classList.toggle("hidden");
      }
    }
  });


  /* ===============================
     UPLOAD MODAL
     =============================== */
  const openModalBtn  = document.querySelector("[data-open-upload]");
  const closeModalBtn = document.querySelector("[data-close-upload]");
  const modal         = document.getElementById("uploadModal");

  if (openModalBtn && modal) {
    openModalBtn.addEventListener("click", () => {
      modal.classList.remove("hidden");
    });
  }

  if (closeModalBtn && modal) {
    closeModalBtn.addEventListener("click", () => {
      modal.classList.add("hidden");
    });
  }

  // Close modal when clicking backdrop
  if (modal) {
    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.classList.add("hidden");
      }
    });
  }

});

/* ===============================
   PROFILE MODAL
   =============================== */
const openProfile = document.querySelector("[data-open-profile]");
const closeProfile = document.querySelector("[data-close-profile]");
const profileModal = document.getElementById("profileModal");

if (openProfile && profileModal) {
  openProfile.addEventListener("click", () => {
    profileModal.classList.remove("hidden");
  });
}

if (closeProfile && profileModal) {
  closeProfile.addEventListener("click", () => {
    profileModal.classList.add("hidden");
  });
}

if (profileModal) {
  profileModal.addEventListener("click", (e) => {
    if (e.target === profileModal) {
      profileModal.classList.add("hidden");
    }
  });
}

/* ===============================
   PAYSLIP MODAL
   =============================== */
const openPayslipBtns = document.querySelectorAll("[data-open-payslip]");
const closePayslipBtns = document.querySelectorAll("[data-close-payslip]");
const payslipModal = document.getElementById("payslipModal");

if (payslipModal) {
  openPayslipBtns.forEach(btn => {
    btn.addEventListener("click", () => {
      payslipModal.classList.remove("hidden");
    });
  });

  closePayslipBtns.forEach(btn => {
    btn.addEventListener("click", () => {
      payslipModal.classList.add("hidden");
    });
  });

  payslipModal.addEventListener("click", (e) => {
    if (e.target === payslipModal) {
      payslipModal.classList.add("hidden");
    }
  });
}

/* ===============================
   TIMEKEEPING MODALS
   =============================== */
const leaveModal = document.getElementById("leaveModal");
const overtimeModal = document.getElementById("overtimeModal");

document.querySelectorAll("[data-open-leave]").forEach(btn =>
  btn.addEventListener("click", () => leaveModal?.classList.remove("hidden"))
);

document.querySelectorAll("[data-close-leave]").forEach(btn =>
  btn.addEventListener("click", () => leaveModal?.classList.add("hidden"))
);

document.querySelectorAll("[data-open-overtime]").forEach(btn =>
  btn.addEventListener("click", () => overtimeModal?.classList.remove("hidden"))
);

document.querySelectorAll("[data-close-overtime]").forEach(btn =>
  btn.addEventListener("click", () => overtimeModal?.classList.add("hidden"))
);

[leaveModal, overtimeModal].forEach(modal => {
  modal?.addEventListener("click", e => {
    if (e.target === modal) modal.classList.add("hidden");
  });
});

/* ===============================
   PERSONAL REPORTS MODALS
   =============================== */
const reportMap = [
  ['attendance', 'attendanceReportModal'],
  ['payroll', 'payrollReportModal'],
  ['performance', 'performanceReportModal'],
];

reportMap.forEach(([key, modalId]) => {
  const modal = document.getElementById(modalId);

  document.querySelectorAll(`[data-open-${key}-report]`).forEach(btn =>
    btn.addEventListener("click", () => modal?.classList.remove("hidden"))
  );

  document.querySelectorAll(`[data-close-${key}-report]`).forEach(btn =>
    btn.addEventListener("click", () => modal?.classList.add("hidden"))
  );

  modal?.addEventListener("click", e => {
    if (e.target === modal) modal.classList.add("hidden");
  });
});

// Initialize charts when any report modal opens
document.querySelectorAll(
  "[data-open-attendance-report], [data-open-payroll-report], [data-open-performance-report]"
).forEach(btn => {
  btn.addEventListener("click", () => {
    setTimeout(initReportCharts, 100);
  });
});

/* ===============================
   PRAISE MODALS
   =============================== */
const praiseMap = [
  ['self-eval', 'selfEvalModal'],
  ['supervisor-eval', 'supervisorEvalModal'],
  ['awards', 'awardsModal'],
];

praiseMap.forEach(([key, modalId]) => {
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
