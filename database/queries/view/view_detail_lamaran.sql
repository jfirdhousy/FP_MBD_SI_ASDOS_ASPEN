-- View 1: Menampilkan detail lamaran beserta informasi mahasiswa
CREATE VIEW view_detail_lamaran_mahasiswa AS
SELECT
    l.id AS lamaran_id,
    l.lowongan_id,
    l.tanggal_melamar,
    l.status_lamaran,
    l.note_dosen,
    m.nrp AS mahasiswa_nrp,
    m.nama_mahasiswa,
    m.email,
    m.no_telp,
    d.nama_departemen AS departemen
FROM
    lamaran l
JOIN
    mahasiswa m ON l.mahasiswa_nrp = m.nrp
LEFT JOIN
    departemen d ON m.departemen_id = d.id;
