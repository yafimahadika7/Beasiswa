<?php
$host   = "localhost";
$user   = "root";      // default XAMPP
$pass   = "";          // kalau pakai password, isi di sini
$dbname = "db_beasiswa";

$koneksi = mysqli_connect($host, $user, $pass, $dbname);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
