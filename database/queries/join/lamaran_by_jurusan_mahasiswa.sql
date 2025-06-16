-- database/queries/searching/lamaran_by_jurusan_mahasiswa.sql

SELECT
    l.id AS lamaran_id,
    l.tanggal_melamar,
    l.status_lamaran,
    m.nrp,
    m.nama_mahasiswa,
    m.email,
    m.jurusan,
    l.lowongan_id
FROM
    lamaran l
JOIN
    mahasiswa m ON l.mahasiswa_nrp = m.nrp
WHERE
    m.jurusan = 'Teknik Informatika'
ORDER BY
    m.nama_mahasiswa, l.tanggal_melamar DESC;
