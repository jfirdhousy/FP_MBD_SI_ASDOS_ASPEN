-- database/ddl/create_lamaran_table.sql

CREATE TABLE lamaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_melamar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_lamaran ENUM('Pending', 'Diterima', 'Ditolak', 'Ditinjau') NOT NULL DEFAULT 'Pending',
    cv_url VARCHAR(1024),
    transkrip_url VARCHAR(1024),
    note_dosen TEXT,
    lowongan_id INT NOT NULL,
    mahasiswa_nrp VARCHAR(10) NOT NULL,

    FOREIGN KEY (lowongan_id) REFERENCES lowongan(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    FOREIGN KEY (mahasiswa_nrp) REFERENCES mahasiswa(nrp)
        ON UPDATE CASCADE
        ON DELETE CASCADE

    UNIQUE (lowongan_id, mahasiswa_nrp)
);

CREATE INDEX idx_lamaran_lowongan_id ON lamaran(lowongan_id);
CREATE INDEX idx_lamaran_mahasiswa_nrp ON lamaran(mahasiswa_nrp);
CREATE INDEX idx_lamaran_status ON lamaran(status_lamaran);
