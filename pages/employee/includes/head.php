<?php require_once __DIR__ . '/../../shared/lib/system-helpers.php'; ?>
  <?= systemRenderHeadAssets([
    'title' => $pageTitle ?? 'Employee | DA HRIS',
    'sweetalert' => true,
    'flatpickr' => false,
    'chart_js' => true,
    'material_icons' => true,
    'material_symbols' => true,
  ]) ?>

  <style>
    html {
      scroll-behavior: smooth;
    }

    body,
    body * {
      scrollbar-width: thin;
      scrollbar-color: rgba(148, 163, 184, 0.85) rgba(226, 232, 240, 0.75);
    }

    body::-webkit-scrollbar,
    body *::-webkit-scrollbar {
      width: 10px;
      height: 10px;
    }

    body::-webkit-scrollbar-track,
    body *::-webkit-scrollbar-track {
      background: rgba(226, 232, 240, 0.75);
      border-radius: 9999px;
    }

    body::-webkit-scrollbar-thumb,
    body *::-webkit-scrollbar-thumb {
      background: linear-gradient(180deg, rgba(148, 163, 184, 0.95) 0%, rgba(100, 116, 139, 0.95) 100%);
      border: 2px solid rgba(226, 232, 240, 0.85);
      border-radius: 9999px;
      min-height: 28px;
    }

    body::-webkit-scrollbar-thumb:hover,
    body *::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(180deg, rgba(100, 116, 139, 1) 0%, rgba(71, 85, 105, 1) 100%);
    }
  </style>

