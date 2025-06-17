<?php
// public/lowongan_list.php
session_start();
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$search_departemen_id = isset($_GET['departemen_id']) ? (int)$_GET['departemen_id'] : 0;
$search_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$search_skill_id = isset($_GET['skill_id']) ? (int)$_GET['skill_id'] : 0; // Dari Anggota 3

// Ambil daftar departemen untuk filter (Logika Anggota 4)
$departemen_filter_options = [];
try {
    $stmt_dept_filter = $conn->query("SELECT id, nama_departemen FROM departemen ORDER BY nama_departemen ASC");
    $departemen_filter_options = $stmt_dept_filter->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
}

// Ambil daftar skill untuk filter (Logika Anggota 3)
$skill_filter_options = [];
try {
    $stmt_skill_filter = $conn->query("SELECT id, nama_skill FROM skill ORDER BY nama_skill ASC");
    $skill_filter_options = $stmt_skill_filter->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
}

// Query lowongan dengan filter departemen dan skill
// Menggunakan struktur query dari list_lowongan_per_dept.sql sebagai dasar.
$query = "SELECT l.id AS lowongan_id, l.nama_lowongan, l.deskripsi, l.jumlah_diterima, l.jenis, l.tanggal_post, l.deadline,
                 d.nama_dosen, dp.nama_departemen,
                 GROUP_CONCAT(DISTINCT s.nama_skill SEPARATOR ', ') AS skill_yang_dibutuhkan
          FROM lowongan l
          JOIN dosen d ON l.dosen_nip = d.nip
          JOIN departemen dp ON d.departemen_id = dp.id
          LEFT JOIN skill_lowongan sl ON l.id = sl.lowongan_id
          LEFT JOIN skill s ON sl.skill_id = s.id
          WHERE l.deadline >= CURDATE() "; // Filter hanya lowongan yang masih aktif

if ($search_departemen_id > 0) {
    $query .= " AND dp.id = :departemen_id "; // Filter departemen
}
if ($search_skill_id > 0) { // Filter skill dari Anggota 3
    $query .= " AND l.id IN (SELECT lowongan_id FROM skill_lowongan WHERE skill_id = :skill_id) ";
}
if (!empty($search_keyword)) {
    $query .= " AND (l.nama_lowongan LIKE :keyword OR l.deskripsi LIKE :keyword OR d.nama_dosen LIKE :keyword) ";
}

$query .= " GROUP BY l.id, l.nama_lowongan, l.jenis, l.tanggal_post, l.deadline, d.nama_dosen, dp.nama_departemen ORDER BY l.tanggal_post DESC";

$stmt = $conn->prepare($query);

if ($search_departemen_id > 0) {
    $stmt->bindParam(':departemen_id', $search_departemen_id, PDO::PARAM_INT);
}
if ($search_skill_id > 0) {
    $stmt->bindParam(':skill_id', $search_skill_id, PDO::PARAM_INT);
}
if (!empty($search_keyword)) {
    $like_keyword = '%' . $search_keyword . '%';
    $stmt->bindParam(':keyword', $like_keyword);
}

$stmt->execute();
$lowongan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Daftar Lowongan Asisten</h1>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Filter Lowongan</h5>
        </div>
        <div class="card-body">
            <form action="<?php echo BASE_URL; ?>lowongan_list.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="departemen_id" class="form-label">Departemen</label>
                    <select class="form-select" id="departemen_id" name="departemen_id">
                        <option value="0">Semua Departemen</option>
                        <?php foreach ($departemen_filter_options as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['id']); ?>" <?php echo ($search_departemen_id == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['nama_departemen']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="skill_id" class="form-label">Skill</label>
                    <select class="form-select" id="skill_id" name="skill_id">
                        <option value="0">Semua Skill</option>
                        <?php foreach ($skill_filter_options as $skill): ?>
                            <option value="<?php echo htmlspecialchars($skill['id']); ?>" <?php echo ($search_skill_id == $skill['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($skill['nama_skill']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="keyword" class="form-label">Kata Kunci</label>
                    <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($search_keyword); ?>" placeholder="Cari judul, deskripsi, dosen...">
                </div>
                <div class="col-md-auto mt-auto">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <a href="<?php echo BASE_URL; ?>lowongan_list.php" class="btn btn-secondary ms-2">Reset Filter</a>
                </div>
            </form>
        </div>
    </div>


    <?php if (!empty($lowongan_list)): ?>
        <?php foreach ($lowongan_list as $lowongan): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($lowongan['nama_lowongan']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted">
                        Oleh: <?php echo htmlspecialchars($lowongan['nama_dosen']); ?> (<?php echo htmlspecialchars($lowongan['nama_departemen']); ?>)
                    </h6>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($lowongan['deskripsi'], 0, 250))); ?>...</p>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Jenis:</strong> <?php echo htmlspecialchars($lowongan['jenis']); ?></li>
                        <li class="list-group-item"><strong>Batas Lamar:</strong> <span class="badge bg-danger"><?php echo date('d M Y', strtotime($lowongan['deadline'])); ?></span></li>
                        <li class="list-group-item"><strong>Skill Dibutuhkan:</strong> <?php echo $lowongan['skill_yang_dibutuhkan'] ? htmlspecialchars($lowongan['skill_yang_dibutuhkan']) : 'Tidak ada'; ?></li>
                    </ul>
                    <a href="#" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $lowongan['lowongan_id']; ?>">Lihat Detail</a>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'mahasiswa'): ?>
                        <a href="#" class="btn btn-success btn-sm ms-2">Lamar Sekarang</a> <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-success btn-sm ms-2">Login untuk Lamar</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="modal fade" id="detailModal<?php echo $lowongan['lowongan_id']; ?>" tabindex="-1" aria-labelledby="detailModalLabel<?php echo $lowongan['lowongan_id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="detailModalLabel<?php echo $lowongan['lowongan_id']; ?>"><?php echo htmlspecialchars($lowongan['nama_lowongan']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Dosen:</strong> <?php echo htmlspecialchars($lowongan['nama_dosen']); ?> (<?php echo htmlspecialchars($lowongan['nama_departemen']); ?>)</p>
                            <p><strong>Deskripsi:</strong><br><?php echo nl2br(htmlspecialchars($lowongan['deskripsi'])); ?></p>
                            <p><strong>Jumlah Diterima:</strong> <?php echo htmlspecialchars($lowongan['jumlah_diterima']); ?></p>
                            <p><strong>Jenis:</strong> <?php echo htmlspecialchars($lowongan['jenis']); ?></p>
                            <p><strong>Tanggal Posting:</strong> <?php echo date('d M Y', strtotime($lowongan['tanggal_post'])); ?></p>
                            <p><strong>Deadline:</strong> <?php echo date('d M Y', strtotime($lowongan['deadline'])); ?></p>
                            <p><strong>Skill Dibutuhkan:</strong> <?php echo $lowongan['skill_yang_dibutuhkan'] ? htmlspecialchars($lowongan['skill_yang_dibutuhkan']) : 'Tidak ada'; ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'mahasiswa'): ?>
                                <a href="#" class="btn btn-success">Lamar Sekarang</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info" role="alert">
            Tidak ada lowongan aktif yang ditemukan dengan kriteria tersebut.
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>