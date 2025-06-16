-- Menambahkan data admin
INSERT INTO admin VALUES 
('admin1@its.ac.id', 'passwordAdmin123', 'Budi Santoso'),
('admin2@its.ac.id', 'rahasiaAdmin456', 'Siti Aminah');

-- Menambahkan data dosen
INSERT INTO dosen VALUES 
('198001012005011001', 'anton@its.ac.id', 'passDosenA', 'Prof. Dr. Anton', '081234567890', 1),
('198505102010022002', 'bella@its.ac.id', 'passDosenB', 'Dr. Bella Permata', '085678901234', 2),
('197511202000031003', 'citra@its.ac.id', 'passDosenC', 'Ir. Citra Dewi', '087812345678', 1);

-- Menambahkan data lowongan
INSERT INTO lowongan VALUES 
(
    'Asisten Dosen Mata Kuliah Basis Data',
    'Membantu dalam pengajaran, praktikum, dan penilaian mata kuliah Basis Data.',
    3,
    'Asisten Dosen',
    '2024-06-01',
    '2024-06-15',
    '198001012005011001'
),
(
    'Asisten Penelitian Proyek AI',
    'Membantu dalam pengumpulan data, analisis, dan penulisan laporan untuk proyek penelitian kecerdasan buatan.',
    2,
    'Asisten Penelitian',
    '2024-05-10',
    '2024-06-3',
    '198505102010022002'
),
(
    'Asisten Praktikum Algoritma',
    'Membantu pelaksanaan praktikum dan bimbingan mahasiswa mata kuliah Algoritma dan Struktur Data.',
    4,
    'Asisten Praktikum',
    '2024-06-05',
    '2024-06-20',
    '198001012005011001'
);

INSERT INTO mahasiswa (nrp, email, password, nama_mahasiswa, no_telp, alamat, tanggal_lahir, jurusan, angkatan, foto_profil_url, linkedin_url, ipk, departemen_id)
SELECT
    CONCAT('50252', LPAD(id_seq, 5, '0')) AS nrp,
    CONCAT('mahasiswa', id_seq, '@example.com') AS email,
    'password123' AS password,
    CONCAT('Mahasiswa ', CHAR(64 + id_seq)) AS nama_mahasiswa,
    CONCAT('0812', LPAD(FLOOR(10000000 + (RAND() * 89999999)), 8, '0')) AS no_telp,
    CONCAT('Jl. Dummy No.', id_seq, ', Kota Fiktif') AS alamat,
    DATE_SUB(CURRENT_DATE, INTERVAL (18 + FLOOR(RAND() * 5)) YEAR) AS tanggal_lahir,
    CASE FLOOR(1 + (RAND() * 4))
        WHEN 1 THEN 'Teknik Informatika'
        WHEN 2 THEN 'Sistem Informasi'
        WHEN 3 THEN 'Manajemen Bisnis'
        ELSE 'Ilmu Komunikasi'
    END AS jurusan,
    (2025 - FLOOR(1 + (RAND() * 4))) AS angkatan,
    CONCAT('https://example.com/foto/', CONCAT('mahasiswa', id_seq), '.jpg') AS foto_profil_url,
    CONCAT('https://linkedin.com/in/', CONCAT('mahasiswa', id_seq)) AS linkedin_url,
    ROUND(3.00 + (RAND() * 1.00), 2) AS ipk,
    FLOOR(1 + (RAND() * 4)) AS departemen_id
FROM
    (SELECT @rn:=@rn+1 as id_seq FROM information_schema.columns, (SELECT @rn:=0) t LIMIT 20) AS nums;



INSERT INTO lamaran (tanggal_melamar, status_lamaran, cv_url, transkrip_url, catatan_dosen, lowongan_id, mahasiswa_nrp)
SELECT
    DATE_ADD('2025-05-01 08:00:00', INTERVAL FLOOR(RAND() * 45) DAY + INTERVAL FLOOR(RAND() * 24) HOUR + INTERVAL FLOOR(RAND() * 60) MINUTE) AS tanggal_melamar,
    ELT(FLOOR(1 + (RAND() * 4)), 'Pending', 'Diterima', 'Ditolak', 'Ditinjau') AS status_lamaran,
    CONCAT('https://example.com/cv/', m.nrp, '_', LPAD(id_lamaran_seq, 2, '0'), '.pdf') AS cv_url,
    CONCAT('https://example.com/transkrip/', m.nrp, '_', LPAD(id_lamaran_seq, 2, '0'), '.pdf') AS transkrip_url,
    CASE FLOOR(1 + (RAND() * 5))
        WHEN 1 THEN 'Kandidat sangat menjanjikan dan sesuai kriteria.'
        WHEN 2 THEN 'Perlu ditinjau lebih dalam, ada beberapa area yang cocok.'
        WHEN 3 THEN 'Profil menarik, tapi kurang pengalaman relevan.'
        WHEN 4 THEN 'Terima kasih atas lamaran Anda. Kami akan menghubungi Anda segera.'
        ELSE 'Lamaran sedang dalam proses peninjauan.'
    END AS catatan_dosen,
    FLOOR(1 + (RAND() * 3)) AS lowongan_id,
    (SELECT nrp FROM mahasiswa ORDER BY RAND() LIMIT 1) AS mahasiswa_nrp
FROM
    (SELECT @idx:=@idx+1 as id_lamaran_seq FROM information_schema.columns, (SELECT @idx:=0) t LIMIT 60) AS lamaran_nums;
