<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title><?= $pageTitle ?? 'DA HRIS' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="/hris-system/assets/images/favicon.png">
  <link rel="icon" type="image/x-icon" href="/hris-system/assets/images/favicon.ico">

  <!-- Apple Touch Icon (optional) -->
  <link rel="apple-touch-icon" sizes="180x180" href="/hris-system/assets/images/apple-touch-icon.png">


  <link rel="stylesheet" href="../global.css">

  <script src="/hris/assets/js/script.js" defer></script>

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