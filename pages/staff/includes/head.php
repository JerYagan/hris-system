<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title><?= $pageTitle ?? 'Staff | DA HRIS' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <link rel="icon" type="image/png" sizes="32x32" href="/hris-system/assets/images/favicon.png">
  <link rel="icon" type="image/x-icon" href="/hris-system/assets/images/favicon.ico">
  <link rel="apple-touch-icon" sizes="180x180" href="/hris-system/assets/images/apple-touch-icon.png">
  <link rel="stylesheet" href="/hris-system/global.css">

  <style>
    .material-symbols-outlined {
      font-variation-settings: 'wght' 400;
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

<body class="bg-gray-100 text-gray-800"></body>
