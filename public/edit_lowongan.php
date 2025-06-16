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
$lowongan_id = $_GET['id'] ?? null;
$lowongan_data = null;
$message = '';
$message_type = '';

// Ambil data lowongan yang akan diedit (hanya jika ID valid dan dosen_nip cocok)
if ($lowongan_id && is_numeric($lowongan_id)) {
    $query_get_lowongan = "
        SELECT id, nama_lowongan, deskripsi, jumlah_diterima, jenis, tanggal_post, deadline, dosen_nip
        FROM lowongan
        WHERE id = :id AND dosen_nip = :dosen_nip LIMIT 1";
    $stmt_get_lowongan = $conn->prepare($query_get_lowongan);
    $stmt_get_lowongan->bindParam(':id', $lowongan_id, PDO::PARAM_INT);
    $stmt_get_lowongan->bindParam(':dosen_nip', $dosen_nip);
    $stmt_get_lowongan->execute();
    $lowongan_data = $stmt_get_lowongan->fetch(PDO::FETCH_ASSOC);

    if (!$lowongan_data) {
        $message = "Lowongan tidak ditemukan atau Anda tidak memiliki izin untuk mengeditnya.";
        $message_type = 'danger';
        $lowongan_id = null; // Set null agar form tidak ditampilkan
    } else {
        // Ambil skill yang sudah terkait dengan lowongan ini
        $query_get_current_skills = "SELECT skill_id FROM skill_lowongan WHERE lowongan_id = :lowongan_id";
        $stmt_get_current_skills = $conn->prepare($query_get_current_skills);
        $stmt_get_current_skills->bindParam(':lowongan_id', $lowongan_id, PDO::PARAM_INT);
        $stmt_get_current_skills->execute();
        $current_skills_ids = $stmt_get_current_skills->fetchAll(PDO::FETCH_COLUMN); // Ambil hanya kolom skill_id
    }
} else {
    $message = "ID lowongan tidak valid.";
    $message_type = 'danger';
}

// Ambil semua daftar skill yang tersedia dari tabel 'skill'
$all_skills = [];
$query_all_skills = "SELECT id, nama_skill FROM skill ORDER BY nama_skill ASC";
$stmt_all_skills = $conn->prepare($query_all_skills);
$stmt_all_skills->execute();
$all_skills = $stmt_all_skills->fetchAll(PDO::FETCH_ASSOC);


// Proses update lowongan jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lowongan_data) {
    $nama_lowongan = $_POST['nama_lowongan'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $jumlah_diterima = $_POST['jumlah_diterima'] ?? '';
    $jenis = $_POST['jenis'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    $tanggal_post_form = $_POST['tanggal_post'] ?? ''; // Menerima tanggal_post dari form (akan diabaikan oleh trigger)
    $selected_skills = $_POST['skills'] ?? []; // Ambil array ID skill yang dipilih

    if (empty($nama_lowongan) || empty($deskripsi) || empty($jumlah_diterima) || empty($jenis) || empty($deadline)) {
        $message = "Semua field wajib diisi.";
        $message_type = 'danger';
    } else {
        try {
            $conn->beginTransaction(); // Mulai transaksi untuk memastikan atomicity

            // 1. Update data lowongan utama
            $query_update = "
                UPDATE lowongan
                SET
                    nama_lowongan = :nama_lowongan,
                    deskripsi = :deskripsi,
                    jumlah_diterima = :jumlah_diterima,
                    jenis = :jenis,
                    tanggal_post = :tanggal_post_form, -- Akan diabaikan trigger tr_lowongan_prevent_tanggal_post_update_bu
                    deadline = :deadline
                WHERE id = :id AND dosen_nip = :dosen_nip";
            $stmt_update = $conn->prepare($query_update);

            $stmt_update->bindParam(':nama_lowongan', $nama_lowongan);
            $stmt_update->bindParam(':deskripsi', $deskripsi);
            $stmt_update->bindParam(':jumlah_diterima', $jumlah_diterima, PDO::PARAM_INT);
            $stmt_update->bindParam(':jenis', $jenis);
            $stmt_update->bindParam(':tanggal_post_form', $tanggal_post_form);
            $stmt_update->bindParam(':deadline', $deadline);
            $stmt_update->bindParam(':id', $lowongan_id, PDO::PARAM_INT);
            $stmt_update->bindParam(':dosen_nip', $dosen_nip);

            $stmt_update->execute();

            // 2. Hapus skill lama dari lowongan ini di tabel skill_lowongan
            $query_delete_skills = "DELETE FROM skill_lowongan WHERE lowongan_id = :lowongan_id";
            $stmt_delete_skills = $conn->prepare($query_delete_skills);
            $stmt_delete_skills->bindParam(':lowongan_id', $lowongan_id, PDO::PARAM_INT);
            $stmt_delete_skills->execute();

            // 3. Masukkan skill yang baru dipilih ke tabel skill_lowongan
            if (!empty($selected_skills)) {
                $query_insert_skill = "INSERT INTO skill_lowongan (lowongan_id, skill_id) VALUES (:lowongan_id, :skill_id)";
                $stmt_insert_skill = $conn->prepare($query_insert_skill);
                foreach ($selected_skills as $skill_id) {
                    $stmt_insert_skill->bindParam(':lowongan_id', $lowongan_id, PDO::PARAM_INT);
                    $stmt_insert_skill->bindParam(':skill_id', $skill_id, PDO::PARAM_INT);
                    $stmt_insert_skill->execute();
                }
            }

            $conn->commit(); // Commit transaksi jika semua berhasil

            // Update data di $lowongan_data agar form menampilkan nilai terbaru
            // Perlu di-fetch ulang juga skill yang terkait
            $lowongan_data['nama_lowongan'] = $nama_lowongan;
            $lowongan_data['deskripsi'] = $deskripsi;
            $lowongan_data['jumlah_diterima'] = $jumlah_diterima;
            $lowongan_data['jenis'] = $jenis;
            $lowongan_data['deadline'] = $deadline;
            $current_skills_ids = $selected_skills; // Update array skill yang dipilih

            $message = "Lowongan berhasil diperbarui!";
            $message_type = 'success';
            // Opsional: Redirect setelah berhasil
            // header("Location: /FP_MBD_SI_ASDOS_ASPEN/public/dosen_dashboard.php?msg=updated");
            // exit();

        } catch (PDOException $e) {
            $conn->rollBack(); // Rollback transaksi jika terjadi kesalahan
            $message_type = 'danger';
            // Cek apakah error berasal dari trigger dengan SQLSTATE '01000' (warning)
            if ($e->getCode() == '01000') {
                 $message = "Perhatian: " . $e->getMessage();
            } else {
                 $message = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
}


include_once __DIR__ . '/../includes/header.php';
?>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2 class="mb-4"><?php echo $lowongan_data ? 'Edit Lowongan' : 'Lowongan Tidak Ditemukan'; ?></h2>
        <p class="lead">Perbarui detail lowongan asisten Anda.</p>

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
                        <input type="text" class="form-control" id="nama_lowongan" name="nama_lowongan" required value="<?php echo htmlspecialchars($lowongan_data['nama_lowongan'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi Lowongan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required><?php echo htmlspecialchars($lowongan_data['deskripsi'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="jumlah_diterima" class="form-label">Jumlah Asisten Diterima <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jumlah_diterima" name="jumlah_diterima" required min="1" value="<?php echo htmlspecialchars($lowongan_data['jumlah_diterima'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="jenis" class="form-label">Jenis Lowongan <span class="text-danger">*</span></label>
                        <select class="form-select" id="jenis" name="jenis" required>
                            <option value="">Pilih Jenis</option>
                            <option value="Asisten Dosen" <?php echo (($lowongan_data['jenis'] ?? '') == 'Asisten Dosen') ? 'selected' : ''; ?>>Asisten Dosen</option>
                            <option value="Asisten Penelitian" <?php echo (($lowongan_data['jenis'] ?? '') == 'Asisten Penelitian') ? 'selected' : ''; ?>>Asisten Penelitian</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tanggal_post" class="form-label text-muted">Tanggal Posting</label>
                        <input type="date" readonly class="form-control bg-light text-secondary" id="tanggal_post" name="tanggal_post" value="<?php echo htmlspecialchars($lowongan_data['tanggal_post'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="deadline" class="form-label">Deadline Pendaftaran <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="deadline" name="deadline" required value="<?php echo htmlspecialchars($lowongan_data['deadline'] ?? ''); ?>">
                    </div>

                    <!-- BAGIAN BARU: Edit Skill yang Dibutuhkan -->
                    <div class="mb-3">
                        <label class="form-label">Skill yang Dibutuhkan:</label>
                        <?php if (!empty($all_skills)): ?>
                            <div class="row">
                                <?php foreach ($all_skills as $skill): ?>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   id="skill_<?php echo htmlspecialchars($skill['id']); ?>"
                                                   name="skills[]"
                                                   value="<?php echo htmlspecialchars($skill['id']); ?>"
                                                   <?php echo in_array($skill['id'], $current_skills_ids) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="skill_<?php echo htmlspecialchars($skill['id']); ?>">
                                                <?php echo htmlspecialchars($skill['nama_skill']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">Tidak ada skill tersedia.</div>
                        <?php endif; ?>
                    </div>
                    <!-- AKHIR BAGIAN BARU -->

                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="/FP_MBD_SI_ASDOS_ASPEN/public/dosen_dashboard.php" class="btn btn-secondary ms-2">Batal</a>
                </form>
            </div>
        </div>
        <?php else: ?>
            <a href="/FP_MBD_SI_ASDOS_ASPEN/public/dosen_dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>