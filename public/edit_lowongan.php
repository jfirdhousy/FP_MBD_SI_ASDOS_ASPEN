<?php
session_start();

// Pastikan BASE_URL sudah didefinisikan di sini
if (!defined('BASE_URL')) {
    define('BASE_URL', '/FP_MBD_SI_ASDOS_ASPEN/public/'); // Sesuaikan dengan path proyek Anda
}

// Redirect jika user belum login atau bukan dosen/admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'dosen' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$lowongan_id = $_GET['id'] ?? null;
$lowongan_data = null;
$message = '';
$message_type = '';
$selected_skills = []; // Untuk menyimpan skill yang sudah dipilih di form

// --- Ambil Data Lowongan yang Akan Diedit ---
if ($lowongan_id && is_numeric($lowongan_id)) {
    try {
        $query_lowongan = "
            SELECT
                l.id,
                l.nama_lowongan,
                l.deskripsi,
                l.jumlah_diterima,
                l.jenis,
                l.tanggal_post,
                l.deadline,
                l.dosen_nip,
                GROUP_CONCAT(sl.skill_id) AS current_skill_ids
            FROM
                lowongan l
            LEFT JOIN
                skill_lowongan sl ON l.id = sl.lowongan_id
            WHERE
                l.id = :lowongan_id
            GROUP BY l.id";
        
        $stmt_lowongan = $conn->prepare($query_lowongan);
        $stmt_lowongan->bindParam(':lowongan_id', $lowongan_id, PDO::PARAM_INT);
        $stmt_lowongan->execute();
        $lowongan_data = $stmt_lowongan->fetch(PDO::FETCH_ASSOC);

        if (!$lowongan_data) {
            $message = "Lowongan tidak ditemukan.";
            $message_type = 'danger';
            $lowongan_id = null; // Set null agar form tidak ditampilkan
        } else {
            // Verifikasi bahwa dosen yang login adalah pemilik lowongan, atau user adalah admin
            // Ini tetap penting agar user yang tidak berhak tidak bisa submit form edit
            if ($_SESSION['user_role'] === 'dosen' && $_SESSION['user_id'] != $lowongan_data['dosen_nip'] && $_SESSION['user_role'] !== 'admin') {
                $message = "Anda tidak memiliki izin untuk mengedit lowongan ini.";
                $message_type = 'danger';
                $lowongan_data = null; // Sembunyikan form jika tidak berizin
            }
            // Populate selected_skills from existing data
            if (!empty($lowongan_data['current_skill_ids'])) {
                $selected_skills = array_map('intval', explode(',', $lowongan_data['current_skill_ids']));
            }
        }
    } catch (PDOException $e) {
        $message = "Terjadi kesalahan database saat mengambil data lowongan: " . $e->getMessage();
        $message_type = 'danger';
        $lowongan_id = null;
    }
} else {
    $message = "ID lowongan tidak valid untuk diedit.";
    $message_type = 'danger';
}

// --- Mengambil daftar skill dari database (untuk checkbox) ---
$all_skills = [];
try {
    $query_all_skills = "SELECT id, nama_skill FROM skill ORDER BY nama_skill ASC";
    $stmt_all_skills = $conn->prepare($query_all_skills);
    $stmt_all_skills->execute();
    $all_skills = $stmt_all_skills->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Hindari menimpa $message jika sudah ada error dari atas
    if (empty($message)) {
        $message = "Gagal mengambil daftar skill: " . $e->getMessage();
        $message_type = 'danger';
    }
}


// --- Proses Update Lowongan saat Form Disubmit ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lowongan_data) {
    // Validasi ulang izin edit saat POST
    if ($_SESSION['user_role'] === 'dosen' && $_SESSION['user_id'] != $lowongan_data['dosen_nip'] && $_SESSION['user_role'] !== 'admin') {
        $message = "Anda tidak memiliki izin untuk melakukan update pada lowongan ini.";
        $message_type = 'danger';
    } else {
        $new_nama_lowongan = $_POST['nama_lowongan'] ?? '';
        $new_deskripsi = $_POST['deskripsi'] ?? '';
        $new_jumlah_diterima = $_POST['jumlah_diterima'] ?? '';
        $new_jenis = $_POST['jenis'] ?? '';
        $new_deadline = $_POST['deadline'] ?? '';
        $new_selected_skills = $_POST['skills'] ?? [];

        if (empty($new_nama_lowongan) || empty($new_deskripsi) || empty($new_jumlah_diterima) || empty($new_jenis) || empty($new_deadline)) {
            $message = "Semua field wajib diisi.";
            $message_type = 'danger';
        } else {
            try {
                $conn->beginTransaction();

                // 1. Update Tabel Lowongan
                $query_update = "
                    UPDATE lowongan
                    SET
                        nama_lowongan = :nama_lowongan,
                        deskripsi = :deskripsi,
                        jumlah_diterima = :jumlah_diterima,
                        jenis = :jenis,
                        deadline = :deadline
                    WHERE id = :lowongan_id";
                
                $stmt_update = $conn->prepare($query_update);
                $stmt_update->bindParam(':nama_lowongan', $new_nama_lowongan);
                $stmt_update->bindParam(':deskripsi', $new_deskripsi);
                $stmt_update->bindParam(':jumlah_diterima', $new_jumlah_diterima, PDO::PARAM_INT);
                $stmt_update->bindParam(':jenis', $new_jenis);
                $stmt_update->bindParam(':deadline', $new_deadline);
                $stmt_update->bindParam(':lowongan_id', $lowongan_id, PDO::PARAM_INT);

                $stmt_update->execute();

                if ($stmt_update->errorCode() === '01000') {
                    $trigger_warning = $stmt_update->errorInfo()[2];
                    if (strpos($trigger_warning, 'Tanggal posting tidak bisa diubah') !== false) {
                        $message = "Lowongan berhasil diperbarui. " . htmlspecialchars($trigger_warning);
                        $message_type = 'warning';
                    } else {
                        $message = "Lowongan berhasil diperbarui. Namun ada peringatan: " . htmlspecialchars($trigger_warning);
                        $message_type = 'warning';
                    }
                } else {
                    $message = "Lowongan berhasil diperbarui!";
                    $message_type = 'success';
                }

                // 2. Update Relasi Skill (Hapus yang lama, masukkan yang baru)
                $query_delete_skills = "DELETE FROM skill_lowongan WHERE lowongan_id = :lowongan_id";
                $stmt_delete_skills = $conn->prepare($query_delete_skills);
                $stmt_delete_skills->bindParam(':lowongan_id', $lowongan_id, PDO::PARAM_INT);
                $stmt_delete_skills->execute();

                if (!empty($new_selected_skills)) {
                    $query_insert_skill = "INSERT INTO skill_lowongan (skill_id, lowongan_id) VALUES (:skill_id, :lowongan_id)";
                    $stmt_insert_skill = $conn->prepare($query_insert_skill);
                    $stmt_insert_skill->bindParam(':lowongan_id', $lowongan_id, PDO::PARAM_INT);
                    foreach ($new_selected_skills as $skill_id) {
                        $stmt_insert_skill->bindParam(':skill_id', $skill_id, PDO::PARAM_INT);
                        $stmt_insert_skill->execute();
                    }
                }
                
                $conn->commit();

                // Setelah sukses update, refresh data lowongan untuk ditampilkan di form
                $query_lowongan_refresh = "
                    SELECT
                        l.id, l.nama_lowongan, l.deskripsi, l.jumlah_diterima, l.jenis, l.tanggal_post, l.deadline, l.dosen_nip,
                        GROUP_CONCAT(sl.skill_id) AS current_skill_ids
                    FROM lowongan l
                    LEFT JOIN skill_lowongan sl ON l.id = sl.lowongan_id
                    WHERE l.id = :lowongan_id
                    GROUP BY l.id";
                $stmt_lowongan_refresh = $conn->prepare($query_lowongan_refresh);
                $stmt_lowongan_refresh->bindParam(':lowongan_id', $lowongan_id, PDO::PARAM_INT);
                $stmt_lowongan_refresh->execute();
                $lowongan_data = $stmt_lowongan_refresh->fetch(PDO::FETCH_ASSOC);

                // Perbarui $selected_skills untuk form
                $selected_skills = !empty($lowongan_data['current_skill_ids']) ? array_map('intval', explode(',', $lowongan_data['current_skill_ids'])) : [];


            } catch (PDOException $e) {
                $conn->rollBack();
                if ($e->getCode() == '45000') {
                    $message = "Gagal memperbarui lowongan: " . $e->getMessage();
                } else {
                    $message = "Terjadi kesalahan database: " . $e->getMessage();
                }
                $message_type = 'danger';
            }
        }
    }
}


include_once __DIR__ . '/../includes/header.php';
?>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2 class="mb-4">Edit Lowongan Asisten</h2>
        <p class="lead">Ubah detail lowongan ini dan skill yang dibutuhkan.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($lowongan_data): ?>
            <div class="card">
                <div class="card-body">
                    <form action="edit_lowongan.php?id=<?php echo htmlspecialchars($lowongan_id); ?>" method="POST">
                        <div class="mb-3">
                            <label for="nama_lowongan" class="form-label">Nama Lowongan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_lowongan" name="nama_lowongan" required value="<?php echo htmlspecialchars($_POST['nama_lowongan'] ?? $lowongan_data['nama_lowongan']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi Lowongan <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required><?php echo htmlspecialchars($_POST['deskripsi'] ?? $lowongan_data['deskripsi']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="jumlah_diterima" class="form-label">Jumlah Asisten Diterima <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="jumlah_diterima" name="jumlah_diterima" required min="1" value="<?php echo htmlspecialchars($_POST['jumlah_diterima'] ?? $lowongan_data['jumlah_diterima']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="jenis" class="form-label">Jenis Lowongan <span class="text-danger">*</span></label>
                            <select class="form-select" id="jenis" name="jenis" required>
                                <option value="">Pilih Jenis</option>
                                <option value="Asisten Dosen" <?php echo (($_POST['jenis'] ?? $lowongan_data['jenis']) == 'Asisten Dosen') ? 'selected' : ''; ?>>Asisten Dosen</option>
                                <option value="Asisten Penelitian" <?php echo (($_POST['jenis'] ?? $lowongan_data['jenis']) == 'Asisten Penelitian') ? 'selected' : ''; ?>>Asisten Penelitian</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal_post" class="form-label">Tanggal Posting (Tidak Dapat Diubah)</label>
                            <input type="date" class="form-control" id="tanggal_post" name="tanggal_post" value="<?php echo htmlspecialchars($lowongan_data['tanggal_post']); ?>" disabled>
                            <small class="form-text text-muted">Tanggal posting lowongan tidak dapat diubah setelah dibuat.</small>
                        </div>
                        <div class="mb-3">
                            <label for="deadline" class="form-label">Deadline Pendaftaran <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="deadline" name="deadline" required value="<?php echo htmlspecialchars($_POST['deadline'] ?? $lowongan_data['deadline']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Skill yang Dibutuhkan:</label>
                            <?php if (!empty($all_skills)): ?>
                                <div class="row">
                                    <?php foreach ($all_skills as $skill): ?>
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

                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <?php
                            $cancel_link = '';
                            if ($_SESSION['user_role'] === 'dosen') {
                                $cancel_link = BASE_URL . 'dosen_dashboard.php';
                            } elseif ($_SESSION['user_role'] === 'admin') {
                                $cancel_link = BASE_URL . 'lowongan_list.php';
                            }
                        ?>
                        <a href="<?php echo $cancel_link; ?>" class="btn btn-secondary ms-2">Batal</a>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Lowongan tidak dapat dimuat atau Anda tidak memiliki izin untuk mengeditnya.
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm ms-3">Kembali</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>