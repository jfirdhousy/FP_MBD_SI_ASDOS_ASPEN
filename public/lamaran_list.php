<?php
// public/lamaran_list.php
session_start();

// Periksa apakah admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$message = '';
$error = '';

// --- Konfigurasi Paginasi ---
$items_per_page = 10; // Jumlah lamaran per halaman

// Fungsi Helper untuk Paginasi (Anda bisa memindahkan ini ke file helper terpisah jika ada)
// (Ini adalah duplikasi dari admin_dashboard.php, sebaiknya di refactor ke common_functions.php)
function get_pagination_params_lamaran($conn, $base_query, $params, $page_param_name, $items_per_page) {
    $current_page = isset($_GET[$page_param_name]) ? (int)$_GET[$page_param_name] : 1;
    if ($current_page < 1) $current_page = 1;

    // Untuk hitung total, kita hanya perlu query COUNT(*)
    // Hapus ORDER BY, LIMIT, OFFSET dari query utama untuk count
    $count_query = preg_replace('/ORDER BY(.*?)LIMIT(.*?)OFFSET(.*?)$/is', '', $base_query);
    $count_query = "SELECT COUNT(*) FROM (" . $count_query . ") AS count_alias";

    $stmt_count = $conn->prepare($count_query);
    // Bind parameter untuk count query. Hanya bind parameter yang ada di $base_query
    $clean_params = $params;
    unset($clean_params[':limit']); // Hapus limit/offset karena tidak diperlukan di count_query
    unset($clean_params[':offset']);
    $stmt_count->execute($clean_params); // Bind parameter untuk count query
    $total_items = $stmt_count->fetchColumn();

    $total_pages = ceil($total_items / $items_per_page);
    if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

    $offset = ($current_page - 1) * $items_per_page;

    return [
        'current_page' => $current_page,
        'total_items' => $total_items,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'limit' => $items_per_page,
    ];
}

// Fungsi Helper untuk render link paginasi (dari admin_dashboard.php)
function render_pagination_links_lamaran($pagination_params, $page_param_name) {
    $current_page = $pagination_params['current_page'];
    $total_pages = $pagination_params['total_pages'];

    if ($total_pages <= 1) return;

    $query_params = $_GET;
    unset($query_params[$page_param_name]);

    $base_url = BASE_URL . 'lamaran_list.php?';
    $base_query_string = http_build_query($query_params);
    if (!empty($base_query_string)) {
        $base_url .= $base_query_string . '&';
    }

    $output = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

    $output .= '<li class="page-item ' . ($current_page <= 1 ? 'disabled' : '') . '">';
    $output .= '<a class="page-link" href="' . $base_url . $page_param_name . '=' . ($current_page - 1) . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>';
    $output .= '</li>';

    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);

    if ($start_page > 1) {
        $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . $page_param_name . '=1">1</a></li>';
        if ($start_page > 2) {
            $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start_page; $i <= $end_page; $i++) {
        $output .= '<li class="page-item ' . ($current_page == $i ? 'active' : '') . '">';
        $output .= '<a class="page-link" href="' . $base_url . $page_param_name . '=' . $i . '">' . $i . '</a>';
        $output .= '</li>';
    }

    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . $page_param_name . '=' . $total_pages . '">' . $total_pages . '</a></li>';
    }

    $output .= '<li class="page-item ' . ($current_page >= $total_pages ? 'disabled' : '') . '">';
    $output .= '<a class="page-link" href="' . $base_url . $page_param_name . '=' . ($current_page + 1) . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>';
    $output .= '</li>';

    $output .= '</ul></nav>';
    echo $output;
}


// --- LOGIKA FILTER LAMARAN ---
$filter_status = $_GET['status'] ?? '';
$filter_lowongan_id = isset($_GET['lowongan_id']) ? (int)$_GET['lowongan_id'] : 0;
$filter_mahasiswa_nrp = $_GET['mahasiswa_nrp'] ?? '';
$filter_dosen_nip = $_GET['dosen_nip'] ?? '';
$filter_keyword = $_GET['keyword'] ?? '';

// Ambil daftar Lowongan untuk filter (Anggota 1/Kolaborasi)
$lowongan_options = [];
try {
    $stmt_lowongan = $conn->query("SELECT id, nama_lowongan FROM lowongan ORDER BY nama_lowongan ASC");
    $lowongan_options = $stmt_lowongan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* handle error */ }

// Ambil daftar Dosen untuk filter (Anggota 1)
$dosen_options = [];
try {
    $stmt_dosen = $conn->query("SELECT nip, nama_dosen FROM dosen ORDER BY nama_dosen ASC");
    $dosen_options = $stmt_dosen->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* handle error */ }

// --- Query Utama untuk Lamaran ---
$query_lamaran_base = "
    SELECT
        l.id AS lamaran_id,
        l.tanggal_melamar,
        l.status_lamaran,
        l.cv_url,
        l.transkrip_url,
        l.note_dosen,
        low.nama_lowongan,
        low.jenis AS lowongan_jenis,
        low.deadline AS lowongan_deadline,
        mhs.nama_mahasiswa,
        mhs.nrp AS mahasiswa_nrp,
        dos.nama_dosen AS dosen_pembuat,
        dos.nip AS dosen_nip,
        dept.nama_departemen
    FROM
        lamaran l
    JOIN
        lowongan low ON l.lowongan_id = low.id
    JOIN
        mahasiswa mhs ON l.mahasiswa_nrp = mhs.nrp
    JOIN
        dosen dos ON low.dosen_nip = dos.nip
    JOIN
        departemen dept ON mhs.departemen_id = dept.id
    WHERE 1=1
";

$params_lamaran = [];

if (!empty($filter_status)) {
    $query_lamaran_base .= " AND l.status_lamaran = :status_lamaran ";
    $params_lamaran[':status_lamaran'] = $filter_status;
}
if ($filter_lowongan_id > 0) {
    $query_lamaran_base .= " AND low.id = :lowongan_id ";
    $params_lamaran[':lowongan_id'] = $filter_lowongan_id;
}
if (!empty($filter_mahasiswa_nrp)) {
    $query_lamaran_base .= " AND mhs.nrp = :mahasiswa_nrp ";
    $params_lamaran[':mahasiswa_nrp'] = $filter_mahasiswa_nrp;
}
if (!empty($filter_dosen_nip)) {
    $query_lamaran_base .= " AND dos.nip = :dosen_nip ";
    $params_lamaran[':dosen_nip'] = $filter_dosen_nip;
}
if (!empty($filter_keyword)) {
    $query_lamaran_base .= " AND (low.nama_lowongan LIKE :keyword OR mhs.nama_mahasiswa LIKE :keyword OR dos.nama_dosen LIKE :keyword) ";
    $params_lamaran[':keyword'] = '%' . $filter_keyword . '%';
}

$query_lamaran_base .= " ORDER BY l.tanggal_melamar DESC ";

// --- Paginasi Lamaran ---
$lamaran_pagination_params = get_pagination_params_lamaran($conn, $query_lamaran_base, $params_lamaran, 'page_lamaran', $items_per_page);
$lamaran_offset = $lamaran_pagination_params['offset'];
$lamaran_limit = $lamaran_pagination_params['limit'];

$final_query_lamaran = $query_lamaran_base . " LIMIT :limit OFFSET :offset";

$lamaran_list = [];
try {
    $stmt_lamaran = $conn->prepare($final_query_lamaran);

    // Bind parameters secara eksplisit untuk LIMIT dan OFFSET sebagai INTEGER
    foreach ($params_lamaran as $key => $val) {
        if ($key === ':limit' || $key === ':offset') {
            // Ini tidak akan terjadi karena kita akan bind terpisah
        } else {
            $stmt_lamaran->bindValue($key, $val);
        }
    }
    $stmt_lamaran->bindValue(':limit', (int)$lamaran_limit, PDO::PARAM_INT); // Bind as INT
    $stmt_lamaran->bindValue(':offset', (int)$lamaran_offset, PDO::PARAM_INT); // Bind as INT

    $stmt_lamaran->execute(); // Baris ini adalah yang menyebabkan error sebelumnya, sekarang seharusnya baik-baik saja
    $lamaran_list = $stmt_lamaran->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Gagal memuat daftar lamaran: " . $e->getMessage();
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Daftar Lamaran Asisten (Admin)</h1>
    <p class="lead">Kelola dan pantau semua lamaran yang masuk.</p>

    <?php if ($message): ?>
        <div class="alert alert-success" role="alert"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Filter Lamaran</h5>
        </div>
        <div class="card-body">
            <form action="<?php echo BASE_URL; ?>lamaran_list.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status Lamaran</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="Pending" <?php echo ($filter_status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Diterima" <?php echo ($filter_status == 'Diterima') ? 'selected' : ''; ?>>Diterima</option>
                        <option value="Ditolak" <?php echo ($filter_status == 'Ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                        <option value="Ditinjau" <?php echo ($filter_status == 'Ditinjau') ? 'selected' : ''; ?>>Ditinjau</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="lowongan_id" class="form-label">Nama Lowongan</label>
                    <select class="form-select" id="lowongan_id" name="lowongan_id">
                        <option value="0">Semua Lowongan</option>
                        <?php foreach ($lowongan_options as $low): ?>
                            <option value="<?php echo htmlspecialchars($low['id']); ?>" <?php echo ($filter_lowongan_id == $low['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($low['nama_lowongan']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="dosen_nip" class="form-label">Dosen Pembuat</label>
                    <select class="form-select" id="dosen_nip" name="dosen_nip">
                        <option value="">Semua Dosen</option>
                        <?php foreach ($dosen_options as $dos): ?>
                            <option value="<?php echo htmlspecialchars($dos['nip']); ?>" <?php echo ($filter_dosen_nip == $dos['nip']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dos['nama_dosen']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="keyword" class="form-label">Cari Kata Kunci</label>
                    <input type="text" class="form-control" id="keyword" name="keyword" value="<?php echo htmlspecialchars($filter_keyword); ?>" placeholder="Nama mhs, nama lowongan...">
                </div>
                <div class="col-md-auto mt-auto">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?php echo BASE_URL; ?>lamaran_list.php" class="btn btn-secondary ms-2">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($lamaran_list)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID Lamaran</th>
                        <th>Tanggal Melamar</th>
                        <th>Lowongan</th>
                        <th>Pelamar</th>
                        <th>Dosen</th>
                        <th>Departemen Mhs</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lamaran_list as $lamaran): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lamaran['lamaran_id']); ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($lamaran['tanggal_melamar'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($lamaran['nama_lowongan']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($lamaran['lowongan_jenis']); ?> (Deadline: <?php echo date('d M Y', strtotime($lamaran['lowongan_deadline'])); ?>)</small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($lamaran['nama_mahasiswa']); ?></strong><br>
                                <small class="text-muted">NRP: <?php echo htmlspecialchars($lamaran['mahasiswa_nrp']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($lamaran['dosen_pembuat']); ?></td>
                            <td><?php echo htmlspecialchars($lamaran['nama_departemen']); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                switch ($lamaran['status_lamaran']) {
                                    case 'Pending': $status_class = 'bg-warning'; break;
                                    case 'Diterima': $status_class = 'bg-success'; break;
                                    case 'Ditolak': $status_class = 'bg-danger'; break;
                                    case 'Ditinjau': $status_class = 'bg-info'; break;
                                    default: $status_class = 'bg-secondary'; break;
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($lamaran['status_lamaran']); ?></span>
                            </td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-info" title="Lihat Detail Lamaran">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <a href="<?php echo htmlspecialchars($lamaran['cv_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat CV">
                                    <i class="bi bi-file-earmark-person-fill"></i>
                                </a>
                                <a href="<?php echo htmlspecialchars($lamaran['transkrip_url']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Lihat Transkrip">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                </a>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php render_pagination_links_lamaran($lamaran_pagination_params, 'page_lamaran'); ?>
    <?php else: ?>
        <div class="alert alert-info" role="alert">
            Tidak ada lamaran yang ditemukan dengan filter yang dipilih.
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>