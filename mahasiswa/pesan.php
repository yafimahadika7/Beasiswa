<?php
session_start();
require_once "../config/koneksi.php";

if ($_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Daftar Pesan";
$id_user = $_SESSION['id_user'];

/* ================================
   AMBIL DAFTAR PESAN
================================ */
$notif_q = $koneksi->query("
    SELECT id, pesan, tgl, is_read
    FROM notifikasi
    WHERE id_user = '$id_user'
    ORDER BY id DESC
");

/* ================================
   HITUNG PESAN BELUM DIBACA
================================ */
$notif_unread = $koneksi->query("
    SELECT COUNT(*) AS jml
    FROM notifikasi
    WHERE id_user = '$id_user' AND is_read = 0
")->fetch_assoc()['jml'];

/* ================================
   UPDATE PESAN JADI 'DIBACA'
   Begitu halaman ini dibuka
================================ */
$koneksi->query("
    UPDATE notifikasi
    SET is_read = 1
    WHERE id_user = '$id_user' AND is_read = 0
");

ob_start();
?>

<h3 class="mb-3">Semua Pesan</h3>

<div class="card shadow-sm">
    <div class="card-body">

        <?php if ($notif_q->num_rows == 0): ?>
            <p class="text-center text-muted">Belum ada pesan.</p>

        <?php else: ?>
            <ul class="list-group">

                <?php while ($msg = $notif_q->fetch_assoc()): ?>

                    <li class="list-group-item">
                        <b><?= htmlspecialchars($msg['pesan']) ?></b><br>

                        <small class="text-muted">
                            <?= date('d-m-Y H:i', strtotime($msg['tgl'])) ?>
                        </small>
                    </li>

                <?php endwhile; ?>

            </ul>
        <?php endif; ?>

    </div>
</div>

<?php
$content = ob_get_clean();
include "layout.php";
?>