-- DDL for table 'lowongan'
CREATE TABLE lowongan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_lowonga VARCHAR(150),
    deskripsi LONGTEXT,
    jumlah_diterim INT,
    jenis ENUM('Asisten Dosen', 'Asisten Penelitian', 'Asisten Praktikum', 'Asisten Lab', 'Asisten Lainnya'), -- Assuming common types for 'jenis'
    tanggal_post DATE,
    deadline DATE,
    dosen_nip VARCHAR(18),
    CONSTRAINT fk_lowongan_dosen
        FOREIGN KEY (dosen_nip)
        REFERENCES dosen(nip)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);