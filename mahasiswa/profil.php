<?php
session_start();
require_once "../config/koneksi.php";

if ($_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Profil Mahasiswa";

$npm = $_SESSION['npm'] ?? $_SESSION['username'];

// Ambil data mahasiswa
$stmt = $koneksi->prepare("
    SELECT m.*, u.username 
    FROM mahasiswa m
    JOIN users u ON u.username = m.npm
    WHERE m.npm=? LIMIT 1
");
$stmt->bind_param("s", $npm);
$stmt->execute();
$mhs = $stmt->get_result()->fetch_assoc();

$alert = "";

/* ============================================================
   HANDLE UBAH PASSWORD (DARI MODAL)
============================================================ */
if (isset($_POST['ubah_password'])) {

    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $password_konfirmasi = $_POST['password_konfirmasi'];

    // Ambil data user
    $stmt2 = $koneksi->prepare("SELECT id_user, password FROM users WHERE username=? LIMIT 1");
    $stmt2->bind_param("s", $npm);
    $stmt2->execute();
    $user = $stmt2->get_result()->fetch_assoc();

    // Validasi password lama
    if (!password_verify($password_lama, $user['password'])) {
        $alert = '<div class="alert alert-danger">Password lama tidak sesuai.</div>';
    } 
    elseif ($password_baru !== $password_konfirmasi) {
        $alert = '<div class="alert alert-warning">Konfirmasi password tidak sama.</div>';
    } 
    elseif (strlen($password_baru) < 6) {
        $alert = '<div class="alert alert-warning">Password baru minimal 6 karakter.</div>';
    } 
    else {
        // Update dengan hash baru
        $hashed = password_hash($password_baru, PASSWORD_DEFAULT);

        $stmtUp = $koneksi->prepare("UPDATE users SET password=? WHERE id_user=?");
        $stmtUp->bind_param("si", $hashed, $user['id_user']);
        $stmtUp->execute();

        $alert = '<div class="alert alert-success">Password berhasil diperbarui.</div>';
    }
}

ob_start();
?>

<h4 class="mb-3">Profil Mahasiswa</h4>

<?= $alert ?>

<div class="card shadow-sm border-0">
    <div class="card-body">

        <div class="row">
            <div class="col-md-4 profile-left d-flex flex-column align-items-center justify-content-center">

                <h5 class="fw-bold mb-0"><?= $mhs['nama']; ?></h5>
                <p class="text-muted mb-2"><?= $mhs['npm']; ?></p>

                <!-- Tombol Ubah Password (Modal) -->
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalPassword">
                    <i class="bi bi-key"></i> Ubah Password
                </button>
            </div>

            <div class="col-md-8">

                <table class="table table-borderless mt-3">
                    <tr>
                        <th width="180">NPM</th>
                        <td>: <?= $mhs['npm']; ?></td>
                    </tr>
                    <tr>
                        <th>Nama Lengkap</th>
                        <td>: <?= $mhs['nama']; ?></td>
                    </tr>
                    <tr>
                        <th>Jurusan</th>
                        <td>: <?= $mhs['fakultas']; ?></td>
                    </tr>
                    <tr>
                        <th>Semester</th>
                        <td>: <?= $mhs['semester']; ?></td>
                    </tr>
                    <tr>
                        <th>IPK Terakhir</th>
                        <td>: <?= $mhs['ipk_terakhir']; ?></td>
                    </tr>
                </table>

            </div>
        </div>

    </div>
</div>

<!-- ============================================================
     MODAL UBAH PASSWORD
============================================================ -->
<div class="modal fade" id="modalPassword" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">
            
            <form method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-key"></i> Ubah Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="password_lama" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password_baru" class="form-control" required minlength="6">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="password_konfirmasi" class="form-control" required minlength="6">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>

                    <button type="submit" name="ubah_password" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Password
                    </button>
                </div>

            </form>

        </div>

    </div>
</div>

<?php
$content = ob_get_clean();
include "layout.php";
