const createSkeletonCard = () => {
  const card = document.createElement('div');
  card.className = 'rounded-xl border bg-white p-4';

  const lines = [
    'h-4 w-1/3',
    'h-3 w-full',
    'h-3 w-5/6',
    'h-3 w-2/3',
  ];

  card.innerHTML = lines
    .map((line) => `<div class="${line} bg-gray-200 rounded mb-3 animate-pulse motion-reduce:animate-none"></div>`)
    .join('');

  return card;
};

export const runPageStatePass = ({
  pageKey = 'employee-page',
  rootSelector = 'main',
  minSkeletonMs = 320,
} = {}) => {
  const root = document.querySelector(rootSelector) || document.body;
  root.setAttribute('aria-busy', 'true');

  const overlay = document.createElement('div');
  overlay.id = `${pageKey}-loading-skeleton`;
  overlay.setAttribute('aria-live', 'polite');
  overlay.setAttribute('role', 'status');
  overlay.className = 'fixed inset-0 z-40 bg-white/85 px-4 py-6 pointer-events-none';

  const container = document.createElement('div');
  container.className = 'max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-4';

  for (let index = 0; index < 4; index += 1) {
    container.appendChild(createSkeletonCard());
  }

  overlay.appendChild(container);
  document.body.appendChild(overlay);

  window.setTimeout(() => {
    overlay.remove();
    root.setAttribute('aria-busy', 'false');
  }, Math.max(300, minSkeletonMs));
};

export default runPageStatePass;
