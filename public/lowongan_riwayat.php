<?php
session_start();

// Pastikan BASE_URL sudah didefinisikan
if (!defined('BASE_URL')) {
    define('BASE_URL', '/FP_MBD_SI_ASDOS_ASPEN/public/'); // Sesuaikan dengan path proyek Anda
}

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$message = '';
$message_type = '';

// Parameter filter
$filter_departemen_id = isset($_GET['departemen_id']) ? (int)$_GET['departemen_id'] : 0;
$search_dosen_name = isset($_GET['dosen_name']) ? trim($_GET['dosen_name']) : '';

// Ambil daftar departemen untuk filter dropdown
$departemen_options = [];
try {
    $stmt_dept = $conn->query("SELECT id, nama_departemen FROM departemen ORDER BY nama_departemen ASC");
    $departemen_options = $stmt_dept->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Gagal memuat daftar departemen: " . $e->getMessage();
    $message_type = 'danger';
}

$riwayat_lowongan_list = [];
$current_date = new DateTime(); // Dapatkan tanggal saat ini di PHP

// --- LOGIKA KONDISIONAL BERDASARKAN INPUT NAMA DOSEN ---
if (!empty($search_dosen_name)) {
    // Jalur 1: Nama Dosen diisi, panggil Stored Procedure, lalu filter di PHP
    try {
        $stmt_sp = $conn->prepare("CALL MENCARI_LOWONGAN_BERDASARKAN_NAMA_DOSEN(:keyword)");
        $stmt_sp->bindParam(':keyword', $search_dosen_name);
        $stmt_sp->execute();
        
        $raw_sp_results = $stmt_sp->fetchAll(PDO::FETCH_ASSOC);
        
        // Penting: Konsumsi resultset berikutnya jika ada
        if ($stmt_sp->nextRowset()) {} 

        // Filter hasil dari Stored Procedure di PHP
        foreach ($raw_sp_results as $lowongan) {
            $deadline_dt = new DateTime($lowongan['deadline']);

            // Filter 1: Pastikan lowongan sudah kadaluarsa (riwayat)
            if ($deadline_dt < $current_date) {
                // Filter 2: Pastikan sesuai departemen jika filter departemen_id dipilih
                if ($filter_departemen_id > 0) {
                    // Dapatkan ID departemen dari nama departemen di hasil SP
                    // Ini tidak efisien, tapi harus dilakukan karena SP mengembalikan nama departemen, bukan ID
                    $stmt_get_dept_id = $conn->prepare("SELECT id FROM departemen WHERE nama_departemen = :nama_departemen LIMIT 1");
                    $stmt_get_dept_id->bindParam(':nama_departemen', $lowongan['departemen']);
                    $stmt_get_dept_id->execute();
                    $lowongan_dept_id = $stmt_get_dept_id->fetchColumn();
                    if ($stmt_get_dept_id->nextRowset()) {}

                    if ($lowongan_dept_id == $filter_departemen_id) {
                        $riwayat_lowongan_list[] = $lowongan;
                    }
                } else {
                    // Jika tidak ada filter departemen, tambahkan saja
                    $riwayat_lowongan_list[] = $lowongan;
                }
            }
        }
        
    } catch (PDOException $e) {
        $message = "Terjadi kesalahan database saat mencari lowongan berdasarkan nama dosen: " . $e->getMessage();
        $message_type = 'danger';
    }

} else {
    // Jalur 2: Nama Dosen kosong, gunakan query SELECT biasa dengan filter
    $query_riwayat_lowongan = "
        SELECT
            l.id AS lowongan_id,
            l.nama_lowongan,
            l.jenis,
            l.tanggal_post,
            l.deadline,
            dep.nama_departemen AS departemen,
            d.nama_dosen
        FROM
            lowongan l
        JOIN
            dosen d ON l.dosen_nip = d.nip
        JOIN
            departemen dep ON d.departemen_id = dep.id
        WHERE
            l.deadline < CURDATE() -- Lowongan yang sudah kadaluarsa
    ";

    $params_for_binding = [];

    if ($filter_departemen_id > 0) {
        $query_riwayat_lowongan .= " AND dep.id = :departemen_id ";
        $params_for_binding[':departemen_id'] = $filter_departemen_id;
    }

    $query_riwayat_lowongan .= " ORDER BY l.deadline DESC";

    try {
        $stmt = $conn->prepare($query_riwayat_lowongan);
        foreach ($params_for_binding as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        $riwayat_lowongan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Gagal memuat riwayat lowongan: " . $e->getMessage();
        $message_type = 'danger';
    }
}


include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Riwayat Lowongan Kadaluarsa</h1>
    <p class="lead">Daftar lowongan asisten yang telah melewati batas waktu pendaftaran.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Filter Riwayat Lowongan</h5>
        </div>
        <div class="card-body">
            <form action="<?php echo BASE_URL; ?>lowongan_riwayat.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="departemen_id" class="form-label">Departemen</label>
                    <select class="form-select" id="departemen_id" name="departemen_id">
                        <option value="0">Semua Departemen</option>
                        <?php foreach ($departemen_options as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['id']); ?>" <?php echo ($filter_departemen_id == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['nama_departemen']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="dosen_name" class="form-label">Nama Dosen</label>
                    <input type="text" class="form-control" id="dosen_name" name="dosen_name" value="<?php echo htmlspecialchars($search_dosen_name); ?>" placeholder="Cari nama dosen...">
                </div>
                <div class="col-md-auto mt-auto">
                    <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                    <a href="<?php echo BASE_URL; ?>lowongan_riwayat.php" class="btn btn-secondary ms-2">Reset Filter</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($riwayat_lowongan_list)): ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nama Lowongan</th>
                        <th>Jenis</th>
                        <th>Dosen Pembuat</th>
                        <th>Departemen</th>
                        <th>Tgl Posting</th>
                        <th>Deadline</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($riwayat_lowongan_list as $lowongan): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lowongan['lowongan_id']); ?></td>
                            <td><?php echo htmlspecialchars($lowongan['nama_lowongan']); ?></td>
                            <td><?php echo htmlspecialchars($lowongan['jenis']); ?></td>
                            <td><?php echo htmlspecialchars($lowongan['nama_dosen']); ?></td> <td><?php echo htmlspecialchars($lowongan['departemen']); ?></td>
                            <td><?php echo date('d M Y', strtotime($lowongan['tanggal_post'])); ?></td>
                            <td><span class="badge bg-danger"><?php echo date('d M Y', strtotime($lowongan['deadline'])); ?></span></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>detail_lowongan.php?id=<?php echo $lowongan['lowongan_id']; ?>" class="btn btn-sm btn-info" title="Lihat Detail"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info" role="alert">
            Tidak ada riwayat lowongan yang ditemukan dengan kriteria tersebut.
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
