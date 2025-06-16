DELIMITER $$

CREATE FUNCTION HITUNG_LOWONGAN_PER_DOSEN(p_dosen_nip VARCHAR(18))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE jumlah_lowongan INT;

    SELECT COUNT(*)
    INTO jumlah_lowongan
    FROM lowongan
    WHERE dosen_nip = p_dosen_nip;

    RETURN jumlah_lowongan;
END$$

DELIMITER ;