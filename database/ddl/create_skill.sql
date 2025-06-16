--DDL tabel 'skill'

CREATE TABLE skill (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_skill VARCHAR(50) NOT NULL
);

CREATE INDEX idx_nama_skill ON skill(nama_skill);
