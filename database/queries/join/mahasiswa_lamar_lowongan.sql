-- database/queries/searching/mahasiswa_lamar_lowongan.sql

SELECT
    m.nrp,
    m.nama_mahasiswa,
    m.email,
    m.jurusan,
    l.id AS lamaran_id,
    l.tanggal_melamar,
    l.status_lamaran,
    l.cv_url,
    l.transkrip_url
FROM
    mahasiswa m
JOIN
    lamaran l ON m.nrp = l.mahasiswa_nrp
WHERE
    l.lowongan_id = 1
ORDER BY
    l.tanggal_melamar DESC;
