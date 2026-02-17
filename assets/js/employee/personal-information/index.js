const toggleModal = (modal, open) => {
  if (!modal) return;
  modal.classList.toggle('hidden', !open);
  modal.setAttribute('aria-hidden', open ? 'false' : 'true');
};

const wireModal = ({ openSelector, closeSelector, modalId, onOpen }) => {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  document.querySelectorAll(openSelector).forEach((button) => {
    button.addEventListener('click', () => {
      toggleModal(modal, true);
      if (typeof onOpen === 'function') {
        onOpen();
      }
    });
  });

  document.querySelectorAll(closeSelector).forEach((button) => {
    button.addEventListener('click', () => toggleModal(modal, false));
  });

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      toggleModal(modal, false);
    }
  });
};

const wireProfileTabs = () => {
  const tabs = Array.from(document.querySelectorAll('[data-profile-tab-target]'));
  const sections = Array.from(document.querySelectorAll('[data-profile-section]'));
  const prevButton = document.querySelector('[data-profile-prev]');
  const nextButton = document.querySelector('[data-profile-next]');

  if (!tabs.length || !sections.length) {
    return { reset: () => {} };
  }

  const order = tabs.map((tab) => tab.getAttribute('data-profile-tab-target') || '');
  let currentIndex = 0;

  const render = () => {
    const activeKey = order[currentIndex] || order[0];

    tabs.forEach((tab) => {
      const key = tab.getAttribute('data-profile-tab-target');
      const isActive = key === activeKey;
      tab.classList.toggle('bg-gray-50', isActive);
      tab.classList.toggle('text-daGreen', isActive);
      tab.classList.toggle('font-medium', isActive);
      tab.classList.toggle('border-daGreen', isActive);
      tab.classList.toggle('bg-white', !isActive);
      tab.classList.toggle('text-gray-600', !isActive);
      tab.classList.toggle('border-transparent', !isActive);
    });

    sections.forEach((section) => {
      const key = section.getAttribute('data-profile-section');
      section.classList.toggle('hidden', key !== activeKey);
    });

    if (prevButton) {
      prevButton.disabled = currentIndex === 0;
      prevButton.classList.toggle('opacity-50', currentIndex === 0);
      prevButton.classList.toggle('cursor-not-allowed', currentIndex === 0);
    }

    if (nextButton) {
      const isLast = currentIndex >= order.length - 1;
      nextButton.disabled = isLast;
      nextButton.classList.toggle('opacity-50', isLast);
      nextButton.classList.toggle('cursor-not-allowed', isLast);
    }
  };

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => {
      currentIndex = index;
      render();
    });
  });

  prevButton?.addEventListener('click', () => {
    if (currentIndex > 0) {
      currentIndex -= 1;
      render();
    }
  });

  nextButton?.addEventListener('click', () => {
    if (currentIndex < order.length - 1) {
      currentIndex += 1;
      render();
    }
  });

  render();

  return {
    reset: () => {
      currentIndex = 0;
      render();
    },
  };
};

const wireDynamicRows = () => {
  const childrenRows = document.getElementById('childrenRows');
  const childTemplate = document.getElementById('childRowTemplate');
  const addChildButton = document.querySelector('[data-add-child-row]');

  const educationRows = document.getElementById('educationRows');
  const educationTemplate = document.getElementById('educationRowTemplate');
  const addEducationButton = document.querySelector('[data-add-education-row]');

  addChildButton?.addEventListener('click', () => {
    if (!childrenRows || !childTemplate) return;
    childrenRows.insertAdjacentHTML('beforeend', childTemplate.innerHTML.trim());
  });

  addEducationButton?.addEventListener('click', () => {
    if (!educationRows || !educationTemplate) return;
    educationRows.insertAdjacentHTML('beforeend', educationTemplate.innerHTML.trim());
  });

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;

    if (target.matches('[data-remove-child-row]')) {
      const row = target.closest('.child-row');
      if (row && childrenRows && childrenRows.querySelectorAll('.child-row').length > 1) {
        row.remove();
      }
    }

    if (target.matches('[data-remove-education-row]')) {
      const row = target.closest('.education-row');
      if (row && educationRows && educationRows.querySelectorAll('.education-row').length > 1) {
        row.remove();
      }
    }
  });
};

const wirePermanentAddressToggle = () => {
  const checkbox = document.getElementById('sameAddress');
  const container = document.querySelector('[data-permanent-address-container]');
  if (!checkbox || !container) return;

  const inputs = Array.from(container.querySelectorAll('input'));
  const render = () => {
    const disabled = checkbox.checked;
    inputs.forEach((input) => {
      input.disabled = disabled;
      input.classList.toggle('bg-gray-100', disabled);
    });
  };

  checkbox.addEventListener('change', render);
  render();
};

const initEmployeePersonalInformationPage = () => {
  const profileTabs = wireProfileTabs();
  wireDynamicRows();
  wirePermanentAddressToggle();

  wireModal({
    openSelector: '[data-open-profile]',
    closeSelector: '[data-close-profile]',
    modalId: 'profileModal',
    onOpen: () => profileTabs.reset(),
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    ['profileModal'].forEach((modalId) => {
      const modal = document.getElementById(modalId);
      if (modal && !modal.classList.contains('hidden')) {
        toggleModal(modal, false);
      }
    });
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeePersonalInformationPage);
} else {
  initEmployeePersonalInformationPage();
}

export default initEmployeePersonalInformationPage;
