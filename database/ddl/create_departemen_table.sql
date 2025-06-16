-- database/ddl/004_create_departemen_table.sql

CREATE TABLE departemen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_departemen VARCHAR(100) NOT NULL UNIQUE
);

CREATE INDEX idx_nama_departemen ON departemen(nama_departemen);
