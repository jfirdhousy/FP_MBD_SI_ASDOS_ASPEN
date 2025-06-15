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

