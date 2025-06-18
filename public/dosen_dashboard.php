<?php
// public/dosen_dashboard.php
session_start();

define('BASE_URL', '/FP_MBD_SI_ASDOS_ASPEN/public/');

// Periksa apakah dosen sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'dosen') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$dosen_nip = $_SESSION['user_id']; // NIP dosen yang login
$message = ''; // Variabel untuk pesan umum
$message_type = ''; // Tipe pesan (success, danger, info)

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'lowongan_created') {
        $message = "Lowongan berhasil dibuat!";
        $message_type = 'success';
    }
}

// --- Menggunakan Fungsi HITUNG_LOWONGAN_PER_DOSEN (Anggota 1)---
$total_lowongan_dibuat = 0;
try {
    $stmt_total = $conn->prepare("SELECT HITUNG_LOWONGAN_PER_DOSEN(:dosen_nip) AS total_lowongan");
    $stmt_total->bindParam(':dosen_nip', $dosen_nip);
    $stmt_total->execute();
    $total_lowongan_dibuat = $stmt_total->fetchColumn();
} catch (PDOException $e) {
    $message = "Gagal mengambil jumlah lowongan: " . $e->getMessage();
    $message_type = 'danger';
}

// Ambil pesan dari parameter URL jika ada
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = htmlspecialchars($_GET['type']);
}

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

// --- Ambil Daftar Lowongan Aktif ---
// Menggunakan VIEW_LOWONGAN_PER_DEPARTEMEN dari Anggota 4.
// Menambahkan GROUP_CONCAT untuk menampilkan skill yang dibutuhkan.
$active_lowongan_list = [];
try {
    $query_active_lowongan = "
        SELECT vl.lowongan_id, vl.nama_lowongan, vl.jenis, vl.jumlah_diterima, vl.deadline, vl.tanggal_post, vl.nama_dosen, vl.nama_departemen,
               GROUP_CONCAT(DISTINCT s.nama_skill SEPARATOR ', ') AS skills_needed
        FROM lowongan l
        JOIN VIEW_LOWONGAN_PER_DEPARTEMEN vl ON l.id = vl.lowongan_id -- Gabungkan dengan VIEW_LOWONGAN_PER_DEPARTEMEN
        LEFT JOIN skill_lowongan sl ON l.id = sl.lowongan_id
        LEFT JOIN skill s ON sl.skill_id = s.id
        WHERE vl.dosen_nip = :nip_dosen_aktif AND vl.deadline >= CURDATE() -- Filter lowongan aktif milik dosen ini
        GROUP BY vl.lowongan_id, vl.nama_lowongan, vl.jenis, vl.jumlah_diterima, vl.deadline, vl.tanggal_post, vl.nama_dosen, vl.nama_departemen
        ORDER BY vl.tanggal_post DESC;
    ";
    $stmt_active_lowongan = $conn->prepare($query_active_lowongan);
    $stmt_active_lowongan->bindParam(':nip_dosen_aktif', $dosen_nip);
    $stmt_active_lowongan->execute();
    $active_lowongan_list = $stmt_active_lowongan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Terjadi kesalahan saat mengambil lowongan aktif: " . $e->getMessage();
    $message_type = 'danger';
}

// --- Ambil Daftar Riwayat Lowongan (sudah tidak aktif) ---
// Menggunakan VIEW_RIWAYAT_LOWONGAN_STATISTIK dari Anggota 1.
$riwayat_lowongan_list = [];
try {
    $query_riwayat_lowongan = "
        SELECT lowongan_id, nama_lowongan, jenis, tanggal_post, deadline, total_lamaran, lamaran_diterima, lamaran_ditolak
        FROM VIEW_RIWAYAT_LOWONGAN_STATISTIK
        WHERE dosen_nip = :nip_dosen_riwayat -- Memfilter riwayat lowongan yang dibuat oleh dosen ini
        ORDER BY deadline DESC;
    ";
    $stmt_riwayat_lowongan = $conn->prepare($query_riwayat_lowongan);
    $stmt_riwayat_lowongan->bindParam(':nip_dosen_riwayat', $dosen_nip);
    $stmt_riwayat_lowongan->execute();
    $riwayat_lowongan_list = $stmt_riwayat_lowongan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Terjadi kesalahan saat mengambil riwayat lowongan: " . $e->getMessage();
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

        <h4 class="mb-3">Profil Saya</h4>
        <?php if ($profil_dosen): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($profil_dosen['nama_dosen']); ?></p>
                    <p><strong>NIP:</strong> <?php echo htmlspecialchars($profil_dosen['nip']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($profil_dosen['email']); ?></p>
                    <p><strong>No. Telp:</strong> <?php echo htmlspecialchars($profil_dosen['no_telp']); ?></p>
                    <p><strong>Departemen:</strong> <?php echo htmlspecialchars($profil_dosen['nama_departemen']); ?></p>
                    <p><strong>Total Lowongan Dibuat:</strong> <?php echo $total_lowongan_dibuat; ?></p>
                    <a href="<?php echo BASE_URL; ?>edit_profile.php" class="btn btn-primary btn-sm">Edit Profil</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Data profil tidak ditemukan.
            </div>
        <?php endif; ?>

        <hr>

        <h4 class="mb-3">Lowongan Aktif Saya</h4>
        <a href="<?php echo BASE_URL; ?>create_lowongan.php" class="btn btn-success mb-3"><i class="bi bi-plus-circle-fill me-2"></i>Buat Lowongan Baru</a>

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
                        <th>Skill Dibutuhkan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_lowongan_list as $lowongan): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($lowongan['nama_lowongan']); ?></td>
                        <td><?php echo htmlspecialchars($lowongan['jenis']); ?></td>
                        <td><?php echo date('d M Y', strtotime($lowongan['tanggal_post'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($lowongan['deadline'])); ?></td>
                        <td><?php echo htmlspecialchars($lowongan['jumlah_diterima']); ?></td>
                        <td>
                            <?php
                            if (!empty($lowongan['skills_needed'])) {
                                $skills = explode(', ', $lowongan['skills_needed']);
                                foreach ($skills as $skill) {
                                    echo '<span class="badge bg-secondary me-1">' . htmlspecialchars($skill) . '</span>';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>detail_lowongan.php?id=<?php echo $lowongan['lowongan_id']; ?>" class="btn btn-info btn-sm"><i class="bi bi-info-circle me-1"></i>Detail</a>
                            <a href="<?php echo BASE_URL; ?>edit_lowongan.php?id=<?php echo $lowongan['lowongan_id']; ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-fill me-1"></i>Edit</a>
                            <a href="<?php echo BASE_URL; ?>delete_lowongan.php?id=<?php echo $lowongan['lowongan_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus lowongan ini? Ini akan menghapus semua lamaran terkait!');"><i class="bi bi-trash-fill me-1"></i>Hapus</a>
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
                            <a href="<?php echo BASE_URL; ?>detail_lowongan.php?id=<?php echo $lowongan['lowongan_id']; ?>" class="btn btn-info btn-sm">Detail</a>
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