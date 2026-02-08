<?php if (!empty($breadcrumbs)): ?>
  <nav class="text-sm text-gray-500 mb-4">
    <ol class="flex items-center gap-2">
      <?php foreach ($breadcrumbs as $i => $crumb): ?>
        <li class="<?= $i === array_key_last($breadcrumbs) ? 'text-gray-800 font-medium' : '' ?>">
          <?= $crumb ?>
        </li>
        <?php if ($i !== array_key_last($breadcrumbs)): ?>
          <li>/</li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ol>
  </nav>
<?php endif; ?>
