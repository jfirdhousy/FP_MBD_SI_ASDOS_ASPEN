-- database/ddl/008_create_skill_lowongan_table.sql

CREATE TABLE skill_lowongan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lowongan_id INT NOT NULL,
    skill_id INT NOT NULL,
    FOREIGN KEY (lowongan_id) REFERENCES lowongan(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skill(id) ON DELETE CASCADE,
    UNIQUE (lowongan_id, skill_id) -- Memastikan skill tidak duplikat untuk satu lowongan
);
