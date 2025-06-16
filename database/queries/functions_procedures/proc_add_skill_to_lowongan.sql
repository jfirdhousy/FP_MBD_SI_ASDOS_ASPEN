DELIMITER $$
CREATE PROCEDURE PROC_ADD_SKILL_TO_LOWONGAN(
    IN p_lowongan_id INT,
    IN p_skill_id INT
)
BEGIN
    -- Memastikan skill_id dan lowongan_id valid sebelum insert
    IF EXISTS (SELECT 1 FROM lowongan WHERE id = p_lowongan_id) AND
       EXISTS (SELECT 1 FROM skill WHERE id = p_skill_id) THEN
        -- Memeriksa apakah kombinasi sudah ada untuk menghindari duplikasi
        IF NOT EXISTS (SELECT 1 FROM skill_lowongan WHERE lowongan_id = p_lowongan_id AND skill_id = p_skill_id) THEN
            INSERT INTO skill_lowongan (lowongan_id, skill_id)
            VALUES (p_lowongan_id, p_skill_id);
        END IF;
    ELSE
        -- Handle error jika lowongan_id atau skill_id tidak valid
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lowongan ID atau Skill ID tidak valid.';
    END IF;
END$$
DELIMITER ;