CREATE VIEW VIEW_LOWONGAN_ACTIVE AS
SELECT
    l.nama_lowongan,
    l.jenis AS jenis_lowongan,
    l.jumlah_diterima,
    l.deadline,
    l.tanggal_post,
    d.nama_dosen,
    dep.nama_departemen AS departemen
FROM
    lowongan l
JOIN
    dosen d ON l.dosen_nip = d.nip
JOIN
    departemen dep ON d.departemen_id = dep.id
WHERE
    l.deadline >= CURDATE() -- Filter lowongan yang masih aktif
ORDER BY
    l.tanggal_post DESC;