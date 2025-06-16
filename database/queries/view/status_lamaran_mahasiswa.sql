-- View 2: Menampilkan semua mahasiswa dan status aplikasi mereka
CREATE VIEW view_mahasiswa_dan_status_aplikasi AS
SELECT
    m.nrp,
    m.nama_mahasiswa,
    m.email,
    CASE
        WHEN l.id IS NOT NULL THEN 'Sudah Melamar'
        ELSE 'Belum Melamar'
    END AS status_aplikasi_umum,
    l.status_lamaran AS detail_status_lamaran,
    l.tanggal_melamar
FROM
    mahasiswa m
LEFT JOIN
    lamaran l ON m.nrp = l.mahasiswa_nrp;
