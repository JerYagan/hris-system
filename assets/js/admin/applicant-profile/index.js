const setButtonState = (buttons, activeSection) => {
  buttons.forEach((button) => {
    const isActive = button.getAttribute('data-applicant-profile-section') === activeSection;
    button.classList.toggle('bg-slate-900', isActive);
    button.classList.toggle('text-white', isActive);
    button.classList.toggle('border-slate-900', isActive);
    button.classList.toggle('text-slate-700', !isActive);
    button.classList.toggle('border-slate-300', !isActive);
    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });
};

const initAdminApplicantProfilePage = () => {
  const region = document.getElementById('adminApplicantProfileAsyncRegion');
  const placeholder = document.getElementById('adminApplicantProfilePlaceholder');
  const skeleton = document.getElementById('adminApplicantProfileSkeleton');
  const errorBox = document.getElementById('adminApplicantProfileError');
  const retryButton = document.getElementById('adminApplicantProfileRetry');
  const content = document.getElementById('adminApplicantProfileContent');

  if (!region || !placeholder || !skeleton || !errorBox || !retryButton || !content) {
    return;
  }

  const buttons = Array.from(region.querySelectorAll('[data-applicant-profile-section]'));
  if (buttons.length === 0) {
    return;
  }

  const sectionCache = new Map();
  let lastRequestedSection = '';

  const fetchHtml = async (url) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (response.redirected) {
      window.location.href = response.url;
      return '';
    }

    if (!response.ok) {
      throw new Error(`Applicant profile partial failed with status ${response.status}`);
    }

    const html = await response.text();
    if (html.trim() === '') {
      throw new Error('Applicant profile partial returned an empty response.');
    }

    return html;
  };

  const setLoadingState = (isLoading) => {
    placeholder.classList.toggle('hidden', isLoading || !content.classList.contains('hidden'));
    skeleton.classList.toggle('hidden', !isLoading);
    errorBox.classList.add('hidden');
    if (isLoading) {
      content.classList.add('hidden');
    }
    retryButton.disabled = isLoading;
  };

  const showErrorState = () => {
    skeleton.classList.add('hidden');
    content.classList.add('hidden');
    errorBox.classList.remove('hidden');
    retryButton.disabled = false;
  };

  const showContent = (html) => {
    content.innerHTML = html;
    content.classList.remove('hidden');
    placeholder.classList.add('hidden');
    skeleton.classList.add('hidden');
    errorBox.classList.add('hidden');
    retryButton.disabled = false;
  };

  const loadSection = async (section, forceRefresh = false) => {
    const button = buttons.find((item) => item.getAttribute('data-applicant-profile-section') === section);
    const url = button?.getAttribute('data-section-url') || '';
    if (!button || url === '') {
      return;
    }

    lastRequestedSection = section;
    setButtonState(buttons, section);

    if (!forceRefresh && sectionCache.has(section)) {
      showContent(sectionCache.get(section) || '');
      return;
    }

    setLoadingState(true);

    try {
      const html = await fetchHtml(url);
      sectionCache.set(section, html);
      showContent(html);
    } catch (error) {
      console.error(error);
      showErrorState();
    }
  };

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const section = button.getAttribute('data-applicant-profile-section') || '';
      if (section === '') {
        return;
      }

      loadSection(section).catch(console.error);
    });
  });

  retryButton.addEventListener('click', () => {
    if (lastRequestedSection === '') {
      return;
    }

    loadSection(lastRequestedSection, true).catch(console.error);
  });

  document.querySelectorAll('[data-applicant-profile-open-actions]').forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      loadSection('actions').catch(console.error);
    });
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAdminApplicantProfilePage, { once: true });
} else {
  initAdminApplicantProfilePage();
}

export default initAdminApplicantProfilePage;