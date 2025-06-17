<?php
// public/admin_dashboard.php
session_start();

define('BASE_URL', '/FP_MBD_SI_ASDOS_ASPEN/public/');

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
$items_per_page = 10; // Jumlah item per halaman

// Fungsi Helper untuk Paginasi
function get_pagination_params($conn, $table_name, $id_column, $page_param_name, $items_per_page, $extra_where = "") {
    $current_page = isset($_GET[$page_param_name]) ? (int)$_GET[$page_param_name] : 1;
    if ($current_page < 1) $current_page = 1;

    // Hitung total item
    $count_query = "SELECT COUNT(*) FROM " . $table_name . " " . $extra_where;
    $stmt_count = $conn->prepare($count_query);
    $stmt_count->execute();
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

// Fungsi Helper untuk render link paginasi
function render_pagination_links($pagination_params, $page_param_name, $current_active_tab_param = null, $current_active_tab_value = null) {
    $current_page = $pagination_params['current_page'];
    $total_pages = $pagination_params['total_pages'];

    if ($total_pages <= 1) return;

    // Base URL for pagination links, maintaining other GET parameters
    $query_params = $_GET;
    // Remove current page parameter for the base URL construction
    unset($query_params[$page_param_name]);

    $base_url = BASE_URL . 'admin_dashboard.php?';

    // Add back the active tab parameter if provided
    if ($current_active_tab_param && $current_active_tab_value) {
        $query_params[$current_active_tab_param] = $current_active_tab_value;
    }
    
    // Build the query string from remaining parameters
    $base_query_string = http_build_query($query_params);
    if (!empty($base_query_string)) {
        $base_url .= $base_query_string . '&';
    }


    $output = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

    // Previous button
    $output .= '<li class="page-item ' . ($current_page <= 1 ? 'disabled' : '') . '">';
    $output .= '<a class="page-link" href="' . $base_url . $page_param_name . '=' . ($current_page - 1) . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>';
    $output .= '</li>';

    // Page numbers
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

    // Next button
    $output .= '<li class="page-item ' . ($current_page >= $total_pages ? 'disabled' : '') . '">';
    $output .= '<a class="page-link" href="' . $base_url . $page_param_name . '=' . ($current_page + 1) . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>';
    $output .= '</li>';

    $output .= '</ul></nav>';
    echo $output;
}


// --- LOGIKA MANAJEMEN DEPARTEMEN (Anggota 4) ---
// Tambah Departemen
if (isset($_POST['add_departemen'])) {
    $nama_departemen = trim($_POST['nama_departemen']);
    if (!empty($nama_departemen)) {
        try {
            $stmt = $conn->prepare("INSERT INTO departemen (nama_departemen) VALUES (:nama_departemen)");
            $stmt->bindParam(':nama_departemen', $nama_departemen);
            if ($stmt->execute()) {
                $message = "Departemen '$nama_departemen' berhasil ditambahkan.";
            } else {
                $error = "Gagal menambahkan departemen.";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // Duplicate entry error code
                $error = "Departemen '$nama_departemen' sudah ada.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Nama departemen tidak boleh kosong.";
    }
}

// Hapus Departemen
if (isset($_GET['action']) && $_GET['action'] == 'delete_departemen' && isset($_GET['id'])) {
    $departemen_id = $_GET['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM departemen WHERE id = :id");
        $stmt->bindParam(':id', $departemen_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $message = "Departemen berhasil dihapus.";
        } else {
            $error = "Gagal menghapus departemen.";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == '45000') { // Custom SQLSTATE dari trigger
            $error = $e->getMessage();
        } else if ($e->getCode() == '23000') { // Foreign key constraint violation (fallback)
            $error = "Departemen tidak bisa dihapus karena masih ada Dosen/Mahasiswa yang berafiliasi.";
        } else {
            $error = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}

// --- LOGIKA HAPUS PENGGUNA ---
if (isset($_GET['action']) && $_GET['action'] == 'delete_user' && isset($_GET['id']) && isset($_GET['role'])) {
    $user_id = $_GET['id'];
    $user_role_to_delete = $_GET['role'];
    $delete_success = false;

    $table_to_delete = '';
    $id_column_to_delete = '';
    $user_display_name = ''; // Untuk pesan konfirmasi

    switch ($user_role_to_delete) {
        case 'dosen':
            $table_to_delete = 'dosen';
            $id_column_to_delete = 'nip';
            try {
                $stmt_name = $conn->prepare("SELECT nama_dosen FROM dosen WHERE nip = :id");
                $stmt_name->bindParam(':id', $user_id);
                $stmt_name->execute();
                $user_display_name = $stmt_name->fetchColumn();
            } catch (PDOException $e) {}
            break;
        case 'mahasiswa':
            $table_to_delete = 'mahasiswa';
            $id_column_to_delete = 'nrp';
            try {
                $stmt_name = $conn->prepare("SELECT nama_mahasiswa FROM mahasiswa WHERE nrp = :id");
                $stmt_name->bindParam(':id', $user_id);
                $stmt_name->execute();
                $user_display_name = $stmt_name->fetchColumn();
            } catch (PDOException $e) {}
            break;
        case 'admin':
            $table_to_delete = 'admin';
            $id_column_to_delete = 'id';
            try {
                $stmt_name = $conn->prepare("SELECT nama_admin FROM admin WHERE id = :id");
                $stmt_name->bindParam(':id', $user_id, PDO::PARAM_INT);
                $stmt_name->execute();
                $user_display_name = $stmt_name->fetchColumn();
            } catch (PDOException $e) {}
            break;
        default:
            $error = "Tipe pengguna tidak valid untuk dihapus.";
            break;
    }

    if (!empty($table_to_delete) && !empty($id_column_to_delete)) {
        try {
            // Cek apakah admin mencoba menghapus dirinya sendiri
            if ($user_role_to_delete === 'admin' && $user_id == $_SESSION['user_id']) {
                $error = "Anda tidak dapat menghapus akun admin Anda sendiri.";
            } else {
                $stmt = $conn->prepare("DELETE FROM {$table_to_delete} WHERE {$id_column_to_delete} = :id");
                if ($user_role_to_delete === 'admin') {
                    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
                } else {
                    $stmt->bindParam(':id', $user_id, PDO::PARAM_STR);
                }
                
                if ($stmt->execute()) {
                    $message = ucfirst($user_role_to_delete) . " '" . htmlspecialchars($user_display_name) . "' berhasil dihapus.";
                    $delete_success = true;
                } else {
                    $error = "Gagal menghapus " . ucfirst($user_role_to_delete) . ".";
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // Foreign key constraint violation
                $error = ucfirst($user_role_to_delete) . " '" . htmlspecialchars($user_display_name) . "' tidak bisa dihapus karena masih memiliki data terkait (misal: lowongan, lamaran, skill).";
            } else {
                $error = "Terjadi kesalahan database: " . $e->getMessage();
            }
        }
    }
}


// --- Paginasi Departemen ---
$departemen_pagination = get_pagination_params($conn, 'departemen', 'id', 'page_dept', $items_per_page);
$departemen_offset = $departemen_pagination['offset'];
$departemen_limit = $departemen_pagination['limit'];

$departemen_list = [];
try {
    $query_departemen = "SELECT departemen_id, nama_departemen, jumlah_dosen, jumlah_mahasiswa 
                         FROM VIEW_INFORMASI_DEPARTEMEN_LENGKAP 
                         ORDER BY nama_departemen ASC 
                         LIMIT :limit OFFSET :offset";
    $stmt_departemen = $conn->prepare($query_departemen);
    $stmt_departemen->bindParam(':limit', $departemen_limit, PDO::PARAM_INT);
    $stmt_departemen->bindParam(':offset', $departemen_offset, PDO::PARAM_INT);
    $stmt_departemen->execute();
    $departemen_list = $stmt_departemen->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= " Gagal memuat daftar departemen: " . $e->getMessage();
}

// --- LOGIKA MENAMPILKAN DAFTAR PENGGUNA ---
$show_users = $_GET['show_users'] ?? 'dosen'; // Default: tampilkan dosen
$valid_user_views = ['dosen', 'mahasiswa', 'admin'];

if (!in_array($show_users, $valid_user_views)) {
    $show_users = 'dosen'; // Fallback jika parameter tidak valid
}

// Inisialisasi daftar pengguna yang akan ditampilkan
$current_user_list = [];
$current_pagination_params = [];
$current_page_param_name = '';
$current_table_name_for_count = '';

// Variabel untuk tombol "Tambah Akun" yang dinamis
$add_account_link = '';
$add_account_button_text = '';

switch ($show_users) {
    case 'dosen':
        $current_table_name_for_count = 'dosen';
        $current_page_param_name = 'page_dosen';
        $current_pagination_params = get_pagination_params($conn, $current_table_name_for_count, 'nip', $current_page_param_name, $items_per_page);
        
        $add_account_link = BASE_URL . 'register_dosen.php';
        $add_account_button_text = 'Tambah Dosen';

        try {
            // Modifikasi query SELECT untuk dosen: sertakan panggilan fungsi HITUNG_LOWONGAN_PER_DOSEN
            $stmt = $conn->prepare("SELECT d.nip, d.nama_dosen, d.email, d.no_telp, dep.nama_departemen, 
                                            HITUNG_LOWONGAN_PER_DOSEN(d.nip) AS total_lowongan_dibuat
                                    FROM dosen d 
                                    JOIN departemen dep ON d.departemen_id = dep.id 
                                    ORDER BY d.nama_dosen ASC 
                                    LIMIT :limit OFFSET :offset");
            $stmt->bindParam(':limit', $current_pagination_params['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $current_pagination_params['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $current_user_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error .= " Gagal memuat daftar dosen: " . $e->getMessage();
        }
        break;

    case 'mahasiswa':
        $current_table_name_for_count = 'mahasiswa';
        $current_page_param_name = 'page_mhs';
        $current_pagination_params = get_pagination_params($conn, $current_table_name_for_count, 'nrp', $current_page_param_name, $items_per_page);

        $add_account_link = BASE_URL . 'register_mahasiswa.php';
        $add_account_button_text = 'Tambah Mahasiswa';

        try {
            $stmt = $conn->prepare("SELECT m.nrp, m.nama_mahasiswa, m.email, m.no_telp, dep.nama_departemen 
                                    FROM mahasiswa m 
                                    JOIN departemen dep ON m.departemen_id = dep.id 
                                    ORDER BY m.nama_mahasiswa ASC 
                                    LIMIT :limit OFFSET :offset");
            $stmt->bindParam(':limit', $current_pagination_params['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $current_pagination_params['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $current_user_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error .= " Gagal memuat daftar mahasiswa: " . $e->getMessage();
        }
        break;

    case 'admin':
        $current_table_name_for_count = 'admin';
        $current_page_param_name = 'page_admin';
        // Tambahkan kondisi WHERE untuk mengecualikan admin yang sedang login
        $current_pagination_params = get_pagination_params($conn, $current_table_name_for_count, 'id', $current_page_param_name, $items_per_page, "WHERE id <> " . $_SESSION['user_id']);
        
        $add_account_link = BASE_URL . 'register_admin.php';
        $add_account_button_text = 'Tambah Admin';

        try {
            // Query juga harus mengecualikan admin yang sedang login
            $stmt = $conn->prepare("SELECT id, nama_admin, email FROM admin WHERE id <> :admin_id ORDER BY id ASC LIMIT :limit OFFSET :offset");
            $stmt->bindParam(':admin_id', $_SESSION['user_id'], PDO::PARAM_INT); // Bind ID admin yang login
            $stmt->bindParam(':limit', $current_pagination_params['limit'], PDO::PARAM_INT);
            $stmt->bindParam(':offset', $current_pagination_params['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $current_user_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error .= " Gagal memuat daftar admin: " . $e->getMessage();
        }
        break;
}


include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Dashboard Admin</h1>
    <p class="lead">Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>!</p>

    <?php if ($message): ?>
        <div class="alert alert-success" role="alert"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0 text-black">Manajemen Departemen</h5>
                </div>
                <div class="card-body">
                    <h6 class="card-subtitle mb-3 text-muted">Tambah Departemen Baru</h6>
                    <form action="<?php echo BASE_URL; ?>admin_dashboard.php" method="POST" class="mb-4">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="Nama Departemen" name="nama_departemen" required>
                            <button class="btn btn-primary" type="submit" name="add_departemen">Tambah</button>
                        </div>
                    </form>

                    <h6 class="card-subtitle mb-3 text-muted mt-4">Daftar Departemen Terdaftar</h6>
                    <?php if (!empty($departemen_list)): ?>
                        <ul class="list-group mb-3">
                            <?php foreach ($departemen_list as $dept): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <?php echo htmlspecialchars($dept['nama_departemen']); ?>
                                        <span class="badge bg-secondary ms-2">Dosen: <?php echo $dept['jumlah_dosen'] ?? 0; ?> | Mhs: <?php echo $dept['jumlah_mahasiswa'] ?? 0; ?></span>
                                    </span>
                                    <a href="<?php echo BASE_URL; ?>admin_dashboard.php?action=delete_departemen&id=<?php echo $dept['departemen_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus departemen ini? Jika ada dosen/mahasiswa yang berafiliasi, penghapusan akan gagal.');">Hapus</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php render_pagination_links($departemen_pagination, 'page_dept', 'show_users', $show_users); ?>
                    <?php else: ?>
                        <p>Belum ada departemen yang terdaftar.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0 text-black">Manajemen Akun Pengguna</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <select class="form-select w-auto me-2" id="user_type_select" onchange="window.location.href = '<?php echo BASE_URL; ?>admin_dashboard.php?show_users=' + this.value;">
                            <option value="dosen" <?php echo ($show_users == 'dosen' ? 'selected' : ''); ?>>Dosen</option>
                            <option value="mahasiswa" <?php echo ($show_users == 'mahasiswa' ? 'selected' : ''); ?>>Mahasiswa</option>
                            <option value="admin" <?php echo ($show_users == 'admin' ? 'selected' : ''); ?>>Admin</option>
                        </select>
                        <a href="<?php echo $add_account_link; ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-person-plus-fill me-1"></i> <?php echo $add_account_button_text; ?>
                        </a>
                    </div>

                    <h6 class="card-subtitle mb-3 text-muted">Daftar <?php echo ucfirst($show_users); ?></h6>
                    
                    <?php if (!empty($current_user_list)): ?>
                        <ul class="list-group mb-3">
                            <?php foreach ($current_user_list as $user): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($show_users == 'dosen'): ?>
                                            <strong><?php echo htmlspecialchars($user['nama_dosen']); ?></strong> (NIP: <?php echo htmlspecialchars($user['nip']); ?>)<br>
                                            Departemen: <?php echo htmlspecialchars($user['nama_departemen']); ?><br>
                                            Email: <?php echo htmlspecialchars($user['email']); ?><br>
                                            Telp: <?php echo htmlspecialchars($user['no_telp']); ?><br>
                                            <span class="badge bg-secondary ms-2">Lowongan Dibuat: <?php echo $user['total_lowongan_dibuat'] ?? 0; ?></span>
                                        <?php elseif ($show_users == 'mahasiswa'): ?>
                                            <strong><?php echo htmlspecialchars($user['nama_mahasiswa']); ?></strong> (NRP: <?php echo htmlspecialchars($user['nrp']); ?>)<br>
                                            Departemen: <?php echo htmlspecialchars($user['nama_departemen']); ?><br>
                                            Email: <?php echo htmlspecialchars($user['email']); ?><br>
                                            Telp: <?php echo htmlspecialchars($user['no_telp']); ?><br>
                                        <?php elseif ($show_users == 'admin'): ?>
                                            <strong><?php echo htmlspecialchars($user['nama_admin']); ?></strong> (ID: <?php echo htmlspecialchars($user['id']); ?>)<br>
                                            Email: <?php echo htmlspecialchars($user['email']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ms-auto">
                                        <a href="<?php echo BASE_URL; ?>edit_profile.php?id=<?php echo htmlspecialchars($user[$show_users === 'admin' ? 'id' : ($show_users === 'dosen' ? 'nip' : 'nrp')]); ?>&role=<?php echo $show_users; ?>" class="btn btn-sm btn-outline-primary me-2">Edit</a>
                                        <a href="<?php echo BASE_URL; ?>admin_dashboard.php?action=delete_user&id=<?php echo htmlspecialchars($user[$show_users === 'admin' ? 'id' : ($show_users === 'dosen' ? 'nip' : 'nrp')]) . '&role=' . $show_users; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus ' . htmlspecialchars($user[$show_users === 'admin' ? 'nama_admin' : ($show_users === 'dosen' ? 'nama_dosen' : 'nama_mahasiswa')]) . ' ini? Aksi ini tidak dapat dibatalkan dan mungkin menghapus data terkait.');">Delete</a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php render_pagination_links($current_pagination_params, $current_page_param_name, 'show_users', $show_users); ?>
                    <?php else: ?>
                        <p>Belum ada <?php echo $show_users; ?> yang terdaftar.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>