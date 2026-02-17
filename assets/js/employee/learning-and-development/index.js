import { runPageStatePass } from '../../shared/ui/page-state.js';

const normalize = (value) => (value || '').toString().trim().toLowerCase();

const applyTableFilters = ({ rows, searchInput, statusInput }) => {
  const query = normalize(searchInput?.value || '');
  const status = normalize(statusInput?.value || '');

  rows.forEach((row) => {
    const searchText = normalize(row.getAttribute('data-search'));
    const rowStatus = normalize(row.getAttribute('data-status'));

    const searchMatch = query === '' || searchText.includes(query);
    const statusMatch = status === '' || rowStatus === status;

    row.classList.toggle('hidden', !(searchMatch && statusMatch));
  });
};

const initEmployeeLearningAndDevelopmentPage = () => {
  runPageStatePass({ pageKey: 'employee-learning-and-development' });

  const availableRows = Array.from(document.querySelectorAll('[data-lnd-available-row]'));
  const availableSearch = document.getElementById('lndAvailableSearch');
  const availableStatus = document.getElementById('lndAvailableStatus');

  const takenRows = Array.from(document.querySelectorAll('[data-lnd-taken-row]'));
  const takenSearch = document.getElementById('lndTakenSearch');
  const takenStatus = document.getElementById('lndTakenStatus');

  const refreshAvailable = () => applyTableFilters({
    rows: availableRows,
    searchInput: availableSearch,
    statusInput: availableStatus,
  });

  const refreshTaken = () => applyTableFilters({
    rows: takenRows,
    searchInput: takenSearch,
    statusInput: takenStatus,
  });

  availableSearch?.addEventListener('input', refreshAvailable);
  availableStatus?.addEventListener('change', refreshAvailable);

  takenSearch?.addEventListener('input', refreshTaken);
  takenStatus?.addEventListener('change', refreshTaken);

  refreshAvailable();
  refreshTaken();
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeeLearningAndDevelopmentPage);
} else {
  initEmployeeLearningAndDevelopmentPage();
}

export default initEmployeeLearningAndDevelopmentPage;
