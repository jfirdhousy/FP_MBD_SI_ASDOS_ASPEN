-- Trigger 2: Mengubah email mahasiswa menjadi huruf kecil sebelum UPDATE
DELIMITER $$
CREATE TRIGGER trg_mahasiswa_lowercase_email
BEFORE UPDATE ON mahasiswa
FOR EACH ROW
BEGIN
    SET NEW.email = LOWER(NEW.email);
END$$
DELIMITER ;
