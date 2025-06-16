SELECT
    d.nip,
    d.nama_dosen,
    d.email,
    dp.nama_departemen 
FROM
    dosen d
JOIN
    departemen dp ON d.departemen_id = dp.id
ORDER BY
    dp.nama_departemen, d.nama_dosen;
