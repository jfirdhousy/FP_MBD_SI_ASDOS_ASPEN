--database/queries/join/mahasiswa_punya_skill.sql
SELECT m.nrp, m.nama_mahasiswa, s.nama_skill
FROM mahasiswa m
JOIN mahasiswa_skill ms ON m.nrp = ms.mahasiswa_n
JOIN skill s ON ms.skill_id = s.id
WHERE s.nama_skill = 'Python';
