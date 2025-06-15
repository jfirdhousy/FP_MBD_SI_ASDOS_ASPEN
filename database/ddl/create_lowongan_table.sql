-- DDL for table 'lowongan'
CREATE TABLE lowongan (
    id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    nama_lowongan VARCHAR(150) NOT NULL,
    deskripsi LONGTEXT NOT NULL,
    jumlah_diterima INT NOT NULL,
    jenis ENUM('Asisten Dosen', 'Asisten Penelitian'),
    tanggal_post DATE,
    deadline DATE,
    dosen_nip VARCHAR(18) NOT NULL UNIQUE,

    FOREIGN KEY (dosen_nip) REFERENCES dosen(nip)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE INDEX idx_lowongan_dosen_nip ON lowongan(dosen_nip);
CREATE INDEX idx_lowongan_jenis ON lowongan(jenis);
CREATE INDEX idx_lowongan_tanggal_post ON lowongan(tanggal_post);
CREATE INDEX idx_lowongan_deadline ON lowongan(deadline);
