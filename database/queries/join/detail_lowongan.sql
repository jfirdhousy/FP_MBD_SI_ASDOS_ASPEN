SELECT
    l.id AS lowongan_id,
    l.nama_lowongan,
    l.jenis AS jenis_lowongan,
    l.jumlah_diterima,
    l.tanggal_post,
    l.deadline,
    l.deskripsi,
    d.nama_dosen,
    d.nip AS dosen_nip,
    dep.nama_departemen AS departemen,
    GROUP_CONCAT(s.nama_skill SEPARATOR ', ') AS skill_dibutuhkan
FROM
    lowongan l
JOIN
    dosen d ON l.dosen_nip = d.nip
JOIN
    departemen dep ON d.departemen_id = dep.id
LEFT JOIN
    skill_lowongan sl ON l.id = sl.lowongan_id
LEFT JOIN
    skill s ON sl.skill_id = s.id
WHERE
    l.id = :lowongan_id_param -- Placeholder untuk ID lowongan
GROUP BY
    l.id, l.nama_lowongan, l.jenis, l.jumlah_diterima, l.tanggal_post, l.deadline, l.deskripsi,
    d.nama_dosen, d.nip, d.email, dep.nama_departemen;
