<?php
// public/register_mahasiswa.php
session_start();
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$message = '';
$error = '';

// Ambil daftar departemen dari database (Logika Anggota 4)
$departemen_options = [];
try {
    $stmt = $conn->query("SELECT id, nama_departemen FROM departemen ORDER BY nama_departemen ASC");
    $departemen_options = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Gagal memuat daftar departemen: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nrp = trim($_POST['nrp']);
    $nama_mahasiswa = trim($_POST['nama_mahasiswa']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Tidak dihash sesuai permintaan terakhir
    $no_telp = trim($_POST['no_telp']);
    $departemen_id = (int)$_POST['departemen_id']; // Dari Anggota 4

    // Validasi sederhana
    if (empty($nrp) || empty($nama_mahasiswa) || empty($email) || empty($password) || empty($departemen_id)) {
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
            // Cek apakah NRP atau Email sudah terdaftar
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM mahasiswa WHERE nrp = :nrp OR email = :email");
            $stmt_check->bindParam(':nrp', $nrp);
            $stmt_check->bindParam(':email', $email);
            $stmt_check->execute();
            if ($stmt_check->fetchColumn() > 0) {
                $error = "NRP atau Email sudah terdaftar.";
            } else {
                $stmt = $conn->prepare("INSERT INTO mahasiswa (nrp, nama_mahasiswa, email, password, no_telp, departemen_id) VALUES (:nrp, :nama_mahasiswa, :email, :password, :no_telp, :departemen_id)");
                $stmt->bindParam(':nrp', $nrp);
                $stmt->bindParam(':nama_mahasiswa', $nama_mahasiswa);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $plain_password); // Menyimpan password plain (TIDAK AMAN untuk produksi)
                $stmt->bindParam(':no_telp', $no_telp);
                $stmt->bindParam(':departemen_id', $departemen_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $message = "Registrasi mahasiswa berhasil! Silakan login.";
                    // header("Location: " . BASE_URL . "login.php?registered=mahasiswa"); // Bisa diarahkan setelah registrasi
                    // exit();
                } else {
                    $error = "Gagal melakukan registrasi.";
                }
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Registrasi Akun Mahasiswa</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success" role="alert"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form action="<?php echo BASE_URL; ?>register_mahasiswa.php" method="POST">
                        <div class="mb-3">
                            <label for="nrp" class="form-label">NRP</label>
                            <input type="text" class="form-control" id="nrp" name="nrp" required>
                        </div>
                        <div class="mb-3">
                            <label for="nama_mahasiswa" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_mahasiswa" name="nama_mahasiswa" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="no_telp" class="form-label">No. Telepon</label>
                            <input type="text" class="form-control" id="no_telp" name="no_telp">
                        </div>
                        <div class="mb-3">
                            <label for="departemen_id" class="form-label">Departemen</label>
                            <select class="form-select" id="departemen_id" name="departemen_id" required>
                                <option value="">Pilih Departemen</option>
                                <?php foreach ($departemen_options as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['id']); ?>"><?php echo htmlspecialchars($dept['nama_departemen']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Daftar</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    Sudah punya akun? <a href="<?php echo BASE_URL; ?>login.php">Login di sini</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>