<?php
if (!isset($page_title))
    $page_title = "Reviewer Panel";
if (!isset($content))
    $content = "";
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $page_title ?> | Sistem Beasiswa</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f6f9fc;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        #sidebar {
            width: 230px;
            background-color: #0d6efd;
            color: #fff;
            padding: 1rem;
            transition: margin-left .3s ease;
        }

        #sidebar.collapsed {
            margin-left: -230px;
        }

        #sidebar .nav-link {
            color: #ffffff;
            border-radius: .375rem;
            margin-bottom: .25rem;
        }

        #sidebar .nav-link.active,
        #sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        #main-content {
            flex: 1;
            padding: 1.5rem;
        }

        @media(max-width:768px) {
            #sidebar {
                position: fixed;
                top: 56px;
                left: 0;
                height: calc(100vh - 56px);
                z-index: 1030;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a class="navbar-brand ms-3" href="#">Sistem Beasiswa</a>

            <span class="text-white ms-auto">
                Halo, <?= $_SESSION['nama'] ?> (Reviewer)
            </span>
        </div>
    </nav>

    <div class="wrapper">

        <!-- SIDEBAR -->
        <div id="sidebar">
            <h6 class="text-uppercase fw-bold mb-3">Menu Reviewer</h6>
            <ul class="nav nav-pills flex-column mb-auto">

                <li class="nav-item">
                    <a href="dashboard.php"
                        class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>

                <li>
                    <a href="pendaftaran.php"
                        class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pendaftaran.php' ? 'active' : '' ?>">
                        <i class="bi bi-journal-text me-2"></i> Data Pendaftaran
                    </a>
                </li>

                <li>
                    <a href="penilaian.php"
                        class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'penilaian.php' ? 'active' : '' ?>">
                        <i class="bi bi-clipboard-check me-2"></i> Penilaian
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
            <?= $content ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle').onclick = () =>
            document.getElementById('sidebar').classList.toggle('collapsed');
    </script>

</body>

</html>