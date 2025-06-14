<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'FP_MBD_SI_ASDOS_ASPEN');
define('DB_USER', 'root');
define('DB_PASS', '');

function getConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("set names utf8");
        return $conn;
        return $conn;
    } catch(PDOException $exception) {
        echo "<h3>Kesalahan Koneksi Database!</h3>";
        echo "<p>Detail Error: " . $exception->getMessage() . "</p>";
        die();
    }
}
?>
