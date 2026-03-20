<?php require_once __DIR__ . '/../../shared/lib/system-helpers.php'; ?>
  <?= systemRenderHeadAssets([
    'title' => $pageTitle ?? 'Staff | DA HRIS',
    'sweetalert' => true,
    'flatpickr' => true,
    'chart_js' => true,
    'material_icons' => true,
    'material_symbols' => true,
  ]) ?>

  <style>
  </style>
