<?php
if (!isset($page_title)) $page_title = "Mahasiswa";
if (!isset($content)) $content = "";

// Cegah akses tanpa login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

// Koneksi
require_once "../config/koneksi.php";

$id_user = $_SESSION['id_user'];

// Hitung jumlah notifikasi unread
$notif_count_q = $koneksi->query("
    SELECT COUNT(*) AS jml
    FROM notifikasi
    WHERE id_user = '$id_user' AND is_read = 0
");
$notif_row = $notif_count_q->fetch_assoc();
$notif_count = $notif_row['jml'];

// Ambil daftar pesan
$notif_list_q = $koneksi->query("
    SELECT id, pesan, tgl
    FROM notifikasi
    WHERE id_user = '$id_user'
    ORDER BY id DESC
");

// Simpan ke array (lebih aman di PHP 7)
$notif_list = array();
while ($row = $notif_list_q->fetch_assoc()) {
    $notif_list[] = $row;
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $page_title; ?> | Sistem Beasiswa</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { background: #f6f9fc; }
        .wrapper { display: flex; min-height: 100vh; }

        /* Sidebar */
        #sidebar {
            width: 230px;
            background-color: #0d6efd;
            color: #fff;
            padding: 1rem;
            transition: margin-left .3s ease;
        }
        #sidebar.collapsed { margin-left: -230px; }
        #sidebar .nav-link { color: #ffffff; border-radius: .375rem; margin-bottom: .25rem; }
        #sidebar .nav-link.active,
        #sidebar .nav-link:hover { background-color: rgba(255,255,255,0.15); }

        #main-content { flex: 1; padding: 1.5rem; }

        @media(max-width:768px) {
            #sidebar {
                position: fixed;
                top: 56px; left: 0;
                height: calc(100vh - 56px);
                z-index: 1030;
            }
        }

        /* Notifikasi */
        .notif-icon { position: relative; cursor: pointer; }
        .notif-badge {
            position: absolute; top: -4px; right: -2px;
            background: #dc3545; color: white;
            font-size: 11px; padding: 2px 5px;
            border-radius: 50%; min-width: 18px; text-align: center;
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-primary">
    <div class="container-fluid">

        <button class="navbar-toggler" type="button" id="sidebarToggle">
            <span class="navbar-toggler-icon"></span>
        </button>

        <a class="navbar-brand ms-3" href="#">Sistem Beasiswa</a>

        <div class="d-flex align-items-center ms-auto">

            <!-- ICON NOTIFIKASI -->
            <div class="notif-icon me-3" data-bs-toggle="modal" data-bs-target="#modalNotifikasi">
                <i class="bi bi-bell-fill text-white fs-4"></i>

                <?php if ($notif_count > 0): ?>
                    <span class="notif-badge"><?php echo $notif_count; ?></span>
                <?php endif; ?>
            </div>

            <span class="text-white">
                Halo, <?php echo $_SESSION['nama']; ?> (Mahasiswa)
            </span>

        </div>

    </div>
</nav>

<div class="wrapper">

    <!-- SIDEBAR -->
    <div id="sidebar">
        <h6 class="text-uppercase fw-bold mb-3">Menu Mahasiswa</h6>

        <ul class="nav nav-pills flex-column mb-auto">

            <li class="nav-item">
                <a href="dashboard.php"
                   class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="bi bi-house me-2"></i> Dashboard
                </a>
            </li>

            <li>
                <a href="ajukan.php"
                   class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'ajukan.php') ? 'active' : ''; ?>">
                    <i class="bi bi-pencil-square me-2"></i> Ajukan Beasiswa
                </a>
            </li>

            <li>
                <a href="status.php"
                   class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'status.php') ? 'active' : ''; ?>">
                    <i class="bi bi-list-task me-2"></i> Status Pengajuan
                </a>
            </li>

            <li>
                <a href="pesan.php"
                   class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'pesan.php') ? 'active' : ''; ?>">
                    <i class="bi bi-envelope-paper-fill me-2"></i> Pesan
                </a>
            </li>

            <li>
                <a href="profil.php"
                   class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'profil.php') ? 'active' : ''; ?>">
                    <i class="bi bi-person-circle me-2"></i> Profil
                </a>
            </li>

        </ul>

        <hr>

        <a href="../logout.php" class="btn btn-danger w-100">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>

    <!-- MAIN CONTENT -->
    <div id="main-content">
        <?php echo $content; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle sidebar
document.getElementById('sidebarToggle').onclick = function () {
    document.getElementById('sidebar').classList.toggle('collapsed');
};

// Jika modal notifikasi dibuka → tandai dibaca
var modalNotif = document.getElementById("modalNotifikasi");
if (modalNotif) {
    modalNotif.addEventListener("shown.bs.modal", function () {
        fetch("read_notif.php").then(function() { location.reload(); });
    });
}
</script>

<!-- MODAL NOTIFIKASI -->
<div class="modal fade" id="modalNotifikasi" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header text-white" style="background:#7c4dff;">
                <h5 class="modal-title"><b>TOTAL PESAN</b></h5>
                <span class="badge bg-dark ms-2"><?php echo $notif_count; ?></span>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body" style="background:#9f7dff;">

                <?php if (count($notif_list) == 0): ?>
                    <p class="text-center text-white">Belum ada pesan.</p>

                <?php else: ?>
                    <?php foreach ($notif_list as $n): ?>
                        <div class="p-2 mb-2 rounded text-white" style="background:#8e63ff;">
                            <div class="fw-bold"><?php echo $n['pesan']; ?></div>
                            <div class="small text-light">
                                <?php echo date('d-m-Y H:i', strtotime($n['tgl'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer" style="background:#9f7dff;">
                <a href="pesan.php" class="btn btn-light w-100">Tampilkan Semua</a>
            </div>

        </div>
    </div>
</div>

</body>

</html>