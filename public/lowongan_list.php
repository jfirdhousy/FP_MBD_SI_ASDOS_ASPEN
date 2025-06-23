<?php
session_start();

// Pastikan BASE_URL sudah didefinisikan di sini
if (!defined('BASE_URL')) {
    define('BASE_URL', '/FP_MBD_SI_ASDOS_ASPEN/public/'); // Sesuaikan dengan path proyek Anda
}

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

// --- Konfigurasi Paginasi ---
$items_per_page = 5; // Jumlah lowongan per halaman

// --- Fungsi Helper untuk Paginasi ---
function get_pagination_params_lowongan($conn, $base_query_for_count, $params_for_count, $page_param_name, $items_per_page) {
    $current_page = isset($_GET[$page_param_name]) ? (int)$_GET[$page_param_name] : 1;
    if ($current_page < 1) $current_page = 1;

    // Hitung total item yang cocok dengan filter
    $stmt_count = $conn->prepare($base_query_for_count);
    // BIND PARAMETER DI SINI UNTUK QUERY COUNT
    foreach ($params_for_count as $param => $value) {
        // Penting: bindValue() agar tipe data bisa otomatis dideteksi atau diatur manual jika perlu
        $stmt_count->bindValue($param, $value); 
    }
    $stmt_count->execute(); // BARIS 25 YANG MUNCUL ERROR
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

// Fungsi Helper untuk render link paginasi (tidak ada perubahan pada fungsi ini)
function render_pagination_links_lowongan($pagination_params, $page_param_name) {
    $current_page = $pagination_params['current_page'];
    $total_pages = $pagination_params['total_pages'];

    if ($total_pages <= 1) return;

    $query_params = $_GET;
    unset($query_params[$page_param_name]);

    $base_url = BASE_URL . 'lowongan_list.php?';
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
        if ($start_page > 2) { $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
    }

    for ($i = $start_page; $i <= $end_page; $i++) {
        $output .= '<li class="page-item ' . ($current_page == $i ? 'active' : '') . '">';
        $output .= '<a class="page-link" href="' . $base_url . $page_param_name . '=' . $i . '">' . $i . '</a>';
        $output .= '</li>';
    }

    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) { $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
        $output .= '<li class="page-item"><a class="page-link" href="' . $base_url . $page_param_name . '=' . $total_pages . '">' . $total_pages . '</a></li>';
    }

    $output .= '<li class="page-item ' . ($current_page >= $total_pages ? 'disabled' : '') . '">';
    $output .= '<a class="page-link" href="' . $base_url . $page_param_name . '=' . ($current_page + 1) . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>';
    $output .= '</li>';

    $output .= '</ul></nav>';
    echo $output;
}


$search_departemen_id = isset($_GET['departemen_id']) ? (int)$_GET['departemen_id'] : 0;
$search_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$search_skill_id = isset($_GET['skill_id']) ? (int)$_GET['skill_id'] : 0;

// --- Opsi Sorting ---
$sort_by = $_GET['sort_by'] ?? 'tanggal_post'; // Default sort
$sort_order = $_GET['sort_order'] ?? 'DESC'; // Default order

// Pastikan kolom sorting valid untuk mencegah SQL Injection
$valid_sort_columns = ['tanggal_post', 'deadline', 'nama_lowongan', 'nama_dosen', 'departemen']; // Kolom dari VIEW_LOWONGAN_ACTIVE
$valid_sort_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $valid_sort_columns)) {
    $sort_by = 'tanggal_post';
}
if (!in_array(strtoupper($sort_order), $valid_sort_orders)) {
    $sort_order = 'DESC';
}

// Ambil daftar departemen untuk filter
$departemen_filter_options = [];
try {
    $stmt_dept_filter = $conn->query("SELECT id, nama_departemen FROM departemen ORDER BY nama_departemen ASC");
    $departemen_filter_options = $stmt_dept_filter->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
}

// Ambil daftar skill untuk filter
$skill_filter_options = [];
try {
    $stmt_skill_filter = $conn->query("SELECT id, nama_skill FROM skill ORDER BY nama_skill ASC");
    $skill_filter_options = $stmt_skill_filter->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
}

// --- Membangun Query Dasar untuk COUNT dan SELECT MENGGUNAKAN VIEW_LOWONGAN_ACTIVE ---
// Penting: Gunakan NAMA PARAMETER YANG SAMA di query SELECT dan COUNT
$base_query_select = "
    SELECT vla.*,
           GROUP_CONCAT(DISTINCT s.nama_skill ORDER BY s.nama_skill SEPARATOR ', ') AS skill_yang_dibutuhkan
    FROM VIEW_LOWONGAN_ACTIVE vla
    LEFT JOIN skill_lowongan sl ON vla.lowongan_id = sl.lowongan_id
    LEFT JOIN skill s ON sl.skill_id = s.id
    WHERE 1=1 "; 

$base_query_count = "
    SELECT COUNT(DISTINCT vla.lowongan_id)
    FROM VIEW_LOWONGAN_ACTIVE vla
    LEFT JOIN skill_lowongan sl ON vla.lowongan_id = sl.lowongan_id
    LEFT JOIN skill s ON sl.skill_id = s.id
    WHERE 1=1 ";

$query_params_for_binding = [];

if ($search_departemen_id > 0) {
    $base_query_select .= " AND vla.departemen = (SELECT nama_departemen FROM departemen WHERE id = :departemen_id_param) "; // Nama parameter diubah
    $base_query_count .= " AND vla.departemen = (SELECT nama_departemen FROM departemen WHERE id = :departemen_id_param) "; // Nama parameter diubah
    $query_params_for_binding[':departemen_id_param'] = $search_departemen_id; // Nama parameter diubah
}
if ($search_skill_id > 0) {
    $base_query_select .= " AND vla.lowongan_id IN (SELECT lowongan_id FROM skill_lowongan WHERE skill_id = :skill_id_param) "; // Nama parameter diubah
    $base_query_count .= " AND vla.lowongan_id IN (SELECT lowongan_id FROM skill_lowongan WHERE skill_id = :skill_id_param) "; // Nama parameter diubah
    $query_params_for_binding[':skill_id_param'] = $search_skill_id; // Nama parameter diubah
}
if (!empty($search_keyword)) {
    $base_query_select .= " AND (vla.nama_lowongan LIKE :keyword_param OR vla.deskripsi LIKE :keyword_param OR vla.nama_dosen LIKE :keyword_param) "; // Nama parameter diubah
    $base_query_count .= " AND (vla.nama_lowongan LIKE :keyword_param OR vla.deskripsi LIKE :keyword_param OR vla.nama_dosen LIKE :keyword_param) "; // Nama parameter diubah
    $like_keyword = '%' . $search_keyword . '%';
    $query_params_for_binding[':keyword_param'] = $like_keyword; // Nama parameter diubah
}

$base_query_select .= " GROUP BY vla.lowongan_id "; // Group by lowongan ID dari VIEW

// --- Paginasi ---
// Fungsi get_pagination_params_lowongan akan menerima $base_query_count dan $query_params_for_binding
$pagination_params = get_pagination_params_lowongan($conn, $base_query_count, $query_params_for_binding, 'page', $items_per_page);
$offset = $pagination_params['offset'];
$limit = $pagination_params['limit'];


// --- Final Query SELECT dengan Sorting dan Paginasi ---
$final_query = $base_query_select . " 
                ORDER BY " . $sort_by . " " . $sort_order . "
                LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($final_query);

// Bind semua parameter filter
foreach ($query_params_for_binding as $param => $value) {
    $stmt->bindValue($param, $value);
}
// Bind parameter paginasi (limit dan offset)
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$lowongan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Daftar Lowongan Asisten</h1>

    <?php
    // Cek apakah user adalah admin
    $is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
    if ($is_admin):
    ?>
    <div class="d-flex justify-content-end mb-3">
        <a href="<?php echo BASE_URL; ?>lowongan_riwayat.php" class="btn btn-outline-info">
            <i class="bi bi-clock-history me-1"></i> Lihat Riwayat Lowongan
        </a>
    </div>
    <?php endif; ?>
    
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Filter & Urutkan Lowongan</h5>
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
                
                <div class="col-md-4">
                    <label for="sort_by" class="form-label">Urutkan Berdasarkan</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="tanggal_post" <?php echo ($sort_by == 'tanggal_post' ? 'selected' : ''); ?>>Tanggal Posting</option>
                        <option value="deadline" <?php echo ($sort_by == 'deadline' ? 'selected' : ''); ?>>Deadline</option>
                        <option value="nama_lowongan" <?php echo ($sort_by == 'nama_lowongan' ? 'selected' : ''); ?>>Nama Lowongan</option>
                        <option value="nama_dosen" <?php echo ($sort_by == 'nama_dosen' ? 'selected' : ''); ?>>Dosen Pembuat</option>
                        <option value="departemen" <?php echo ($sort_by == 'departemen' ? 'selected' : ''); ?>>Departemen</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="sort_order" class="form-label">Urutan</label>
                    <select class="form-select" id="sort_order" name="sort_order">
                        <option value="DESC" <?php echo ($sort_order == 'DESC' ? 'selected' : ''); ?>>Terbaru / Z-A</option>
                        <option value="ASC" <?php echo ($sort_order == 'ASC' ? 'selected' : ''); ?>>Terlama / A-Z</option>
                    </select>
                </div>

                <div class="col-md-auto mt-auto">
                    <button type="submit" class="btn btn-primary">Terapkan Filter & Urutkan</button>
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
                        Oleh: <?php echo htmlspecialchars($lowongan['nama_dosen']); ?> (<?php echo htmlspecialchars($lowongan['departemen']); ?>)
                    </h6>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($lowongan['deskripsi'])); ?>...</p> <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Jenis:</strong> <?php echo htmlspecialchars($lowongan['jenis_lowongan']); ?></li>
                        <li class="list-group-item"><strong>Batas Lamar:</strong> <span class="badge bg-danger"><?php echo date('d M Y', strtotime($lowongan['deadline'])); ?></span></li>
                        <li class="list-group-item"><strong>Skill Dibutuhkan:</strong> <?php echo $lowongan['skill_yang_dibutuhkan'] ? htmlspecialchars($lowongan['skill_yang_dibutuhkan']) : 'Tidak ada'; ?></li>
                    </ul>
                    <a href="<?php echo BASE_URL; ?>detail_lowongan.php?id=<?php echo $lowongan['lowongan_id']; ?>" class="btn btn-info btn-sm">Lihat Detail</a>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'mahasiswa'): ?>
                        <a href="#" class="btn btn-success btn-sm ms-2">Lamar Sekarang</a>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-success btn-sm ms-2">Login untuk Lamar</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php render_pagination_links_lowongan($pagination_params, 'page'); ?>

    <?php else: ?>
        <div class="alert alert-info" role="alert">
            Tidak ada lowongan aktif yang ditemukan dengan kriteria tersebut.
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>