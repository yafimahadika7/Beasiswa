<?php
session_start();
require_once 'config/koneksi.php';

$error = "";

if (isset($_POST['login'])) {

    // Ambil username & password dari form
    $username = trim(mysqli_real_escape_string($koneksi, $_POST['username']));
    $password = $_POST['password'];

    // Cari user berdasarkan username
    $sql = "SELECT * FROM users WHERE username='$username' LIMIT 1";
    $qry = mysqli_query($koneksi, $sql);
    $data = mysqli_fetch_assoc($qry);

    if ($data) {
        // Cek password hash
        if (password_verify($password, $data['password'])) {

            $_SESSION['id_user'] = $data['id_user'];
            $_SESSION['nama'] = $data['nama_lengkap'];
            $_SESSION['role'] = $data['role'];

            if ($data['role'] === 'mahasiswa') {
                $_SESSION['npm'] = $data['username'];
            }
            
            // Redirect sesuai role
            if ($data['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($data['role'] === 'mahasiswa') {
                header("Location: mahasiswa/dashboard.php");
            } elseif ($data['role'] === 'reviewer') {
                header("Location: reviewer/dashboard.php");
            } else {
                header("Location: dashboard.php"); // fallback
            }
            exit;

        } else {
            $error = "Username atau password salah.";
        }
    } else {
        $error = "Username tidak terdaftar.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login | Sistem Pendaftaran Beasiswa</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #1d5dff;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: url("assets/background.jpg") no-repeat center center/cover;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Poppins", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        /* Overlay biru */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 70, 255, 0.65);
            backdrop-filter: brightness(0.8);
        }

        .container {
            position: relative;
            z-index: 2;
        }

        /* Card Login */
        .card-login {
            position: relative;
            z-index: 2;
            border-radius: 20px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            opacity: 0;
            transform: translateY(24px);
            transition: all 0.6s ease;
        }

        .card-login.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Left Panel */
        .card-login .left {
            background: linear-gradient(135deg, rgba(17, 76, 200, 0.8), rgba(2, 32, 110, 0.8));
            backdrop-filter: blur(8px);
            color: #fff;
            text-align: center;
        }

        .logo-unpam {
            max-width: 110px;
            border-radius: 50%;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
        }

        /* Buttons */
        .btn {
            border-radius: 999px;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 10px 24px rgba(29, 93, 255, 0.35);
        }

        .btn-primary:hover {
            background-color: #1544c0;
            border-color: #1544c0;
            box-shadow: 0 12px 30px rgba(21, 68, 192, 0.45);
        }

        /* Text */
        .small-text {
            font-size: 0.84rem;
            opacity: 0.9;
        }

        /* Floating Label Group */
        .floating-group {
            margin-bottom: 1rem;
        }

        .floating-wrapper {
            position: relative;
        }

        .floating-input {
            padding-top: 18px;
            padding-bottom: 8px;
            border-radius: 999px;
        }

        .floating-label {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.85rem;
            color: #9e9e9e;
            pointer-events: none;
            transition: all 0.18s ease;
            z-index: 3;
        }

        /* Floating effect (CSS-Only) */
        .floating-wrapper .floating-input:focus~.floating-label,
        .floating-wrapper .floating-input:not(:placeholder-shown)~.floating-label {
            top: 6px;
            transform: translateY(0);
            font-size: 0.7rem;
            letter-spacing: 0.02em;
            color: #1d5dff;
        }

        /* Password toggle button */
        .input-group-text {
            border-radius: 0 999px 999px 0;
            cursor: pointer;
        }

        /* Glow menyatu antara input + tombol Lihat */
        .input-group .form-control:focus {
            box-shadow: none;
        }

        .input-group:focus-within {
            border-radius: 999px;
            box-shadow: 0 0 0 0.25rem rgba(29, 93, 255, 0.35);
        }

        .input-group:focus-within .form-control,
        .input-group:focus-within .input-group-text {
            border-color: #86b7fe !important;
        }

        /* Responsive */
        @media (max-width: 767.98px) {
            .card-login {
                border-radius: 16px;
            }

            .left {
                padding-bottom: 1.5rem !important;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center px-2">
            <div class="col-md-9 col-lg-7">
                <div class="card card-login">
                    <div class="row g-0">
                        <!-- LEFT SIDE -->
                        <div class="col-md-5 p-4 d-flex flex-column justify-content-center left">
                            <div class="mb-3">
                                <img src="assets/logo-unpam.png" alt="Logo Universitas Pamulang"
                                    class="img-fluid logo-unpam mb-3">
                                <h5 class="fw-semibold mb-2">Sistem Beasiswa</h5>
                                <p class="small-text mb-0">
                                    Sistem informasi pendaftaran beasiswa<br>
                                    Universitas Terbuka.
                                </p>
                            </div>
                        </div>

                        <!-- RIGHT SIDE -->
                        <div class="col-md-7 p-4 p-md-5 bg-white">
                            <h5 class="fw-semibold mb-3 text-center">LOGIN</h5>

                            <?php if ($error): ?>
                                <div class="alert alert-danger py-2 mb-3" id="alertBox"><?= $error; ?></div>
                            <?php endif; ?>

                            <form method="post" autocomplete="off">

                                <!-- USERNAME -->
                                <div class="floating-group">
                                    <div class="floating-wrapper">
                                        <input type="text" name="username" id="username"
                                            class="form-control floating-input" placeholder=" " required>
                                        <label for="username" class="floating-label">Username</label>
                                    </div>
                                </div>

                                <!-- PASSWORD -->
                                <div class="floating-group">
                                    <div class="input-group floating-wrapper">
                                        <input type="password" name="password" id="password"
                                            class="form-control floating-input" placeholder=" " required>
                                        <span class="input-group-text" id="togglePassword">Lihat</span>
                                        <label for="password" class="floating-label">Password</label>
                                    </div>
                                </div>

                                <button type="submit" name="login" class="btn btn-primary w-100 mb-2">
                                    Masuk
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const card = document.querySelector('.card-login');
            if (card) {
                setTimeout(() => card.classList.add('show'), 100);
            }

            const alertBox = document.getElementById('alertBox');
            if (alertBox) {
                setTimeout(() => {
                    alertBox.style.opacity = '0';
                    alertBox.style.transition = 'opacity .4s';
                    setTimeout(() => alertBox.remove(), 400);
                }, 3000);
            }

            // Toggle show/hide password
            const toggle = document.getElementById('togglePassword');
            const pwd = document.getElementById('password');

            if (toggle && pwd) {
                toggle.addEventListener('click', () => {
                    const type = pwd.getAttribute('type') === 'password' ? 'text' : 'password';
                    pwd.setAttribute('type', type);
                    toggle.textContent = (type === 'password') ? 'Lihat' : 'Tutup';
                });
            }
        });
    </script>
</body>

</html>