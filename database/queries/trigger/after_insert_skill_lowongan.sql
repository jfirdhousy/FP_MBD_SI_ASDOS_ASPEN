CREATE TABLE log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    aksi VARCHAR(255) NOT NULL,
    keterangan TEXT
);

DELIMITER $$
CREATE TRIGGER after_insert_skill_lowongan
AFTER INSERT ON skill_lowongan
FOR EACH ROW
BEGIN
    -- Menambahkan log ketika skill ditambahkan ke lowongan
    INSERT INTO log_aktivitas (aksi, keterangan)
    VALUES (
        'Penambahan Skill Lowongan',
        CONCAT('Skill ID: ', NEW.skill_id, ' ditambahkan ke Lowongan ID: ', NEW.lowongan_id)
    );
END$$

DELIMITER ;