<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Pendaftaran Beasiswa</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="#">Sistem Beasiswa</a>

    <ul class="navbar-nav ms-auto">
      <?php if(isset($_SESSION['role'])): ?>

        <?php if($_SESSION['role'] == 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="/UAS_07TPLE004_221011400189_YAFI-MAHADIKA/admin/dashboard.php">Dashboard</a></li>
        <?php elseif($_SESSION['role'] == 'mahasiswa'): ?>
          <li class="nav-item"><a class="nav-link" href="/UAS_07TPLE004_221011400189_YAFI-MAHADIKA/mahasiswa/dashboard.php">Dashboard</a></li>
        <?php elseif($_SESSION['role'] == 'reviewer'): ?>
          <li class="nav-item"><a class="nav-link" href="/UAS_07TPLE004_221011400189_YAFI-MAHADIKA/reviewer/dashboard.php">Dashboard</a></li>
        <?php endif; ?>

        <li class="nav-item"><a class="nav-link" href="/UAS_07TPLE004_221011400189_YAFI-MAHADIKA/auth/logout.php">Logout</a></li>
      <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="/UAS_07TPLE004_221011400189_YAFI-MAHADIKA/login.php">Login</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<div class="container mt-4">
