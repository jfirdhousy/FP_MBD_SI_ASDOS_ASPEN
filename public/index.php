<?php
// public/index.php

session_start();

include_once __DIR__ . '/../includes/header.php';
?>

<?php if (!isset($_SESSION['user_id'])): // Tampilkan bagian ini hanya jika belum login ?>
    <div class="row align-items-center py-5 hero-section mb-5">
        <div class="col-lg-7 text-center text-lg-start px-md-5">
            <h1 class="display-4 fw-bold lh-1 mb-3 text-white">
                Temukan Peluang<br>Asisten Dosen/Penelitian<br>Impian Anda!
            </h1>
            <p class="lead mb-4 text-white-50">Platform terpadu untuk menghubungkan mahasiswa berprestasi dengan dosen dan peneliti yang membutuhkan asisten.</p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary btn-lg px-4">Login Sekarang</a>
            </div>
        </div>
        <div class="col-lg-5 text-center mt-4 mt-lg-0">
            <img src="https://cdna.artstation.com/p/assets/images/images/037/888/632/original/ryan-haight-uw-spring.gif?1621576640" class="img-fluid rounded-3 shadow-lg" alt="Ilustrasi Pixel Art Kampus Mahasiswa dan Dosen">
        </div>
    </div>

    <section class="py-5 text-center">
        <div class="container-fluid">
            <h2 class="pb-3 mb-5">Bergabunglah Bersama Kami</h2>

            <div class="row featurette g-5">
                <div class="col-md-6">
                    <div class="feature-icon bg-primary bg-gradient text-white rounded-circle mb-3 mx-auto">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <h3 class="fw-normal">Daftar Sebagai Mahasiswa</h3>
                    <p>Telusuri beragam lowongan asisten dosen dan penelitian, ajukan lamaran dengan mudah, dan kelola status aplikasi Anda dalam satu tempat.</p>
                    <p><a class="btn btn-primary" href="<?php echo BASE_URL; ?>register_mahasiswa.php">Daftar Sekarang &raquo;</a></p>
                </div>
                <div class="col-md-6 dosen-feature">
                    <div class="feature-icon bg-success bg-gradient text-white rounded-circle mb-3 mx-auto">
                        <i class="bi bi-book-fill"></i>
                    </div>
                    <h3 class="fw-normal">Daftar Sebagai Dosen</h3>
                    <p>Publikasikan lowongan asisten Anda, tinjau profil dan lamaran dari mahasiswa berkualitas, serta pilih kandidat terbaik.</p>
                    <p><a class="btn btn-success" href="<?php echo BASE_URL; ?>register_dosen.php">Daftar Sekarang &raquo;</a></p>
                </div>
            </div>
        </div>
    </section>

<?php else: // Jika sudah login, tampilkan pesan sambutan atau redirect ?>
    <div class="alert alert-success text-center" role="alert">
        <h4 class="alert-heading">Selamat Datang Kembali, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h4>
        <p>Anda saat ini login sebagai **<?php echo ucfirst($_SESSION['user_role']); ?>**.</p>
        <hr>
        <p class="mb-0">Silakan menuju dashboard Anda untuk melanjutkan aktivitas.</p>
        <?php if ($_SESSION['user_role'] == 'dosen'): ?>
            <a href="<?php echo BASE_URL; ?>dosen_dashboard.php" class="btn btn-primary mt-3">Pergi ke Dashboard Dosen</a>
        <?php elseif ($_SESSION['user_role'] == 'mahasiswa'): ?>
            <a href="<?php echo BASE_URL; ?>mahasiswa_dashboard.php" class="btn btn-primary mt-3">Pergi ke Dashboard Mahasiswa</a>
        <?php elseif ($_SESSION['user_role'] == 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>admin_dashboard.php" class="btn btn-primary mt-3">Pergi ke Dashboard Admin</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>