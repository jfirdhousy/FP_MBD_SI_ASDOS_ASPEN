SELECT
    l.id AS lowongan_id,
    l.nama_lowongan,
    l.jenis,
    l.tanggal_post,
    l.deadline,
    d.nama_dosen,
    dp.nama_departemen,
    GROUP_CONCAT(s.nama_skill SEPARATOR ', ') AS skill_yang_dibutuhkan
FROM
    lowongan l
JOIN
    dosen d ON l.dosen_nip = d.nip
JOIN
    departemen dp ON d.departemen_id = dp.id
LEFT JOIN
    skill_lowongan sl ON l.id = sl.lowongan_id
LEFT JOIN
    skill s ON sl.skill_id = s.id
WHERE
    dp.nama_departemen = 'Teknik Informatika' 
GROUP BY
    l.id, l.nama_lowongan, l.jenis, l.tanggal_post, l.deadline, d.nama_dosen, dp.nama_departemen
ORDER BY
    l.tanggal_post DESC;