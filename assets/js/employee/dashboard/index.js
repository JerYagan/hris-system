const MIN_SKELETON_MS = 300;

const initEmployeeDashboardPage = () => {
  const region = document.getElementById('employeeDashboardAsyncRegion');
  const summarySkeleton = document.getElementById('employeeDashboardSummarySkeleton');
  const summaryContent = document.getElementById('employeeDashboardSummaryContent');
  const summaryError = document.getElementById('employeeDashboardSummaryError');
  const summaryRetry = document.getElementById('employeeDashboardSummaryRetry');
  const summaryUrl = region?.getAttribute('data-dashboard-summary-url') || '';
  const secondaryTopSkeleton = document.getElementById('employeeDashboardSecondaryTopSkeleton');
  const secondaryTopContent = document.getElementById('employeeDashboardSecondaryTopContent');
  const secondaryBottomSkeleton = document.getElementById('employeeDashboardSecondaryBottomSkeleton');
  const secondaryBottomContent = document.getElementById('employeeDashboardSecondaryBottomContent');
  const secondaryError = document.getElementById('employeeDashboardSecondaryError');
  const secondaryRetry = document.getElementById('employeeDashboardSecondaryRetry');
  const secondaryUrl = region?.getAttribute('data-dashboard-secondary-url') || '';

  if (!region || !summarySkeleton || !summaryContent || !summaryError || !summaryRetry || summaryUrl === '' || !secondaryTopSkeleton || !secondaryTopContent || !secondaryBottomSkeleton || !secondaryBottomContent || !secondaryError || !secondaryRetry || secondaryUrl === '') {
    return;
  }

  const createRequestState = (url) => {
    const state = {
      url,
      status: 'pending',
      html: '',
      error: null,
      promise: null,
    };

    state.promise = (async () => {
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
        throw new Error(`Dashboard partial request failed with status ${response.status}`);
      }

      const html = await response.text();
      if (html.trim() === '') {
        throw new Error('Dashboard partial returned an empty response.');
      }

      state.status = 'fulfilled';
      state.html = html;

      return html;
    })().catch((error) => {
      state.status = 'rejected';
      state.error = error;
      throw error;
    });

    return state;
  };

  const setRegionBusyState = () => {
    const summaryBusy = !summarySkeleton.classList.contains('hidden');
    const secondaryBusy = !secondaryTopSkeleton.classList.contains('hidden') || !secondaryBottomSkeleton.classList.contains('hidden');
    region.setAttribute('aria-busy', summaryBusy || secondaryBusy ? 'true' : 'false');
  };

  const setLoadingState = ({ skeleton, content, errorBox, retryButton }, isLoading) => {
    region.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    skeleton.classList.toggle('hidden', !isLoading);
    content.classList.toggle('hidden', isLoading);
    retryButton.disabled = isLoading;
    if (isLoading) {
      errorBox.classList.add('hidden');
    }
    setRegionBusyState();
  };

  const showErrorState = ({ skeleton, content, errorBox }) => {
    skeleton.classList.add('hidden');
    content.classList.add('hidden');
    errorBox.classList.remove('hidden');
    setRegionBusyState();
  };

  const hideSecondaryContent = () => {
    secondaryTopContent.classList.add('hidden');
    secondaryBottomContent.classList.add('hidden');
    secondaryTopContent.innerHTML = '';
    secondaryBottomContent.innerHTML = '';
  };

  const setSecondaryLoadingState = (isLoading) => {
    secondaryTopSkeleton.classList.toggle('hidden', !isLoading);
    secondaryBottomSkeleton.classList.toggle('hidden', !isLoading);
    secondaryRetry.disabled = isLoading;
    if (isLoading) {
      secondaryError.classList.add('hidden');
      hideSecondaryContent();
    }
    setRegionBusyState();
  };

  const showSecondaryErrorState = () => {
    secondaryTopSkeleton.classList.add('hidden');
    secondaryBottomSkeleton.classList.add('hidden');
    hideSecondaryContent();
    secondaryError.classList.remove('hidden');
    secondaryRetry.disabled = false;
    setRegionBusyState();
  };

  const renderSecondaryContent = (html) => {
    const template = document.createElement('template');
    template.innerHTML = html;

    const topSection = template.content.querySelector('[data-dashboard-secondary-top]');
    const bottomSection = template.content.querySelector('[data-dashboard-secondary-bottom]');

    secondaryTopContent.innerHTML = topSection ? topSection.innerHTML : html;
    secondaryBottomContent.innerHTML = bottomSection ? bottomSection.innerHTML : '';
    secondaryTopContent.classList.remove('hidden');
    secondaryBottomContent.classList.remove('hidden');
    secondaryTopSkeleton.classList.add('hidden');
    secondaryBottomSkeleton.classList.add('hidden');
    secondaryError.classList.add('hidden');
    secondaryRetry.disabled = false;
    setRegionBusyState();
  };

  const loadPartial = async ({ requestState, skeleton, content, errorBox, retryButton, onSuccess, showSkeletonWhilePending = true }) => {
    const shouldShowSkeleton = showSkeletonWhilePending && requestState.status === 'pending';
    if (shouldShowSkeleton) {
      setLoadingState({ skeleton, content, errorBox, retryButton }, true);
    } else {
      retryButton.disabled = true;
      errorBox.classList.add('hidden');
      content.classList.add('hidden');
      setRegionBusyState();
    }

    const startedAt = window.performance?.now?.() ?? Date.now();

    try {
      const html = requestState.status === 'fulfilled'
        ? requestState.html
        : await requestState.promise;

      if (html === '') {
        return;
      }

      const elapsed = (window.performance?.now?.() ?? Date.now()) - startedAt;
      const remaining = shouldShowSkeleton ? Math.max(0, MIN_SKELETON_MS - elapsed) : 0;

      window.setTimeout(() => {
        content.innerHTML = html;
        content.classList.remove('hidden');
        skeleton.classList.add('hidden');
        errorBox.classList.add('hidden');
        retryButton.disabled = false;
        setRegionBusyState();
        if (typeof onSuccess === 'function') {
          onSuccess();
        }
      }, remaining);
    } catch (error) {
      console.error(error);
      showErrorState({ skeleton, content, errorBox });
      retryButton.disabled = false;
    }
  };

  let secondaryRequestState = createRequestState(secondaryUrl);

  const loadSecondary = async ({ showSkeletonWhilePending = true } = {}) => {
    if (showSkeletonWhilePending && secondaryRequestState.status === 'pending') {
      setSecondaryLoadingState(true);
    } else {
      secondaryRetry.disabled = true;
      secondaryError.classList.add('hidden');
      setRegionBusyState();
    }

    try {
      const html = secondaryRequestState.status === 'fulfilled'
        ? secondaryRequestState.html
        : await secondaryRequestState.promise;

      if (html === '') {
        return;
      }

      renderSecondaryContent(html);
    } catch (error) {
      console.error(error);
      showSecondaryErrorState();
    }
  };

  summaryRetry.addEventListener('click', () => {
    secondaryRequestState = createRequestState(secondaryUrl);
    loadPartial({
      requestState: createRequestState(summaryUrl),
      skeleton: summarySkeleton,
      content: summaryContent,
      errorBox: summaryError,
      retryButton: summaryRetry,
      onSuccess: () => {
        loadSecondary({ showSkeletonWhilePending: secondaryRequestState.status === 'pending' }).catch(console.error);
      },
    }).catch(console.error);
  });

  secondaryRetry.addEventListener('click', () => {
    secondaryRequestState = createRequestState(secondaryUrl);
    loadSecondary({ showSkeletonWhilePending: true }).catch(console.error);
  });

  const summaryRequestState = createRequestState(summaryUrl);

  loadPartial({
    requestState: summaryRequestState,
    skeleton: summarySkeleton,
    content: summaryContent,
    errorBox: summaryError,
    retryButton: summaryRetry,
    onSuccess: () => {
      loadSecondary({ showSkeletonWhilePending: secondaryRequestState.status === 'pending' }).catch(console.error);
    },
  }).catch(console.error);
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeeDashboardPage);
} else {
  initEmployeeDashboardPage();
}

export default initEmployeeDashboardPage;