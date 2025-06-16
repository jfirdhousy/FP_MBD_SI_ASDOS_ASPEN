DELIMITER $$
CREATE FUNCTION FUNC_GET_JUMLAH_MAHASISWA_DEPARTEMEN(
    p_departemen_id INT
)
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE jumlah_mahasiswa INT;
    SELECT COUNT(nrp) INTO jumlah_mahasiswa
    FROM mahasiswa
    WHERE departemen_id = p_departemen_id;
    RETURN jumlah_mahasiswa;
END$$
DELIMITER ;