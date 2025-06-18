-- Ini adalah contoh hipotetis jika VIEW_INFORMASI_DEPARTEMEN_LENGKAP memanggil fungsi:
CREATE VIEW VIEW_INFORMASI_DEPARTEMEN_LENGKAP AS
SELECT
    d.id AS departemen_id,
    d.nama_departemen,
    COUNT(DISTINCT dos.nip) AS jumlah_dosen,
    FUNC_GET_JUMLAH_MAHASISWA_DEPARTEMEN(d.id) AS jumlah_mahasiswa -- Panggilan fungsi di sini
FROM
    departemen d
LEFT JOIN
    dosen dos ON d.id = dos.departemen_id
GROUP BY
    d.id, d.nama_departemen;
