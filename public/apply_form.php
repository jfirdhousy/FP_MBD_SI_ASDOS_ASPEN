<?php
// public/apply_form.php
session_start();
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$error_message = '';
$success_message = '';
$lowongan_detail = null;

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mahasiswa') {
    header("Location: " . BASE_URL . "login.php?redirect=apply_form&lowongan_id=" . ($_GET['lowongan_id'] ?? ''));
    exit();
}

$mahasiswa_nrp = $_SESSION['user_id'];
$nama_mahasiswa = $_SESSION['user_name'];

$lowongan_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lowongan_id'])) {
        $lowongan_id = (int)$_POST['lowongan_id'];
    }
} else {
    if (isset($_GET['lowongan_id'])) {
        $lowongan_id = (int)$_GET['lowongan_id'];
    }
}

if ($lowongan_id <= 0) {
    $error_message = "ID lowongan tidak valid. Silakan kembali ke daftar lowongan.";
    $lowongan_detail = null;
} else {
    try {
        $query_lowongan = "SELECT
                            l.id,
                            l.nama_lowongan,
                            l.deskripsi,
                            l.jenis,
                            l.deadline,
                            d.nama_dosen AS dosen_penanggung_jawab
                           FROM
                            lowongan l
                           JOIN
                            dosen d ON l.dosen_nip = d.nip
                           WHERE
                            l.id = :lowongan_id";

        $stmt_lowongan = $conn->prepare($query_lowongan);
        $stmt_lowongan->bindParam(':lowongan_id', $lowongan_id);
        $stmt_lowongan->execute();
        $lowongan_detail = $stmt_lowongan->fetch(PDO::FETCH_ASSOC);

        if (!$lowongan_detail) {
            $error_message = "Lowongan tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $error_message = "Gagal mengambil detail lowongan: " . $e->getMessage();
        $lowongan_detail = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_message && $lowongan_detail) {
    $cv_url = trim($_POST['cv_url']);
    $transkrip_url = trim($_POST['transkrip_url']);
    $note_dosen = trim($_POST['note_dosen']);

    if (empty($cv_url) || empty($transkrip_url)) {
        $error_message = "URL CV dan URL Transkrip wajib diisi.";
    } elseif (!filter_var($cv_url, FILTER_VALIDATE_URL)) {
        $error_message = "Format URL CV tidak valid.";
    } elseif (!filter_var($transkrip_url, FILTER_VALIDATE_URL)) {
        $error_message = "Format URL Transkrip tidak valid.";
    } else {
        try {
            $check_query = "SELECT id FROM lamaran WHERE lowongan_id = :lowongan_id AND mahasiswa_nrp = :mahasiswa_nrp LIMIT 1";
            $stmt_check = $conn->prepare($check_query);
            $stmt_check->bindParam(':lowongan_id', $lowongan_id);
            $stmt_check->bindParam(':mahasiswa_nrp', $mahasiswa_nrp);
            $stmt_check->execute();

            if ($stmt_check->rowCount() > 0) {
                $error_message = "Anda sudah pernah melamar lowongan ini sebelumnya.";
            } else {
                // --- Simpan Lamaran ke Database ---
                $query_insert_lamaran = "INSERT INTO lamaran (tanggal_melamar, status_lamaran, cv_url, transkrip_url, note_dosen, lowongan_id, mahasiswa_nrp)
                                        VALUES (NOW(), 'Pending', :cv_url, :transkrip_url, :note_dosen, :lowongan_id, :mahasiswa_nrp)";

                $stmt_insert = $conn->prepare($query_insert_lamaran);
                $stmt_insert->bindParam(':cv_url', $cv_url);
                $stmt_insert->bindParam(':transkrip_url', $transkrip_url);
                $stmt_insert->bindParam(':note_dosen', $note_dosen);
                $stmt_insert->bindParam(':lowongan_id', $lowongan_id);
                $stmt_insert->bindParam(':mahasiswa_nrp', $mahasiswa_nrp);

                if ($stmt_insert->execute()) {
                    $success_message = "Lamaran Anda untuk lowongan '" . htmlspecialchars($lowongan_detail['nama_lowongan']) . "' berhasil diajukan!";
                } else {
                    $error_message = "Gagal mengajukan lamaran. Silakan coba lagi.";
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error_message = "Error: Lamaran gagal diajukan. Mungkin Anda sudah pernah melamar lowongan ini. Harap periksa aturan lamaran.";
            } else {
                $error_message = "Terjadi kesalahan database saat mengajukan lamaran: " . $e->getMessage();
            }
        }
    }
}

// Sertakan header halaman
include_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0">Ajukan Lamaran</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                        <br><a href="<?php echo BASE_URL; ?>mahasiswa_dashboard.php" class="alert-link">Lihat status lamaran Anda.</a>
                    </div>
                <?php endif; ?>

                <?php if ($lowongan_detail): ?>
                <h5 class="mb-3">Detail Lowongan:</h5>
                <p><strong>Nama Lowongan:</strong> <?php echo htmlspecialchars($lowongan_detail['nama_lowongan']); ?></p>
                <p><strong>Jenis:</strong> <?php echo htmlspecialchars($lowongan_detail['jenis']); ?></p>
                <p><strong>Dosen Penanggung Jawab:</strong> <?php echo htmlspecialchars($lowongan_detail['dosen_penanggung_jawab']); ?></p>
                <p><strong>Batas Akhir Melamar:</strong> <?php echo date('d M Y', strtotime($lowongan_detail['deadline'])); ?></p>
                <hr class="mb-4">

                <h5 class="mb-3">Formulir Lamaran Anda:</h5>
                <form action="apply_form.php?lowongan_id=<?php echo $lowongan_id; ?>" method="POST">
                    <input type="hidden" name="lowongan_id" value="<?php echo $lowongan_id; ?>">

                    <div class="mb-3">
                        <label for="cv_url" class="form-label">URL CV <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="cv_url" name="cv_url" placeholder="Contoh: https://drive.google.com/your_cv.pdf" value="<?php echo $_POST['cv_url'] ?? ''; ?>" required>
                        <small class="form-text text-muted">Pastikan URL dapat diakses publik atau melalui tautan berbagi.</small>
                    </div>
                    <div class="mb-3">
                        <label for="transkrip_url" class="form-label">URL Transkrip Nilai <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="transkrip_url" name="transkrip_url" placeholder="Contoh: https://drive.google.com/your_transcript.pdf" value="<?php echo $_POST['transkrip_url'] ?? ''; ?>" required>
                        <small class="form-text text-muted">Pastikan URL dapat diakses publik atau melalui tautan berbagi.</small>
                    </div>
                    <div class="mb-3">
                        <label for="note_dosen" class="form-label">Catatan Tambahan untuk Dosen</label>
                        <textarea class="form-control" id="note_dosen" name="note_dosen" rows="3" placeholder="Contoh: Saya memiliki pengalaman di bidang..."><?php echo $_POST['note_dosen'] ?? ''; ?></textarea>
                        <small class="form-text text-muted">Opsional, bisa berisi motivasi atau highlight keahlian Anda.</small>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="<?php echo BASE_URL; ?>lowongan_list.php" class="btn btn-secondary btn-lg">Batal & Kembali ke Daftar Lowongan</a>
                        <button type="submit" class="btn btn-primary btn-lg">Ajukan Lamaran</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center py-3">
                <p class="mb-0 text-muted">Pastikan data yang Anda ajukan sudah benar.</p>
            </div>
        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>
