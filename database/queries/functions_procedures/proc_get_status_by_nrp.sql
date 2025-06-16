-- Procedure 1: Mengambil status lamaran seorang mahasiswa berdasarkan NRP
DELIMITER $$
CREATE PROCEDURE GET_STATUS_LAMARAN_MAHASISWA(
    IN p_mahasiswa_nrp VARCHAR(10),
    OUT p_status_lamaran ENUM('Pending', 'Diterima', 'Ditolak', 'Ditinjau')
)
BEGIN
    SELECT status_lamaran INTO p_status_lamaran
    FROM lamaran
    WHERE mahasiswa_nrp = p_mahasiswa_nrp;
END$$
DELIMITER ;
