SELECT
    l.id AS lowongan_id,
    l.nama_lowongan,
    l.jenis,
    l.tanggal_post,
    l.deadline,
    dep.nama_departemen AS departemen
FROM
    lowongan l
JOIN
    dosen d ON l.dosen_nip = d.nip
JOIN
    departemen dep ON d.departemen_id = dep.id
WHERE
    l.deadline < CURDATE() -- Pastikan riwayat lowongan (tidak aktif)
    AND dep.nama_departemen = :nama_departemen_param
ORDER BY
    l.deadline DESC;
