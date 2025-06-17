-- database/queries/view/view_lowongan_per_departemen.sql

-- Buat VIEW baru dengan kolom jumlah_diterima
CREATE VIEW VIEW_LOWONGAN_PER_DEPARTEMEN AS
SELECT
    l.id AS lowongan_id,
    l.nama_lowongan,
    l.jenis,
    l.jumlah_diterima, -- TAMBAHAN INI
    l.tanggal_post,
    l.deadline,
    dosen.nama_dosen,
    dosen.nip AS dosen_nip, -- Tambahkan dosen_nip agar bisa difilter di PHP
    dept.nama_departemen
FROM
    lowongan l
JOIN
    dosen dosen ON l.dosen_nip = dosen.nip
JOIN
    departemen dept ON dosen.departemen_id = dept.id
ORDER BY
    dept.nama_departemen, l.tanggal_post DESC;