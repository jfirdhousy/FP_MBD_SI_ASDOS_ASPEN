<?php
session_start();

// Periksa apakah dosen sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'dosen') {
    // Jika tidak login sebagai dosen, arahkan ke halaman login
    header("Location: /FP_MBD_SI_ASDOS_ASPEN/public/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$dosen_nip = $_SESSION['user_id']; // NIP dosen yang login dari sesi
$lowongan_id = $_GET['id'] ?? null; // Ambil ID lowongan dari parameter URL

$message = '';
$message_type = '';

// Pastikan ID lowongan ada dan valid
if (!$lowongan_id || !is_numeric($lowongan_id)) {
    $message = "ID lowongan tidak valid.";
    $message_type = 'danger';
} else {
    try {
        // Pertama, verifikasi apakah lowongan ini memang milik dosen yang sedang login
        $query_verify_ownership = "SELECT id FROM lowongan WHERE id = :id AND dosen_nip = :dosen_nip LIMIT 1";
        $stmt_verify = $conn->prepare($query_verify_ownership);
        $stmt_verify->bindParam(':id', $lowongan_id, PDO::PARAM_INT);
        $stmt_verify->bindParam(':dosen_nip', $dosen_nip);
        $stmt_verify->execute();

        if ($stmt_verify->rowCount() === 0) {
            // Lowongan tidak ditemukan atau bukan milik dosen ini
            $message = "Lowongan tidak ditemukan atau Anda tidak memiliki izin untuk menghapusnya.";
            $message_type = 'danger';
        } else {
            // Lowongan ditemukan dan milik dosen, lanjutkan proses penghapusan
            $query_delete = "DELETE FROM lowongan WHERE id = :id";
            $stmt_delete = $conn->prepare($query_delete);
            $stmt_delete->bindParam(':id', $lowongan_id, PDO::PARAM_INT);

            if ($stmt_delete->execute()) {
                $message = "Lowongan berhasil dihapus!";
                $message_type = 'success';
            } else {
                $message = "Gagal menghapus lowongan. Silakan coba lagi.";
                $message_type = 'danger';
            }
        }
    } catch (PDOException $e) {
        // Tangkap error dari trigger SQL (misal: sebelum delete jika ada lamaran aktif)
        $message_type = 'danger';
        // Periksa pesan error spesifik dari trigger yang kita buat (jika ada)
        if (strpos($e->getMessage(), 'Tidak dapat menghapus dosen yang masih memiliki lowongan aktif.') !== false ||
            strpos($e->getMessage(), 'Tidak dapat menghapus lowongan yang masih memiliki lamaran pending atau diterima.') !== false) {
            $message = "Gagal menghapus: " . $e->getMessage();
        } else {
            $message = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}

// Redirect kembali ke halaman dashboard dosen dengan pesan
header("Location: /FP_MBD_SI_ASDOS_ASPEN/public/dosen_dashboard.php?msg=" . urlencode($message) . "&type=" . urlencode($message_type));
exit();
?>