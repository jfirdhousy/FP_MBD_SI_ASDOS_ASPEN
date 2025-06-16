--database/queries/functions_procedures/func_get_nama_skill.sql
DELIMITER $$
CREATE FUNCTION FUNC_GET_SKILL_NAME(p_skill_id INT)
RETURNS VARCHAR(50)
DETERMINISTIC
BEGIN
  DECLARE v_nama_skill VARCHAR(50);
  SELECT nama_skill INTO v_nama_skill
  FROM skill
  WHERE id = p_skill_id;
  RETURN v_nama_skill;
END$$
DELIMITER ;
