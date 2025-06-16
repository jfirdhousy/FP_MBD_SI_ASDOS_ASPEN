<?php
session_start();

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$lowongan_id = $_GET['id'] ?? null;
$lowongan_detail = null;

if ($lowongan_id && is_numeric($lowongan_id)) {
    // Path ke file SQL query Anda
    $query_file_path = __DIR__ . '/../database/queries/join/detail_lowongan.sql';

    // Membaca isi file SQL ke dalam variabel string
    $query_detail_lowongan = file_get_contents($query_file_path);

    if ($query_detail_lowongan === false) {
        // Handle error jika file tidak ditemukan atau tidak bisa dibaca
        $message = "Error: File query SQL tidak ditemukan atau tidak dapat dibaca.";
        $message_type = 'danger';
    } else {
        try {
            $stmt = $conn->prepare($query_detail_lowongan);
            $stmt->bindParam(':lowongan_id_param', $lowongan_id, PDO::PARAM_INT);
            $stmt->execute();
            $lowongan_detail = $stmt->fetch(PDO::FETCH_ASSOC);
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

        <?php if (isset($message)): ?>
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
                        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'dosen' && $_SESSION['user_id'] == $lowongan_detail['dosen_nip']): ?>
                            <a href="/FP_MBD_SI_ASDOS_ASPEN/public/edit_lowongan.php?id=<?php echo $lowongan_detail['lowongan_id']; ?>" class="btn btn-outline-primary btn-lg ms-3"><i class="bi bi-pencil-fill me-2"></i>Edit Lowongan</a>
                            <?php endif; ?>
                        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg ms-3"><i class="bi bi-arrow-left-circle me-2"></i>Kembali</a>
                    </div>
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