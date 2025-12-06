<?php
session_start();
require_once "../config/koneksi.php";

// Cek role mahasiswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Dashboard Mahasiswa";
$nama = isset($_SESSION['nama']) ? $_SESSION['nama'] : "";

// Ambil NPM mahasiswa dari tabel mahasiswa
$npm = null;

if (isset($_SESSION['id_user'])) {

    $getNPM = $koneksi->prepare("
        SELECT npm 
        FROM mahasiswa 
        WHERE id_user = ?
    ");
    $getNPM->bind_param("i", $_SESSION['id_user']);
    $getNPM->execute();
    $resultNPM = $getNPM->get_result();

    if ($resultNPM && $resultNPM->num_rows > 0) {
        $dataNPM = $resultNPM->fetch_assoc();
        $npm = $dataNPM['npm'];
    }

    $getNPM->close();
}

// Ambil pengajuan terbaru
$statusPengajuan = null;

if ($npm !== null) {
    $query = $koneksi->prepare("
        SELECT 
            tahun,
            jenis_beasiswa,
            status_pendaftaran,
            tgl_daftar
        FROM pendaftaran
        WHERE npm = ?
        ORDER BY id_pendaftaran DESC
        LIMIT 1
    ");

    $query->bind_param("s", $npm);
    $query->execute();
    $res = $query->get_result();

    if ($res && $res->num_rows > 0) {
        $statusPengajuan = $res->fetch_assoc();
    }

    $query->close();
}

ob_start();
?>

<h3 class="fw-bold mb-3">Selamat datang, <?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?>! 👋</h3>
<p class="text-muted mb-4">Berikut adalah ringkasan status pengajuan beasiswa Anda.</p>

<div class="row g-4">

    <!-- CARD STATUS PENGAJUAN -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">

                <h5 class="card-title mb-3">
                    <i class="bi bi-info-circle text-primary me-2"></i>
                    Status Pengajuan Terakhir
                </h5>

                <?php if ($statusPengajuan === null): ?>

                    <p class="text-muted">Anda belum pernah mengajukan beasiswa.</p>
                    <a href="ajukan.php" class="btn btn-primary mt-2">
                        Ajukan Beasiswa Sekarang
                    </a>

                <?php else: ?>

                    <ul class="list-group">

                        <li class="list-group-item">
                            <strong>Tahun:</strong> <?= htmlspecialchars($statusPengajuan['tahun']) ?>
                        </li>

                        <li class="list-group-item">
                            <strong>Jenis Beasiswa:</strong>
                            <?= htmlspecialchars($statusPengajuan['jenis_beasiswa']) ?>
                        </li>

                        <li class="list-group-item">
                            <strong>Tanggal Pengajuan:</strong>
                            <?= htmlspecialchars($statusPengajuan['tgl_daftar']) ?>
                        </li>

                        <li class="list-group-item">
                            <strong>Status:</strong>

                            <?php
                            $status = $statusPengajuan['status_pendaftaran'];

                            // Tentukan warna badge
                            $badgeColor = "secondary";

                            if ($status === "Diajukan")
                                $badgeColor = "warning text-dark";
                            elseif ($status === "Diproses")
                                $badgeColor = "info text-dark";
                            elseif ($status === "Lolos Verifikasi")
                                $badgeColor = "success";
                            elseif ($status === "Tidak Lolos")
                                $badgeColor = "danger";
                            ?>

                            <span class="badge bg-<?= $badgeColor ?> text-uppercase">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </li>

                    </ul>

                    <a href="status.php" class="btn btn-outline-primary mt-3">
                        Lihat Detail Status
                    </a>

                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- CARD INFORMASI PROGRAM -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body">

                <h5 class="card-title mb-3">
                    <i class="bi bi-bell text-warning me-2"></i>
                    Informasi Program Beasiswa
                </h5>

                <p class="text-muted">
                    Sistem ini digunakan untuk mengajukan bantuan pendidikan, memantau proses verifikasi dan penilaian,
                    hingga pengumuman hasil.
                </p>

                <ul>
                    <li>Pengajuan dibuka sesuai jadwal kampus.</li>
                    <li>Pastikan berkas lengkap sebelum mengirim.</li>
                    <li>Proses verifikasi dilakukan oleh admin.</li>
                    <li>Mahasiswa dapat memonitor status pada menu <strong>Status Pengajuan</strong>.</li>
                </ul>

            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
include "layout.php";
