<?php
// public/mahasiswa_dashboard.php
session_start();
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$error_message = '';
$success_message = '';
$message = '';
$message_type = '';

if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = htmlspecialchars($_GET['type']);
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'mahasiswa') {
    header("Location: " . BASE_URL . "login.php?redirect=mahasiswa_dashboard");
    exit();
}

$mahasiswa_nrp = $_SESSION['user_id'];
$nama_mahasiswa = $_SESSION['user_name'];

$profil_mahasiswa = [];
try {
    $query_profil = "SELECT
                        m.nrp,
                        m.email,
                        m.nama_mahasiswa,
                        m.no_telp,
                        d.nama_departemen AS nama_departemen
                     FROM
                        mahasiswa m
                     LEFT JOIN
                        departemen d ON m.departemen_id = d.id
                     WHERE
                        m.nrp = :mahasiswa_nrp";

    $stmt_profil = $conn->prepare($query_profil);
    $stmt_profil->bindParam(':mahasiswa_nrp', $mahasiswa_nrp);
    $stmt_profil->execute();
    $profil_mahasiswa = $stmt_profil->fetch(PDO::FETCH_ASSOC);

    if (!$profil_mahasiswa) {
        $error_message = "Data profil mahasiswa tidak ditemukan.";
    }
} catch (PDOException $e) {
    $message = "Terjadi kesalahan saat mengambil data profil: " . $e->getMessage();
    $message_type = 'danger';
}

$active_lamaran_list = [];
try {
    $query_active_lamaran = "SELECT
                                l.id AS lamaran_id,
                                l.tanggal_melamar,
                                l.status_lamaran,
                                l.cv_url,
                                l.transkrip_url,
                                l.note_dosen,
                                low.nama_lowongan,
                                low.jenis AS jenis_lowongan,
                                low.deadline AS deadline_lowongan
                              FROM
                                lamaran l
                              JOIN
                                lowongan low ON l.lowongan_id = low.id
                              WHERE
                                l.mahasiswa_nrp = :mahasiswa_nrp_active AND l.status_lamaran IN ('Pending', 'Ditinjau')
                              ORDER BY
                                l.tanggal_melamar DESC";

    $stmt_active_lamaran = $conn->prepare($query_active_lamaran);
    $stmt_active_lamaran->bindParam(':mahasiswa_nrp_active', $mahasiswa_nrp);
    $stmt_active_lamaran->execute();
    $active_lamaran_list = $stmt_active_lamaran->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message .= (!empty($message) ? "<br>" : "") . "Terjadi kesalahan saat mengambil lamaran aktif: " . $e->getMessage();
    $message_type = 'danger';
}

$riwayat_lamaran_list = [];
try {
    $query_riwayat_lamaran = "SELECT
                                l.id AS lamaran_id,
                                l.tanggal_melamar,
                                l.status_lamaran,
                                l.cv_url,
                                l.transkrip_url,
                                l.note_dosen,
                                low.nama_lowongan,
                                low.jenis AS jenis_lowongan,
                                low.deadline AS deadline_lowongan
                              FROM
                                lamaran l
                              JOIN
                                lowongan low ON l.lowongan_id = low.id
                              WHERE
                                l.mahasiswa_nrp = :mahasiswa_nrp_riwayat AND l.status_lamaran IN ('Diterima', 'Ditolak')
                              ORDER BY
                                l.tanggal_melamar DESC";

    $stmt_riwayat_lamaran = $conn->prepare($query_riwayat_lamaran);
    $stmt_riwayat_lamaran->bindParam(':mahasiswa_nrp_riwayat', $mahasiswa_nrp);
    $stmt_riwayat_lamaran->execute();
    $riwayat_lamaran_list = $stmt_riwayat_lamaran->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message .= (!empty($message) ? "<br>" : "") . "Terjadi kesalahan saat mengambil riwayat lamaran: " . $e->getMessage();
    $message_type = 'danger';
}


include_once __DIR__ . '/../includes/header.php';
?>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2 class="mb-4">Dashboard Mahasiswa</h2>
        <p class="lead">Selamat datang, <?php echo htmlspecialchars($nama_mahasiswa); ?>. Anda login sebagai Mahasiswa.</p>

        <?php if ($message): // Tampilkan pesan error/info jika ada ?>
            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <hr>

        <h4 class="mb-3">Profil Saya</h4>
        <?php if ($profil_mahasiswa): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($profil_mahasiswa['nama_mahasiswa']); ?></p>
                    <p><strong>NRP:</strong> <?php echo htmlspecialchars($profil_mahasiswa['nrp']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($profil_mahasiswa['email']); ?></p>
                    <p><strong>No. Telp:</strong> <?php echo htmlspecialchars($profil_mahasiswa['no_telp']); ?></p>
                    <p><strong>Departemen:</strong> <?php echo htmlspecialchars($profil_mahasiswa['nama_departemen'] ?? 'Belum Ditentukan'); ?></p>

                    <a href="<?php echo BASE_URL; ?>edit_mahasiswa_profile.php" class="btn btn-primary btn-sm mt-2"><i class="bi bi-pencil-fill me-1"></i>Edit Profil</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Data profil tidak ditemukan.
            </div>
        <?php endif; ?>

        <hr>

        <h4 class="mb-3">Lamaran Aktif Saya</h4>
        <?php if (!empty($active_lamaran_list)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal Melamar</th>
                        <th>Nama Lowongan</th>
                        <th>Jenis Lowongan</th>
                        <th>Batas Lamar</th>
                        <th>Status Lamaran</th>
                        <th>Catatan Dosen</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($active_lamaran_list as $lamaran): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo date('d M Y H:i', strtotime($lamaran['tanggal_melamar'])); ?></td>
                        <td><?php echo htmlspecialchars($lamaran['nama_lowongan']); ?></td>
                        <td><?php echo htmlspecialchars($lamaran['jenis_lowongan']); ?></td>
                        <td><?php echo date('d M Y', strtotime($lamaran['deadline_lowongan'])); ?></td>
                        <td>
                            <?php
                                $status_class = '';
                                switch ($lamaran['status_lamaran']) {
                                    case 'Diterima': $status_class = 'bg-success'; break;
                                    case 'Ditolak': $status_class = 'bg-danger'; break;
                                    case 'Ditinjau': $status_class = 'bg-warning text-dark'; break;
                                    default: $status_class = 'bg-secondary'; break; // Pending
                                }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($lamaran['status_lamaran']); ?></span>
                        </td>
                        <td><?php echo !empty($lamaran['note_dosen']) ? htmlspecialchars($lamaran['note_dosen']) : '-'; ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($lamaran['cv_url']); ?>" target="_blank" class="btn btn-sm btn-info text-white me-1" title="Lihat CV">CV</a>
                            <a href="<?php echo htmlspecialchars($lamaran['transkrip_url']); ?>" target="_blank" class="btn btn-sm btn-info text-white" title="Lihat Transkrip">Transkrip</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                Anda belum memiliki lamaran aktif atau yang sedang ditinjau.
            </div>
        <?php endif; ?>

        <hr>

        <h4 class="mb-3">Riwayat Lamaran</h4>
        <?php if (!empty($riwayat_lamaran_list)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal Melamar</th>
                        <th>Nama Lowongan</th>
                        <th>Jenis Lowongan</th>
                        <th>Deadline</th>
                        <th>Status Lamaran</th>
                        <th>Catatan Dosen</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php foreach ($riwayat_lamaran_list as $lamaran): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo date('d M Y H:i', strtotime($lamaran['tanggal_melamar'])); ?></td>
                        <td><?php echo htmlspecialchars($lamaran['nama_lowongan']); ?></td>
                        <td><?php echo htmlspecialchars($lamaran['jenis_lowongan']); ?></td>
                        <td><?php echo date('d M Y', strtotime($lamaran['deadline_lowongan'])); ?></td>
                        <td>
                            <?php
                                $status_class = '';
                                switch ($lamaran['status_lamaran']) {
                                    case 'Diterima': $status_class = 'bg-success'; break;
                                    case 'Ditolak': $status_class = 'bg-danger'; break;
                                    default: $status_class = 'bg-secondary'; break;
                                }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($lamaran['status_lamaran']); ?></span>
                        </td>
                        <td><?php echo !empty($lamaran['note_dosen']) ? htmlspecialchars($lamaran['note_dosen']) : '-'; ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($lamaran['cv_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info me-1" title="Lihat CV">CV</a>
                            <a href="<?php echo htmlspecialchars($lamaran['transkrip_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Lihat Transkrip">Transkrip</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                Anda belum memiliki riwayat lamaran (yang diterima atau ditolak).
            </div>
        <?php endif; ?>

    </div>
</div>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>
