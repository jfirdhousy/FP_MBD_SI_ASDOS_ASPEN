<?php
session_start();

// Pastikan BASE_URL sudah didefinisikan
if (!defined('BASE_URL')) {
    define('BASE_URL', '/FP_MBD_SI_ASDOS_ASPEN/public/'); // Sesuaikan dengan path proyek Anda
}


require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$lowongan_id = $_GET['id'] ?? null;
$lowongan_detail = null;
$lamaran_list = []; // Inisialisasi array untuk daftar lamaran
$message = '';
$message_type = '';

if ($lowongan_id && is_numeric($lowongan_id)) {
    // --- Ambil Detail Lowongan (Query ini tidak berubah, karena fokusnya detail lowongan) ---
    // Pastikan file detail_lowongan.sql Anda sudah ada dan berisi query yang benar
    $query_detail_file = __DIR__ . '/../database/queries/join/detail_lowongan.sql';
    $query_detail_lowongan = file_get_contents($query_detail_file);

    if ($query_detail_lowongan === false) {
        $message = "Error: File query SQL detail lowongan tidak ditemukan atau tidak dapat dibaca.";
        $message_type = 'danger';
    } else {
        try {
            $stmt = $conn->prepare($query_detail_lowongan);
            $stmt->bindParam(':lowongan_id_param', $lowongan_id, PDO::PARAM_INT);
            $stmt->execute();
            $lowongan_detail = $stmt->fetch(PDO::FETCH_ASSOC);

            // --- Jika detail lowongan ditemukan, ambil daftar lamaran dari VIEW ---
            if ($lowongan_detail) {
                // Periksa apakah user adalah dosen pembuat lowongan atau admin
                $can_view_applicants = false;
                $can_edit_lowongan = false; // BARU: Tambahkan flag untuk mengontrol tombol edit
                if (isset($_SESSION['user_role'])) {
                    if ($_SESSION['user_role'] === 'admin') {
                        $can_view_applicants = true;
                        $can_edit_lowongan = true; // Admin bisa melihat pelamar dan mengedit lowongan
                    } elseif ($_SESSION['user_role'] === 'dosen' && $_SESSION['user_id'] == $lowongan_detail['dosen_nip']) {
                        $can_view_applicants = true;
                        $can_edit_lowongan = true; // Dosen pemilik bisa melihat pelamar dan mengedit lowongan
                    }
                }

                if ($can_view_applicants) {
                    // MENGGUNAKAN VIEW view_detail_lamaran_mahasiswa
                    $query_lamaran_view = "SELECT * FROM view_detail_lamaran_mahasiswa WHERE lowongan_id = :lowongan_id_param ORDER BY tanggal_melamar DESC";
                    
                    try {
                        $stmt_lamaran = $conn->prepare($query_lamaran_view);
                        $stmt_lamaran->bindParam(':lowongan_id_param', $lowongan_id, PDO::PARAM_INT);
                        $stmt_lamaran->execute();
                        $lamaran_list = $stmt_lamaran->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $message = "Terjadi kesalahan database saat mengambil daftar lamaran dari VIEW: " . $e->getMessage();
                        $message_type = 'danger';
                    }
                }
            }
        } catch (PDOException $e) {
            $message = "Terjadi kesalahan database saat mengambil detail lowongan: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
} else {
    $message = "ID lowongan tidak valid.";
    $message_type = 'danger';
}

include_once __DIR__ . '/../includes/header.php';
?>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2 class="mb-4">Detail Lowongan Asisten</h2>

        <?php if (isset($message) && $message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($lowongan_detail): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo htmlspecialchars($lowongan_detail['nama_lowongan']); ?></h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="card-text"><strong>Jenis Lowongan:</strong> <span class="badge bg-info"><?php echo htmlspecialchars($lowongan_detail['jenis_lowongan']); ?></span></p>
                            <p class="card-text"><strong>Jumlah Diterima:</strong> <?php echo htmlspecialchars($lowongan_detail['jumlah_diterima']); ?></p>
                            <p class="card-text"><strong>Tanggal Posting:</strong> <?php echo date('d M Y', strtotime($lowongan_detail['tanggal_post'])); ?></p>
                            <p class="card-text"><strong>Deadline:</strong> <span class="badge bg-danger"><?php echo date('d M Y', strtotime($lowongan_detail['deadline'])); ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="card-text"><strong>Dosen Pembuat:</strong> <?php echo htmlspecialchars($lowongan_detail['nama_dosen']); ?></p>
                            <p class="card-text"><strong>NIP Dosen:</strong> <?php echo htmlspecialchars($lowongan_detail['dosen_nip']); ?></p>
                            <p class="card-text"><strong>Departemen Dosen:</strong> <?php echo htmlspecialchars($lowongan_detail['departemen']); ?></p>
                        </div>
                    </div>
                    <h5 class="mt-4">Deskripsi Lowongan:</h5>
                    <p class="card-text border p-3 rounded bg-light"><?php echo nl2br(htmlspecialchars($lowongan_detail['deskripsi'])); ?></p>

                    <?php if (!empty($lowongan_detail['skill_dibutuhkan'])): ?>
                        <h5 class="mt-4">Skill yang Dibutuhkan:</h5>
                        <p class="card-text">
                            <?php
                            $skills = explode(', ', $lowongan_detail['skill_dibutuhkan']);
                            foreach ($skills as $skill) {
                                echo '<span class="badge bg-secondary me-2">' . htmlspecialchars($skill) . '</span>';
                            }
                            ?>
                        </p>
                    <?php else: ?>
                        <p class="card-text text-muted">Tidak ada skill spesifik yang dicantumkan.</p>
                    <?php endif; ?>

                    <div class="mt-4 text-center">
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'mahasiswa'): ?>
                            <a href="#" class="btn btn-success btn-lg"><i class="bi bi-send-fill me-2"></i>Lamar Lowongan Ini</a>
                        <?php endif; ?>

                        <?php if (isset($can_edit_lowongan) && $can_edit_lowongan): // Tampilkan tombol edit jika diizinkan ?>
                            <a href="<?php echo BASE_URL; ?>edit_lowongan.php?id=<?php echo $lowongan_detail['lowongan_id']; ?>" class="btn btn-outline-primary btn-lg ms-3"><i class="bi bi-pencil-fill me-2"></i>Edit Lowongan</a>
                        <?php endif; ?>

                        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg ms-3"><i class="bi bi-arrow-left-circle me-2"></i>Kembali</a>
                    </div>

                    <?php if (isset($can_view_applicants) && $can_view_applicants): ?>
                    <hr class="my-4">
                    <h4 class="mb-3">Daftar Pelamar untuk Lowongan Ini</h4>
                    <?php if (!empty($lamaran_list)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Pelamar</th>
                                        <th>NRP</th>
                                        <th>Email</th>
                                        <th>Departemen</th>
                                        <th>Tanggal Lamar</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($lamaran_list as $lamaran): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($lamaran['nama_mahasiswa']); ?></td>
                                            <td><?php echo htmlspecialchars($lamaran['mahasiswa_nrp']); ?></td>
                                            <td><?php echo htmlspecialchars($lamaran['email']); ?></td>
                                            <td><?php echo htmlspecialchars($lamaran['departemen'] ?? '-'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($lamaran['tanggal_melamar'])); ?></td>
                                            <td>
                                                <?php
                                                $badge_class = 'bg-secondary';
                                                if ($lamaran['status_lamaran'] == 'Diterima') $badge_class = 'bg-success';
                                                else if ($lamaran['status_lamaran'] == 'Ditolak') $badge_class = 'bg-danger';
                                                else if ($lamaran['status_lamaran'] == 'Pending') $badge_class = 'bg-warning text-dark';
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($lamaran['status_lamaran']); ?></span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>detail_lamaran.php?id=<?php echo $lamaran['lamaran_id']; ?>" class="btn btn-sm btn-info me-1" title="Lihat Detail Lamaran"><i class="bi bi-eye"></i></a>
                                                </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            Belum ada pelamar untuk lowongan ini.
                        </div>
                    <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Lowongan tidak ditemukan atau ID lowongan tidak valid.
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm ms-3">Kembali</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>