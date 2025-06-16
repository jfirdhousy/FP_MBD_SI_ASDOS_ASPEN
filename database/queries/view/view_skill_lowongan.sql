--database/queries/view/view_skill_lowongan.sql
CREATE OR REPLACE VIEW VIEW_SKILL_LOWONGAN AS
SELECT l.id AS lowongan_id, l.nama_lowongan, s.nama_skill
FROM lowongan l
JOIN skill_lowongan sl ON l.id = sl.lowongan_id
JOIN skill s ON sl.skill_id = s.id;
