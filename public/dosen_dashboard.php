<?php
session_start();

// Periksa apakah dosen sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'dosen') {
    header("Location: /FP_MBD_SI_ASDOS_ASPEN/public/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$dosen_nip = $_SESSION['user_id']; // NIP dosen yang login
$message = ''; // Variabel untuk pesan umum
$message_type = ''; // Tipe pesan (success, danger, info)

// Ambil data profil dosen
$query_profil_dosen = "
    SELECT d.nip, d.nama_dosen, d.email, d.no_telp, dep.nama_departemen
    FROM dosen d
    JOIN departemen dep ON d.departemen_id = dep.id
    WHERE d.nip = :nip LIMIT 1";
$stmt_profil_dosen = $conn->prepare($query_profil_dosen);
$stmt_profil_dosen->bindParam(':nip', $dosen_nip);
$stmt_profil_dosen->execute();
$profil_dosen = $stmt_profil_dosen->fetch(PDO::FETCH_ASSOC);

// --- Ambil Daftar Lowongan Aktif dari VIEW_LOWONGAN_ACTIVE ---
$active_lowongan_list = [];
$query_active_lowongan_file_path = __DIR__ . '/../database/queries/view/view_active_lowongan.sql';

if (file_exists($query_active_lowongan_file_path)) {
    $query_active_lowongan = "
        SELECT lowongan_id, nama_lowongan, jenis_lowongan, jumlah_diterima, deadline, tanggal_post, nama_dosen, departemen
        FROM VIEW_LOWONGAN_ACTIVE
        WHERE dosen_nip = :nip_dosen_aktif -- Memfilter lowongan aktif yang dibuat oleh dosen ini
        ORDER BY tanggal_post DESC;
    ";
    try {
        $stmt_active_lowongan = $conn->prepare($query_active_lowongan);
        $stmt_active_lowongan->bindParam(':nip_dosen_aktif', $dosen_nip);
        $stmt_active_lowongan->execute();
        $active_lowongan_list = $stmt_active_lowongan->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Terjadi kesalahan saat mengambil lowongan aktif: " . $e->getMessage();
        $message_type = 'danger';
    }
} else {
    $message = "Error: File SQL untuk VIEW_LOWONGAN_ACTIVE tidak ditemukan di jalur yang diharapkan.";
    $message_type = 'danger';
}

// --- Ambil Daftar Riwayat Lowongan dari VIEW_RIWAYAT_LOWONGAN_STATISTIK ---
$riwayat_lowongan_list = [];
$query_riwayat_lowongan_file_path = __DIR__ . '/../database/queries/view/riwayat_lowongan_statistic.sql';

if (file_exists($query_riwayat_lowongan_file_path)) {
    // Sama seperti VIEW_LOWONGAN_ACTIVE, VIEW ini sudah ada di database, cukup panggil.
    $query_riwayat_lowongan = "
        SELECT lowongan_id, nama_lowongan, jenis, tanggal_post, deadline, total_lamaran, lamaran_diterima, lamaran_ditolak
        FROM VIEW_RIWAYAT_LOWONGAN_STATISTIK
        WHERE dosen_nip = :nip_dosen_riwayat -- Memfilter riwayat lowongan yang dibuat oleh dosen ini
        ORDER BY deadline DESC;
    ";
    try {
        $stmt_riwayat_lowongan = $conn->prepare($query_riwayat_lowongan);
        $stmt_riwayat_lowongan->bindParam(':nip_dosen_riwayat', $dosen_nip);
        $stmt_riwayat_lowongan->execute();
        $riwayat_lowongan_list = $stmt_riwayat_lowongan->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Terjadi kesalahan saat mengambil riwayat lowongan: " . $e->getMessage();
        $message_type = 'danger';
    }
} else {
    $message = "Error: File SQL untuk VIEW_RIWAYAT_LOWONGAN_STATISTIK tidak ditemukan di jalur yang diharapkan.";
    $message_type = 'danger';
}


include_once __DIR__ . '/../includes/header.php';
?>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2 class="mb-4">Dashboard Dosen</h2>
        <p class="lead">Selamat datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?>. Anda login sebagai Dosen.</p>

        <?php if ($message): // Tampilkan pesan error/info jika ada ?>
            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <hr>

        <!-- Profil Dosen -->
        <h4 class="mb-3">Profil Saya</h4>
        <?php if ($profil_dosen): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($profil_dosen['nama_dosen']); ?></p>
                    <p><strong>NIP:</strong> <?php echo htmlspecialchars($profil_dosen['nip']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($profil_dosen['email']); ?></p>
                    <p><strong>No. Telp:</strong> <?php echo htmlspecialchars($profil_dosen['no_telp']); ?></p>
                    <p><strong>Departemen:</strong> <?php echo htmlspecialchars($profil_dosen['nama_departemen']); ?></p>
                    <a href="#" class="btn btn-primary btn-sm">Edit Profil</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Data profil tidak ditemukan.
            </div>
        <?php endif; ?>

        <hr>

        <!-- Lowongan yang Masih Aktif -->
        <h4 class="mb-3">Lowongan Aktif Saya</h4>
        <a href="/FP_MBD_SI_ASDOS_ASPEN/public/create_lowongan.php" class="btn btn-success mb-3">Buat Lowongan Baru</a>

        <?php if (!empty($active_lowongan_list)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nama Lowongan</th>
                        <th>Jenis</th>
                        <th>Tanggal Post</th>
                        <th>Deadline</th>
                        <th>Jumlah Diterima</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_lowongan_list as $lowongan): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($lowongan['nama_lowongan']); ?></td>
                        <td><?php echo htmlspecialchars($lowongan['jenis_lowongan']); ?></td>
                        <td><?php echo date('d M Y', strtotime($lowongan['tanggal_post'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($lowongan['deadline'])); ?></td>
                        <td><?php echo htmlspecialchars($lowongan['jumlah_diterima']); ?></td>
                        <td>
                            <a href="/FP_MBD_SI_ASDOS_ASPEN/public/detail_lowongan.php?id=<?php echo $lowongan['lowongan_id']; ?>" class="btn btn-info btn-sm">Detail</a>
                            <a href="/FP_MBD_SI_ASDOS_ASPEN/public/edit_lowongan.php?id=<?php echo $lowongan['lowongan_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="/FP_MBD_SI_ASDOS_ASPEN/public/delete_lowongan.php?id=<?php echo $lowongan['lowongan_id']; ?>" class="btn btn-danger btn-sm">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                Anda belum memiliki lowongan aktif saat ini.
            </div>
        <?php endif; ?>

        <hr>

        <!-- Lowongan Riwayat (Tidak Aktif) -->
        <h4 class="mb-3">Riwayat Lowongan</h4>

        <?php if (!empty($riwayat_lowongan_list)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nama Lowongan</th>
                        <th>Jenis</th>
                        <th>Tanggal Post</th>
                        <th>Deadline</th>
                        <th>Total Lamaran</th>
                        <th>Diterima</th>
                        <th>Ditolak</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($riwayat_lowongan_list as $lowongan): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($lowongan['nama_lowongan']); ?></td>
                        <td><?php echo htmlspecialchars($lowongan['jenis']); ?></td>
                        <td><?php echo date('d M Y', strtotime($lowongan['tanggal_post'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($lowongan['deadline'])); ?></td>
                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($lowongan['total_lamaran']); ?></span></td>
                        <td><span class="badge bg-success"><?php echo htmlspecialchars($lowongan['lamaran_diterima']); ?></span></td>
                        <td><span class="badge bg-danger"><?php echo htmlspecialchars($lowongan['lamaran_ditolak']); ?></span></td>
                        <td>
                            <a href="/FP_MBD_SI_ASDOS_ASPEN/public/lowongan_detail.php?id=<?php echo $lowongan['lowongan_id']; ?>" class="btn btn-info btn-sm">Detail</a>
                            <!-- Tambahan aksi seperti lihat daftar pelamar riwayat -->
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                Anda belum memiliki riwayat lowongan yang sudah tidak aktif.
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>