<?php
session_start();
require_once '../config/koneksi.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Jumlah penerima beasiswa (Valid + Diterima)
$res = $koneksi->query("
    SELECT COUNT(*) AS total 
    FROM pendaftaran 
    WHERE status_reviewer='Diterima' 
      AND status_admin='Valid'
");
$j_beasiswa = $res ? $res->fetch_assoc()['total'] : 0;

// Jumlah Mahasiswa
$result = $koneksi->query("SELECT COUNT(*) AS total FROM mahasiswa");
$j_mahasiswa = $result ? $result->fetch_assoc()['total'] : 0;

// Jumlah Pendaftaran
$res = $koneksi->query("SELECT COUNT(*) AS total FROM pendaftaran");
$j_daftar = $res ? $res->fetch_assoc()['total'] : 0;

$page_title = "Dashboard Admin";

// ===== Dashboard Content (lebih rapih) =====
$content = '
<h4 class="mb-2">Selamat datang, <b>' . $_SESSION['nama'] . '</b>!</h4>
<p class="text-muted mb-4">Anda login sebagai admin.</p>

<div class="row g-4 mt-2">

    <div class="col-md-4">
        <div class="card shadow-sm p-4 text-center h-100">
            <h6 class="text-muted">Penerima Beasiswa</h6>
            <h1 class="fw-bold mt-2 mb-3">' . $j_beasiswa . '</h1>
            <a href="penerima_beasiswa.php" class="btn btn-primary btn-sm px-4">Kelola</a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm p-4 text-center h-100">
            <h6 class="text-muted">Mahasiswa</h6>
            <h1 class="fw-bold mt-2 mb-3">' . $j_mahasiswa . '</h1>
            <a href="mahasiswa.php" class="btn btn-primary btn-sm px-4">Kelola</a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm p-4 text-center h-100">
            <h6 class="text-muted">Pendaftaran</h6>
            <h1 class="fw-bold mt-2 mb-3">' . $j_daftar . '</h1>
            <a href="pendaftaran.php" class="btn btn-primary btn-sm px-4">Kelola</a>
        </div>
    </div>

</div>
';
include 'layout.php';
?>
