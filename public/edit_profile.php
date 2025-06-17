<?php
session_start();

// Pastikan BASE_URL sudah didefinisikan (jika belum di header.php)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/FP_MBD_SI_ASDOS_ASPEN/public/'); // Sesuaikan dengan path proyek Anda
}

// Redirect jika user belum login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$message = '';
$message_type = '';

// --- Tentukan user yang sedang diedit ---
// Secara default, edit profil sendiri
$editing_user_id = $_SESSION['user_id'];
$editing_user_role = $_SESSION['user_role'];
$is_admin_editing_other = false; // Flag untuk menandai admin sedang mengedit user lain

// Jika ada parameter ID dan ROLE di URL, dan user yang login adalah ADMIN
if (isset($_GET['id']) && isset($_GET['role']) && $_SESSION['user_role'] === 'admin') {
    $requested_id = $_GET['id'];
    $requested_role = $_GET['role'];

    // Validasi peran yang diminta
    $valid_roles = ['dosen', 'mahasiswa', 'admin'];
    if (in_array($requested_role, $valid_roles)) {
        // Jika admin mengedit dirinya sendiri melalui link ini, tetap anggap sebagai 'sendiri'
        if ($requested_role === $_SESSION['user_role'] && $requested_id == $_SESSION['user_id']) {
            // Biarkan editing_user_id dan editing_user_role tetap default (dari session)
            $is_admin_editing_other = false;
        } else {
            $editing_user_id = $requested_id;
            $editing_user_role = $requested_role;
            $is_admin_editing_other = true; // Admin mengedit user lain
        }
    } else {
        $message = "Peran pengguna tidak valid.";
        $message_type = 'danger';
        // Fallback to editing own profile if role is invalid
        $editing_user_id = $_SESSION['user_id'];
        $editing_user_role = $_SESSION['user_role'];
        $is_admin_editing_other = false;
    }
}

$profile_data = null;
$table_name = '';
$id_column = '';
$name_column = '';

// Tentukan tabel dan kolom ID berdasarkan peran user yang sedang diedit
switch ($editing_user_role) {
    case 'dosen':
        $table_name = 'dosen';
        $id_column = 'nip';
        $name_column = 'nama_dosen';
        break;
    case 'mahasiswa':
        $table_name = 'mahasiswa';
        $id_column = 'nrp';
        $name_column = 'nama_mahasiswa';
        break;
    case 'admin':
        $table_name = 'admin';
        $id_column = 'id'; // ID Admin biasanya int auto increment
        $name_column = 'nama_admin';
        break;
    default:
        // Ini seharusnya tidak tercapai karena validasi di atas, tapi untuk jaga-jaga
        header("Location: " . BASE_URL . "login.php");
        exit();
}

// --- Ambil Data Profil Saat Ini ---
try {
    $query_select = "SELECT * FROM {$table_name} WHERE {$id_column} = :user_id LIMIT 1";
    $stmt_select = $conn->prepare($query_select);

    // Bind parameter sesuai tipe kolom ID
    if ($editing_user_role === 'admin') {
        $stmt_select->bindParam(':user_id', $editing_user_id, PDO::PARAM_INT);
    } else {
        $stmt_select->bindParam(':user_id', $editing_user_id, PDO::PARAM_STR);
    }
    
    $stmt_select->execute();
    $profile_data = $stmt_select->fetch(PDO::FETCH_ASSOC);

    if (!$profile_data) {
        $message = "Profil tidak ditemukan.";
        $message_type = 'danger';
        // Admin tidak bisa mengedit jika user tidak ditemukan, kembali ke dashboard admin
        if ($is_admin_editing_other) {
            header("Location: " . BASE_URL . "admin_dashboard.php");
            exit();
        }
    }
} catch (PDOException $e) {
    $message = "Terjadi kesalahan database saat mengambil data profil: " . $e->getMessage();
    $message_type = 'danger';
    if ($is_admin_editing_other) {
        header("Location: " . BASE_URL . "admin_dashboard.php");
        exit();
    }
}

// --- Proses Update Profil saat Form Disubmit ---
// Aksi POST akan selalu mengedit profil yang sedang ditampilkan ($editing_user_id, $editing_user_role)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profile_data) {
    // Ambil data dari form
    $new_email = $_POST['email'] ?? $profile_data['email'];
    $new_name = $_POST['nama'] ?? $profile_data[$name_column];
    $new_phone = $_POST['no_telp'] ?? ($profile_data['no_telp'] ?? null);
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi dasar
    if (empty($new_email) || empty($new_name)) {
        $message = "Email dan Nama tidak boleh kosong.";
        $message_type = 'danger';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $message = "Konfirmasi password tidak cocok.";
        $message_type = 'danger';
    } else {
        try {
            $conn->beginTransaction();

            $update_fields = [];
            $update_values = [];

            $update_fields[] = "email = :email";
            $update_values[':email'] = $new_email;

            $update_fields[] = "{$name_column} = :nama";
            $update_values[':nama'] = $new_name;

            if ($editing_user_role === 'dosen' || $editing_user_role === 'mahasiswa') {
                $update_fields[] = "no_telp = :no_telp";
                $update_values[':no_telp'] = $new_phone;
            }

            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_fields[] = "password = :password";
                $update_values[':password'] = $hashed_password;
            }

            $query_update = "UPDATE {$table_name} SET " . implode(', ', $update_fields) . " WHERE {$id_column} = :target_user_id";
            $stmt_update = $conn->prepare($query_update);

            foreach ($update_values as $param => $value) {
                $stmt_update->bindValue($param, $value);
            }
            
            // Bind user_id yang menjadi target update
            if ($editing_user_role === 'admin') {
                $stmt_update->bindParam(':target_user_id', $editing_user_id, PDO::PARAM_INT);
            } else {
                $stmt_update->bindParam(':target_user_id', $editing_user_id, PDO::PARAM_STR);
            }

            if ($stmt_update->execute()) {
                $conn->commit();
                $message = "Profil berhasil diperbarui!";
                $message_type = 'success';

                // Jika admin mengedit profilnya sendiri, perbarui session name
                if (!$is_admin_editing_other) {
                    $_SESSION['user_name'] = $new_name;
                }
                
                // Refresh data profil setelah update berhasil
                $query_select_after_update = "SELECT * FROM {$table_name} WHERE {$id_column} = :user_id LIMIT 1";
                $stmt_select_after_update = $conn->prepare($query_select_after_update);
                if ($editing_user_role === 'admin') {
                    $stmt_select_after_update->bindParam(':user_id', $editing_user_id, PDO::PARAM_INT);
                } else {
                    $stmt_select_after_update->bindParam(':user_id', $editing_user_id, PDO::PARAM_STR);
                }
                $stmt_select_after_update->execute();
                $profile_data = $stmt_select_after_update->fetch(PDO::FETCH_ASSOC);

            } else {
                $conn->rollBack();
                $message = "Gagal memperbarui profil. Silakan coba lagi: " . $stmt_update->errorInfo()[2];
                $message_type = 'danger';
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $message = "Terjadi kesalahan database: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div id="page-content-wrapper">
    <div class="container-fluid">
        <h2 class="mb-4">Edit Profil 
            <?php 
                echo $is_admin_editing_other ? htmlspecialchars($profile_data[$name_column]) . ' (' . ucfirst($editing_user_role) . ')' : 'Anda'; 
            ?>
        </h2>
        <p class="lead">Perbarui informasi profil <?php echo $is_admin_editing_other ? 'pengguna ini' : 'Anda'; ?> di sini.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($profile_data): ?>
            <div class="card">
                <div class="card-body">
                    <form action="edit_profile.php
                        <?php 
                            // Pastikan link action form kembali ke ID user yang sedang diedit jika admin
                            if ($is_admin_editing_other) {
                                echo '?id=' . htmlspecialchars($editing_user_id) . '&role=' . htmlspecialchars($editing_user_role);
                            }
                        ?>
                    " method="POST">
                        <div class="mb-3">
                            <label for="id_field" class="form-label">
                                <?php
                                echo ($editing_user_role === 'dosen') ? 'NIP' : '';
                                echo ($editing_user_role === 'mahasiswa') ? 'NRP' : '';
                                echo ($editing_user_role === 'admin') ? 'ID Admin' : '';
                                ?>
                            </label>
                            <input type="text" class="form-control" id="id_field" value="<?php echo htmlspecialchars($profile_data[$id_column]); ?>" disabled>
                            <small class="form-text text-muted">ID ini tidak dapat diubah.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama</label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($_POST['nama'] ?? $profile_data[$name_column]); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $profile_data['email']); ?>" required>
                        </div>

                        <?php if ($editing_user_role === 'dosen' || $editing_user_role === 'mahasiswa'): ?>
                            <div class="mb-3">
                                <label for="no_telp" class="form-label">Nomor Telepon</label>
                                <input type="text" class="form-control" id="no_telp" name="no_telp" value="<?php echo htmlspecialchars($_POST['no_telp'] ?? ($profile_data['no_telp'] ?? '')); ?>">
                            </div>
                        <?php endif; ?>

                        <hr class="my-4">

                        <h5>Ganti Password (Opsional)</h5>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah password.</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="<?php echo BASE_URL; ?><?php 
                            echo $is_admin_editing_other ? 'admin_dashboard.php?show_users=' . htmlspecialchars($editing_user_role) : $editing_user_role . '_dashboard.php'; 
                        ?>" class="btn btn-secondary ms-2">Batal</a>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                Gagal memuat data profil atau Anda tidak memiliki izin.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>