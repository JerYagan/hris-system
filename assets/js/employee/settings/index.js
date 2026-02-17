import { runPageStatePass } from '../../shared/ui/page-state.js';

const activateSettingsTab = (tab) => {
  const tabs = document.querySelectorAll('.settings-tab');
  const panels = document.querySelectorAll('[data-tab-content]');

  tabs.forEach((button) => {
    const isActive = button.getAttribute('data-tab') === tab;
    button.classList.toggle('bg-daGreen/10', isActive);
    button.classList.toggle('text-daGreen', isActive);
    button.classList.toggle('font-medium', isActive);
    if (!isActive) {
      button.classList.remove('bg-daGreen/10', 'text-daGreen', 'font-medium');
    }
  });

  panels.forEach((panel) => {
    const isActive = panel.getAttribute('data-tab-content') === tab;
    panel.classList.toggle('hidden', !isActive);
  });
};

const initEmployeeSettingsPage = () => {
  runPageStatePass({ pageKey: 'employee-settings' });

  const settingsPageRoot = document.getElementById('employeeSettingsPage');
  const tabs = document.querySelectorAll('.settings-tab');

  tabs.forEach((button) => {
    button.addEventListener('click', () => {
      const tab = button.getAttribute('data-tab');
      if (!tab) return;
      activateSettingsTab(tab);
    });
  });

  const hashTab = (window.location.hash || '').replace('#', '').toLowerCase();
  const queryTab = new URLSearchParams(window.location.search).get('tab')?.toLowerCase();
  const dataTab = settingsPageRoot?.getAttribute('data-initial-tab')?.toLowerCase();
  const allowedTabs = new Set(['account', 'security', 'notifications', 'privacy']);

  const initialTab = [hashTab, queryTab, dataTab, 'account'].find((value) => value && allowedTabs.has(value));
  activateSettingsTab(initialTab || 'account');
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initEmployeeSettingsPage);
} else {
  initEmployeeSettingsPage();
}

export default initEmployeeSettingsPage;
