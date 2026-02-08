<?php
session_start();

if (!isset($_SESSION['user'])) {
  header('Location: /hris-system/auth/login.php');
  exit;
}
