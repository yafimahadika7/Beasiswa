<?php
// admin/users.php
session_start();
require_once '../config/koneksi.php';

// ====== CEK LOGIN & ROLE ======
if (empty($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$nama_session = $_SESSION['nama'] ?? '';
$role_session = $_SESSION['role'] ?? '';

// ====== CSRF TOKEN SEDERHANA ======
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ====== FLASH MESSAGE HELPER ======
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

// ====== HANDLE POST (TAMBAH / UPDATE) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        set_flash('Token keamanan tidak valid. Silakan muat ulang halaman.', 'danger');
        header('Location: users.php');
        exit;
    }

    // ---- TAMBAH USER ----
    if (isset($_POST['tambah'])) {
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password_raw = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $allowedRoles = ['admin', 'reviewer'];
        if (!in_array($role, $allowedRoles, true)) {
            set_flash('Level user tidak valid.', 'danger');
            header('Location: users.php');
            exit;
        }

        if ($nama_lengkap === '' || $username === '' || $password_raw === '' || $role === '') {
            set_flash('Nama, Username, Password, dan Level wajib diisi.', 'danger');
            header('Location: users.php');
            exit;
        }

        // Cek username unik
        $stmtCek = $koneksi->prepare('SELECT id_user FROM users WHERE username = ? LIMIT 1');
        if (!$stmtCek) {
            set_flash('Gagal cek username: ' . $koneksi->error, 'danger');
            header('Location: users.php');
            exit;
        }
        $stmtCek->bind_param('s', $username);
        $stmtCek->execute();
        $resCek = $stmtCek->get_result();
        if ($resCek && $resCek->num_rows > 0) {
            set_flash('Username sudah digunakan, silakan pilih yang lain.', 'warning');
            $stmtCek->close();
            header('Location: users.php');
            exit;
        }
        $stmtCek->close();

        $hash = password_hash($password_raw, PASSWORD_DEFAULT);

        $stmtIns = $koneksi->prepare(
            'INSERT INTO users (nama_lengkap, username, password, role) VALUES (?, ?, ?, ?)'
        );
        if (!$stmtIns) {
            set_flash('Gagal menyiapkan query tambah user: ' . $koneksi->error, 'danger');
            header('Location: users.php');
            exit;
        }
        $stmtIns->bind_param('ssss', $nama_lengkap, $username, $hash, $role);

        if ($stmtIns->execute()) {
            set_flash('User baru berhasil ditambahkan.', 'success');
        } else {
            set_flash('Gagal menambah user: ' . $stmtIns->error, 'danger');
        }
        $stmtIns->close();

        header('Location: users.php');
        exit;
    }

    // ---- UPDATE USER ----
    if (isset($_POST['update'])) {
        $id_user = (int) ($_POST['id_user'] ?? 0);
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $allowedRoles = ['admin', 'reviewer'];
        if (!in_array($role, $allowedRoles, true)) {
            set_flash('Level user tidak valid.', 'danger');
            header('Location: users.php');
            exit;
        }
        $password_raw = trim($_POST['password'] ?? ''); // boleh kosong

        if ($id_user <= 0 || $nama_lengkap === '' || $username === '' || $role === '') {
            set_flash('Data user tidak lengkap.', 'danger');
            header('Location: users.php');
            exit;
        }

        // Cek username bentrok dengan user lain
        $stmtCek = $koneksi->prepare(
            'SELECT id_user FROM users WHERE username = ? AND id_user <> ? LIMIT 1'
        );
        if (!$stmtCek) {
            set_flash('Gagal cek username: ' . $koneksi->error, 'danger');
            header('Location: users.php');
            exit;
        }
        $stmtCek->bind_param('si', $username, $id_user);
        $stmtCek->execute();
        $resCek = $stmtCek->get_result();
        if ($resCek && $resCek->num_rows > 0) {
            set_flash('Username sudah dipakai user lain.', 'warning');
            $stmtCek->close();
            header('Location: users.php');
            exit;
        }
        $stmtCek->close();

        if ($password_raw !== '') {
            $hash = password_hash($password_raw, PASSWORD_DEFAULT);
            $stmtUpd = $koneksi->prepare(
                'UPDATE users SET nama_lengkap = ?, username = ?, role = ?, password = ? WHERE id_user = ?'
            );
            if (!$stmtUpd) {
                set_flash('Gagal menyiapkan query update user: ' . $koneksi->error, 'danger');
                header('Location: users.php');
                exit;
            }
            $stmtUpd->bind_param('ssssi', $nama_lengkap, $username, $role, $hash, $id_user);
        } else {
            $stmtUpd = $koneksi->prepare(
                'UPDATE users SET nama_lengkap = ?, username = ?, role = ? WHERE id_user = ?'
            );
            if (!$stmtUpd) {
                set_flash('Gagal menyiapkan query update user: ' . $koneksi->error, 'danger');
                header('Location: users.php');
                exit;
            }
            $stmtUpd->bind_param('sssi', $nama_lengkap, $username, $role, $id_user);
        }

        if ($stmtUpd->execute()) {
            set_flash('Data user berhasil diubah.', 'success');
        } else {
            set_flash('Gagal mengubah data user: ' . $stmtUpd->error, 'danger');
        }
        $stmtUpd->close();

        header('Location: users.php');
        exit;
    }
}

// ====== HANDLE GET HAPUS ======
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    if ($id <= 0) {
        set_flash('ID user tidak valid.', 'danger');
        header('Location: users.php');
        exit;
    }

    // Opsional: cegah hapus akun sendiri
    if (!empty($_SESSION['id_user']) && (int) $_SESSION['id_user'] === $id) {
        set_flash('Anda tidak dapat menghapus akun yang sedang digunakan.', 'warning');
        header('Location: users.php');
        exit;
    }

    $stmtDel = $koneksi->prepare('DELETE FROM users WHERE id_user = ?');
    if (!$stmtDel) {
        set_flash('Gagal menyiapkan query hapus: ' . $koneksi->error, 'danger');
        header('Location: users.php');
        exit;
    }
    $stmtDel->bind_param('i', $id);

    if ($stmtDel->execute()) {
        set_flash('User berhasil dihapus.', 'success');
    } else {
        set_flash('Gagal menghapus user: ' . $stmtDel->error, 'danger');
    }
    $stmtDel->close();

    header('Location: users.php');
    exit;
}

// ====== AMBIL DATA USER + SEARCH ======
$keyword = trim($_GET['q'] ?? '');
$dataUser = [];

if ($keyword !== '') {
    $sql = 'SELECT id_user, nama_lengkap, username, role
         FROM users
         WHERE (nama_lengkap LIKE ?
            OR username LIKE ?
            OR role LIKE ?)
           AND role IN ("admin", "reviewer")
         ORDER BY id_user ASC';
    $like = "%{$keyword}%";
    $stmt = $koneksi->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $dataUser[] = $row;
        }
        $stmt->close();
    } else {
        set_flash('Gagal mengambil data user: ' . $koneksi->error, 'danger');
    }
} else {
    $res = $koneksi->query(
        'SELECT id_user, nama_lengkap, username, role
                FROM users
                WHERE role IN ("admin", "reviewer")
                ORDER BY id_user ASC'
    );
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $dataUser[] = $row;
        }
        $res->free();
    } else {
        set_flash('Gagal mengambil data user: ' . $koneksi->error, 'danger');
    }
}

$flash = get_flash();
$pageTitle = 'Manajemen User';

// ====== MULAI KONTEN (untuk dimasukkan ke layout) ======
ob_start();
?>
<div class="card shadow-sm">
    <div class="card-body">

        <h3 class="mb-3">Manajemen User</h3>

        <?php if ($flash): ?>
            <div
                class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                <?= htmlspecialchars($flash['msg'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- BARIS TOMBOL + SEARCH -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3 gap-2">
            <!-- Tombol Tambah -->
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-circle me-1"></i> Tambah User
            </button>

            <!-- Search -->
            <form method="get" action="users.php" class="d-flex">
                <div class="input-group" style="width: 320px;">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control" placeholder="Cari Nama / Username / Level"
                        value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($keyword !== ''): ?>
                        <a href="users.php" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- TABEL USER -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th style="width:5%;">No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th style="width:15%;">Level</th>
                        <th style="width:20%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($dataUser) === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <?php if ($keyword !== ''): ?>
                                    Tidak ada user yang cocok dengan kata kunci
                                    "<strong><?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?></strong>".
                                <?php else: ?>
                                    Belum ada data user.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($dataUser as $u): ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center">
                                    <!-- Tombol Edit -->
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#modalEdit<?= (int) $u['id_user'] ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <button type="button" class="btn btn-danger btn-sm btn-delete"
                                        data-href="users.php?hapus=<?= (int) $u['id_user'] ?>"
                                        data-username="<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- MODAL EDIT USER -->
                            <div class="modal fade" id="modalEdit<?= (int) $u['id_user'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning">
                                            <h5 class="modal-title">Edit User</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" class="needs-validation" novalidate>
                                            <div class="modal-body">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id_user" value="<?= (int) $u['id_user'] ?>">

                                                <div class="mb-3">
                                                    <label class="form-label">Nama Lengkap</label>
                                                    <input type="text" name="nama_lengkap" class="form-control"
                                                        value="<?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>"
                                                        required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Username</label>
                                                    <input type="text" name="username" class="form-control"
                                                        value="<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>"
                                                        required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Level</label>
                                                    <select name="role" class="form-select" required>
                                                        <?php
                                                        $roles = ['admin', 'reviewer'];
                                                        foreach ($roles as $r):
                                                            ?>
                                                            <option value="<?= $r ?>" <?= ($u['role'] === $r ? 'selected' : '') ?>>
                                                                <?= ucfirst($r) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Password Baru (opsional)</label>
                                                    <input type="password" name="password" class="form-control"
                                                        placeholder="Isi jika ingin mengganti password">
                                                    <small class="text-muted">
                                                        Kosongkan jika tidak ingin mengubah password.
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="update" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- MODAL TAMBAH USER -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Tambah User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Level</label>
                        <select name="role" class="form-select" required>
                            <option value="">Pilih Level</option>
                            <option value="admin">Admin</option>
                            <option value="reviewer">Reviewer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="tambah" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS -->
<div class="modal fade" id="modalHapus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus user:
                <strong id="hapusUsername"></strong> ?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" class="btn btn-danger" id="btnHapusConfirm">Ya, Hapus</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Validasi form Bootstrap
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })();

    // Konfirmasi Hapus dengan Modal
    const modalHapus = document.getElementById('modalHapus');
    const hapusUsername = document.getElementById('hapusUsername');
    const btnHapusConfirm = document.getElementById('btnHapusConfirm');

    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const username = this.getAttribute('data-username');
            const href = this.getAttribute('data-href');

            hapusUsername.textContent = username;
            btnHapusConfirm.setAttribute('href', href);

            const bsModal = new bootstrap.Modal(modalHapus);
            bsModal.show();
        });
    });
</script>

<?php
// akhir konten
$content = ob_get_clean();

// panggil layout utama admin
require 'layout.php';