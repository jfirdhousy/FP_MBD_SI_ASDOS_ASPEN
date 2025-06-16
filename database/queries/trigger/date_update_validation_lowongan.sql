DELIMITER $$

CREATE TRIGGER tr_lowongan_prevent_tanggal_post_update_bu
BEFORE UPDATE ON lowongan
FOR EACH ROW
BEGIN
    -- 1. Pastikan tanggal_post tidak bisa diubah
    IF NEW.tanggal_post <> OLD.tanggal_post THEN
        -- Jika ada upaya mengubah tanggal_post, kembalikan ke nilai OLD (abaikan perubahan) kemudian memberikan SIGNAL
        SET NEW.tanggal_post = OLD.tanggal_post;
        SIGNAL SQLSTATE '01000' SET MESSAGE_TEXT = 'Tanggal posting tidak bisa diubah dan dikembalikan ke nilai semula.';
    END IF;

    -- 2. Memastikan deadline tidak lebih awal dari tanggal_post
    IF NEW.deadline < NEW.tanggal_post THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Deadline lowongan tidak boleh lebih awal dari tanggal posting.';
    END IF;
END$$

DELIMITER ;