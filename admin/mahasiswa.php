<?php
// admin/mahasiswa.php
session_start();
require_once '../config/koneksi.php';

// ====== CEK LOGIN & ROLE (hanya admin) ======
if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$nama_session = $_SESSION['nama'] ?? '';
$role_session = $_SESSION['role'] ?? '';

// ====== CSRF TOKEN ======
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ====== FLASH MESSAGE ======
function set_flash($msg, $type = 'success')
{
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
function get_flash()
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/* ===========================================================
   HANDLE POST (TAMBAH / UPDATE)
   =========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Cek CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        set_flash('Token keamanan tidak valid. Silakan muat ulang halaman.', 'danger');
        header('Location: mahasiswa.php');
        exit;
    }

    /* =======================================================
       TAMBAH MAHASISWA (INSERT USERS + INSERT MAHASISWA)
       ======================================================= */
    if (isset($_POST['tambah'])) {

        $npm = trim($_POST['npm'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $prodi = trim($_POST['prodi'] ?? '');
        $fakultas = trim($_POST['fakultas'] ?? '');
        $angkatan = trim($_POST['angkatan'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $ipk = trim($_POST['ipk_terakhir'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');

        // Validasi wajib
        if ($npm === '' || $nama === '' || $prodi === '' || $fakultas === '' || $angkatan === '' || $semester === '') {
            set_flash('Semua field wajib diisi.', 'danger');
            header('Location: mahasiswa.php');
            exit;
        }

        // Validasi format NPM
        if (strlen($npm) !== 12 || !ctype_digit($npm)) {
            set_flash('NPM harus 12 digit angka.', 'danger');
            header('Location: mahasiswa.php');
            exit;
        }

        // Cek NPM unik
        $stmtCek = $koneksi->prepare("SELECT npm FROM mahasiswa WHERE npm=? LIMIT 1");
        $stmtCek->bind_param("s", $npm);
        $stmtCek->execute();
        $res = $stmtCek->get_result();
        if ($res->num_rows > 0) {
            set_flash("NPM sudah terdaftar.", "warning");
            header("Location: mahasiswa.php");
            exit;
        }
        $stmtCek->close();

        // 1) INSERT KE USERS (akun login)
        $username = $npm;
        $default_pass_plain = "#unpam" . substr($npm, -6);
        $default_pass_hash = password_hash($default_pass_plain, PASSWORD_DEFAULT);
        $role = "mahasiswa";

        $stmtUser = $koneksi->prepare(
            "INSERT INTO users (nama_lengkap, username, password, role)
             VALUES (?, ?, ?, ?)"
        );
        $stmtUser->bind_param("ssss", $nama, $username, $default_pass_hash, $role);

        if (!$stmtUser->execute()) {
            set_flash("Gagal membuat akun login: " . $stmtUser->error, "danger");
            $stmtUser->close();
            header("Location: mahasiswa.php");
            exit;
        }

        $id_user = $stmtUser->insert_id;
        $stmtUser->close();

        // 2) INSERT KE TABEL MAHASISWA
        $stmtIns = $koneksi->prepare(
            "INSERT INTO mahasiswa 
            (npm, password, id_user, nama, prodi, fakultas, angkatan, semester, ipk_terakhir, alamat, no_hp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmtIns->bind_param(
            "ssissssssss",
            $npm,
            $default_pass_hash, // tetap isi agar tidak NULL
            $id_user,
            $nama,
            $prodi,
            $fakultas,
            $angkatan,
            $semester,
            $ipk,
            $alamat,
            $no_hp
        );

        if ($stmtIns->execute()) {
            set_flash(
                'Mahasiswa berhasil ditambahkan.<br>Password awal: <b>' . $default_pass_plain . '</b>',
                'success'
            );
        } else {
            set_flash("Gagal insert ke mahasiswa: " . $stmtIns->error, "danger");
        }

        $stmtIns->close();
        header("Location: mahasiswa.php");
        exit;
    }

    /* =======================================================
       UPDATE MAHASISWA
       ======================================================= */
    if (isset($_POST['update'])) {

        $npm = trim($_POST['npm'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $prodi = trim($_POST['prodi'] ?? '');
        $fakultas = trim($_POST['fakultas'] ?? '');
        $angkatan = trim($_POST['angkatan'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        $ipk = trim($_POST['ipk_terakhir'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');

        if ($npm === '' || $nama === '' || $prodi === '' || $fakultas === '' || $angkatan === '' || $semester === '') {
            set_flash("Data mahasiswa tidak lengkap.", "danger");
            header("Location: mahasiswa.php");
            exit;
        }

        $stmtUpd = $koneksi->prepare(
            "UPDATE mahasiswa SET 
                nama=?, prodi=?, fakultas=?, angkatan=?, semester=?,
                ipk_terakhir=?, alamat=?, no_hp=?
             WHERE npm=?"
        );

        $stmtUpd->bind_param(
            "sssssssss",
            $nama,
            $prodi,
            $fakultas,
            $angkatan,
            $semester,
            $ipk,
            $alamat,
            $no_hp,
            $npm
        );

        if ($stmtUpd->execute()) {
            set_flash("Data mahasiswa berhasil diperbarui.", "success");
        } else {
            set_flash("Gagal update: " . $stmtUpd->error, "danger");
        }

        $stmtUpd->close();
        header("Location: mahasiswa.php");
        exit;
    }

} // END POST

/* ===========================================================
   IMPORT CSV / EXCEL
   =========================================================== */
if (isset($_POST['import_csv'])) {

    if (!isset($_FILES['file_import']) || $_FILES['file_import']['error'] !== UPLOAD_ERR_OK) {
        set_flash("Gagal upload file.", "danger");
        header("Location: mahasiswa.php");
        exit;
    }

    $file_tmp = $_FILES['file_import']['tmp_name'];
    $file_name = $_FILES['file_import']['name'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // ==== BACA FILE CSV ====
    if ($ext === 'csv') {
        $rows = array_map('str_getcsv', file($file_tmp));
    } else {
        set_flash("Format file tidak didukung. Gunakan CSV.", "danger");
        header("Location: mahasiswa.php");
        exit;
    }

    // Hapus header CSV
    $header = array_shift($rows);

    $insert_success = 0;
    $insert_fail = 0;

    foreach ($rows as $row) {
        if (count($row) < 9)
            continue; // data tidak lengkap

        list($npm, $nama, $prodi, $fakultas, $angkatan, $semester, $ipk, $alamat, $no_hp) = $row;

        // Validasi minimal
        if (strlen($npm) !== 12 || !ctype_digit($npm)) {
            $insert_fail++;
            continue;
        }

        // cek duplicate
        $stmtCek = $koneksi->prepare("SELECT npm FROM mahasiswa WHERE npm=? LIMIT 1");
        $stmtCek->bind_param("s", $npm);
        $stmtCek->execute();
        $result = $stmtCek->get_result();
        if ($result->num_rows > 0) {
            $insert_fail++;
            continue;
        }
        $stmtCek->close();

        // ==== Insert ke users ====
        $default_pass_plain = "#unpam" . substr($npm, -6);
        $default_pass_hash = password_hash($default_pass_plain, PASSWORD_DEFAULT);
        $username = $npm;
        $role = "mahasiswa";

        $stmtUser = $koneksi->prepare(
            "INSERT INTO users (nama_lengkap, username, password, role)
             VALUES (?, ?, ?, ?)"
        );
        $stmtUser->bind_param("ssss", $nama, $username, $default_pass_hash, $role);
        if (!$stmtUser->execute()) {
            $insert_fail++;
            continue;
        }
        $id_user = $stmtUser->insert_id;
        $stmtUser->close();

        // ==== Insert ke mahasiswa ====
        $stmtIns = $koneksi->prepare(
            "INSERT INTO mahasiswa 
            (npm, password, id_user, nama, prodi, fakultas, angkatan, semester, ipk_terakhir, alamat, no_hp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmtIns->bind_param(
            "ssissssssss",
            $npm,
            $default_pass_hash,
            $id_user,
            $nama,
            $prodi,
            $fakultas,
            $angkatan,
            $semester,
            $ipk,
            $alamat,
            $no_hp
        );

        if ($stmtIns->execute())
            $insert_success++;
        else
            $insert_fail++;
        $stmtIns->close();
    }

    set_flash("Import selesai: <b>$insert_success berhasil</b>, <b>$insert_fail gagal</b>.", "success");
    header("Location: mahasiswa.php");
    exit;
}

/* ===========================================================
   HANDLE GET HAPUS
   =========================================================== */
if (isset($_GET['hapus'])) {
    $npm = trim($_GET['hapus'] ?? '');

    if ($npm === '') {
        set_flash("NPM tidak valid.", "danger");
        header("Location: mahasiswa.php");
        exit;
    }

    $stmtDel = $koneksi->prepare("DELETE FROM mahasiswa WHERE npm=?");
    $stmtDel->bind_param("s", $npm);

    if ($stmtDel->execute()) {
        set_flash("Mahasiswa berhasil dihapus.", "success");
    } else {
        set_flash("Gagal hapus: " . $stmtDel->error, "danger");
    }

    $stmtDel->close();
    header("Location: mahasiswa.php");
    exit;
}

/* ===========================================================
   EXPORT CSV
=========================================================== */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data_mahasiswa.csv');

    $output = fopen('php://output', 'w');

    // Header kolom
    fputcsv($output, [
        'NPM',
        'Nama',
        'Prodi',
        'Fakultas',
        'Angkatan',
        'Semester',
        'IPK Terakhir',
        'Alamat',
        'No HP'
    ]);

    // Ambil data dari DB
    $query = $koneksi->query("
        SELECT npm, nama, prodi, fakultas, angkatan, semester, ipk_terakhir, alamat, no_hp
        FROM mahasiswa
        ORDER BY npm ASC
    ");

    while ($row = $query->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}




/* ===========================================================
   AMBIL DATA MAHASISWA
   =========================================================== */
$dataMhs = array();

$keyword = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'npm';              // default sort by npm
$order = $_GET['order'] ?? 'asc';           // default ascending

// daftar kolom yang aman
$allowedSort = ['npm', 'nama', 'prodi', 'fakultas', 'angkatan', 'semester'];

if (!in_array($sort, $allowedSort)) {
    $sort = 'npm';
}

$order = strtolower($order) === 'desc' ? 'desc' : 'asc';

if ($keyword !== '') {
    $sql = "
        SELECT 
            npm, nama, prodi, fakultas, angkatan, semester,
            ipk_terakhir, alamat, no_hp
        FROM mahasiswa
        WHERE npm LIKE ?
           OR nama LIKE ?
           OR prodi LIKE ?
           OR fakultas LIKE ?
        ORDER BY $sort $order
    ";
    $like = "%$keyword%";

    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $dataMhs[] = $row;
    }
    $stmt->close();
} else {
    $res = $koneksi->query("
        SELECT 
            npm, nama, prodi, fakultas, angkatan, semester,
            ipk_terakhir, alamat, no_hp
        FROM mahasiswa
        ORDER BY $sort $order
    ");
    while ($row = $res->fetch_assoc()) {
        $dataMhs[] = $row;
    }
    $res->free();
}

$flash = get_flash();
$pageTitle = "Data Mahasiswa";

// ===========================================================
// MULAI BAGIAN HTML
// ===========================================================
ob_start();
?>

<?php
// fungsi pembuat URL sort ASC/DESC
function sortUrl($column, $currentSort, $currentOrder)
{
    // toggle asc ⇄ desc
    $nextOrder = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';

    $q = isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '';

    return "mahasiswa.php?sort=$column&order=$nextOrder$q";
}
?>

<div class="card shadow-sm">
    <div class="card-body">

        <h3 class="mb-3">Data Mahasiswa</h3>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                <?= $flash['msg'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- BARIS TOMBOL + SEARCH -->
        <div class="d-flex justify-content-between align-items-center mb-3">

            <!-- Grup tombol kiri -->
            <div class="d-flex gap-2">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Mahasiswa
                </button>

                <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalImport">
                    <i class="bi bi-upload me-1"></i> Import CSV/Excel
                </button>

                <a href="mahasiswa.php?export=csv" class="btn btn-info">
                    <i class="bi bi-download me-1"></i> Export CSV
                </a>
            </div>

            <!-- Search -->
            <form method="get" action="mahasiswa.php" class="d-flex">
                <div class="input-group" style="width: 320px;">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control" placeholder="Cari NPM / Nama / Prodi / Fakultas"
                        value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>">

                    <?php if ($keyword !== ''): ?>
                        <a href="mahasiswa.php" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

        </div>

        <!-- TABEL -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>No</th>

                        <th>
                            <a href="<?= sortUrl('npm', $sort, $order) ?>" class="text-dark text-decoration-none">
                                NPM <?= ($sort == 'npm' ? ($order == 'asc' ? '▲' : '▼') : '') ?>
                            </a>
                        </th>

                        <th>
                            <a href="<?= sortUrl('nama', $sort, $order) ?>" class="text-dark text-decoration-none">
                                Nama <?= ($sort == 'nama' ? ($order == 'asc' ? '▲' : '▼') : '') ?>
                            </a>
                        </th>

                        <th>
                            <a href="<?= sortUrl('prodi', $sort, $order) ?>" class="text-dark text-decoration-none">
                                Prodi <?= ($sort == 'prodi' ? ($order == 'asc' ? '▲' : '▼') : '') ?>
                            </a>
                        </th>

                        <th>
                            <a href="<?= sortUrl('fakultas', $sort, $order) ?>" class="text-dark text-decoration-none">
                                Fakultas <?= ($sort == 'fakultas' ? ($order == 'asc' ? '▲' : '▼') : '') ?>
                            </a>
                        </th>

                        <th>
                            <a href="<?= sortUrl('angkatan', $sort, $order) ?>" class="text-dark text-decoration-none">
                                Angkatan <?= ($sort == 'angkatan' ? ($order == 'asc' ? '▲' : '▼') : '') ?>
                            </a>
                        </th>

                        <th>
                            <a href="<?= sortUrl('semester', $sort, $order) ?>" class="text-dark text-decoration-none">
                                Semester <?= ($sort == 'semester' ? ($order == 'asc' ? '▲' : '▼') : '') ?>
                            </a>
                        </th>

                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>

                    <?php if (count($dataMhs) === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center">Belum ada data.</td>
                        </tr>
                    <?php else:
                        $no = 1;
                        foreach ($dataMhs as $m): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= $m['npm'] ?></td>
                                <td><?= $m['nama'] ?></td>
                                <td><?= $m['prodi'] ?></td>
                                <td><?= $m['fakultas'] ?></td>
                                <td class="text-center"><?= $m['angkatan'] ?></td>
                                <td class="text-center"><?= $m['semester'] ?></td>
                                <td class="text-center">

                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#modalEdit<?= $m['npm'] ?>" title="Edit Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-npm="<?= $m['npm'] ?>"
                                        data-nama="<?= htmlspecialchars($m['nama'], ENT_QUOTES) ?>"
                                        data-href="mahasiswa.php?hapus=<?= $m['npm'] ?>" title="Hapus Data">
                                        <i class="bi bi-trash3"></i>
                                    </button>

                                </td>
                            </tr>

                            <!-- MODAL EDIT -->
                            <div class="modal fade" id="modalEdit<?= $m['npm'] ?>">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning">
                                            <h5>Edit Mahasiswa</h5>
                                            <button class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <div class="modal-body">

                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label>NPM</label>
                                                        <input type="text" name="npm" value="<?= $m['npm'] ?>"
                                                            class="form-control" readonly>
                                                    </div>

                                                    <div class="col-md-8">
                                                        <label>Nama</label>
                                                        <input type="text" name="nama" value="<?= $m['nama'] ?>"
                                                            class="form-control" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label>Prodi</label>
                                                        <input type="text" name="prodi" value="<?= $m['prodi'] ?>"
                                                            class="form-control" required>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label>Fakultas</label>
                                                        <input type="text" name="fakultas" value="<?= $m['fakultas'] ?>"
                                                            class="form-control" required>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label>Angkatan</label>
                                                        <input type="text" name="angkatan" value="<?= $m['angkatan'] ?>"
                                                            class="form-control" required>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label>Semester</label>
                                                        <input type="text" name="semester" value="<?= $m['semester'] ?>"
                                                            class="form-control" required>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label>IPK Terakhir</label>
                                                        <input type="text" name="ipk_terakhir" value="<?= $m['ipk_terakhir'] ?>"
                                                            class="form-control">
                                                    </div>

                                                    <div class="col-md-8">
                                                        <label>Alamat</label>
                                                        <textarea name="alamat"
                                                            class="form-control"><?= $m['alamat'] ?></textarea>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label>No HP</label>
                                                        <input type="text" name="no_hp" value="<?= $m['no_hp'] ?>"
                                                            class="form-control">
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="modal-footer">
                                                <button name="update" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>

                                    </div>
                                </div>
                            </div>

                        <?php endforeach; endif; ?>

                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header bg-success text-white">
                <h5>Tambah Mahasiswa</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label>NPM (12 digit)</label>
                            <input type="text" name="npm" maxlength="12" pattern="\d{12}" class="form-control" required>
                        </div>

                        <div class="col-md-8">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label>Prodi</label>
                            <input type="text" name="prodi" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label>Fakultas</label>
                            <input type="text" name="fakultas" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label>Angkatan</label>
                            <input type="text" name="angkatan" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label>Semester</label>
                            <input type="text" name="semester" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label>IPK Terakhir</label>
                            <input type="text" name="ipk_terakhir" class="form-control">
                        </div>

                        <div class="col-md-8">
                            <label>Alamat</label>
                            <textarea name="alamat" class="form-control"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label>No HP</label>
                            <input type="text" name="no_hp" class="form-control">
                        </div>

                    </div>

                    <hr>
                    <small>Password awal otomatis: <b>#unpam######</b></small>
                </div>

                <div class="modal-footer">
                    <button name="tambah" class="btn btn-success">Simpan</button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- MODAL IMPORT -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">Import Data Mahasiswa (CSV/Excel)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Pilih File CSV / Excel</label>
                        <input type="file" name="file_import" class="form-control" accept=".csv, .xlsx" required>
                        <small class="text-muted">Hanya mendukung file .csv dan .xlsx</small>
                    </div>

                    <hr>
                    <p><b>Template Import:</b></p>

                    <a href="template_mahasiswa.csv" class="btn btn-outline-primary btn-sm" download>
                        <i class="bi bi-file-earmark-spreadsheet"></i> Download Template CSV
                    </a>

                    <ul class="mt-3">
                        <li>NPM harus 12 digit</li>
                        <li>Field wajib: npm, nama, prodi, fakultas, angkatan, semester</li>
                        <li>Password dibuat otomatis (#unpam######)</li>
                    </ul>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="import_csv" class="btn btn-secondary">
                        Import Sekarang
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- MODAL HAPUS -->
<div class="modal fade" id="modalHapus">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-danger text-white">
                <h5>Konfirmasi Hapus</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                Yakin ingin menghapus:<br>
                NPM: <b id="hapusNpm"></b><br>
                Nama: <b id="hapusNama"></b>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="btnHapusConfirm" class="btn btn-danger">Hapus</a>
            </div>

        </div>
    </div>
</div>

<script>
    // Konfirmasi hapus
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('hapusNpm').textContent = this.dataset.npm;
            document.getElementById('hapusNama').textContent = this.dataset.nama;
            document.getElementById('btnHapusConfirm').setAttribute('href', this.dataset.href);

            new bootstrap.Modal(document.getElementById('modalHapus')).show();
        });
    });
</script>

<?php
$content = ob_get_clean();
require 'layout.php';
