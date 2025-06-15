-- DDL for table 'admin'
CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(50) NOT NULL,
    nama_admin VARCHAR(255)
);

CREATE UNIQUE INDEX idx_admin_email ON admin(email);
