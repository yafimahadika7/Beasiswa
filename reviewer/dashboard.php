<?php
session_start();

if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'reviewer') {
    header("Location: ../login.php");
    exit;
}

require_once '../config/koneksi.php';

$page_title = "Dashboard Reviewer";
$id_reviewer = intval($_SESSION['id_user']);

// ---------------------------
// HITUNG DATA UNTUK REVIEWER
// ---------------------------

// Total semua pendaftaran
$total_pendaftaran = $koneksi->query("
    SELECT COUNT(*) AS jml 
    FROM pendaftaran
")->fetch_assoc()['jml'];


// Sudah dinilai oleh reviewer ini
$total_dinilai = $koneksi->query("
    SELECT COUNT(*) AS jml 
    FROM penilaian 
    WHERE id_reviewer = $id_reviewer
")->fetch_assoc()['jml'];


// Belum dinilai → pendaftaran yang TIDAK ADA di tabel penilaian
$total_pending = $koneksi->query("
    SELECT COUNT(*) AS jml
    FROM pendaftaran p
    LEFT JOIN penilaian pn 
        ON pn.id_pendaftaran = p.id_pendaftaran 
        AND pn.id_reviewer = $id_reviewer
    WHERE pn.id_penilaian IS NULL
")->fetch_assoc()['jml'];


// ---------------------------
// TAMPILAN DASHBOARD
// ---------------------------
ob_start();
?>

<h3 class="mb-3">Dashboard Reviewer</h3>
<p>Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>!
    Berikut ringkasan aktivitas Anda sebagai reviewer:</p>

<div class="row g-4 mt-2">

    <!-- Total Pendaftaran -->
    <div class="col-md-4 col-12">
        <div class="card text-center shadow-sm p-4 h-100">
            <h6 class="text-muted mb-2">Total Pendaftaran</h6>
            <h1 class="fw-bold"><?= $total_pendaftaran ?></h1>
            <a href="pendaftaran.php" class="btn btn-primary btn-sm mt-3 px-4">Lihat</a>
        </div>
    </div>

    <!-- Sudah Dinilai -->
    <div class="col-md-4 col-12">
        <div class="card text-center shadow-sm p-4 h-100">
            <h6 class="text-muted mb-2">Sudah Dinilai</h6>
            <h1 class="fw-bold"><?= $total_dinilai ?></h1>
            <a href="penilaian.php" class="btn btn-success btn-sm mt-3 px-4">Lihat</a>
        </div>
    </div>

    <!-- Belum Dinilai -->
    <div class="col-md-4 col-12">
        <div class="card text-center shadow-sm p-4 h-100">
            <h6 class="text-muted mb-2">Belum Dinilai</h6>
            <h1 class="fw-bold text-warning"><?= $total_pending ?></h1>
            <a href="penilaian.php" class="btn btn-warning btn-sm mt-3 px-4">Nilai Sekarang</a>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require 'layout.php';
