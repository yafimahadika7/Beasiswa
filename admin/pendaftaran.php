<?php
session_start();
require_once "../config/koneksi.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Manajemen Pendaftaran";

/* ====================================================
   HANDLE VALIDASI ADMIN
==================================================== */
if (isset($_POST['validasi_admin'])) {
    $id_pendaftaran = $_POST['id_pendaftaran'];
    $status_admin = $_POST['status_admin'];
    $catatan_admin = $_POST['catatan_admin'];

    $stmt = $koneksi->prepare("
        UPDATE pendaftaran
        SET status_admin=?, catatan_admin=?
        WHERE id_pendaftaran=?
    ");
    $stmt->bind_param("ssi", $status_admin, $catatan_admin, $id_pendaftaran);
    $stmt->execute();

    $_SESSION['notif'] = "Status administrasi berhasil diperbarui.";
    header("Location: pendaftaran.php");
    exit;
}

/* ====================================================
   AMBIL DATA PENDAFTARAN
==================================================== */
/* ====================================================
   AMBIL DATA PENDAFTARAN + SEARCH
==================================================== */

$keyword = trim($_GET['q'] ?? '');
$dataList = [];

if ($keyword !== '') {

    $like = "%$keyword%";

    $stmt = $koneksi->prepare("
        SELECT 
            p.id_pendaftaran,
            p.npm,
            m.nama,
            b.nama_beasiswa,
            p.jenis_beasiswa,
            p.tahun,
            p.status_reviewer,
            p.status_admin,
            p.tgl_daftar,
            p.catatan_admin
        FROM pendaftaran p
        JOIN mahasiswa m ON p.npm = m.npm
        JOIN beasiswa b ON p.id_beasiswa = b.id_beasiswa
        WHERE p.npm LIKE ?
           OR m.nama LIKE ?
           OR b.nama_beasiswa LIKE ?
           OR p.jenis_beasiswa LIKE ?
           OR p.tahun LIKE ?
        ORDER BY p.id_pendaftaran DESC
    ");

    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc())
        $dataList[] = $row;
    $stmt->close();

} else {

    $sql = "
        SELECT 
            p.id_pendaftaran,
            p.npm,
            m.nama,
            b.nama_beasiswa,
            p.jenis_beasiswa,
            p.tahun,
            p.status_reviewer,
            p.status_admin,
            p.tgl_daftar,
            p.catatan_admin
        FROM pendaftaran p
        JOIN mahasiswa m ON p.npm = m.npm
        JOIN beasiswa b ON p.id_beasiswa = b.id_beasiswa
        ORDER BY p.id_pendaftaran DESC
    ";

    $res = $koneksi->query($sql);
    while ($row = $res->fetch_assoc())
        $dataList[] = $row;
}

ob_start();
?>


<div class="card shadow-sm">
    <div class="card-body">
        <h4 class="mb-3">Manajemen Pendaftaran</h4>

        <?php if (!empty($_SESSION['notif'])): ?>
            <div class="alert alert-success"><?= $_SESSION['notif']; ?></div>
            <?php unset($_SESSION['notif']); ?>
        <?php endif; ?>

        <!-- SEARCH BOX -->
        <div class="d-flex justify-content-end mb-3">
            <form method="get" action="pendaftaran.php" class="d-flex">
                <div class="input-group" style="width: 320px;">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search"></i>
                    </span>

                    <input type="text" name="q" class="form-control" placeholder="Cari NPM / Nama / Beasiswa / Tahun..."
                        value="<?= htmlspecialchars($keyword) ?>">

                    <?php if ($keyword !== ''): ?>
                        <a href="pendaftaran.php" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="card-body">

            <table class="table table-bordered table-hover">
                <thead class="table-primary text-center">
                    <tr>
                        <th>No</th>
                        <th>NPM</th>
                        <th>Nama</th>
                        <th>Beasiswa</th>
                        <th>Jenis</th>
                        <th>Status Reviewer</th>
                        <th>Status Admin</th>
                        <th>Tgl Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $no = 1;
                    foreach ($dataList as $p):
                        $idp = $p['id_pendaftaran'];

                        // ambil berkas
                        $berkas = $koneksi->query("
                    SELECT *
                    FROM berkas_pendaftaran
                    WHERE id_pendaftaran = '$idp'
                ");
                        ?>
                        <tr class="align-middle">
                            <td class="text-center"><?= $no++; ?></td>
                            <td class="text-center"><?= $p['npm']; ?></td>
                            <td><?= htmlspecialchars($p['nama']); ?></td>
                            <td><?= htmlspecialchars($p['nama_beasiswa']); ?></td>
                            <td class="text-center"><?= $p['jenis_beasiswa']; ?></td>

                            <td class="text-center">
                                <?php
                                $sr = $p['status_reviewer'];
                                $badge = "secondary";

                                if ($sr == "Diajukan")
                                    $badge = "warning text-dark";
                                if ($sr == "Diverifikasi")
                                    $badge = "info text-dark";
                                if ($sr == "Ditolak")
                                    $badge = "danger";
                                if ($sr == "Diterima")
                                    $badge = "success";

                                echo "<span class='badge bg-$badge'>$sr</span>";
                                ?>
                            </td>

                            <td class="text-center">
                                <?php
                                $sa = $p['status_admin'];
                                $badge = "secondary";

                                if ($sa == "Menunggu")
                                    $badge = "warning text-dark";
                                if ($sa == "Valid")
                                    $badge = "success";
                                if ($sa == "Tidak Valid")
                                    $badge = "danger";

                                echo "<span class='badge bg-$badge'>$sa</span>";
                                ?>
                            </td>

                            <td class="text-center"><?= date('d-m-Y H:i', strtotime($p['tgl_daftar'])); ?></td>

                            <td class="text-center">

                                <div class="d-flex justify-content-center gap-2">

                                    <!-- Lihat berkas -->
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal"
                                        data-bs-target="#modalBerkas<?= $idp ?>" title="Lihat Berkas">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <?php if ($p['status_reviewer'] === "Diterima"): ?>
                                        <!-- Validasi Admin -->
                                        <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                                            data-bs-target="#modalValidasi<?= $idp ?>" title="Validasi Admin">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                    <?php else: ?>
                                        <!-- Locked -->
                                        <button class="btn btn-sm btn-secondary" disabled title="Menunggu reviewer">
                                            <i class="bi bi-lock"></i>
                                        </button>
                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                        <!-- MODAL LIHAT BERKAS -->
                        <div class="modal fade" id="modalBerkas<?= $idp ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered">

                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="bi bi-folder"></i> Berkas Pengajuan</h5>
                                        <button class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                        <ul class="list-group">
                                            <?php while ($b = $berkas->fetch_assoc()): ?>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>
                                                        <strong><?= $b['jenis_berkas']; ?></strong>
                                                        <small>(<?= $b['status_verifikasi']; ?>)</small>
                                                    </span>

                                                    <a href="<?= $b['path_file']; ?>" target="_blank"
                                                        class="btn btn-sm btn-secondary">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </li>
                                            <?php endwhile; ?>
                                        </ul>
                                    </div>

                                    <div class="modal-footer">
                                        <button class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x-circle"></i> Tutup
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- MODAL VALIDASI ADMIN -->
                        <div class="modal fade" id="modalValidasi<?= $idp ?>" tabindex="-1">
                            <div class="modal-dialog modal-md modal-dialog-centered">

                                <div class="modal-content">
                                    <form method="POST">

                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="bi bi-check2-circle"></i> Validasi Admin
                                            </h5>
                                            <button class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">

                                            <input type="hidden" name="id_pendaftaran" value="<?= $idp ?>">

                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status_admin" class="form-select" required>
                                                    <option value="Valid">Valid</option>
                                                    <option value="Tidak Valid">Tidak Valid</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Catatan Admin</label>
                                                <textarea name="catatan_admin" class="form-control" rows="3"></textarea>
                                            </div>

                                        </div>

                                        <div class="modal-footer">
                                            <button class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="bi bi-x-circle"></i> Batal
                                            </button>
                                            <button type="submit" name="validasi_admin" class="btn btn-primary">
                                                <i class="bi bi-save"></i> Simpan
                                            </button>
                                        </div>

                                    </form>
                                </div>

                            </div>
                        </div>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include "layout.php";
