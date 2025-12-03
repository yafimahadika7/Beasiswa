<?php
// reviewer/pendaftaran.php
session_start();
require_once '../config/koneksi.php';

// ====== VALIDASI ROLE REVIEWER ======
if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'reviewer') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Data Pendaftaran";

// ===============================
// SEARCH HANDLER
// ===============================
$keyword = "";
$where = "";

if (!empty($_GET['cari'])) {
    $keyword = $koneksi->real_escape_string($_GET['cari']);

    $where = "
        WHERE 
            m.npm LIKE '%$keyword%' OR
            m.nama LIKE '%$keyword%' OR
            m.prodi LIKE '%$keyword%' OR
            b.nama_beasiswa LIKE '%$keyword%'
    ";
}

// ===============================
// LOAD DATA PENDAFTARAN
// ===============================
$sql = "
    SELECT 
        p.id_pendaftaran,
        p.tgl_daftar,
        p.status_pendaftaran,
        m.npm,
        m.nama,
        m.prodi,
        b.nama_beasiswa
    FROM pendaftaran p
    JOIN mahasiswa m ON p.npm = m.npm
    JOIN beasiswa b ON p.id_beasiswa = b.id_beasiswa
    $where
    ORDER BY p.id_pendaftaran DESC
";

$res = $koneksi->query($sql);

$dataPendaftar = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $dataPendaftar[] = $row;
    }
}

// ====== VIEW ======
ob_start();
?>

<div class="card shadow-sm">
    <div class="card-body">

        <h3 class="mb-3">Data Pendaftar Beasiswa</h3>

        <!-- ========================== SEARCH BAR ========================== -->
        <form method="GET" class="input-group mb-3" style="max-width: 400px;">
            <input type="text" name="cari" class="form-control" placeholder="Cari NPM / Nama / Prodi / Beasiswa..."
                value="<?= htmlspecialchars($keyword) ?>">
            <button class="btn btn-primary">
                <i class="bi bi-search"></i>
            </button>
        </form>

        <div class="table-responsive mt-3">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>No</th>
                        <th>NPM</th>
                        <th>Nama</th>
                        <th>Prodi</th>
                        <th>Beasiswa</th>
                        <th>Tanggal Daftar</th>
                        <th>Status Pendaftaran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>

                    <?php if (count($dataPendaftar) === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($dataPendaftar as $d): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($d['npm']) ?></td>
                                <td><?= htmlspecialchars($d['nama']) ?></td>
                                <td><?= htmlspecialchars($d['prodi']) ?></td>
                                <td><?= htmlspecialchars($d['nama_beasiswa']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($d['tgl_daftar']) ?></td>

                                <td class="text-center">
                                    <?php
                                    $status = $d['status_pendaftaran'] ?? '-';
                                    $badge = "secondary";

                                    switch ($status) {
                                        case "Diajukan":
                                            $badge = "secondary";
                                            break;
                                        case "Diproses":
                                            $badge = "warning";
                                            break;
                                        case "Lolos Verifikasi":
                                            $badge = "info";
                                            break;
                                        case "Lolos Seleksi":
                                            $badge = "success";
                                            break;
                                        case "Tidak Lolos":
                                            $badge = "danger";
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= $status ?></span>
                                </td>

                                <td class="text-center">
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail"
                                        onclick='lihatDetail(<?= json_encode($d) ?>)'>
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- ====================== MODAL DETAIL ====================== -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Detail Pendaftaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">NPM</th>
                        <td id="d_npm"></td>
                    </tr>
                    <tr>
                        <th>Nama</th>
                        <td id="d_nama"></td>
                    </tr>
                    <tr>
                        <th>Program Studi</th>
                        <td id="d_prodi"></td>
                    </tr>
                    <tr>
                        <th>Beasiswa</th>
                        <td id="d_beasiswa"></td>
                    </tr>
                    <tr>
                        <th>Tanggal Daftar</th>
                        <td id="d_tanggal"></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td id="d_status"></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function lihatDetail(d) {
        document.getElementById("d_npm").innerText = d.npm;
        document.getElementById("d_nama").innerText = d.nama;
        document.getElementById("d_prodi").innerText = d.prodi;
        document.getElementById("d_beasiswa").innerText = d.nama_beasiswa;
        document.getElementById("d_tanggal").innerText = d.tgl_daftar;
        document.getElementById("d_status").innerText = d.status_pendaftaran ?? "-";
    }
</script>

<?php
$content = ob_get_clean();
require 'layout.php';
