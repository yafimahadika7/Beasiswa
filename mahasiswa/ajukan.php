<?php
session_start();
require_once "../config/koneksi.php";

if ($_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Ajukan Beasiswa";

// Ambil NPM saat login
$npm = $_SESSION['npm'] ?? $_SESSION['username'] ?? '';
if ($npm === "") {
    die("Sesi mahasiswa tidak ditemukan. Silakan login ulang.");
}

// Ambil IPK & semester mahasiswa
$stmtMhs = $koneksi->prepare("SELECT ipk_terakhir, semester FROM mahasiswa WHERE npm=? LIMIT 1");
$stmtMhs->bind_param("s", $npm);
$stmtMhs->execute();
$mhs = $stmtMhs->get_result()->fetch_assoc();
$ipk = $mhs['ipk_terakhir'];
$semester = $mhs['semester'];

// Ambil list beasiswa yang masih aktif dan memenuhi syarat
$sqlBeasiswa = "
    SELECT id_beasiswa, nama_beasiswa
    FROM beasiswa
    WHERE status='aktif'
      AND CURDATE() BETWEEN tgl_mulai_daftar AND tgl_selesai_daftar
      AND (ipk_minimal IS NULL OR ipk_minimal <= $ipk)
      AND (semester_minimal IS NULL OR semester_minimal <= $semester)
    ORDER BY nama_beasiswa ASC
";
$res = $koneksi->query($sqlBeasiswa);
$beasiswa = [];
while ($row = $res->fetch_assoc()) {
    $beasiswa[] = $row;
}

$alert = "";
$show_upload_form = false;
$id_pendaftaran_aktif = 0;

/* ============================================
   1. AJUKAN BEASISWA
============================================ */
if (isset($_POST['ajukan_beasiswa'])) {

    $id_beasiswa = (int) $_POST['id_beasiswa'];
    $tahun = date('Y');

    if ($id_beasiswa <= 0) {
        $alert = '<div class="alert alert-danger">Silakan pilih jenis beasiswa.</div>';
    } else {

        // Cek apakah pernah mengajukan beasiswa yang sama tahun ini
        $stmtCek = $koneksi->prepare("
            SELECT id_pendaftaran
            FROM pendaftaran
            WHERE npm=? AND id_beasiswa=? AND tahun=?
            LIMIT 1
        ");
        $stmtCek->bind_param("sii", $npm, $id_beasiswa, $tahun);
        $stmtCek->execute();
        $cek = $stmtCek->get_result();

        if ($cek->num_rows > 0) {
            $alert = '<div class="alert alert-warning">
                        Anda sudah mengajukan beasiswa ini pada tahun ' . $tahun . '.
                      </div>';
        } else {

            // Ambil jenis beasiswa internal/eksternal
            $stmtJ = $koneksi->prepare("SELECT jenis FROM beasiswa WHERE id_beasiswa=? LIMIT 1");
            $stmtJ->bind_param("i", $id_beasiswa);
            $stmtJ->execute();
            $jenis_beasiswa = $stmtJ->get_result()->fetch_assoc()['jenis'];

            // Insert pendaftaran baru
            $stmtIns = $koneksi->prepare("
                INSERT INTO pendaftaran (id_beasiswa, npm, status_pendaftaran, tahun, jenis_beasiswa)
                VALUES (?, ?, 'Diajukan', ?, ?)
            ");
            $stmtIns->bind_param("isis", $id_beasiswa, $npm, $tahun, $jenis_beasiswa);

            if ($stmtIns->execute()) {
                $id_pendaftaran_aktif = $stmtIns->insert_id;
                $show_upload_form = true;
                $alert = '<div class="alert alert-success">Pengajuan berhasil. Silakan upload dokumen persyaratan.</div>';
            } else {
                $alert = '<div class="alert alert-danger">Gagal mengajukan: ' . $stmtIns->error . '</div>';
            }
        }
    }
}

/* ============================================
   2. UPLOAD BERKAS
============================================ */
if (isset($_POST['upload_berkas'])) {

    $id_pendaftaran = (int) $_POST['id_pendaftaran'];

    $jenis_berkas = [
        "KTP" => "ktp",
        "Kartu Keluarga" => "kartu_keluarga",
        "Transkrip Nilai" => "transkrip_nilai"
    ];

    foreach ($jenis_berkas as $label => $field) {
        if (!empty($_FILES[$field]['name'])) {

            $filename = time() . "_" . basename($_FILES[$field]['name']);
            $target = "../uploads/" . $filename;

            if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {

                $stmtUp = $koneksi->prepare("
                    INSERT INTO berkas_pendaftaran (id_pendaftaran, jenis_berkas, nama_file, path_file, status_verifikasi)
                    VALUES (?, ?, ?, ?, 'valid')
                ");
                $stmtUp->bind_param("isss", $id_pendaftaran, $label, $filename, $target);
                $stmtUp->execute();
            }
        }
    }

    // Setelah upload selesai → kembali ke menu pilih beasiswa
    $alert = '<div class="alert alert-success">Upload berkas berhasil. Anda dapat mengajukan beasiswa lain.</div>';
    $show_upload_form = false;
    $id_pendaftaran_aktif = 0;
}

/* ============================================
   3. Output UI
============================================ */
ob_start();
?>

<h4 class="mb-3">Ajukan Beasiswa</h4>
<?= $alert ?>

<?php if (!$show_upload_form): ?>
    <!-- ======================================
         FORM PILIH BEASISWA
    ======================================= -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <p class="text-muted">Silakan pilih jenis beasiswa yang ingin Anda ajukan.</p>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Jenis Beasiswa</label>
                    <select name="id_beasiswa" class="form-select" required>
                        <option value="">-- Pilih Beasiswa --</option>

                        <?php if (empty($beasiswa)): ?>
                            <option disabled>Tidak ada beasiswa yang tersedia</option>
                        <?php else: ?>
                            <?php foreach ($beasiswa as $b): ?>
                                <option value="<?= $b['id_beasiswa']; ?>">
                                    <?= htmlspecialchars($b['nama_beasiswa']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" name="ajukan_beasiswa" class="btn btn-primary">
                    <i class="bi bi-send"></i> Ajukan Beasiswa
                </button>
            </form>
        </div>
    </div>

<?php endif; ?>

<?php if ($show_upload_form && $id_pendaftaran_aktif > 0): ?>
    <!-- ======================================
         FORM UPLOAD BERKAS
    ======================================= -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5>Upload Dokumen Persyaratan</h5>
            <p class="text-muted">Silakan upload dokumen dalam format PDF.</p>

            <form method="POST" enctype="multipart/form-data">

                <input type="hidden" name="id_pendaftaran" value="<?= $id_pendaftaran_aktif ?>">

                <div class="mb-3">
                    <label class="form-label">KTP (PDF)</label>
                    <input type="file" name="ktp" class="form-control" accept="application/pdf" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kartu Keluarga (PDF)</label>
                    <input type="file" name="kartu_keluarga" class="form-control" accept="application/pdf" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Transkrip Nilai / IPK (PDF)</label>
                    <input type="file" name="transkrip_nilai" class="form-control" accept="application/pdf" required>
                </div>

                <button type="submit" name="upload_berkas" class="btn btn-success">
                    <i class="bi bi-upload"></i> Upload Berkas
                </button>

            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include "layout.php";
