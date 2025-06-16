--database/queries/trigger/prevent_delete_skill.sql
DELIMITER $$

CREATE TRIGGER prevent_delete_skill
BEFORE DELETE ON skill
FOR EACH ROW
BEGIN
  IF EXISTS (SELECT 1 FROM mahasiswa_skill WHERE skill_id = OLD.id) OR
     EXISTS (SELECT 1 FROM skill_lowongan WHERE skill_id = OLD.id) THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Skill masih terpakai.';
  END IF;
END$$

DELIMITER ;
