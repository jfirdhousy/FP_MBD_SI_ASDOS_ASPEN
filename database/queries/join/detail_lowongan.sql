SELECT
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
LEFT JOIN -- LEFT JOIN karena mungkin ada lowongan tanpa skill terkait di skill_lowongan
    skill_lowongan sl ON l.id = sl.lowongan_id
LEFT JOIN
    skill s ON sl.skill_id = s.id
WHERE
    l.id = :lowongan_id_param
GROUP BY
    l.nama_lowongan, l.jenis, l.jumlah_diterima, l.tanggal_post, l.deadline, l.deskripsi,
    d.nama_dosen, d.nip, dep.nama_departemen;