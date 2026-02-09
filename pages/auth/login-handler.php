<?php
session_start();

// FAKE USER (DEMO ONLY)
$demoUser = [
  'email' => 'employee@da.gov.ph',
  'password' => 'password123',
  'name' => 'Juan Dela Cruz',
  'role' => 'Employee'
];

// Get input
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Check credentials
if (
  $email === $demoUser['email'] &&
  $password === $demoUser['password']
) {
  // Set session
  $_SESSION['user'] = [
    'email' => $demoUser['email'],
    'name'  => $demoUser['name'],
    'role'  => $demoUser['role']
  ];

  // Redirect to dashboard
  header('Location: ../employee/dashboard.php');
  exit;
}

// Invalid login → back to login with error
header('Location: login.php?error=1');
exit;
