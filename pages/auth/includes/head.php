<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title><?= $pageTitle ?? 'ATI HRIS Portal' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(authAppPath('/assets/images/favicon.png'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars(authAppPath('/assets/images/favicon.ico'), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(authAppPath('/assets/images/apple-touch-icon.png'), ENT_QUOTES, 'UTF-8') ?>">


  <link rel="stylesheet" href="<?= htmlspecialchars(authAppPath('/global.css'), ENT_QUOTES, 'UTF-8') ?>">

  <script src="<?= htmlspecialchars(authAppPath('/assets/js/script.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>

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
</head>

<body class="bg-gray-50">