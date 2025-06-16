<?php
session_start();

// Periksa apakah dosen sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'dosen') {
    header("Location: /FP_MBD_SI_ASDOS_ASPEN/public/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$dosen_nip = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lowongan = $_POST['nama_lowongan'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $jumlah_diterima = $_POST['jumlah_diterima'] ?? '';
    $jenis = $_POST['jenis'] ?? '';
    $deadline = $_POST['deadline'] ?? '';

    if (empty($nama_lowongan) || empty($deskripsi) || empty($jumlah_diterima) || empty($jenis) || empty($deadline)) {
        $message = "Semua field harus diisi.";
        $message_type = 'danger';
    } else {
        try {
            $query = "INSERT INTO lowongan (nama_lowongan, deskripsi, jumlah_diterima, jenis, tanggal_post, deadline, dosen_nip)
                      VALUES (:nama_lowongan, :deskripsi, :jumlah_diterima, :jenis, CURDATE(), :deadline, :dosen_nip)";
            $stmt = $conn->prepare($query);

            $stmt->bindParam(':nama_lowongan', $nama_lowongan);
            $stmt->bindParam(':deskripsi', $deskripsi);
            $stmt->bindParam(':jumlah_diterima', $jumlah_diterima, PDO::PARAM_INT);
            $stmt->bindParam(':jenis', $jenis);
            // tanggal_post akan otomatis diisi CURDATE() oleh trigger tr_lowongan_set_tanggal_post_bi
            $stmt->bindParam(':deadline', $deadline);
            $stmt->bindParam(':dosen_nip', $dosen_nip);

            if ($stmt->execute()) {
                $message = "Lowongan berhasil dibuat!";
                $message_type = 'success';
                // Opsional: Redirect setelah berhasil
                // header("Location: /FP_MBD_SI_ASDOS_ASPEN/public/dosen_dashboard.php?msg=success");
                // exit();
            } else {
                $message = "Gagal membuat lowongan. Silakan coba lagi. " . $stmt->errorInfo()[2];
                $message_type = 'danger';
            }
        } catch (PDOException $e) {
            // Tangkap error dari trigger SQL (misal: deadline < tanggal_post)
            $message = "Terjadi kesalahan: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2 class="mb-4">Buat Lowongan Baru</h2>
        <p class="lead">Isi detail lowongan asisten yang ingin Anda publikasikan.</p>

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
                        <input type="text" class="form-control" id="nama_lowongan" name="nama_lowongan" required value="<?php echo htmlspecialchars($_POST['nama_lowongan'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi Lowongan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required><?php echo htmlspecialchars($_POST['deskripsi'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="jumlah_diterima" class="form-label">Jumlah Asisten Diterima <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jumlah_diterima" name="jumlah_diterima" required min="1" value="<?php echo htmlspecialchars($_POST['jumlah_diterima'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="jenis" class="form-label">Jenis Lowongan <span class="text-danger">*</span></label>
                        <select class="form-select" id="jenis" name="jenis" required>
                            <option value="">Pilih Jenis</option>
                            <option value="Asisten Dosen" <?php echo (($_POST['jenis'] ?? '') == 'Asisten Dosen') ? 'selected' : ''; ?>>Asisten Dosen</option>
                            <option value="Asisten Penelitian" <?php echo (($_POST['jenis'] ?? '') == 'Asisten Penelitian') ? 'selected' : ''; ?>>Asisten Penelitian</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="deadline" class="form-label">Deadline Pendaftaran <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="deadline" name="deadline" required value="<?php echo htmlspecialchars($_POST['deadline'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Buat Lowongan</button>
                    <a href="/FP_MBD_SI_ASDOS_ASPEN/public/dosen_dashboard.php" class="btn btn-secondary ms-2">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>