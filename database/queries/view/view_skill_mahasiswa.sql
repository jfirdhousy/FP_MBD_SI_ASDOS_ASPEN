--database/queries/view/view_skill_mahasiswa.sql
CREATE OR REPLACE VIEW VIEW_SKILL_MAHASISWA AS
SELECT m.nrp, m.nama_mahasiswa, s.nama_skill
FROM mahasiswa m
JOIN mahasiswa_skill ms ON m.nrp = ms.mahasiswa_n
JOIN skill s ON ms.skill_id = s.id;
