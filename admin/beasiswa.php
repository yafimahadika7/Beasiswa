<?php
session_start();
require_once '../config/koneksi.php';

if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Master Beasiswa";


// ===========================================================
// ===================== PROSES CRUD =========================
// ===========================================================

// ========== TAMBAH DATA BEASISWA ==========
if (isset($_POST['tambah'])) {

    // Normalisasi dan casting angka
    $kuota = (int) ($_POST['kuota'] ?? 0);
    $ipk_minimal = (float) str_replace(',', '.', $_POST['ipk_minimal'] ?? 0);
    $semester_minimal = (int) ($_POST['semester_minimal'] ?? 0);

    $sql = "INSERT INTO beasiswa 
        (nama_beasiswa, penyelenggara, jenis, deskripsi, 
         tgl_mulai_daftar, tgl_selesai_daftar, kuota,
         ipk_minimal, semester_minimal, status)
        VALUES (?,?,?,?,?,?,?,?,?,?)";

    // s = string, i = integer, d = double
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param(
        "ssssssidis",                       // <<< DIBETULKAN (10 huruf)
        $_POST['nama_beasiswa'],
        $_POST['penyelenggara'],
        $_POST['jenis'],
        $_POST['deskripsi'],
        $_POST['tgl_mulai_daftar'],
        $_POST['tgl_selesai_daftar'],
        $kuota,
        $ipk_minimal,
        $semester_minimal,
        $_POST['status']
    );
    $stmt->execute();
    $stmt->close();

    header("Location: beasiswa.php");
    exit;
}


// ========== EDIT / UPDATE DATA BEASISWA ==========
if (isset($_POST['edit'])) {

    // Normalisasi dan casting angka
    $kuota = (int) ($_POST['kuota'] ?? 0);
    $ipk_minimal = (float) str_replace(',', '.', $_POST['ipk_minimal'] ?? 0);
    $semester_minimal = (int) ($_POST['semester_minimal'] ?? 0);
    $id_beasiswa = (int) ($_POST['id_beasiswa'] ?? 0);

    $sql = "UPDATE beasiswa SET 
                nama_beasiswa = ?, penyelenggara = ?, jenis = ?, deskripsi = ?,
                tgl_mulai_daftar = ?, tgl_selesai_daftar = ?, kuota = ?,
                ipk_minimal = ?, semester_minimal = ?, status = ?
            WHERE id_beasiswa = ?";

    // s = string, i = integer, d = double
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param(
        "ssssssidisi",                       // <<< DIBETULKAN (11 huruf)
        $_POST['nama_beasiswa'],
        $_POST['penyelenggara'],
        $_POST['jenis'],
        $_POST['deskripsi'],
        $_POST['tgl_mulai_daftar'],
        $_POST['tgl_selesai_daftar'],
        $kuota,
        $ipk_minimal,
        $semester_minimal,
        $_POST['status'],
        $id_beasiswa
    );
    $stmt->execute();
    $stmt->close();

    header("Location: beasiswa.php");
    exit;
}


// ========== HAPUS DATA ==========
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $koneksi->query("DELETE FROM beasiswa WHERE id_beasiswa = $id");
    header("Location: beasiswa.php");
    exit;
}



// ===========================================================
// ===================== LOAD DATA ===========================
// ===========================================================
$dataBeasiswa = [];
$sql = "SELECT * FROM beasiswa ORDER BY id_beasiswa DESC";
$res = $koneksi->query($sql);
while ($row = $res->fetch_assoc()) {
    $dataBeasiswa[] = $row;
}



// ===========================================================
// ===================== TAMPILAN ============================
// ===========================================================
ob_start();
?>

<div class="card shadow-sm">
    <div class="card-body">

        <h3 class="mb-3">Master Beasiswa</h3>

        <!-- TOMBOL TAMBAH -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-circle"></i> Tambah Beasiswa
        </button>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th style="width:5%;">No</th>
                        <th>Nama Beasiswa</th>
                        <th>Penyelenggara</th>
                        <th>Jenis</th>
                        <th>IPK Min</th>
                        <th>Semester Min</th>
                        <th>Kuota</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th style="width:12%;">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($dataBeasiswa) === 0): ?>
                        <tr>
                            <td colspan="10" class="text-center">Belum ada data beasiswa.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($dataBeasiswa as $b): ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= htmlspecialchars($b['nama_beasiswa']); ?></td>
                                <td><?= htmlspecialchars($b['penyelenggara']); ?></td>
                                <td><?= ucfirst($b['jenis']); ?></td>
                                <td class="text-center"><?= $b['ipk_minimal']; ?></td>
                                <td class="text-center"><?= $b['semester_minimal']; ?></td>
                                <td class="text-center"><?= $b['kuota']; ?></td>
                                <td class="text-center">
                                    <?= $b['tgl_mulai_daftar']; ?> <br> s/d <br> <?= $b['tgl_selesai_daftar']; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $b['status'] === 'aktif' ? 'success' : 'secondary'; ?>">
                                        <?= $b['status']; ?>
                                    </span>
                                </td>

                                <td class="text-center">
                                    <!-- Edit -->
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit"
                                        onclick='editData(<?= json_encode($b); ?>)'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <!-- Hapus -->
                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalHapus"
                                        onclick="setHapusBeasiswa(<?= $b['id_beasiswa']; ?>, '<?= htmlspecialchars($b['nama_beasiswa'], ENT_QUOTES); ?>')">
                                        <i class="bi bi-trash3"></i>
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



<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Beasiswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="tambah" value="1">

                    <div class="mb-3">
                        <label>Nama Beasiswa</label>
                        <input type="text" name="nama_beasiswa" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Penyelenggara</label>
                        <input type="text" name="penyelenggara" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Jenis</label>
                        <select name="jenis" class="form-control">
                            <option value="internal">Internal</option>
                            <option value="eksternal">Eksternal</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai_daftar" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai_daftar" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Kuota</label>
                            <input type="number" name="kuota" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>IPK Minimal</label>
                            <input type="text" name="ipk_minimal" class="form-control" placeholder="Contoh: 3.00"
                                required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Semester Minimal</label>
                            <input type="number" name="semester_minimal" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL HAPUS -->
<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p>Yakin ingin menghapus data beasiswa berikut?</p>
                <strong id="hapus_nama_beasiswa"></strong>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="btnHapusFix" class="btn btn-danger">Hapus</a>
            </div>

        </div>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Beasiswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="edit" value="1">
                    <input type="hidden" name="id_beasiswa" id="edit_id_beasiswa">

                    <div class="mb-3">
                        <label>Nama Beasiswa</label>
                        <input type="text" name="nama_beasiswa" id="edit_nama_beasiswa" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Penyelenggara</label>
                        <input type="text" name="penyelenggara" id="edit_penyelenggara" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Jenis</label>
                        <select name="jenis" id="edit_jenis" class="form-control">
                            <option value="internal">Internal</option>
                            <option value="eksternal">Eksternal</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai_daftar" id="edit_mulai" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai_daftar" id="edit_selesai" class="form-control"
                                required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Kuota</label>
                            <input type="number" name="kuota" id="edit_kuota" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>IPK Minimal</label>
                            <input type="text" name="ipk_minimal" id="edit_ipk" class="form-control"
                                placeholder="Contoh: 3.00" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Semester Minimal</label>
                            <input type="number" name="semester_minimal" id="edit_semester" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary">Update Data</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    function editData(data) {
        document.getElementById("edit_id_beasiswa").value = data.id_beasiswa;
        document.getElementById("edit_nama_beasiswa").value = data.nama_beasiswa;
        document.getElementById("edit_penyelenggara").value = data.penyelenggara;
        document.getElementById("edit_jenis").value = data.jenis;
        document.getElementById("edit_deskripsi").value = data.deskripsi;
        document.getElementById("edit_mulai").value = data.tgl_mulai_daftar;
        document.getElementById("edit_selesai").value = data.tgl_selesai_daftar;
        document.getElementById("edit_kuota").value = data.kuota;

        let ipk = data.ipk_minimal.toString().replace(",", ".");
        document.getElementById("edit_ipk").value = ipk;

        document.getElementById("edit_semester").value = data.semester_minimal;
        document.getElementById("edit_status").value = data.status;
    }

    function setHapusBeasiswa(id, nama) {
        document.getElementById("hapus_nama_beasiswa").innerText = nama;
        document.getElementById("btnHapusFix").href = "beasiswa.php?hapus=" + id;
    }
</script>

<?php
$content = ob_get_clean();
require 'layout.php';
