CREATE VIEW VIEW_RIWAYAT_LOWONGAN_STATISTIK AS
SELECT
    l.id AS lowongan_id,
    l.nama_lowongan,
    l.jenis,
    l.tanggal_post,
    l.deadline,
    d.nama_dosen,
    d.nip AS dosen_nip,
    dep.nama_departemen AS departemen,
    COUNT(lr.id) AS total_lamaran,
    SUM(CASE WHEN lr.status_lamar = 'Diterima' THEN 1 ELSE 0 END) AS lamaran_diterima,
    SUM(CASE WHEN lr.status_lamar = 'Ditolak' THEN 1 ELSE 0 END) AS lamaran_ditolak,
    SUM(CASE WHEN lr.status_lamar = 'Pending' THEN 1 ELSE 0 END) AS lamaran_pending_saat_tutup -- Jumlah pending saat lowongan berakhir
FROM
    lowongan l
JOIN
    dosen d ON l.dosen_nip = d.nip
JOIN
    departemen dep ON d.departemen_id = dep.id
LEFT JOIN -- Gunakan LEFT JOIN agar lowongan tanpa lamaran tetap muncul
    lamaran lr ON l.id = lr.lowongan_id
WHERE
    l.deadline < CURDATE() -- Filter lowongan yang sudah tidak aktif
GROUP BY
    l.id, l.nama_lowongan, l.jenis, l.tanggal_post, l.deadline, d.nama_dosen, d.nip, dep.nama_departemen
ORDER BY
    l.deadline DESC;
