-- DDL for table 'dosen'
CREATE TABLE dosen (
    nip VARCHAR(18) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(50) NOT NULL,
    nama_dosen VARCHAR(255),
    no_telp VARCHAR(16),
    departemen_id INT,
    CONSTRAINT fk_dosen_departemen
        FOREIGN KEY (departemen_id)
        REFERENCES departemen(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);