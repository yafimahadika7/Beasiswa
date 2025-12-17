<?php
session_start();

if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'reviewer') {
    header("Location: ../login.php");
    exit;
}

require_once '../config/koneksi.php';

$page_title = "Dashboard Reviewer";
$id_reviewer = (int) $_SESSION['id_user'];

// ===========================
// HITUNG DATA
// ===========================

// Total pendaftaran
$q1 = $koneksi->query("SELECT COUNT(*) AS total FROM pendaftaran");
$total_pendaftaran = $q1 ? $q1->fetch_assoc()['total'] : 0;

// Sudah dinilai oleh reviewer ini
$q2 = $koneksi->query("
    SELECT COUNT(*) AS total 
    FROM penilaian 
    WHERE id_reviewer = $id_reviewer
");
$total_dinilai = $q2 ? $q2->fetch_assoc()['total'] : 0;

// Belum dinilai
$q3 = $koneksi->query("
    SELECT COUNT(*) AS total
    FROM pendaftaran p
    LEFT JOIN penilaian pn 
        ON pn.id_pendaftaran = p.id_pendaftaran 
        AND pn.id_reviewer = $id_reviewer
    WHERE pn.id_penilaian IS NULL
");
$total_pending = $q3 ? $q3->fetch_assoc()['total'] : 0;

// ===========================
// DASHBOARD VIEW
// ===========================
ob_start();
?>

<h4 class="mb-2">Selamat datang, <b><?= htmlspecialchars($_SESSION['nama']) ?></b> 👋</h4>
<p class="text-muted mb-4">Anda login sebagai <b>Reviewer</b>. Berikut ringkasan tugas Anda.</p>

<div class="row g-4">

    <!-- TOTAL PENDAFTARAN -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body d-flex align-items-center">
                <div class="me-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                        <i class="bi bi-folder2-open text-primary fs-3"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <small class="text-muted">Total Pendaftaran</small>
                    <h3 class="fw-bold mb-1"><?= $total_pendaftaran ?></h3>
                    <a href="pendaftaran.php" class="btn btn-sm btn-outline-primary mt-2">
                        Lihat Data
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- SUDAH DINILAI -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body d-flex align-items-center">
                <div class="me-3">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3">
                        <i class="bi bi-check-circle text-success fs-3"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <small class="text-muted">Sudah Dinilai</small>
                    <h3 class="fw-bold mb-1"><?= $total_dinilai ?></h3>
                    <a href="penilaian.php" class="btn btn-sm btn-outline-success mt-2">
                        Riwayat Penilaian
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- BELUM DINILAI -->
    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-body d-flex align-items-center">
                <div class="me-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                        <i class="bi bi-exclamation-circle text-warning fs-3"></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <small class="text-muted">Belum Dinilai</small>
                    <h3 class="fw-bold mb-1"><?= $total_pending ?></h3>
                    <a href="penilaian.php" class="btn btn-sm btn-warning mt-2">
                        Nilai Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require 'layout.php';
