<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title><?= $pageTitle ?? 'DA HRIS' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="/hris-system/assets/images/favicon.png">
  <link rel="icon" type="image/x-icon" href="/hris-system/assets/images/favicon.ico">

  <!-- Apple Touch Icon (optional) -->
  <link rel="apple-touch-icon" sizes="180x180" href="/hris-system/assets/images/apple-touch-icon.png">


  <link rel="stylesheet" href="/hris-system/global.css">

  <style>
    .material-symbols-outlined {
      font-variation-settings: 'wght' 400;
    }

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