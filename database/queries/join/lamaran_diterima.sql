-- database/queries/join/lamaran_diterima.sql

SELECT
    m.nrp,
    m.nama_mahasiswa,
    m.jurusan,
    l.status_lamaran,
    l.tanggal_melamar
FROM
    mahasiswa m
INNER JOIN
    lamaran l ON m.nrp = l.mahasiswa_nrp
WHERE
    l.status_lamaran = 'Diterima';
