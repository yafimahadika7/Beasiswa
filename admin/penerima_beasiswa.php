<?php
// admin/penerima_beasiswa.php
session_start();
require_once '../config/koneksi.php';

// ==== CEK LOGIN ROLE ADMIN ====
if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Penerima Beasiswa";

/* ====================================================
   EXPORT CSV PENERIMA BEASISWA
==================================================== */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=penerima_beasiswa.csv');

    $output = fopen('php://output', 'w');

    // Header kolom CSV
    fputcsv($output, [
        'NPM',
        'Nama',
        'Prodi',
        'Fakultas',
        'Jenis Beasiswa',
        'Tahun',
        'Tanggal Approve',
        'Approved By'
    ]);

    // Query data penerima beasiswa
    $sql_export = "
        SELECT 
            p.npm,
            m.nama,
            m.prodi,
            m.fakultas,
            b.nama_beasiswa,
            b.jenis,
            YEAR(p.tgl_daftar) AS tahun,
            p.tgl_daftar
        FROM pendaftaran p
        JOIN mahasiswa m ON m.npm = p.npm
        JOIN beasiswa b ON b.id_beasiswa = p.id_beasiswa
        WHERE p.status_reviewer='Diterima'
          AND p.status_admin='Valid'
        ORDER BY p.id_pendaftaran DESC
    ";

    $result_export = $koneksi->query($sql_export);

    while ($row = $result_export->fetch_assoc()) {
        fputcsv($output, [
            $row['npm'],
            $row['nama'],
            $row['prodi'],
            $row['fakultas'],
            $row['nama_beasiswa'] . ' (' . $row['jenis'] . ')',
            $row['tahun'],
            $row['tgl_daftar'],
            'Administrator'
        ]);
    }

    fclose($output);
    exit;
}

// ===== SEARCH FILTER =====
$keyword = trim($_GET['q'] ?? '');

// ===== QUERY MENGAMBIL DATA DARI TABEL PENDAFTARAN =====
// HANYA MENAMPILKAN > Reviewer: Diterima & Admin: Valid

$sql = "
    SELECT 
        p.id_pendaftaran,
        p.npm,
        m.nama,
        m.prodi,
        m.fakultas,
        b.nama_beasiswa,
        b.jenis,
        YEAR(p.tgl_daftar) AS tahun,
        p.tgl_daftar,
        p.status_reviewer,
        p.status_admin
    FROM pendaftaran p
    JOIN mahasiswa m ON m.npm = p.npm
    JOIN beasiswa b ON b.id_beasiswa = p.id_beasiswa
    WHERE p.status_reviewer = 'Diterima'
      AND p.status_admin = 'Valid'
";

// FILTER SEARCH
if ($keyword !== '') {
    $sql .= " AND (
                p.npm LIKE '%$keyword%' OR
                m.nama LIKE '%$keyword%' OR
                m.prodi LIKE '%$keyword%' OR
                m.fakultas LIKE '%$keyword%' OR
                b.nama_beasiswa LIKE '%$keyword%'
            )";
}

$sql .= " ORDER BY p.id_pendaftaran DESC";

$res = $koneksi->query($sql);
$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

ob_start();
?>

<div class="card shadow-sm">
    <div class="card-body">

        <h3 class="mb-3">Penerima Beasiswa</h3>

        <!-- EXPORT + SEARCH -->
        <div class="d-flex justify-content-between align-items-center mb-3">

            <!-- Tombol Export CSV di kiri -->
            <a href="penerima_beasiswa.php?export=csv" class="btn btn-info">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>

            <!-- Search di kanan -->
            <form method="get" class="d-flex" style="max-width: 350px;">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Cari..."
                        value="<?= htmlspecialchars($keyword) ?>">
                    <button class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>No</th>
                        <th>NPM</th>
                        <th>Nama</th>
                        <th>Prodi</th>
                        <th>Fakultas</th>
                        <th>Jenis Beasiswa</th>
                        <th>Tahun</th>
                        <th>Tanggal Approve</th>
                        <th>Approved By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="9" class="text-center">Belum ada penerima beasiswa.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        foreach ($data as $d): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= $d['npm'] ?></td>
                                <td><?= $d['nama'] ?></td>
                                <td><?= $d['prodi'] ?></td>
                                <td><?= $d['fakultas'] ?></td>
                                <td><?= $d['nama_beasiswa'] ?> (<?= $d['jenis'] ?>)</td>
                                <td class="text-center"><?= $d['tahun'] ?></td>
                                <td class="text-center"><?= $d['tgl_daftar'] ?></td>
                                <td class="text-center">Administrator</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>