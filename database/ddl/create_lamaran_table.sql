-- database/ddl/create_mahasiswa_table.sql

CREATE TABLE mahasiswa (
    nrp VARCHAR(10) PRIMARY KEY NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(50) NOT NULL,
    nama_mahasiswa VARCHAR(255) NOT NULL,
    no_telp VARCHAR(16),

    FOREIGN KEY (departemen_id) REFERENCES departemen(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

CREATE INDEX idx_mahasiswa_email ON mahasiswa(email);
CREATE INDEX idx_mahasiswa_departemen_id ON mahasiswa(departemen_id);
