<?php
session_start();
require_once '../config/koneksi.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ================= DATA =================

// Jumlah penerima beasiswa (Valid + Diterima)
$res = $koneksi->query("
    SELECT COUNT(*) AS total 
    FROM pendaftaran 
    WHERE status_reviewer='Diterima' 
      AND status_admin='Valid'
");
$j_beasiswa = $res ? $res->fetch_assoc()['total'] : 0;

// Jumlah Mahasiswa
$res = $koneksi->query("SELECT COUNT(*) AS total FROM mahasiswa");
$j_mahasiswa = $res ? $res->fetch_assoc()['total'] : 0;

// Jumlah Pendaftaran
$res = $koneksi->query("SELECT COUNT(*) AS total FROM pendaftaran");
$j_daftar = $res ? $res->fetch_assoc()['total'] : 0;

$page_title = "Dashboard Admin";

ob_start();
?>

<style>
    .dashboard-card {
        border-radius: 14px;
        padding: 1.5rem;
        background: #fff;
        position: relative;
        overflow: hidden;
        transition: all .3s ease;
    }

    .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, .08);
    }

    .dashboard-card::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        width: 6px;
        height: 100%;
    }

    .card-green::before {
        background: #20c997;
    }

    .card-blue::before {
        background: #0d6efd;
    }

    .card-orange::before {
        background: #fd7e14;
    }

    .card-title {
        font-size: 13px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: .5px;
    }

    .card-value {
        font-size: 34px;
        font-weight: 700;
    }

    .card-icon {
        font-size: 42px;
        opacity: .25;
    }
</style>

<h4 class="fw-bold mb-1">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?> 👋</h4>
<p class="text-muted mb-4">Anda login sebagai <b>Admin</b>.</p>

<div class="row g-4">

    <!-- Penerima Beasiswa -->
    <div class="col-md-4">
        <div class="dashboard-card card-green h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-title text-success">Penerima Beasiswa</div>
                    <div class="card-value"><?= $j_beasiswa ?></div>
                    <a href="penerima_beasiswa.php" class="btn btn-sm btn-success mt-3">
                        Kelola Data
                    </a>
                </div>
                <i class="bi bi-award-fill card-icon text-success"></i>
            </div>
        </div>
    </div>

    <!-- Mahasiswa -->
    <div class="col-md-4">
        <div class="dashboard-card card-blue h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-title text-primary">Mahasiswa</div>
                    <div class="card-value"><?= $j_mahasiswa ?></div>
                    <a href="mahasiswa.php" class="btn btn-sm btn-primary mt-3">
                        Kelola Data
                    </a>
                </div>
                <i class="bi bi-people-fill card-icon text-primary"></i>
            </div>
        </div>
    </div>

    <!-- Pendaftaran -->
    <div class="col-md-4">
        <div class="dashboard-card card-orange h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="card-title text-warning">Pendaftaran</div>
                    <div class="card-value"><?= $j_daftar ?></div>
                    <a href="pendaftaran.php" class="btn btn-sm btn-warning mt-3">
                        Kelola Data
                    </a>
                </div>
                <i class="bi bi-file-earmark-text-fill card-icon text-warning"></i>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
include 'layout.php';

