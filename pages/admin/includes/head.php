<?php
require_once __DIR__ . '/../../shared/lib/system-helpers.php';

$adminActivePage = $activePage ?? '';
$adminChartPages = ['dashboard.php', 'report-analytics.php'];
$adminDataTablePages = [
  'recruitment.php',
  'applicants.php',
  'applicant-tracking.php',
  'evaluation.php',
  'document-management.php',
  'personal-information.php',
  'timekeeping.php',
  'payroll-management.php',
  'report-analytics.php',
  'learning-and-development.php',
  'user-management.php',
  'notifications.php',
];

$loadAdminCharts = in_array($adminActivePage, $adminChartPages, true);
$loadAdminDataTables = in_array($adminActivePage, $adminDataTablePages, true);

echo systemRenderHeadAssets([
  'title' => $pageTitle ?? 'Admin | DA HRIS',
  'sweetalert' => true,
  'flatpickr' => true,
  'chart_js' => $loadAdminCharts,
  'material_icons' => false,
  'material_symbols' => true,
]);
?>
  <?php if ($loadAdminDataTables): ?>
  <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.1.2/css/buttons.dataTables.min.css">
  <script defer src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
  <script defer src="https://cdn.datatables.net/buttons/3.1.2/js/dataTables.buttons.min.js"></script>
  <script defer src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script defer src="https://cdn.datatables.net/buttons/3.1.2/js/buttons.html5.min.js"></script>
  <?php endif; ?>

<style>
  .admin-shell {
    background: #c8d2d8;
  }

  .admin-topbar {
    box-shadow: 0 8px 24px rgba(2, 6, 23, 0.18);
  }

  .admin-sidebar {
    border-right: 0;
    overflow: hidden;
    box-shadow: 8px 0 24px rgba(2, 6, 23, 0.24);
  }

  .admin-search {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    min-height: 2.5rem;
    border-radius: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.09);
    background: rgba(30, 41, 59, 0.35);
    padding: 0 0.75rem;
    color: rgb(148 163 184);
  }

  .admin-search input {
    flex: 1;
    background: transparent;
    border: 0;
    outline: none;
    color: rgb(226 232 240);
    font-size: 0.82rem;
  }

  .admin-search input::placeholder {
    color: rgb(100 116 139);
  }

  .admin-nav-link {
    display: grid;
    grid-template-columns: 2rem minmax(0, 1fr) auto;
    align-items: center;
    column-gap: 0.55rem;
    min-height: 2.5rem;
    padding: 0.2rem 0.55rem;
    border-radius: 0.85rem;
    border: 0;
    background: transparent;
    position: relative;
    transition: all 0.2s ease;
  }

  .admin-nav-link:hover {
    background: rgba(148, 163, 184, 0.1);
  }

  .admin-nav-link.active {
    background: transparent;
    color: rgb(248 250 252) !important;
    box-shadow: none;
  }

  .admin-nav-link.active::before {
    content: "";
    position: absolute;
    left: -0.45rem;
    top: 0.35rem;
    bottom: 0.35rem;
    width: 0.2rem;
    border-radius: 999px;
    background: linear-gradient(180deg, rgb(52 211 153) 0%, rgb(16 185 129) 100%);
  }

  .admin-nav-icon {
    width: 2rem;
    height: 2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.65rem;
    background: transparent;
    border: 0;
    color: rgb(203 213 225);
  }

  .admin-nav-link.active .admin-nav-icon {
    color: rgb(52 211 153);
  }

  .admin-link-label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .admin-section-label {
    min-height: 1rem;
    letter-spacing: 0.08em;
    color: rgba(255, 255, 255, 0.56);
  }

  .admin-category-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 0.5rem;
    border: 0;
    background: transparent;
    cursor: pointer;
  }

  .admin-category-chevron {
    color: rgba(148, 163, 184, 0.8);
    transition: transform 0.18s ease;
  }

  .admin-category-toggle[aria-expanded="false"] .admin-category-chevron {
    transform: rotate(-90deg);
  }

  .admin-brand-text {
    transition: opacity 0.18s ease, max-width 0.18s ease;
    max-width: 12rem;
  }

  .admin-top-chip {
    border: 0;
    background: transparent;
    color: rgb(226 232 240);
    border-radius: 0.75rem;
    transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
  }

  .admin-top-chip:hover {
    background: rgba(16, 185, 129, 0.14);
    color: rgb(248 250 252);
    transform: translateY(-1px);
  }

  .admin-top-search {
    background: rgba(15, 23, 42, 0.72);
    color: rgb(226 232 240);
    border: 0;
    border-radius: 0.8rem;
    box-shadow: inset 0 0 0 1px rgba(100, 116, 139, 0.42);
    transition: background 0.2s ease, box-shadow 0.2s ease;
  }

  .admin-top-search::placeholder {
    color: rgb(148 163 184);
  }

  .admin-top-search:hover {
    background: rgba(15, 23, 42, 0.8);
    box-shadow: inset 0 0 0 1px rgba(100, 116, 139, 0.58);
  }

  .admin-top-search:focus {
    background: rgba(15, 23, 42, 0.88);
    box-shadow: inset 0 0 0 1px rgba(16, 185, 129, 0.68);
  }

  .admin-link-badge {
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0;
    border-radius: 999px;
    color: rgb(110 231 183);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    line-height: 1;
    font-weight: 700;
  }

  #profileMenu {
    border: 0;
    border-radius: 0.85rem;
    box-shadow: 0 16px 30px rgba(2, 6, 23, 0.22);
  }

  #profileMenu .border-t {
    border-top: 0;
    height: 1px;
    background: rgba(148, 163, 184, 0.25);
    margin: 0.35rem 0;
  }

  #sidebar nav {
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 116, 139, 0.6) transparent;
  }

  #sidebar nav::-webkit-scrollbar {
    width: 8px;
  }

  #sidebar nav::-webkit-scrollbar-track {
    background: transparent;
  }

  #sidebar nav::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, rgba(100, 116, 139, 0.55), rgba(71, 85, 105, 0.72));
    border-radius: 999px;
    border: 2px solid transparent;
    background-clip: padding-box;
  }

  #sidebar nav::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, rgba(148, 163, 184, 0.7), rgba(100, 116, 139, 0.88));
    background-clip: padding-box;
  }

  #sidebar {
    transition: transform 0.2s ease;
  }

  .dt-container .dt-search input,
  .dt-container .dt-length select {
    border: 1px solid rgb(203 213 225);
    border-radius: 0.375rem;
    padding: 0.35rem 0.5rem;
    font-size: 0.875rem;
    background: #fff;
  }

  .dt-container .dt-info,
  .dt-container .dt-paging .dt-paging-button {
    font-size: 0.82rem;
  }

  .dt-container table.dataTable thead > tr > th,
  .dt-container table.dataTable thead > tr > td {
    position: relative;
  }

  .dt-container table.dataTable thead > tr > th .dt-column-order,
  .dt-container table.dataTable thead > tr > td .dt-column-order {
    position: relative;
    display: inline-block;
    width: 0.9rem;
    margin-left: 0.35rem;
    vertical-align: middle;
  }

  .dt-container table.dataTable thead > tr > th .dt-column-order::before,
  .dt-container table.dataTable thead > tr > th .dt-column-order::after,
  .dt-container table.dataTable thead > tr > td .dt-column-order::before,
  .dt-container table.dataTable thead > tr > td .dt-column-order::after {
    position: absolute;
    left: 0;
    font-size: 0.55rem;
    line-height: 1;
    color: rgb(100 116 139);
    opacity: 0.55;
  }

  .dt-container table.dataTable thead > tr > th .dt-column-order::before,
  .dt-container table.dataTable thead > tr > td .dt-column-order::before {
    content: '▲';
    top: -0.4rem;
  }

  .dt-container table.dataTable thead > tr > th .dt-column-order::after,
  .dt-container table.dataTable thead > tr > td .dt-column-order::after {
    content: '▼';
    top: 0.2rem;
  }

  .dt-container table.dataTable thead > tr > th.dt-ordering-asc .dt-column-order::before,
  .dt-container table.dataTable thead > tr > td.dt-ordering-asc .dt-column-order::before {
    color: rgb(15 23 42);
    opacity: 0.95;
  }

  .dt-container table.dataTable thead > tr > th.dt-ordering-desc .dt-column-order::after,
  .dt-container table.dataTable thead > tr > td.dt-ordering-desc .dt-column-order::after {
    color: rgb(15 23 42);
    opacity: 0.95;
  }

  .admin-action-button {
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    min-height: 2.25rem;
    padding: 0.5rem 0.8rem;
    border: 1px solid rgb(203 213 225);
    border-radius: 0.85rem;
    background: linear-gradient(180deg, rgb(255 255 255) 0%, rgb(248 250 252) 100%);
    color: rgb(51 65 85);
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, transform 0.18s ease;
  }

  .admin-action-button:hover {
    background: linear-gradient(180deg, rgb(255 255 255) 0%, rgb(241 245 249) 100%);
    border-color: rgb(148 163 184);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    transform: translateY(-1px);
  }

  .admin-action-button:focus-visible {
    outline: 0;
    border-color: rgb(148 163 184);
    box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.2);
  }

  .admin-action-button-label {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    min-width: 0;
  }

  .admin-action-button .material-symbols-outlined {
    font-size: 18px;
  }

  .admin-action-chevron {
    color: rgb(100 116 139);
    transition: transform 0.18s ease;
  }

  .admin-action-button[aria-expanded="true"] .admin-action-chevron {
    transform: rotate(180deg);
  }

  .admin-action-menu {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 70;
    padding: 0.35rem;
    border: 1px solid rgb(226 232 240);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.16);
    backdrop-filter: blur(10px);
    transform-origin: top right;
    overflow-y: auto;
    overscroll-behavior: contain;
  }

  .admin-action-menu[data-menu-placement="top"] {
    transform-origin: bottom right;
  }

  .admin-action-item {
    width: 100%;
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.7rem 0.85rem;
    border: 0;
    border-radius: 0.8rem;
    background: transparent;
    color: rgb(51 65 85);
    font-size: 0.875rem;
    font-weight: 500;
    line-height: 1.2;
    text-align: left;
    text-decoration: none;
    transition: background-color 0.16s ease, color 0.16s ease;
  }

  .admin-action-item:hover {
    background: rgb(248 250 252);
  }

  .admin-action-item:focus-visible {
    outline: 0;
    background: rgb(241 245 249);
  }

  .admin-action-item .material-symbols-outlined {
    font-size: 18px;
    opacity: 0.74;
  }

  .admin-action-item-warning {
    color: rgb(180 83 9);
  }

  .admin-action-item-warning:hover,
  .admin-action-item-warning:focus-visible {
    background: rgb(255 251 235);
  }

  .admin-action-item-danger {
    color: rgb(190 24 93);
  }

  .admin-action-item-danger:hover,
  .admin-action-item-danger:focus-visible {
    background: rgb(255 241 242);
  }

  .admin-action-item-disabled,
  .admin-action-item:disabled {
    color: rgb(148 163 184);
    cursor: not-allowed;
  }

  .admin-action-item-disabled:hover,
  .admin-action-item:disabled:hover {
    background: transparent;
  }

  .admin-action-divider {
    height: 1px;
    margin: 0.3rem 0;
    background: rgb(226 232 240);
  }

  .flatpickr-calendar {
    border-radius: 0.75rem;
    box-shadow: 0 12px 26px rgba(2, 6, 23, 0.18);
    border: 1px solid rgb(226 232 240);
  }
</style>

<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          daGreen: "#1B5E20",
          approved: "#B7F7A3",
          pending: "#F9F871",
          rejected: "#FF9A9A"
        }
      }
    }
  }
</script>
