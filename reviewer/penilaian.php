<?php
// reviewer/penilaian.php
session_start();
require_once '../config/koneksi.php';

// ====== VALIDASI ROLE REVIEWER ======
if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'reviewer') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Penilaian Pendaftar";

// =======================================================
// PROSES SIMPAN PENILAIAN
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nilai_submit'])) {

    $id_pendaftaran = intval($_POST['id_pendaftaran']);
    $id_reviewer = intval($_SESSION['id_user']);

    $skor_ipk = intval($_POST['skor_ipk']);
    $skor_prestasi = intval($_POST['skor_prestasi']);
    $skor_wawancara = intval($_POST['skor_wawancara']);
    $rekomendasi = $_POST['rekomendasi'];          // 'layak' / 'tidak_layak'
    $catatan = $_POST['catatan'];

    $total = $skor_ipk + $skor_prestasi + $skor_wawancara;

    // Mapping rekomendasi → status tabel pendaftaran
    // ENUM:
    //  - status_reviewer:  'Diajukan','Diproses','Ditolak','Diterima'
    //  - status_pendaftaran: 'Diajukan','Diproses','Lolos Verifikasi','Lolos Seleksi','Tidak Lolos'
    if ($rekomendasi === "layak") {
        $status_reviewer = "Diterima";
        $status_pendaftaran = "Lolos Verifikasi";
    } else {
        $status_reviewer = "Ditolak";
        $status_pendaftaran = "Tidak Lolos";
    }

    // Cek apakah sudah ada penilaian sebelumnya
    $cek = $koneksi->prepare("SELECT id_penilaian FROM penilaian WHERE id_pendaftaran = ?");
    $cek->bind_param("i", $id_pendaftaran);
    $cek->execute();
    $res = $cek->get_result();

    if ($res && $res->num_rows > 0) {
        // UPDATE PENILAIAN
        $stmt = $koneksi->prepare("
            UPDATE penilaian SET 
                skor_ipk = ?, 
                skor_prestasi = ?, 
                skor_wawancara = ?, 
                skor_total = ?, 
                status_rekomendasi = ?, 
                catatan_reviewer = ?
            WHERE id_pendaftaran = ?
        ");
        $stmt->bind_param(
            "iiisssi",
            $skor_ipk,
            $skor_prestasi,
            $skor_wawancara,
            $total,
            $rekomendasi,
            $catatan,
            $id_pendaftaran
        );
    } else {
        // INSERT PENILAIAN BARU
        $stmt = $koneksi->prepare("
            INSERT INTO penilaian 
            (id_pendaftaran, id_reviewer, skor_ipk, skor_prestasi, skor_wawancara, skor_total, status_rekomendasi, catatan_reviewer)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param(
            "iiiiisss",
            $id_pendaftaran,
            $id_reviewer,
            $skor_ipk,
            $skor_prestasi,
            $skor_wawancara,
            $total,
            $rekomendasi,
            $catatan
        );
    }
    $stmt->execute();
    $stmt->close();
    $cek->close();

    // UPDATE STATUS KE TABEL PENDAFTARAN
    $stmt2 = $koneksi->prepare("
        UPDATE pendaftaran
        SET status_reviewer = ?, status_pendaftaran = ?
        WHERE id_pendaftaran = ?
    ");
    $stmt2->bind_param("ssi", $status_reviewer, $status_pendaftaran, $id_pendaftaran);
    $stmt2->execute();
    $stmt2->close();

    header("Location: penilaian.php");
    exit;
}

// =======================================================
// LOAD DATA PENDAFTAR + PENILAIAN (JIKA SUDAH ADA)
// =======================================================
$sql = "
SELECT 
    p.id_pendaftaran, 
    p.npm, 
    m.nama, 
    m.prodi,
    b.nama_beasiswa,
    p.status_reviewer,
    pn.skor_ipk,
    pn.skor_prestasi,
    pn.skor_wawancara,
    pn.skor_total, 
    pn.status_rekomendasi,
    pn.catatan_reviewer
FROM pendaftaran p
JOIN mahasiswa m ON m.npm = p.npm
JOIN beasiswa b ON b.id_beasiswa = p.id_beasiswa
LEFT JOIN penilaian pn ON pn.id_pendaftaran = p.id_pendaftaran
ORDER BY p.id_pendaftaran DESC
";

$res = $koneksi->query($sql);
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}

// ====== BEGIN VIEW ======================================================
ob_start();
?>

<div class="card shadow-sm">
    <div class="card-body">

        <h3 class="mb-3">Penilaian Pendaftar</h3>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>No</th>
                        <th>NPM</th>
                        <th>Nama</th>
                        <th>Prodi</th>
                        <th>Beasiswa</th>
                        <th>Total Nilai</th>
                        <th>Rekomendasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Belum ada pendaftar.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($data as $d): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($d['npm']) ?></td>
                                <td><?= htmlspecialchars($d['nama']) ?></td>
                                <td><?= htmlspecialchars($d['prodi']) ?></td>
                                <td><?= htmlspecialchars($d['nama_beasiswa']) ?></td>

                                <td class="text-center">
                                    <?= $d['skor_total'] !== null ? htmlspecialchars($d['skor_total']) : "<i>Belum dinilai</i>" ?>
                                </td>

                                <td class="text-center">
                                    <?php
                                    if ($d['status_rekomendasi'] === "layak") {
                                        echo "<span class='badge bg-success'>Layak</span>";
                                    } elseif ($d['status_rekomendasi'] === "tidak_layak") {
                                        echo "<span class='badge bg-danger'>Tidak Layak</span>";
                                    } else {
                                        echo "<span class='badge bg-secondary'>Belum dinilai</span>";
                                    }
                                    ?>
                                </td>

                                <td class="text-center">
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalNilai"
                                        onclick='isiForm(<?= json_encode($d) ?>)'>
                                        <i class="bi bi-pencil-square"></i> Nilai
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

<!-- ========================= MODAL FORM PENILAIAN ====================== -->
<div class="modal fade" id="modalNilai" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST">

            <div class="modal-header bg-warning">
                <h5 class="modal-title">Form Penilaian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" name="id_pendaftaran" id="f_id">

                <div class="mb-3">
                    <label class="form-label">NPM</label>
                    <input type="text" id="f_npm" class="form-control" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" id="f_nama" class="form-control" readonly>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Skor IPK</label>
                        <input type="number" name="skor_ipk" id="f_ipk" class="form-control" min="0" max="100" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Skor Prestasi</label>
                        <input type="number" name="skor_prestasi" id="f_prestasi" class="form-control" min="0" max="100"
                            required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Skor Wawancara</label>
                        <input type="number" name="skor_wawancara" id="f_wawancara" class="form-control" min="0"
                            max="100" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Rekomendasi</label>
                    <select name="rekomendasi" id="f_rekom" class="form-control" required>
                        <option value="">-- Pilih --</option>
                        <option value="layak">Layak</option>
                        <option value="tidak_layak">Tidak Layak</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Catatan Reviewer</label>
                    <textarea name="catatan" id="f_catatan" class="form-control" rows="3"></textarea>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" name="nilai_submit">Simpan Penilaian</button>
            </div>

        </form>
    </div>
</div>

<script>
    function isiForm(d) {
        document.getElementById("f_id").value = d.id_pendaftaran;
        document.getElementById("f_npm").value = d.npm;
        document.getElementById("f_nama").value = d.nama;

        document.getElementById("f_ipk").value = d.skor_ipk ?? 0;
        document.getElementById("f_prestasi").value = d.skor_prestasi ?? 0;
        document.getElementById("f_wawancara").value = d.skor_wawancara ?? 0;
        document.getElementById("f_rekom").value = d.status_rekomendasi ?? "";
        document.getElementById("f_catatan").value = d.catatan_reviewer ?? "";
    }
</script>

<?php
$content = ob_get_clean();
require 'layout.php';
