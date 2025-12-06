<?php
session_start();
require_once "../config/koneksi.php";

// Cek role mahasiswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Daftar Pesan";
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 0;

/* =====================================================
   AMBIL DAFTAR PESAN
===================================================== */
$notif_q = $koneksi->query("
    SELECT id, pesan, tgl, is_read
    FROM notifikasi
    WHERE id_user = '$id_user'
    ORDER BY id DESC
");

/* =====================================================
   HITUNG PESAN BELUM DIBACA
===================================================== */
$notif_unread = 0;
$notif_unread_q = $koneksi->query("
    SELECT COUNT(*) AS jml
    FROM notifikasi
    WHERE id_user = '$id_user' AND is_read = 0
");

if ($notif_unread_q && $notif_unread_q->num_rows > 0) {
    $rowUnread = $notif_unread_q->fetch_assoc();
    $notif_unread = isset($rowUnread['jml']) ? $rowUnread['jml'] : 0;
}

/* =====================================================
   UPDATE PESAN MENJADI "DIBACA"
===================================================== */
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

        <?php if (!$notif_q || $notif_q->num_rows === 0): ?>
            
            <p class="text-center text-muted">Belum ada pesan.</p>

        <?php else: ?>

            <ul class="list-group">

                <?php while ($msg = $notif_q->fetch_assoc()): ?>

                    <li class="list-group-item">
                        <b><?= htmlspecialchars($msg['pesan'], ENT_QUOTES, 'UTF-8') ?></b><br>

                        <small class="text-muted">
                            <?= htmlspecialchars(date('d-m-Y H:i', strtotime($msg['tgl'])), ENT_QUOTES, 'UTF-8') ?>
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