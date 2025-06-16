--DDL tabel 'mahasiswa_skill'

CREATE TABLE mahasiswa_skill (
    mahasiswa_n VARCHAR(10) NOT NULL,
    skill_id INT NOT NULL,
    PRIMARY KEY (mahasiswa_n, skill_id),
    FOREIGN KEY (mahasiswa_n) REFERENCES mahasiswa(nrp) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skill(id) ON DELETE CASCADE
);

CREATE INDEX idx_skill_id ON mahasiswa_skill(skill_id);
