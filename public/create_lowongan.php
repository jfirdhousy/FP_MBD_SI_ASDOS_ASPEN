<?php
session_start();

// Pastikan BASE_URL sudah didefinisikan
if (!defined('BASE_URL')) {
    define('BASE_URL', '/FP_MBD_SI_ASDOS_ASPEN/public/'); // Sesuaikan dengan path proyek Anda
}

// Periksa apakah dosen sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'dosen') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$dosen_nip = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Inisialisasi variabel untuk mempertahankan nilai input jika ada error
$nama_lowongan = $_POST['nama_lowongan'] ?? '';
$deskripsi = $_POST['deskripsi'] ?? '';
$jumlah_diterima = $_POST['jumlah_diterima'] ?? '';
$jenis = $_POST['jenis'] ?? '';
$deadline = $_POST['deadline'] ?? '';
$selected_skills = []; // Untuk menyimpan skill yang sudah dipilih di form

// --- Mengambil daftar skill dari database ---
$skills = [];
try {
    $query_skills = "SELECT id, nama_skill FROM skill ORDER BY nama_skill ASC";
    $stmt_skills = $conn->prepare($query_skills);
    $stmt_skills->execute();
    $skills = $stmt_skills->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error jika gagal mengambil skill
    if (empty($message)) { // Hanya set pesan error jika belum ada
        $message = "Gagal mengambil daftar skill: " . $e->getMessage();
        $message_type = 'danger';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lowongan = trim($_POST['nama_lowongan'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $jumlah_diterima = trim($_POST['jumlah_diterima'] ?? '');
    $jenis = trim($_POST['jenis'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');
    $selected_skills = $_POST['skills'] ?? []; // Array of skill IDs

    if (empty($nama_lowongan) || empty($deskripsi) || empty($jumlah_diterima) || empty($jenis) || empty($deadline)) {
        $message = "Semua field wajib diisi.";
        $message_type = 'danger';
    } else {
        try {
            // Memulai transaksi
            $conn->beginTransaction();

            // 1. Insert Lowongan Baru
            $query_lowongan = "INSERT INTO lowongan (nama_lowongan, deskripsi, jumlah_diterima, jenis, deadline, dosen_nip)
                               VALUES (:nama_lowongan, :deskripsi, :jumlah_diterima, :jenis, :deadline, :dosen_nip)";
            // PERHATIKAN: tanggal_post DIHAPUS dari daftar kolom, karena trigger akan mengaturnya.
            
            $stmt_lowongan = $conn->prepare($query_lowongan);

            $stmt_lowongan->bindParam(':nama_lowongan', $nama_lowongan);
            $stmt_lowongan->bindParam(':deskripsi', $deskripsi);
            $stmt_lowongan->bindParam(':jumlah_diterima', $jumlah_diterima, PDO::PARAM_INT);
            $stmt_lowongan->bindParam(':jenis', $jenis);
            // $stmt_lowongan->bindParam(':tanggal_post', date('Y-m-d')); // BARIS INI DIHAPUS
            $stmt_lowongan->bindParam(':deadline', $deadline);
            $stmt_lowongan->bindParam(':dosen_nip', $dosen_nip);

            $stmt_lowongan->execute(); // Jalankan insert, trigger akan aktif di sini

            // Mendapatkan ID lowongan yang baru saja dibuat
            $lowongan_id = $conn->lastInsertId();

            // 2. Insert Skill yang Dibutuhkan ke skill_lowongan
            if (!empty($selected_skills) && $lowongan_id) {
                $query_skill_lowongan = "INSERT INTO skill_lowongan (skill_id, lowongan_id) VALUES (:skill_id, :lowongan_id)";
                $stmt_skill_lowongan = $conn->prepare($query_skill_lowongan);
                $stmt_skill_lowongan->bindParam(':lowongan_id', $lowongan_id, PDO::PARAM_INT);

                foreach ($selected_skills as $skill_id) {
                    $stmt_skill_lowongan->bindParam(':skill_id', $skill_id, PDO::PARAM_INT);
                    $stmt_skill_lowongan->execute();
                }
            }

            // Komit transaksi jika semua berhasil
            $conn->commit();
            $message = "Lowongan dan skill yang dibutuhkan berhasil dibuat!";
            $message_type = 'success';

            // Bersihkan data form setelah berhasil
            $nama_lowongan = '';
            $deskripsi = '';
            $jumlah_diterima = '';
            $jenis = '';
            $deadline = '';
            $selected_skills = []; // Pastikan ini juga direset
            
        } catch (PDOException $e) {
            // Rollback transaksi jika terjadi kesalahan
            $conn->rollBack();
            // Tangkap error dari trigger SQL (misal: deadline < tanggal_post)
            // Handle SQLSTATE '45000' adalah custom error dari SIGNAL
            if ($e->getCode() == '45000') {
                $message = "Gagal membuat lowongan: " . $e->getMessage(); // Pesan dari SIGNAL SQLSTATE
            } else {
                $message = "Terjadi kesalahan database: " . $e->getMessage();
            }
            $message_type = 'danger';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2 class="mb-4">Buat Lowongan Baru</h2>
        <p class="lead">Isi detail lowongan asisten yang ingin Anda publikasikan dan pilih skill yang dibutuhkan.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form action="create_lowongan.php" method="POST">
                    <div class="mb-3">
                        <label for="nama_lowongan" class="form-label">Nama Lowongan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_lowongan" name="nama_lowongan" required value="<?php echo htmlspecialchars($nama_lowongan); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi Lowongan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required><?php echo htmlspecialchars($deskripsi); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="jumlah_diterima" class="form-label">Jumlah Asisten Diterima <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jumlah_diterima" name="jumlah_diterima" required min="1" value="<?php echo htmlspecialchars($jumlah_diterima); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="jenis" class="form-label">Jenis Lowongan <span class="text-danger">*</span></label>
                        <select class="form-select" id="jenis" name="jenis" required>
                            <option value="">Pilih Jenis</option>
                            <option value="Asisten Dosen" <?php echo ($jenis == 'Asisten Dosen') ? 'selected' : ''; ?>>Asisten Dosen</option>
                            <option value="Asisten Penelitian" <?php echo ($jenis == 'Asisten Penelitian') ? 'selected' : ''; ?>>Asisten Penelitian</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="deadline" class="form-label">Deadline Pendaftaran <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="deadline" name="deadline" required value="<?php echo htmlspecialchars($deadline); ?>">
                    </div>

                    <!-- Input untuk memilih skill -->
                    <div class="mb-3">
                        <label class="form-label">Skill yang Dibutuhkan:</label>
                        <?php if (!empty($skills)): ?>
                            <div class="row">
                                <?php foreach ($skills as $skill): ?>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   id="skill_<?php echo $skill['id']; ?>"
                                                   name="skills[]"
                                                   value="<?php echo $skill['id']; ?>"
                                                   <?php echo in_array($skill['id'], $selected_skills) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="skill_<?php echo $skill['id']; ?>">
                                                <?php echo htmlspecialchars($skill['nama_skill']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada skill tersedia. Harap tambahkan skill terlebih dahulu (melalui admin dashboard, jika ada).</p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Buat Lowongan</button>
                    <a href="/FP_MBD_SI_ASDOS_ASPEN/public/dosen_dashboard.php" class="btn btn-secondary ms-2">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>