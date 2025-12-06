<?php
session_start();
require_once "../config/koneksi.php";

/* ======================================
   CEK ROLE MAHASISWA
====================================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Status Pengajuan";

/* ======================================
   AMBIL NPM DARI SESSION
   (PHP 7.4 TIDAK SUKA CHAINING COMPLEX ??)
====================================== */
$npm = "";
if (isset($_SESSION['npm'])) {
    $npm = $_SESSION['npm'];
} elseif (isset($_SESSION['username'])) {
    $npm = $_SESSION['username'];
}

if ($npm === "") {
    die("Sesi mahasiswa tidak ditemukan.");
}

/* ======================================
   HANDLE EDIT BERKAS
====================================== */
if (isset($_POST['edit_berkas'])) {

    $id_pendaftaran = $_POST['id_pendaftaran'];

    $jenis_list = array(
        "ktp" => "KTP",
        "kk" => "Kartu Keluarga",
        "transkrip" => "Transkrip Nilai"
    );

    foreach ($jenis_list as $field => $label) {

        if (!empty($_FILES[$field]['name'])) {

            $filename = time() . "_" . basename($_FILES[$field]['name']);
            $target = "../uploads/" . $filename;

            if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {

                $sqlUpdate = "
                    UPDATE berkas_pendaftaran
                    SET nama_file='$filename', path_file='$target'
                    WHERE id_pendaftaran='$id_pendaftaran'
                      AND jenis_berkas='$label'
                ";

                $koneksi->query($sqlUpdate);
            }
        }
    }

    $_SESSION['notif'] = "Berkas berhasil diperbarui.";
    header("Location: status.php");
    exit;
}

/* ======================================
   AMBIL DATA PENGAJUAN
====================================== */
$sql = "
    SELECT 
        p.id_pendaftaran,
        b.nama_beasiswa,
        p.jenis_beasiswa,
        p.tahun,
        p.status_pendaftaran,
        p.tgl_daftar
    FROM pendaftaran p
    JOIN beasiswa b ON p.id_beasiswa = b.id_beasiswa
    WHERE p.npm = '$npm'
    ORDER BY p.id_pendaftaran DESC
";

$data = $koneksi->query($sql);

$modal_container = "";

ob_start();
?>

<style>
    .modal {
        z-index: 99999 !important;
    }

    .modal-backdrop {
        z-index: 99998 !important;
    }
</style>

<h4 class="mb-3">Status Pengajuan Beasiswa</h4>

<?php if (isset($_SESSION['notif'])): ?>
    <div class="alert alert-success"><?= $_SESSION['notif']; ?></div>
    <?php unset($_SESSION['notif']); ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">

        <table class="table table-bordered table-hover">
            <thead class="table-primary text-center">
                <tr>
                    <th>No</th>
                    <th>Nama Beasiswa</th>
                    <th>Jenis</th>
                    <th>Tahun</th>
                    <th>Tgl Daftar</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

                <?php
                $no = 1;

                while ($p = $data->fetch_assoc()):
                    $idp = $p['id_pendaftaran'];

                    // Ambil berkas
                    $berkas_q = "
                        SELECT jenis_berkas, nama_file, path_file, status_verifikasi
                        FROM berkas_pendaftaran
                        WHERE id_pendaftaran='$idp'
                    ";
                    $berkas_data = $koneksi->query($berkas_q);

                    // Tentukan badge status (PHP 7.4 COMPAT)
                    $status = $p['status_pendaftaran'];
                    $badge = "bg-secondary";

                    if ($status === "Lolos Verifikasi") {
                        $badge = "bg-success";
                    } elseif ($status === "Ditolak") {
                        $badge = "bg-danger";
                    } elseif ($status === "Menunggu Verifikasi") {
                        $badge = "bg-warning text-dark";
                    }
                    ?>

                    <tr class="align-middle">
                        <td class="text-center"><?= $no++; ?></td>
                        <td><?= htmlspecialchars($p['nama_beasiswa']); ?></td>
                        <td class="text-center"><?= htmlspecialchars($p['jenis_beasiswa']); ?></td>
                        <td class="text-center"><?= htmlspecialchars($p['tahun']); ?></td>
                        <td class="text-center"><?= date('d-m-Y H:i', strtotime($p['tgl_daftar'])); ?></td>

                        <td class="text-center">
                            <span class="badge <?= $badge ?>"><?= $status ?></span>
                        </td>


                        <td class="text-center">
                            
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detail<?= $idp ?>">
                                <i class="bi bi-eye"></i>
                            </button>


                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#edit<?= $idp ?>">
                                <i class="bi bi-pencil-square"></i>
                            </button>

                        </td>
                    </tr>

                    <?php
                    /* ==========================================
                       GENERATE MODAL DETAIL + EDIT BERKAS
                    ===========================================*/
                    $modal_html = "

<!-- Modal Detail -->
<div class='modal fade' id='detail$idp' tabindex='-1'>
<div class='modal-dialog modal-lg modal-dialog-centered'>
<div class='modal-content'>

<div class='modal-header'>
  <h5 class='modal-title'><i class=\"bi bi-eye\"></i> Detail Pengajuan</h5>
  <button class='btn-close' data-bs-dismiss='modal'></button>
</div>

<div class='modal-body'>
<table class='table table-bordered'>
<tr><th>Nama Beasiswa</th><td>{$p['nama_beasiswa']}</td></tr>
<tr><th>Jenis</th><td>{$p['jenis_beasiswa']}</td></tr>
<tr><th>Tahun</th><td>{$p['tahun']}</td></tr>
<tr><th>Status</th><td>{$p['status_pendaftaran']}</td></tr>
<tr><th>Tgl Daftar</th><td>" . date('d-m-Y H:i', strtotime($p['tgl_daftar'])) . "</td></tr>
</table>

<h6>Berkas Upload:</h6>
<ul class='list-group'>";

                    while ($b = $berkas_data->fetch_assoc()) {
                        $modal_html .= "
<li class='list-group-item d-flex justify-content-between align-items-center'>
    <span><strong>{$b['jenis_berkas']}</strong> <small>({$b['status_verifikasi']})</small></span>
    <a href='{$b['path_file']}' target='_blank' class='btn btn-sm btn-secondary'>
        <i class='bi bi-download'></i>
    </a>
</li>";
                    }

                    $modal_html .= "
</ul>
</div>

<div class='modal-footer'>
<button class='btn btn-secondary' data-bs-dismiss='modal'>Tutup</button>
</div>

</div>
</div>
</div>

<!-- Modal Edit -->
<div class='modal fade' id='edit$idp' tabindex='-1'>
<div class='modal-dialog modal-lg modal-dialog-centered'>
<div class='modal-content'>

<div class='modal-header'>
<h5 class='modal-title'><i class=\"bi bi-pencil-square\"></i> Edit Berkas</h5>
<button class='btn-close' data-bs-dismiss='modal'></button>
</div>

<form method='POST' enctype='multipart/form-data'>
<input type='hidden' name='id_pendaftaran' value='$idp'>

<div class='modal-body'>
<label>KTP (PDF)</label>
<input type='file' name='ktp' class='form-control mb-3'>

<label>Kartu Keluarga (PDF)</label>
<input type='file' name='kk' class='form-control mb-3'>

<label>Transkrip Nilai / IPK (PDF)</label>
<input type='file' name='transkrip' class='form-control mb-3'>
</div>

<div class='modal-footer'>
<button class='btn btn-secondary' data-bs-dismiss='modal'>Batal</button>
<button type='submit' name='edit_berkas' class='btn btn-primary'>Simpan</button>
</div>

</form>
</div>
</div>
</div>

";

                    $modal_container .= $modal_html;

                endwhile;
                ?>
            </tbody>
        </table>
    </div>
</div>

<?= $modal_container; ?>

<?php
$content = ob_get_clean();
include "layout.php";
?>