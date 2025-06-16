DELIMITER $$

CREATE TRIGGER tr_lowongan_set_tanggal_post_bi
BEFORE INSERT ON lowongan
FOR EACH ROW
BEGIN
    -- Set tanggal_post ke tanggal saat ini, mengabaikan input apapun
    SET NEW.tanggal_post = CURDATE();

    -- Memastikan deadline tidak lebih awal dari tanggal_post (yang sekarang sudah diatur ke CURDATE())
    IF NEW.deadline < NEW.tanggal_post THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Deadline lowongan tidak boleh lebih awal dari tanggal posting (tanggal hari ini).';
    END IF;
END$$

DELIMITER ;