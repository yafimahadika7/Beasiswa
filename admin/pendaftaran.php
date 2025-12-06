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
   EXPORT CSV PENDAFTARAN
==================================================== */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data_pendaftaran.csv');

    $output = fopen('php://output', 'w');

    // Header kolom
    fputcsv($output, [
        'ID Pendaftaran',
        'NPM',
        'Nama Mahasiswa',
        'Nama Beasiswa',
        'Jenis Beasiswa',
        'Tahun',
        'Status Reviewer',
        'Status Admin',
        'Tanggal Daftar',
        'Catatan Admin'
    ]);

    // Query data
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

    while ($row = $res->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}


/* ====================================================
   AMBIL DATA PENDAFTARAN + SEARCH
==================================================== */

$keyword = trim($_GET['q'] ?? '');
$dataList = [];

# SORTING
$sort = $_GET['sort'] ?? 'id_pendaftaran';  // default
$order = $_GET['order'] ?? 'desc';          // default

$allowedSort = [
    'id_pendaftaran',
    'npm',
    'nama',
    'nama_beasiswa',
    'jenis_beasiswa',
    'status_reviewer',
    'status_admin',
    'tgl_daftar'
];

if (!in_array($sort, $allowedSort)) {
    $sort = 'id_pendaftaran';
}

$order = strtolower($order) === 'asc' ? 'asc' : 'desc';

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
        ORDER BY $sort $order
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
        ORDER BY $sort $order
    ";

    $res = $koneksi->query($sql);
    while ($row = $res->fetch_assoc())
        $dataList[] = $row;
}

ob_start();
?>

<style>
    th a {
        text-decoration: none !important;
        color: #000;
        font-weight: 600;
    }

    th a:hover {
        color: #000;
    }
</style>


<div class="card shadow-sm">
    <div class="card-body">
        <h4 class="mb-3">Manajemen Pendaftaran</h4>

        <?php if (!empty($_SESSION['notif'])): ?>
            <div class="alert alert-success"><?= $_SESSION['notif']; ?></div>
            <?php unset($_SESSION['notif']); ?>
        <?php endif; ?>


        <!-- EXPORT + SEARCH -->
        <div class="d-flex justify-content-between align-items-center mb-3">

            <!-- Tombol Export CSV -->
            <a href="pendaftaran.php?export=csv" class="btn btn-info">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>

            <!-- Search Box -->
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
                <?php
                function sortIcon($field, $sort, $order)
                {
                    if ($field !== $sort)
                        return ""; // kolom tidak sedang di-sort
                
                    return $order === 'asc' ? " ▲" : " ▼";
                }
                ?>
                <thead class="table-primary text-center">
                    <tr>
                        <th>No</th>

                        <th>
                            <a href="?sort=npm&order=<?= ($sort == 'npm' && $order == 'asc') ? 'desc' : 'asc' ?>">
                                NPM<?= sortIcon('npm', $sort, $order) ?>
                            </a>
                        </th>

                        <th>
                            <a href="?sort=nama&order=<?= ($sort == 'nama' && $order == 'asc') ? 'desc' : 'asc' ?>">
                                Nama<?= sortIcon('nama', $sort, $order) ?>
                            </a>
                        </th>

                        <th>
                            <a
                                href="?sort=nama_beasiswa&order=<?= ($sort == 'nama_beasiswa' && $order == 'asc') ? 'desc' : 'asc' ?>">
                                Beasiswa<?= sortIcon('nama_beasiswa', $sort, $order) ?>
                            </a>
                        </th>

                        <th>
                            <a
                                href="?sort=jenis_beasiswa&order=<?= ($sort == 'jenis_beasiswa' && $order == 'asc') ? 'desc' : 'asc' ?>">
                                Jenis<?= sortIcon('jenis_beasiswa', $sort, $order) ?>
                            </a>
                        </th>

                        <th>
                            <a
                                href="?sort=status_reviewer&order=<?= ($sort == 'status_reviewer' && $order == 'asc') ? 'desc' : 'asc' ?>">
                                Status Reviewer<?= sortIcon('status_reviewer', $sort, $order) ?>
                            </a>
                        </th>

                        <th>
                            <a
                                href="?sort=status_admin&order=<?= ($sort == 'status_admin' && $order == 'asc') ? 'desc' : 'asc' ?>">
                                Status Admin<?= sortIcon('status_admin', $sort, $order) ?>
                            </a>
                        </th>

                        <th>
                            <a
                                href="?sort=tgl_daftar&order=<?= ($sort == 'tgl_daftar' && $order == 'asc') ? 'desc' : 'asc' ?>">
                                Tgl Daftar<?= sortIcon('tgl_daftar', $sort, $order) ?>
                            </a>
                        </th>

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
