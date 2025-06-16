DELIMITER $$
CREATE TRIGGER before_delete_departemen
BEFORE DELETE ON departemen
FOR EACH ROW
BEGIN
    -- Cek apakah masih ada dosen yang berafiliasi dengan departemen ini
    IF (SELECT COUNT(*) FROM dosen WHERE departemen_id = OLD.id) > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tidak dapat menghapus departemen: Masih ada dosen yang terdaftar di departemen ini.';
    END IF;

    -- Cek apakah masih ada mahasiswa yang berafiliasi dengan departemen ini
    IF (SELECT COUNT(*) FROM mahasiswa WHERE departemen_id = OLD.id) > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tidak dapat menghapus departemen: Masih ada mahasiswa yang terdaftar di departemen ini.';
    END IF;
END$$
DELIMITER ;