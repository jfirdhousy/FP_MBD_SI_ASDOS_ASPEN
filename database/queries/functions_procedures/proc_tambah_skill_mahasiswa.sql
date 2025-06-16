--database/queries/functions_procedures/proc_tambah_skill_mahasiswa.sql

DELIMITER $$

CREATE PROCEDURE proc_tambah_skill_mahasiswa(
    IN p_nrp VARCHAR(10),
    IN p_skill_id INT
)
BEGIN
  INSERT INTO mahasiswa_skill (mahasiswa_n, skill_id)
  VALUES (p_nrp, p_skill_id);
END$$

DELIMITER ;
