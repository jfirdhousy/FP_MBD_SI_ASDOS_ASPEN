CREATE VIEW VIEW_INFORMASI_DEPARTEMEN_LENGKAP AS
SELECT
    d.id AS departemen_id,
    d.nama_departemen,
    COUNT(DISTINCT dos.nip) AS jumlah_dosen,
    COUNT(DISTINCT mhs.nrp) AS jumlah_mahasiswa
FROM
    departemen d
LEFT JOIN
    dosen dos ON d.id = dos.departemen_id
LEFT JOIN
    mahasiswa mhs ON d.id = mhs.departemen_id
GROUP BY
    d.id, d.nama_departemen
ORDER BY
    d.nama_departemen;