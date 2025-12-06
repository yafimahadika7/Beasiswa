<?php
session_start();
require_once "../config/koneksi.php";

/* ============================================
   CEK SESSION
============================================ */
if (!isset($_SESSION['id_user'])) {
    http_response_code(400); // Bad request
    echo "Session user tidak ditemukan.";
    exit;
}

$id_user = $_SESSION['id_user'];

/* ============================================
   UPDATE NOTIFIKASI MENJADI DIBACA
============================================ */
$sql = "
    UPDATE notifikasi
    SET is_read = 1
    WHERE id_user = '$id_user' AND is_read = 0
";

$koneksi->query($sql);

/* ============================================
   KIRIM RESPONSE (WAJIB UNTUK FETCH)
============================================ */
echo "OK";
?>
