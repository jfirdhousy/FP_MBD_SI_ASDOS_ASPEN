--database/queries/trigger/update_skill_jumlah.sql
ALTER TABLE mahasiswa ADD COLUMN jumlah_skill INT DEFAULT 0;
DELIMITER $$

CREATE TRIGGER update_skill_jumlah
AFTER INSERT ON mahasiswa_skill
FOR EACH ROW
BEGIN
  UPDATE mahasiswa
  SET jumlah_skill = jumlah_skill + 1
  WHERE nrp = NEW.mahasiswa_n;
END$$

DELIMITER ;
