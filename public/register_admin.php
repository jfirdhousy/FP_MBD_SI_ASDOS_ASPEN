<?php
// public/register_dosen.php
session_start();
require_once __DIR__ . '/../config/database.php'; //
$conn = getConnection(); //

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_admin = $_POST['nama_admin'];
    $email = $_POST['email'];
    $password = $_POST['password']; // Tidak dihash sesuai permintaan terakhir

    // Validasi sederhana
    if (empty($nama_admin) || empty($email) || empty($password)) {
        $error = "Semua kolom wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 6) { // Minimal 6 karakter, bisa disesuaikan
        $error = "Password minimal 6 karakter.";
    } else {
        // Catatan: Password TIDAK DIHASH sesuai instruksi terakhir.
        // Dalam aplikasi nyata, SANGAT DIREKOMENDASIKAN untuk menggunakan password_hash().
        $plain_password = $password;

        try {
            // Cek apakah NIP atau Email sudah terdaftar
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM admin email = :email");
            $stmt_check->bindParam(':email', $email);
            $stmt_check->execute();
            if ($stmt_check->fetchColumn() > 0) {
                $error = "Email sudah terdaftar.";
            } else {
                $stmt = $conn->prepare("INSERT INTO admin (nama_admin, email, password) VALUES (:nama_admin, :email, :password)");
                $stmt->bindParam(':nama_admin', $nama_admin);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $plain_password); // Menyimpan password plain (TIDAK AMAN untuk produksi)

                if ($stmt->execute()) {
                    $message = "Registrasi admin berhasil!";
                } else {
                    $error = "Gagal melakukan registrasi.";
                }
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

include_once __DIR__ . '/../includes/header.php'; //
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Registrasi Akun Admin</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success" role="alert"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form action="<?php echo BASE_URL; ?>register_dosen.php" method="POST">
                        <div class="mb-3">
                            <label for="nama_admin" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_dosen" name="nama_dosen" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Daftar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>