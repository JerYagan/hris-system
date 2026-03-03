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
  const checkbox = document.getElementById('profileSameAsPermanentAddress') || document.getElementById('sameAddress');
  const container = document.querySelector('[data-permanent-address-container]');
  if (!checkbox || !container) return;

  const inputs = Array.from(container.querySelectorAll('input'));
  const byId = (id) => document.getElementById(id);
  const residentialInputs = [
    byId('profileResidentialHouseNo'),
    byId('profileResidentialStreet'),
    byId('profileResidentialSubdivision'),
    byId('profileResidentialBarangay'),
    byId('profileResidentialCity'),
    byId('profileResidentialProvince'),
    byId('profileResidentialZipCode'),
  ].filter((item) => item instanceof HTMLInputElement);

  const syncResidentialToPermanent = () => {
    const pairs = [
      ['profileResidentialHouseNo', 'profilePermanentHouseNo'],
      ['profileResidentialStreet', 'profilePermanentStreet'],
      ['profileResidentialSubdivision', 'profilePermanentSubdivision'],
      ['profileResidentialBarangay', 'profilePermanentBarangay'],
      ['profileResidentialCity', 'profilePermanentCity'],
      ['profileResidentialProvince', 'profilePermanentProvince'],
      ['profileResidentialZipCode', 'profilePermanentZipCode'],
    ];

    pairs.forEach(([sourceId, targetId]) => {
      const source = byId(sourceId);
      const target = byId(targetId);
      if (source instanceof HTMLInputElement && target instanceof HTMLInputElement) {
        target.value = source.value;
      }
    });
  };

  const render = () => {
    const disabled = checkbox.checked;
    if (disabled) {
      syncResidentialToPermanent();
    }

    inputs.forEach((input) => {
      input.disabled = disabled;
      input.classList.toggle('bg-gray-100', disabled);
    });
  };

  checkbox.addEventListener('change', render);
  residentialInputs.forEach((input) => {
    input.addEventListener('input', () => {
      if (checkbox.checked) {
        syncResidentialToPermanent();
      }
    });
    input.addEventListener('change', () => {
      if (checkbox.checked) {
        syncResidentialToPermanent();
      }
    });
  });

  render();
};

const wirePhotoUploadPreview = () => {
  const form = document.getElementById('profilePhotoForm');
  const input = document.getElementById('profilePhotoInput');
  const fileNameLabel = document.getElementById('profilePhotoFileName');
  const previewModal = document.getElementById('employeePhotoPreviewModal');
  const previewImage = document.getElementById('employeeProfilePhotoPreviewImage');
  const previewEmpty = document.getElementById('employeeProfilePhotoPreviewEmpty');
  const confirmUploadButton = document.getElementById('employeeProfilePhotoConfirmUpload');

  document.querySelectorAll('[data-trigger-file="profilePhotoInput"]').forEach((button) => {
    button.addEventListener('click', () => {
      if (input instanceof HTMLInputElement) {
        input.click();
      }
    });
  });

  if (!(input instanceof HTMLInputElement)) {
    return;
  }

  input.addEventListener('change', () => {
    const file = input.files && input.files[0] ? input.files[0] : null;
    if (fileNameLabel) {
      fileNameLabel.textContent = file ? file.name : 'No file selected.';
    }

    if (!previewImage || !previewEmpty) {
      return;
    }

    if (!file) {
      previewImage.classList.add('hidden');
      previewImage.removeAttribute('src');
      previewEmpty.classList.remove('hidden');
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      previewImage.src = String(reader.result || '');
      previewImage.classList.remove('hidden');
      previewEmpty.classList.add('hidden');
      previewModal?.classList.remove('hidden');
    };
    reader.readAsDataURL(file);
  });

  if (confirmUploadButton instanceof HTMLButtonElement) {
    confirmUploadButton.addEventListener('click', () => {
      if (!(input instanceof HTMLInputElement) || !input.files || input.files.length === 0) {
        return;
      }

      if (form instanceof HTMLFormElement) {
        form.submit();
      }
    });
  }

  document.querySelectorAll('[data-close-photo-preview="employeePhotoPreviewModal"]').forEach((button) => {
    button.addEventListener('click', () => {
      previewModal?.classList.add('hidden');
    });
  });
};

const wirePasswordChangeFlow = () => {
  const passwordInput = document.getElementById('employeeNewPasswordInput');
  const strengthBar = document.getElementById('employeePasswordStrengthBar');
  const strengthText = document.getElementById('employeePasswordStrengthText');

  const scorePassword = (value) => {
    let score = 0;
    if (value.length >= 10) score += 1;
    if (/[A-Z]/.test(value)) score += 1;
    if (/[a-z]/.test(value)) score += 1;
    if (/\d/.test(value)) score += 1;
    if (/[^a-zA-Z0-9]/.test(value)) score += 1;
    return score;
  };

  const applyStrengthUi = (score) => {
    if (!strengthBar || !strengthText) return;

    const widths = ['0%', '20%', '40%', '60%', '80%', '100%'];
    const labels = [
      'Strength: Enter a new password',
      'Strength: Very Weak',
      'Strength: Weak',
      'Strength: Fair',
      'Strength: Good',
      'Strength: Strong',
    ];
    const classes = ['bg-slate-300', 'bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-lime-500', 'bg-emerald-600'];

    strengthBar.style.width = widths[score] || '0%';
    strengthBar.classList.remove('bg-slate-300', 'bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-lime-500', 'bg-emerald-600');
    strengthBar.classList.add(classes[score] || 'bg-slate-300');
    strengthText.textContent = labels[score] || labels[0];
  };

  if (passwordInput) {
    applyStrengthUi(0);
    passwordInput.addEventListener('input', () => {
      const value = passwordInput.value || '';
      const score = value.length === 0 ? 0 : scorePassword(value);
      applyStrengthUi(score);
    });
  }

  const passwordFlowDataNode = document.getElementById('employeePasswordFlowData');
  let passwordFlowData = { has_pending_code: false, state: '', message: '' };
  if (passwordFlowDataNode && passwordFlowDataNode.textContent) {
    try {
      passwordFlowData = JSON.parse(passwordFlowDataNode.textContent);
    } catch (_error) {
      passwordFlowData = { has_pending_code: false, state: '', message: '' };
    }
  }

  const requestModal = document.getElementById('employeePasswordRequestModal');
  const verifyModal = document.getElementById('employeePasswordVerifyModal');
  const hasPendingCode = Boolean(passwordFlowData.has_pending_code);
  const state = String(passwordFlowData.state || '');
  const message = String(passwordFlowData.message || '');
  const lowerMessage = message.toLowerCase();
  const shouldAutoOpenVerify = hasPendingCode && (
    (state === 'success' && lowerMessage.includes('verification code sent'))
    || state === 'error'
  );

  if (shouldAutoOpenVerify && verifyModal) {
    requestModal?.classList.add('hidden');
    verifyModal.classList.remove('hidden');
    verifyModal.setAttribute('aria-hidden', 'false');
  }
};

const extractDatalistValues = (id) => {
  const datalist = document.getElementById(id);
  if (!(datalist instanceof HTMLDataListElement)) {
    return [];
  }

  return Array.from(datalist.querySelectorAll('option'))
    .map((option) => (option.getAttribute('value') || '').trim())
    .filter((value) => value !== '');
};

const normalizeLookup = (value) => {
  return (value || '')
    .toString()
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
};

const createUniqueSortedOptions = (values) => {
  const deduped = new Map();
  (values || []).forEach((value) => {
    const text = (value || '').toString().trim();
    if (!text) {
      return;
    }
    const key = text.toLowerCase();
    if (!deduped.has(key)) {
      deduped.set(key, text);
    }
  });
  return Array.from(deduped.values()).sort((a, b) => a.localeCompare(b));
};

const buildModernSearch = (inputElement, optionsResolver) => {
  if (!(inputElement instanceof HTMLInputElement) || typeof optionsResolver !== 'function') {
    return;
  }

  const originalListId = inputElement.getAttribute('list') || '';
  if (originalListId !== '') {
    inputElement.removeAttribute('list');
  }

  const parent = inputElement.parentElement;
  if (!parent) {
    return;
  }

  parent.classList.add('relative');

  const container = document.createElement('div');
  container.className = 'hidden absolute left-0 right-0 top-full mt-1 rounded-lg border border-slate-200 bg-white shadow-lg z-40 max-h-52 overflow-y-auto';
  parent.appendChild(container);

  const triggerButton = document.createElement('button');
  triggerButton.type = 'button';
  triggerButton.className = 'absolute inset-y-0 right-0 inline-flex items-center px-3 text-slate-400 hover:text-slate-600';
  triggerButton.setAttribute('aria-label', 'Show options');
  triggerButton.innerHTML = '<span class="material-symbols-outlined text-[18px]">expand_more</span>';
  parent.appendChild(triggerButton);

  let activeIndex = -1;
  let activeOptions = [];

  const applyActiveOption = () => {
    const optionButtons = Array.from(container.querySelectorAll('button[data-modern-search-option]'));
    optionButtons.forEach((button, index) => {
      button.classList.toggle('bg-slate-100', index === activeIndex);
    });
    activeOptions = optionButtons;
    if (activeIndex >= 0 && activeIndex < activeOptions.length) {
      const activeOption = activeOptions[activeIndex];
      if (activeOption instanceof HTMLElement) {
        activeOption.scrollIntoView({ block: 'nearest' });
      }
    }
  };

  const renderOptions = () => {
    const query = normalizeLookup(inputElement.value);
    const options = createUniqueSortedOptions(optionsResolver())
      .filter((option) => query === '' || normalizeLookup(option).includes(query))
      .slice(0, 30);

    container.innerHTML = '';
    if (options.length === 0) {
      container.classList.add('hidden');
      activeIndex = -1;
      activeOptions = [];
      return;
    }

    const fragment = document.createDocumentFragment();
    options.forEach((option) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.setAttribute('data-modern-search-option', '1');
      button.className = 'w-full text-left px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 border-b border-slate-100 last:border-b-0';
      button.textContent = option;
      button.addEventListener('mousedown', (event) => {
        event.preventDefault();
        event.stopPropagation();
        inputElement.value = option;
        inputElement.dispatchEvent(new Event('input', { bubbles: true }));
        inputElement.dispatchEvent(new Event('change', { bubbles: true }));
        container.classList.add('hidden');
        activeIndex = -1;
      });
      fragment.appendChild(button);
    });

    container.appendChild(fragment);
    container.classList.remove('hidden');
    activeIndex = -1;
    applyActiveOption();
  };

  container.addEventListener('mousedown', (event) => event.stopPropagation());
  inputElement.addEventListener('focus', renderOptions);
  inputElement.addEventListener('input', renderOptions);

  const selectActiveOption = () => {
    const optionCount = activeOptions.length;
    if (container.classList.contains('hidden') || optionCount === 0) {
      return false;
    }

    if (activeIndex < 0 || activeIndex >= optionCount) {
      activeIndex = 0;
      applyActiveOption();
    }

    const activeOption = activeOptions[activeIndex];
    if (activeOption instanceof HTMLButtonElement) {
      activeOption.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, cancelable: true }));
      return true;
    }

    return false;
  };

  inputElement.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      if (container.classList.contains('hidden')) {
        renderOptions();
      }
      const nextCount = activeOptions.length;
      if (nextCount > 0) {
        activeIndex = (activeIndex + 1 + nextCount) % nextCount;
        applyActiveOption();
      }
      return;
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault();
      if (container.classList.contains('hidden')) {
        renderOptions();
      }
      const nextCount = activeOptions.length;
      if (nextCount > 0) {
        activeIndex = (activeIndex - 1 + nextCount) % nextCount;
        applyActiveOption();
      }
      return;
    }

    if (event.key === 'Enter') {
      event.preventDefault();
      selectActiveOption();
      return;
    }

    if (event.key === 'Tab' && !container.classList.contains('hidden')) {
      selectActiveOption();
      return;
    }

    if (event.key === 'Escape') {
      container.classList.add('hidden');
      activeIndex = -1;
    }
  });

  triggerButton.addEventListener('mousedown', (event) => {
    event.preventDefault();
    event.stopPropagation();
    inputElement.focus();
    if (container.classList.contains('hidden')) {
      renderOptions();
    } else {
      container.classList.add('hidden');
      activeIndex = -1;
    }
  });

  inputElement.addEventListener('blur', () => {
    window.setTimeout(() => container.classList.add('hidden'), 120);
  });
};

const wireModernProfileSearch = async () => {
  const cityInputIds = {
    residential: 'profileResidentialCity',
    permanent: 'profilePermanentCity',
  };
  const barangayInputIds = {
    residential: 'profileResidentialBarangay',
    permanent: 'profilePermanentBarangay',
  };
  const zipInputIds = {
    residential: 'profileResidentialZipCode',
    permanent: 'profilePermanentZipCode',
  };

  const cityInputs = {
    residential: document.getElementById(cityInputIds.residential),
    permanent: document.getElementById(cityInputIds.permanent),
  };
  const barangayInputs = {
    residential: document.getElementById(barangayInputIds.residential),
    permanent: document.getElementById(barangayInputIds.permanent),
  };
  const zipInputs = {
    residential: document.getElementById(zipInputIds.residential),
    permanent: document.getElementById(zipInputIds.permanent),
  };

  const cityOptions = extractDatalistValues('profileCityList');
  const provinceOptions = extractDatalistValues('profileProvinceList');
  const placeOfBirthOptions = extractDatalistValues('profilePlaceOfBirthList');
  const civilStatusOptions = extractDatalistValues('profileCivilStatusList');
  const bloodTypeOptions = extractDatalistValues('profileBloodTypeList');

  let barangayByCityLookup = {};
  const employeeAddressLookupData = document.getElementById('employeeAddressLookupData');
  if (employeeAddressLookupData) {
    try {
      const parsedLookup = JSON.parse(employeeAddressLookupData.textContent || '{}');
      if (parsedLookup && typeof parsedLookup === 'object' && parsedLookup.barangayByCity && typeof parsedLookup.barangayByCity === 'object') {
        barangayByCityLookup = parsedLookup.barangayByCity;
      }
    } catch {
      barangayByCityLookup = {};
    }
  }

  let zipLookupByCityBarangay = {};
  if (typeof window.fetch === 'function') {
    try {
      const response = await window.fetch('/hris-system/assets/zip-codes.json', { credentials: 'same-origin' });
      if (response.ok) {
        const payload = await response.json();
        if (payload && typeof payload === 'object') {
          zipLookupByCityBarangay = payload;
        }
      }
    } catch {
      zipLookupByCityBarangay = {};
    }
  }

  const resolveBarangayOptions = (groupKey) => {
    const cityInput = cityInputs[groupKey];
    if (!(cityInput instanceof HTMLInputElement)) {
      return [];
    }
    const cityKey = normalizeLookup(cityInput.value);
    if (!cityKey) {
      return [];
    }

    const mappedBarangays = barangayByCityLookup[cityKey];
    if (Array.isArray(mappedBarangays) && mappedBarangays.length > 0) {
      return createUniqueSortedOptions(mappedBarangays);
    }

    const rawBarangays = zipLookupByCityBarangay[cityKey];
    if (!rawBarangays || typeof rawBarangays !== 'object') {
      return [];
    }

    return createUniqueSortedOptions(Object.keys(rawBarangays));
  };

  const resolveZipOptions = (groupKey) => {
    const cityInput = cityInputs[groupKey];
    const barangayInput = barangayInputs[groupKey];
    if (!(cityInput instanceof HTMLInputElement)) {
      return [];
    }

    const cityKey = normalizeLookup(cityInput.value);
    if (!cityKey) {
      return [];
    }

    const rawBarangays = zipLookupByCityBarangay[cityKey];
    if (!rawBarangays || typeof rawBarangays !== 'object') {
      return [];
    }

    const barangayKey = barangayInput instanceof HTMLInputElement ? normalizeLookup(barangayInput.value) : '';
    if (barangayKey && Array.isArray(rawBarangays[barangayKey])) {
      return createUniqueSortedOptions(rawBarangays[barangayKey]);
    }

    const aggregate = [];
    Object.values(rawBarangays).forEach((zipList) => {
      if (Array.isArray(zipList)) {
        zipList.forEach((zipValue) => aggregate.push((zipValue || '').toString()));
      }
    });
    return createUniqueSortedOptions(aggregate);
  };

  const autofillZip = (groupKey) => {
    const zipInput = zipInputs[groupKey];
    if (!(zipInput instanceof HTMLInputElement)) {
      return;
    }

    const options = resolveZipOptions(groupKey);
    if (options.length === 1) {
      zipInput.value = options[0];
    }
  };

  buildModernSearch(document.getElementById('profilePlaceOfBirth'), () => placeOfBirthOptions);
  buildModernSearch(document.getElementById('profileCivilStatus'), () => civilStatusOptions);
  buildModernSearch(document.getElementById('profileBloodType'), () => bloodTypeOptions);
  buildModernSearch(document.getElementById('profileResidentialCity'), () => cityOptions);
  buildModernSearch(document.getElementById('profilePermanentCity'), () => cityOptions);
  buildModernSearch(document.getElementById('profileResidentialProvince'), () => provinceOptions);
  buildModernSearch(document.getElementById('profilePermanentProvince'), () => provinceOptions);
  buildModernSearch(document.getElementById('profileResidentialBarangay'), () => resolveBarangayOptions('residential'));
  buildModernSearch(document.getElementById('profilePermanentBarangay'), () => resolveBarangayOptions('permanent'));
  buildModernSearch(document.getElementById('profileResidentialZipCode'), () => resolveZipOptions('residential'));
  buildModernSearch(document.getElementById('profilePermanentZipCode'), () => resolveZipOptions('permanent'));

  Object.entries(cityInputs).forEach(([groupKey, cityInput]) => {
    if (!(cityInput instanceof HTMLInputElement)) {
      return;
    }
    cityInput.addEventListener('input', () => autofillZip(groupKey));
    cityInput.addEventListener('change', () => autofillZip(groupKey));
  });

  Object.entries(barangayInputs).forEach(([groupKey, barangayInput]) => {
    if (!(barangayInput instanceof HTMLInputElement)) {
      return;
    }
    barangayInput.addEventListener('input', () => autofillZip(groupKey));
    barangayInput.addEventListener('change', () => autofillZip(groupKey));
  });
};

const initEmployeePersonalInformationPage = () => {
  const profileTabs = wireProfileTabs();
  wireDynamicRows();
  wirePermanentAddressToggle();
  wirePhotoUploadPreview();
  wirePasswordChangeFlow();
  wireModernProfileSearch();

  wireModal({
    openSelector: '[data-open-profile]',
    closeSelector: '[data-close-profile]',
    modalId: 'profileModal',
    onOpen: () => profileTabs.reset(),
  });

  wireModal({
    openSelector: '[data-open-spouse-request]',
    closeSelector: '[data-close-spouse-request]',
    modalId: 'spouseRequestModal',
  });

  wireModal({
    openSelector: '[data-open-password-request]',
    closeSelector: '[data-close-password-request]',
    modalId: 'employeePasswordRequestModal',
  });

  wireModal({
    openSelector: '[data-open-password-verify]',
    closeSelector: '[data-close-password-verify]',
    modalId: 'employeePasswordVerifyModal',
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    ['profileModal', 'spouseRequestModal', 'employeePasswordRequestModal', 'employeePasswordVerifyModal'].forEach((modalId) => {
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
