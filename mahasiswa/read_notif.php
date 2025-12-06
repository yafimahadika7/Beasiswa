<?php
session_start();
require_once "../config/koneksi.php";

$id_user = $_SESSION['id_user'];

$koneksi->query("
    UPDATE notifikasi
    SET is_read = 1
    WHERE id_user = '$id_user' AND is_read = 0
");
?>