<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type']; // 'mahasiswa', 'dosen', 'admin'

    $table = '';
    $id_col = '';
    $name_col = '';
    $redirect_page = '';

    switch ($user_type) {
        case 'mahasiswa':
            $table = 'mahasiswa';
            $id_col = 'nrp';
            $name_col = 'nama_mahasiswa';
            $redirect_page = 'mahasiswa_dashboard.php';
            break;
        case 'dosen':
            $table = 'dosen';
            $id_col = 'nip';
            $name_col = 'nama_dosen';
            $redirect_page = 'dosen_dashboard.php';
            break;
        case 'admin':
            $table = 'admin';
            $id_col = 'id';
            $name_col = 'nama_admin';
            $redirect_page = 'admin_dashboard.php';
            break;
        default:
            $error_message = "Tipe pengguna tidak valid.";
            break;
    }

    if (empty($error_message)) {
        $query = "SELECT $id_col, email, password, $name_col FROM $table WHERE email = :email LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($password === $user['password']) {

                $_SESSION['user_id'] = $user[$id_col];
                $_SESSION['user_name'] = $user[$name_col];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user_type;

                // Pastikan path redirect ini benar
                if ($user_type == 'mahasiswa') {
                    header("Location: /FP_MBD_SI_ASDOS_ASPEN/public/mahasiswa_dashboard.php");
                } elseif ($user_type == 'dosen') {
                    header("Location: /FP_MBD_SI_ASDOS_ASPEN/public/dosen_dashboard.php");
                } elseif ($user_type == 'admin') {
                    header("Location: /FP_MBD_SI_ASDOS_ASPEN/public/admin_dashboard.php");
                }
                exit();
            } else {
                $error_message = "Email atau password salah.";
            }
        } else {
            $error_message = "Email atau password salah.";
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Login</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="user_type" class="form-label">Login Sebagai</label>
                        <select class="form-select" id="user_type" name="user_type" required>
                            <option value="mahasiswa">Mahasiswa</option>
                            <option value="dosen">Dosen</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
            <div class="card-footer text-center">
                Belum punya akun? <a href="/sistem_lowongan_asisten_simple/public/register_mahasiswa.php">Daftar Mahasiswa</a> atau <a href="/sistem_lowongan_asisten_simple/public/register_dosen.php">Daftar Dosen</a>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>