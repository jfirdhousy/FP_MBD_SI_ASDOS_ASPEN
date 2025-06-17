<?php
// public/admin_dashboard.php
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
        // Trigger before_delete_departemen (dibuat di SQL oleh Anggota 4) akan berjalan otomatis
        $stmt = $conn->prepare("DELETE FROM departemen WHERE id = :id");
        $stmt->bindParam(':id', $departemen_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $message = "Departemen berhasil dihapus.";
        } else {
            $error = "Gagal menghapus departemen.";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == '45000') { // Custom SQLSTATE dari trigger
            $error = $e->getMessage(); // Ambil pesan error dari trigger
        } else if ($e->getCode() == '23000') { // Foreign key constraint violation (fallback jika trigger belum sempurna)
            $error = "Departemen tidak bisa dihapus karena masih ada Dosen/Mahasiswa yang berafiliasi.";
        } else {
            $error = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}

// Ambil daftar departemen (menggunakan VIEW_INFORMASI_DEPARTEMEN_LENGKAP dari SQL Anggota 4)
// Pastikan VIEW_INFORMASI_DEPARTEMEN_LENGKAP.sql sudah di-import ke database.
$departemen_list = [];
try {
    $query_departemen = "SELECT departemen_id, nama_departemen, jumlah_dosen, jumlah_mahasiswa FROM VIEW_INFORMASI_DEPARTEMEN_LENGKAP ORDER BY nama_departemen ASC";
    $stmt_departemen = $conn->prepare($query_departemen);
    $stmt_departemen->execute();
    $departemen_list = $stmt_departemen->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= " Gagal memuat daftar departemen: " . $e->getMessage();
}

// Ambil daftar dosen (untuk admin, dari Anggota 1, Anggota 4 tampilkan)
$dosen_list = [];
try {
    $stmt_dosen = $conn->query("SELECT nip, nama_dosen, email, no_telp, d.nama_departemen FROM dosen JOIN departemen d ON dosen.departemen_id = d.id ORDER BY nama_dosen ASC");
    $dosen_list = $stmt_dosen->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= " Gagal memuat daftar dosen: " . $e->getMessage();
}

// Ambil daftar mahasiswa (untuk admin, dari Anggota 2, Anggota 4 tampilkan)
$mahasiswa_list = [];
try {
    $stmt_mahasiswa = $conn->query("SELECT nrp, nama_mahasiswa, email, no_telp, d.nama_departemen FROM mahasiswa JOIN departemen d ON mahasiswa.departemen_id = d.id ORDER BY nama_mahasiswa ASC");
    $mahasiswa_list = $stmt_mahasiswa->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= " Gagal memuat daftar mahasiswa: " . $e->getMessage();
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Dashboard Admin</h1>
    <p class="lead">Selamat datang, **<?php echo htmlspecialchars($_SESSION['user_name']); ?>**! (Role: <?php echo ucfirst($_SESSION['user_role']); ?>)</p>

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
                    <h5 class="card-title mb-0">Manajemen Departemen</h5>
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
                        <ul class="list-group">
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
                    <?php else: ?>
                        <p>Belum ada departemen yang terdaftar.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">Daftar Pengguna Sistem</h5>
                </div>
                <div class="card-body">
                    <h6 class="card-subtitle mb-3 text-muted">Dosen</h6>
                    <?php if (!empty($dosen_list)): ?>
                        <ul class="list-group mb-4">
                            <?php foreach ($dosen_list as $dosen): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($dosen['nama_dosen']); ?></strong> (NIP: <?php echo htmlspecialchars($dosen['nip']); ?>)<br>
                                    Email: <?php echo htmlspecialchars($dosen['email']); ?><br>
                                    Departemen: <?php echo htmlspecialchars($dosen['nama_departemen']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Belum ada dosen yang terdaftar.</p>
                    <?php endif; ?>

                    <h6 class="card-subtitle mb-3 text-muted mt-4">Mahasiswa</h6>
                    <?php if (!empty($mahasiswa_list)): ?>
                        <ul class="list-group">
                            <?php foreach ($mahasiswa_list as $mahasiswa): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($mahasiswa['nama_mahasiswa']); ?></strong> (NRP: <?php echo htmlspecialchars($mahasiswa['nrp']); ?>)<br>
                                    Email: <?php echo htmlspecialchars($mahasiswa['email']); ?><br>
                                    Departemen: <?php echo htmlspecialchars($mahasiswa['nama_departemen']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Belum ada mahasiswa yang terdaftar.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>