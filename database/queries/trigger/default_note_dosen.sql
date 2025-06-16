-- Trigger 1: Mengatur default '' untuk note_dosen jika NULL pada saat INSERT lamaran
DELIMITER $$
CREATE TRIGGER trg_lamaran_default_note
BEFORE INSERT ON lamaran
FOR EACH ROW
BEGIN
    IF NEW.note_dosen IS NULL THEN
        SET NEW.note_dosen = '';
    END IF;
END$$
DELIMITER ;
