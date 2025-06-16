DELIMITER $$

CREATE PROCEDURE MENCARI_LOWONGAN_BERDASARKAN_NAMA_DOSEN(
    IN p_nama_dosen_keyword VARCHAR(255)
)
BEGIN
    SELECT
        l.id AS lowongan_id,
        l.nama_lowongan,
        l.jenis,
        l.tanggal_post,
        l.deadline,
        l.deskripsi,
        d.nama_dosen,
        d.nip AS dosen_nip,
        d.email AS dosen_email,
        dep.nama_departemen AS departemen
    FROM
        lowongan l
    JOIN
        dosen d ON l.dosen_nip = d.nip
    JOIN
        departemen dep ON d.departemen_id = dep.id
    WHERE
        d.nama_dosen LIKE CONCAT('%', p_nama_dosen_keyword, '%')
    ORDER BY
        l.tanggal_post DESC;
END$$

DELIMITER ;