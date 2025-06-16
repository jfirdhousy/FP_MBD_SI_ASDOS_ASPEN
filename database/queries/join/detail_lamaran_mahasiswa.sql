-- database/queries/join/detail_lamaran_mahasiswa.sql
SELECT
    l.id AS id_lamaran,
    l.tanggal_melamar,
    l.status_lamaran,
    m.nrp,
    m.nama_mahasiswa,
    m.email,
    m.no_telp
FROM
    lamaran l
INNER JOIN
    mahasiswa m ON l.mahasiswa_nrp = m.nrp;
