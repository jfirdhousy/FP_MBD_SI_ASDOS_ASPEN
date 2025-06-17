<?php
// includes/header.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Pastikan BASE_URL sudah didefinisikan
if (!defined('BASE_URL')) {
    define('BASE_URL', '/FP_MBD_SI_ASDOS_ASPEN/public/'); // Sesuaikan dengan path proyek Anda
}

$is_logged_in = isset($_SESSION['user_id']);
$user_role = 'guest';
$user_name = '';

if ($is_logged_in) {
    $user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest';
    $user_name = htmlspecialchars($_SESSION['user_name']);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sistem Informasi Lowongan Asisten</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/id/thumb/0/01/Institut_Teknologi_Sepuluh_Nopember_seal.svg/1200px-Institut_Teknologi_Sepuluh_Nopember_seal.svg.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="<?php echo BASE_URL; ?>css/custom.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>">
                <img src="https://upload.wikimedia.org/wikipedia/id/thumb/0/01/Institut_Teknologi_Sepuluh_Nopember_seal.svg/1200px-Institut_Teknologi_Sepuluh_Nopember_seal.svg.png" alt="ITS Logo" class="header-logo me-2">
                Lowongan Asisten
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <?php if (!$is_logged_in): // Jika belum login, tampilkan Home dan Daftar Lowongan umum ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" aria-current="page" href="<?php echo BASE_URL; ?>">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'lowongan_list.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>lowongan_list.php">Daftar Lowongan</a>
                        </li>
                    <?php endif; ?>

                    <?php if ($is_logged_in): // Jika sudah login, tampilkan dashboard sesuai peran ?>
                        <?php if ($user_role == 'dosen'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dosen_dashboard.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>dosen_dashboard.php">Dashboard Dosen</a>
                            </li>
                        <?php elseif ($user_role == 'mahasiswa'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'mahasiswa_dashboard.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>mahasiswa_dashboard.php">Dashboard Mahasiswa</a>
                            </li>
                        <?php elseif ($user_role == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>admin_dashboard.php">Dashboard Admin</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'lamaran_list.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>lamaran_list.php">Lihat Lamaran</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'lowongan_list.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>lowongan_list.php">Lihat Lowongan</a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <?php if (!$is_logged_in): // Tampilkan Login dan Register jika belum login ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>login.php">Login</a>
                        </li>
                        <li class="nav-item dropdown" id="registerDropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownRegister" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Register
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownRegister">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>register_mahasiswa.php">Mahasiswa</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>register_dosen.php">Dosen</a></li>
                            </ul>
                        </li>
                    <?php else: // Tampilkan nama user dan Logout jika sudah login ?>
                        <li class="nav-item">
                            <span class="navbar-text me-2">
                                Halo, <strong><?php echo $user_name; ?></strong>! (<?php echo ucfirst($user_role); ?>)
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php">Logout</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container mt-4 pt-5">