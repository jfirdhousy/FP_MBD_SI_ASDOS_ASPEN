-- DDL for table 'dosen'
CREATE TABLE dosen (
    nip VARCHAR(18) PRIMARY KEY NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(50) NOT NULL,
    nama_dosen VARCHAR(255),
    no_telp VARCHAR(16),
    departemen_id INT,
    
    FOREIGN KEY (departemen_id) REFERENCES departemen(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

CREATE INDEX idx_dosen_email ON dosen(email);
CREATE INDEX idx_dosen_departemen_id ON dosen(departemen_id);
