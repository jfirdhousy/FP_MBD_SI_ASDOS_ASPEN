DROP DATABASE IF EXISTS FP_MBD_SI_ASDOS_ASPEN;
CREATE DATABASE FP_MBD_SI_ASDOS_ASPEN;
USE FP_MBD_SI_ASDOS_ASPEN;

-- 1. Tabel `departemen`
CREATE TABLE departemen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_departemen VARCHAR(100) NOT NULL UNIQUE
);

-- 2. Tabel `skill`
CREATE TABLE skill (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_skill VARCHAR(50) NOT NULL
);

-- 3. Tabel `admin`
CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(50) NOT NULL,
    nama_admin VARCHAR(255)
);
CREATE UNIQUE INDEX idx_admin_email ON admin(email);


-- 4. Tabel `dosen`
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


-- 5. Tabel `mahasiswa`
CREATE TABLE mahasiswa (
    nrp VARCHAR(10) PRIMARY KEY NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(50) NOT NULL,
    nama_mahasiswa VARCHAR(255) NOT NULL,
    no_telp VARCHAR(16),
    departemen_id INT,

    FOREIGN KEY (departemen_id) REFERENCES departemen(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);
CREATE INDEX idx_mahasiswa_email ON mahasiswa(email);
CREATE INDEX idx_mahasiswa_departemen_id ON mahasiswa(departemen_id);


-- 6. Tabel `lowongan`
CREATE TABLE lowongan (
    id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    nama_lowongan VARCHAR(150) NOT NULL,
    deskripsi LONGTEXT NOT NULL,
    jumlah_diterima INT NOT NULL,
    jenis ENUM('Asisten Dosen', 'Asisten Penelitian'),
    tanggal_post DATE,
    deadline DATE,
    dosen_nip VARCHAR(18) NOT NULL, -- Hapus UNIQUE di sini jika satu dosen bisa membuat banyak lowongan

    FOREIGN KEY (dosen_nip) REFERENCES dosen(nip)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);
CREATE INDEX idx_lowongan_dosen_nip ON lowongan(dosen_nip);
CREATE INDEX idx_lowongan_jenis ON lowongan(jenis);
CREATE INDEX idx_lowongan_tanggal_post ON lowongan(tanggal_post);
CREATE INDEX idx_lowongan_deadline ON lowongan(deadline);


-- 7. Tabel `lamaran`
CREATE TABLE lamaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal_melamar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_lamaran ENUM('Pending', 'Diterima', 'Ditolak', 'Ditinjau') NOT NULL DEFAULT 'Pending',
    cv_url VARCHAR(1024),
    transkrip_url VARCHAR(1024),
    note_dosen TEXT,
    lowongan_id INT NOT NULL,
    mahasiswa_nrp VARCHAR(10) NOT NULL,

    FOREIGN KEY (lowongan_id) REFERENCES lowongan(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    FOREIGN KEY (mahasiswa_nrp) REFERENCES mahasiswa(nrp)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);
CREATE INDEX idx_lamaran_lowongan_id ON lamaran(lowongan_id);
CREATE INDEX idx_lamaran_mahasiswa_nrp ON lamaran(mahasiswa_nrp);
CREATE INDEX idx_lamaran_status ON lamaran(status_lamaran);


-- 8. Tabel `mahasiswa_skill`
CREATE TABLE mahasiswa_skill (
    mahasiswa_nrp VARCHAR(10) NOT NULL,
    skill_id INT NOT NULL,
    PRIMARY KEY (mahasiswa_nrp, skill_id),
    FOREIGN KEY (mahasiswa_nrp) REFERENCES mahasiswa(nrp) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skill(id) ON DELETE CASCADE
);
CREATE INDEX idx_skill_id ON mahasiswa_skill(skill_id);


-- 9. Tabel `skill_lowongan`
CREATE TABLE skill_lowongan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lowongan_id INT NOT NULL,
    skill_id INT NOT NULL,
    FOREIGN KEY (lowongan_id) REFERENCES lowongan(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skill(id) ON DELETE CASCADE,
    UNIQUE (lowongan_id, skill_id) -- Memastikan skill tidak duplikat untuk satu lowongan
);

ALTER TABLE departemen AUTO_INCREMENT = 1;
ALTER TABLE admin AUTO_INCREMENT = 1;
ALTER TABLE lowongan AUTO_INCREMENT = 1;
ALTER TABLE skill AUTO_INCREMENT = 1;
ALTER TABLE lamaran AUTO_INCREMENT = 1;

INSERT INTO admin (email, password, nama_admin) VALUES
('admin1@example.com', 'adminpass1', 'Admin Utama'),
('admin2@example.com', 'adminpass2', 'Koordinator Sistem'),
('admin3@example.com', 'adminpass3', 'Admin Pendaftaran'),
('admin4@example.com', 'adminpass4', 'Admin Lowongan'),
('admin5@example.com', 'adminpass5', 'Admin Mahasiswa'),
('admin6@example.com', 'adminpass6', 'Admin Dosen'),
('admin7@example.com', 'adminpass7', 'Admin IT'),
('admin8@example.com', 'adminpass8', 'Pencatat Data'),
('admin9@example.com', 'adminpass9', 'Verifikator Akun'),
('admin10@example.com', 'adminpass10', 'Admin Keuangan'),
('admin11@example.com', 'adminpass11', 'Admin SDM'),
('admin12@example.com', 'adminpass12', 'Pusat Bantuan'),
('admin13@example.com', 'adminpass13', 'Supervisor Admin'),
('admin14@example.com', 'adminpass14', 'Admin Laporan'),
('admin15@example.com', 'adminpass15', 'Admin Teknis'),
('admin16@example.com', 'adminpass16', 'Pengawas Sistem'),
('admin17@example.com', 'adminpass17', 'Admin Umum'),
('admin18@example.com', 'adminpass18', 'Manajer Database'),
('admin19@example.com', 'adminpass19', 'Admin Jaringan'),
('admin20@example.com', 'adminpass20', 'Admin Pelatihan');

INSERT INTO departemen (nama_departemen) VALUES
('Teknik Informatika'), ('Sistem Informasi'), ('Manajemen Bisnis'), ('Ilmu Komunikasi'),
('Teknik Elektro'), ('Teknik Industri'), ('Teknik Sipil'), ('Arsitektur'),
('Desain Komunikasi Visual'), ('Psikologi'), ('Ekonomi Pembangunan'), ('Akuntansi'),
('Manajemen'), ('Hubungan Internasional'), ('Ilmu Hukum'), ('Pendidikan Bahasa Inggris'),
('Matematika'), ('Fisika'), ('Kimia'), ('Biologi');

INSERT INTO dosen (nip, email, password, nama_dosen, no_telp, departemen_id) VALUES
('198001012005011001', 'dosen_ti1@example.com', 'dosen1pass', 'Dr. Budi Santoso', '081234567890', 1),
('197505152000021002', 'dosen_si1@example.com', 'dosen2pass', 'Prof. Ani Wijaya', '087654321098', 2),
('198211202008031003', 'dosen_mb1@example.com', 'dosen3pass', 'Dr. Citra Lestari', '089987654321', 3),
('197803012003011004', 'dosen_ik1@example.com', 'dosen4pass', 'Prof. Doni Iskandar', '085212345678', 4),
('198507102010041005', 'dosen_te1@example.com', 'dosen5pass', 'Dr. Eka Pratama', '081345678901', 5),
('197002281995011006', 'dosen_ti2@example.com', 'dosen6pass', 'Prof. Fuji Nugraha', '087890123456', 1),
('198809052015051007', 'dosen_si2@example.com', 'dosen7pass', 'Dr. Gita Rahayu', '089123456789', 2),
('197204121998021008', 'dosen_mb2@example.com', 'dosen8pass', 'Prof. Harun Abdullah', '085321098765', 3),
('198106182006031009', 'dosen_ik2@example.com', 'dosen9pass', 'Dr. Irma Sari', '081456789012', 4),
('197701252002011010', 'dosen_te2@example.com', 'dosen10pass', 'Prof. Joni Purwanto', '087901234567', 5),
('198310022009041011', 'dosen_ti3@example.com', 'dosen11pass', 'Dr. Kiki Maulana', '089234567890', 1),
('197108081996021012', 'dosen_si3@example.com', 'dosen12pass', 'Prof. Lia Indah', '085432109876', 2),
('198612142011051013', 'dosen_mb3@example.com', 'dosen13pass', 'Dr. Mikael Tan', '081567890123', 3),
('197909092004031014', 'dosen_ik3@example.com', 'dosen14pass', 'Prof. Nana Setiawan', '087012345678', 4),
('198403222010011015', 'dosen_ti4@example.com', 'dosen15pass', 'Dr. Olivia Zahra', '089345678901', 1),
('197306061999021016', 'dosen_si4@example.com', 'dosen16pass', 'Prof. Panca Gunawan', '085543210987', 2),
('198901012014031017', 'dosen_mb4@example.com', 'dosen17pass', 'Dr. Qonita Fajar', '081678901234', 3),
('197410102000041018', 'dosen_ik4@example.com', 'dosen18pass', 'Prof. Rini Kusuma', '087123456789', 4),
('198705052012011019', 'dosen_ti5@example.com', 'dosen19pass', 'Dr. Satria Utama', '089456789012', 1),
('197611112001021020', 'dosen_si5@example.com', 'dosen20pass', 'Prof. Tanti Rahmawati', '085654321098', 2);

INSERT INTO mahasiswa (nrp, email, password, nama_mahasiswa, no_telp, departemen_id) VALUES
('1010101010', 'mhs_ti1@example.com', 'mhs1pass', 'Aldo Prasetya', '081122334455', 1),
('2020202020', 'mhs_si1@example.com', 'mhs2pass', 'Bella Cahyani', '082211445566', 2),
('3030303030', 'mhs_mb1@example.com', 'mhs3pass', 'Candra Dinata', '083344556677', 3),
('4040404040', 'mhs_ik1@example.com', 'mhs4pass', 'Dina Amelia', '084455667788', 4),
('5050505050', 'mhs_ti2@example.com', 'mhs5pass', 'Eko Wahyudi', '085566778899', 1),
('6060606060', 'mhs_si2@example.com', 'mhs6pass', 'Fany Kartika', '086677889900', 2),
('7070707070', 'mhs_mb2@example.com', 'mhs7pass', 'Gilang Pratama', '087788990011', 3),
('8080808080', 'mhs_ik2@example.com', 'mhs8pass', 'Hana Fitriani', '088899001122', 4),
('9090909090', 'mhs_ti3@example.com', 'mhs9pass', 'Iqbal Ramadhan', '089900112233', 1),
('1111111111', 'mhs_si3@example.com', 'mhs10pass', 'Jihan Safitri', '081011223344', 2),
('2222222222', 'mhs_mb3@example.com', 'mhs11pass', 'Kevin Adi', '082122334455', 3),
('3333333333', 'mhs_ik3@example.com', 'mhs12pass', 'Lia Kumala', '083233445566', 4),
('4444444444', 'mhs_ti4@example.com', 'mhs13pass', 'Miko Susanto', '084344556677', 1),
('5555555555', 'mhs_si4@example.com', 'mhs14pass', 'Nina Puspita', '085455667788', 2),
('6666666666', 'mhs_mb4@example.com', 'mhs15pass', 'Oka Wijaya', '086566778899', 3),
('7777777777', 'mhs_ik4@example.com', 'mhs16pass', 'Putri Nabila', '087677889900', 4),
('8888888888', 'mhs_ti5@example.com', 'mhs17pass', 'Rizky Alamsyah', '088788990011', 1),
('9999999999', 'mhs_si5@example.com', 'mhs18pass', 'Sari Indah', '089899001122', 2),
('1212121212', 'mhs_mb5@example.com', 'mhs19pass', 'Taufik Hidayat', '081900112233', 3),
('1313131313', 'mhs_ik5@example.com', 'mhs20pass', 'Uli Rahmawati', '082011223344', 4);

INSERT INTO skill (nama_skill) VALUES
('SQL'), ('Python'), ('Java'), ('Machine Learning'), ('Data Mining'),
('JavaScript'), ('HTML/CSS'), ('PHP'), ('React'), ('Node.js'),
('UI/UX Design'), ('Figma'), ('Problem Solving'), ('Komunikasi Efektif'), ('Penulisan Ilmiah'),
('Statistika'), ('Algoritma'), ('Jaringan Komputer'), ('Cloud Computing'), ('Mobile Development');

INSERT INTO lowongan (nama_lowongan, deskripsi, jumlah_diterima, jenis, tanggal_post, deadline, dosen_nip) VALUES
('Asisten Dosen Etika Profesi IT', 'Membantu dalam pembahasan kasus-kasus etika di bidang IT.', 1, 'Asisten Dosen', '2025-04-27', '2025-05-02', '197611112001021020'),
('Asisten Riset Keamanan Informasi', 'Membantu penelitian ancaman siber dan solusi perlindungan data.', 1, 'Asisten Penelitian', '2025-04-28', '2025-05-03', '197611112001021020'),
('Asisten Penelitian Sistem Enterprise', 'Membantu implementasi modul ERP untuk perusahaan menengah.', 2, 'Asisten Penelitian', '2025-04-29', '2025-05-04', '197611112001021020'),
('Asisten Dosen Arsitektur Komputer', 'Membantu dalam kelas organisasi komputer dan arsitektur CPU.', 2, 'Asisten Dosen', '2025-04-30', '2025-05-05', '198705052012011019'),
('Penelitian Big Data Streaming', 'Membantu riset pemrosesan data real-time dengan teknologi Big Data.', 1, 'Asisten Penelitian', '2025-05-01', '2025-05-06', '198705052012011019'),
('Asisten Dosen Jaringan Komputer Lanjut', 'Membantu konfigurasi router dan firewall tingkat lanjut.', 3, 'Asisten Dosen', '2025-05-02', '2025-05-07', '198705052012011019'),
('Asisten Penelitian Branding Perusahaan', 'Membantu perancangan identitas merek dan strategi branding.', 1, 'Asisten Penelitian', '2025-05-03', '2025-05-08', '197410102000041018'),
('Asisten Dosen Desain Komunikasi Visual', 'Membantu mahasiswa dalam proyek desain grafis dan visual.', 2, 'Asisten Dosen', '2025-05-04', '2025-05-09', '197410102000041018'),
('Asisten Riset Komunikasi Krisis', 'Membantu penelitian strategi komunikasi saat krisis organisasi.', 1, 'Asisten Penelitian', '2025-05-05', '2025-05-10', '197410102000041018'),
('Asisten Penelitian Analisis Keuangan Perusahaan', 'Membantu analisis kinerja keuangan perusahaan publik.', 1, 'Asisten Penelitian', '2025-05-06', '2025-05-11', '198901012014031017'),
('Asisten Dosen Perilaku Organisasi', 'Membantu praktikum dinamika kelompok dan budaya organisasi.', 2, 'Asisten Dosen', '2025-05-07', '2025-05-12', '198901012014031017'),
('Asisten Riset Ekonomi Digital', 'Membantu penelitian dampak ekonomi dari platform digital.', 2, 'Asisten Penelitian', '2025-05-08', '2025-05-13', '198901012014031017'),
('Asisten Dosen Manajemen Proyek SI', 'Membantu dalam studi kasus manajemen proyek sistem informasi.', 1, 'Asisten Dosen', '2025-05-09', '2025-05-14', '197306061999021016'),
('Asisten Riset Tata Kelola IT', 'Membantu penelitian kerangka kerja tata kelola teknologi informasi.', 1, 'Asisten Penelitian', '2025-05-10', '2025-05-15', '197306061999021016'),
('Asisten Penelitian Audit Sistem Informasi', 'Membantu audit keamanan dan efisiensi sistem informasi.', 2, 'Asisten Penelitian', '2025-05-11', '2025-05-16', '197306061999021016'),
('Asisten Dosen Struktur Data dan Algoritma', 'Membantu dalam kelas struktur data dan analisis algoritma.', 2, 'Asisten Dosen', '2025-05-12', '2025-05-17', '198403222010011015'),
('Penelitian Pengolahan Bahasa Alami', 'Membantu riset dan implementasi NLP untuk analisis teks.', 1, 'Asisten Penelitian', '2025-05-13', '2025-05-18', '198403222010011015'),
('Asisten Dosen Pemrograman Berorientasi Objek', 'Membantu praktikum Java dan konsep OOP.', 3, 'Asisten Dosen', '2025-05-14', '2025-05-19', '198403222010011015'),
('Asisten Penelitian Kampanye Sosial', 'Membantu perancangan dan pelaksanaan kampanye sosial.', 1, 'Asisten Penelitian', '2025-05-15', '2025-05-20', '197909092004031014'),
('Asisten Dosen Jurnalisme Televisi', 'Membantu mahasiswa praktik produksi berita televisi.', 2, 'Asisten Dosen', '2025-05-16', '2025-05-21', '197909092004031014'),
('Asisten Riset Etika Komunikasi', 'Membantu penelitian isu-isu etika dalam media dan komunikasi.', 1, 'Asisten Penelitian', '2025-05-17', '2025-05-22', '197909092004031014'),
('Asisten Penelitian Analisis SWOT', 'Membantu analisis kekuatan, kelemahan, peluang, dan ancaman bisnis.', 1, 'Asisten Penelitian', '2025-05-18', '2025-05-23', '198612142011051013'),
('Asisten Dosen Perencanaan Strategis', 'Membantu praktikum penyusunan rencana strategis perusahaan.', 2, 'Asisten Dosen', '2025-05-19', '2025-05-24', '198612142011051013'),
('Asisten Riset Kewirausahaan Sosial', 'Membantu penelitian model bisnis untuk dampak sosial.', 2, 'Asisten Penelitian', '2025-05-20', '2025-05-25', '198612142011051013'),
('Asisten Dosen Desain Sistem', 'Membantu dalam perancangan dan dokumentasi sistem informasi.', 1, 'Asisten Dosen', '2025-05-21', '2025-05-26', '197108081996021012'),
('Asisten Riset Sistem Rekomendasi', 'Membantu penelitian dan implementasi algoritma sistem rekomendasi.', 1, 'Asisten Penelitian', '2025-05-22', '2025-05-27', '197108081996021012'),
('Asisten Penelitian E-commerce', 'Membantu pengembangan dan manajemen platform e-commerce.', 2, 'Asisten Penelitian', '2025-05-23', '2025-05-28', '197108081996021012'),
('Asisten Dosen Grafika Komputer', 'Membantu dalam kelas grafika komputer dan animasi dasar.', 1, 'Asisten Dosen', '2025-05-24', '2025-05-29', '198310022009041011'),
('Asisten Dosen Struktur Data', 'Membantu pengawasan praktikum dan evaluasi implementasi struktur data.', 2, 'Asisten Dosen', '2025-05-25', '2025-05-30', '198310022009041011'),
('Asisten Riset Komputasi Awan', 'Membantu penelitian arsitektur dan keamanan cloud computing.', 1, 'Asisten Penelitian', '2025-05-26', '2025-05-31', '198310022009041011'),
('Asisten Penelitian Mikrokontroler', 'Membantu pengembangan proyek berbasis mikrokontroler.', 2, 'Asisten Penelitian', '2025-05-27', '2025-06-01', '197701252002011010'),
('Penelitian Cerdas Buatan untuk Otomasi', 'Membantu riset penerapan AI dalam sistem otomasi industri.', 1, 'Asisten Penelitian', '2025-05-28', '2025-06-02', '197701252002011010'),
('Asisten Lab Sistem Kontrol', 'Membantu pengujian dan implementasi sistem kontrol otomatis.', 3, 'Asisten Dosen', '2025-05-29', '2025-06-03', '197701252002011010'),
('Asisten Penelitian Komunikasi Korporat', 'Membantu penyusunan strategi komunikasi internal/eksternal perusahaan.', 1, 'Asisten Penelitian', '2025-05-30', '2025-06-04', '198106182006031009'),
('Asisten Dosen Public Speaking', 'Membantu mahasiswa meningkatkan keterampilan public speaking.', 2, 'Asisten Dosen', '2025-05-31', '2025-06-05', '198106182006031009'),
('Asisten Riset Jurnalisme Digital', 'Membantu penelitian tren dan praktik jurnalisme di era digital.', 1, 'Asisten Penelitian', '2025-06-01', '2025-06-06', '198106182006031009'),
('Asisten Penelitian Analisis Pasar', 'Membantu riset pasar untuk produk atau layanan baru.', 1, 'Asisten Penelitian', '2025-06-02', '2025-06-07', '197204121998021008'),
('Asisten Dosen Pemasaran Internasional', 'Membantu praktikum strategi pemasaran global.', 2, 'Asisten Dosen', '2025-06-03', '2025-06-08', '197204121998021008'),
('Asisten Riset Perilaku Konsumen', 'Membantu analisis data survei perilaku konsumen.', 2, 'Asisten Penelitian', '2025-06-04', '2025-06-09', '197204121998021008'),
('Asisten Dosen Business Intelligence', 'Membantu dalam kelas Business Intelligence dan alat-alatnya.', 1, 'Asisten Dosen', '2025-06-05', '2025-06-10', '198809052015051007'),
('Asisten Riset Data Warehouse', 'Membantu penelitian arsitektur dan implementasi data warehouse.', 1, 'Asisten Penelitian', '2025-06-06', '2025-06-11', '198809052015051007'),
('Asisten Penelitian Big Data Analytics', 'Membantu pengolahan dan visualisasi data besar untuk keputusan bisnis.', 2, 'Asisten Penelitian', '2025-06-07', '2025-06-12', '198809052015051007'),
('Asisten Dosen Sistem Operasi', 'Membantu dalam pembahasan konsep dan praktik sistem operasi.', 1, 'Asisten Dosen', '2025-06-08', '2025-06-13', '197002281995011006'),
('Asisten Dosen Jaringan Komputer', 'Membantu pengawasan dan evaluasi praktikum konfigurasi jaringan.', 2, 'Asisten Dosen', '2025-06-09', '2025-06-14', '197002281995011006'),
('Asisten Riset Keamanan Jaringan', 'Membantu penelitian celah keamanan dan solusi mitigasi di jaringan.', 1, 'Asisten Penelitian', '2025-06-10', '2025-06-15', '197002281995011006'),
('Asisten Dosen Basis Data Lanjut', 'Membantu praktikum basis data, fokus pada SQL lanjutan dan NoSQL.', 3, 'Asisten Dosen', '2025-06-01', '2025-07-01', '198001012005011001'),
('Penelitian Optimasi Algoritma', 'Membantu riset tentang efisiensi algoritma pada data besar.', 1, 'Asisten Penelitian', '2025-06-02', '2025-07-10', '198001012005011001'),
('Asisten Dosen Pemrograman Web', 'Membantu dalam kelas pemrograman web, fokus pada backend PHP.', 2, 'Asisten Dosen', '2025-06-03', '2025-07-05', '198001012005011001'),
('Asisten Penelitian Sistem Informasi', 'Membantu pengembangan sistem informasi manajemen untuk startup.', 2, 'Asisten Penelitian', '2025-06-04', '2025-07-15', '197505152000021002'),
('Penelitian UX/UI Aplikasi Mobile', 'Membantu riset pengalaman pengguna pada aplikasi mobile dan desain UI.', 1, 'Asisten Penelitian', '2025-06-05', '2025-07-20', '197505152000021002'),
('Asisten Dosen Analisis Sistem', 'Membantu dalam kelas analisis dan desain sistem.', 1, 'Asisten Dosen', '2025-06-06', '2025-07-08', '197505152000021002'),
('Asisten Riset Pemasaran Digital', 'Membantu pengumpulan data dan analisis tren pemasaran digital.', 2, 'Asisten Penelitian', '2025-06-07', '2025-07-22', '198211202008031003'),
('Asisten Dosen Manajemen Keuangan', 'Membantu praktikum terkait analisis laporan keuangan dan investasi.', 2, 'Asisten Dosen', '2025-06-08', '2025-07-12', '198211202008031003'),
('Asisten Pengembangan Bisnis Online', 'Membantu perumusan strategi dan operasional bisnis online.', 1, 'Asisten Penelitian', '2025-06-09', '2025-07-25', '198211202008031003'),
('Asisten Riset Komunikasi Antarbudaya', 'Membantu penelitian pola komunikasi di berbagai budaya.', 1, 'Asisten Penelitian', '2025-06-10', '2025-07-28', '197803012003011004'),
('Asisten Produksi Konten Media', 'Membantu dalam pembuatan konten untuk platform media digital.', 2, 'Asisten Penelitian', '2025-06-11', '2025-07-18', '197803012003011004'),
('Asisten Dosen Teori Komunikasi', 'Membantu dalam persiapan materi dan diskusi kelas teori komunikasi.', 1, 'Asisten Dosen', '2025-06-12', '2025-07-30', '197803012003011004'),
('Asisten Lab Elektronika Dasar', 'Membantu pengawasan praktikum sirkuit dan komponen elektronik.', 3, 'Asisten Dosen', '2025-06-13', '2025-08-01', '198507102010041005'),
('Penelitian Energi Terbarukan', 'Membantu riset efisiensi panel surya dan sistem energi hijau.', 1, 'Asisten Penelitian', '2025-06-14', '2025-08-05', '198507102010041005'),
('Asisten Penelitian Robotika', 'Membantu perakitan dan pemrograman robot sederhana.', 2, 'Asisten Penelitian', '2025-06-15', '2025-07-25', '198507102010041005');

INSERT INTO skill_lowongan (skill_id, lowongan_id) VALUES 
(14, 1), 
(5, 2), (2, 2), 
(13, 3), (19, 3), 
(7, 4), (8, 4), 
(2, 5), (4, 5), 
(18, 6), 
(11, 7), (12, 7), 
(14, 8), (11, 8), 
(14, 9), (15, 9), 
(16, 10), 
(14, 11), 
(16, 12), 
(13, 13), (15, 13), 
(13, 14), 
(1, 15), 
(17, 16), 
(2, 17), (4, 17), 
(3, 18), (2, 18), 
(14, 19), 
(14, 20), 
(15, 21), 
(13, 22), 
(13, 23), 
(13, 24), (15, 24), 
(11, 25), 
(2, 26), (4, 26), 
(7, 27), (8, 27), 
(2, 28), (6, 28), 
(3, 29), (17, 29), 
(19, 30), (18, 30), 
(17, 31), 
(4, 32), (2, 32), 
(17, 33), 
(14, 34), (15, 34), 
(14, 35), 
(14, 36), (15, 36), 
(13, 37), (16, 37), 
(14, 38), 
(13, 39), (16, 39), 
(1, 40), (5, 40), 
(1, 41), (5, 41), 
(2, 42), (5, 42), 
(17, 43), 
(18, 44), 
(18, 45), 
(2, 46), (17, 46), 
(15, 47), (16, 47), 
(13, 48), 
(14, 49), 
(7, 50), (11, 50), 
(14, 51), (15, 51), 
(13, 52), (14, 52), 
(15, 53), (16, 53), 
(13, 54), (14, 54), 
(13, 55), 
(11, 56), (12, 56), 
(7, 57), (8, 57), (11, 57), 
(7, 58), (8, 58), 
(2, 59), (17, 59), 
(1, 60), (2, 60);

-- Mahasiswa 1 (Aldo Prasetya - NRP: 1010101010)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('1010101010', 1), ('1010101010', 2), ('1010101010', 7); -- SQL, Python, HTML/CSS
-- Mahasiswa 2 (Bella Cahyani - NRP: 2020202020)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('2020202020', 2), ('2020202020', 4), ('2020202020', 11); -- Python, ML, UI/UX Design
-- Mahasiswa 3 (Candra Dinata - NRP: 3030303030)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('3030303030', 13), ('3030303030', 16), ('3030303030', 14); -- Problem Solving, Statistika, Komunikasi Efektif
-- Mahasiswa 4 (Dina Amelia - NRP: 4040404040)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('4040404040', 14), ('4040404040', 15), ('4040404040', 11); -- Komunikasi Efektif, Penulisan Ilmiah, UI/UX Design
-- Mahasiswa 5 (Eko Wahyudi - NRP: 5050505050)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('5050505050', 1), ('5050505050', 3), ('5050505050', 18); -- SQL, Java, Jaringan Komputer
-- Mahasiswa 6 (Fany Kartika - NRP: 6060606060)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('6060606060', 4), ('6060606060', 5), ('6060606060', 16); -- ML, Data Mining, Statistika
-- Mahasiswa 7 (Gilang Pratama - NRP: 7070707070)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('7070707070', 13), ('7070707070', 14), ('7070707070', 15); -- Problem Solving, Komunikasi Efektif, Penulisan Ilmiah
-- Mahasiswa 8 (Hana Fitriani - NRP: 8080808080)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('8080808080', 7), ('8080808080', 8), ('8080808080', 12); -- HTML/CSS, PHP, Figma
-- Mahasiswa 9 (Iqbal Ramadhan - NRP: 9090909090)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('9090909090', 2), ('9090909090', 17), ('9090909090', 19); -- Python, Algoritma, Cloud Computing
-- Mahasiswa 10 (Jihan Safitri - NRP: 1111111111)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('1111111111', 1), ('1111111111', 5), ('1111111111', 6); -- SQL, Data Mining, JavaScript
-- Mahasiswa 11 (Kevin Adi - NRP: 2222222222)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('2222222222', 13), ('2222222222', 16); -- Problem Solving, Statistika
-- Mahasiswa 12 (Lia Kumala - NRP: 3333333333)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('3333333333', 14), ('3333333333', 15); -- Komunikasi Efektif, Penulisan Ilmiah
-- Mahasiswa 13 (Miko Susanto - NRP: 4444444444)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('4444444444', 3), ('4444444444', 9); -- Java, React
-- Mahasiswa 14 (Nina Puspita - NRP: 5555555555)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('5555555555', 4), ('5555555555', 11); -- ML, UI/UX Design
-- Mahasiswa 15 (Oka Wijaya - NRP: 6666666666)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('6666666666', 13), ('6666666666', 16); -- Problem Solving, Statistika
-- Mahasiswa 16 (Putri Nabila - NRP: 7777777777)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('7777777777', 14), ('7777777777', 15); -- Komunikasi Efektif, Penulisan Ilmiah
-- Mahasiswa 17 (Rizky Alamsyah - NRP: 8888888888)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('8888888888', 18), ('8888888888', 19); -- Jaringan Komputer, Cloud Computing
-- Mahasiswa 18 (Sari Indah - NRP: 9999999999)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('9999999999', 1), ('9999999999', 5); -- SQL, Data Mining
-- Mahasiswa 19 (Taufik Hidayat - NRP: 1212121212)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('1212121212', 13), ('1212121212', 14); -- Problem Solving, Komunikasi Efektif
-- Mahasiswa 20 (Uli Rahmawati - NRP: 1313131313)
INSERT INTO mahasiswa_skill (mahasiswa_nrp, skill_id) VALUES ('1313131313', 11), ('1313131313', 12); -- UI/UX Design, Figma
-- Total: 20 * 3 = 60 baris

INSERT INTO lamaran (tanggal_melamar, status_lamaran, cv_url, transkrip_url, lowongan_id, mahasiswa_nrp) VALUES
('2025-05-01 17:00:00', 'Diterima', 'http://example.com/cv/uli_cv.pdf', 'http://example.com/transkrip/uli_trans.pdf', 1, '1313131313'),
('2025-05-02 17:00:00', 'Ditolak', 'http://example.com/cv/uli_cv.pdf', 'http://example.com/transkrip/uli_trans.pdf', 2, '1313131313'),
('2025-05-02 09:10:00', 'Diterima', 'http://example.com/cv/uli_cv.pdf', 'http://example.com/transkrip/uli_trans.pdf', 3, '1313131313'),
('2025-05-04 17:00:00', 'Ditolak', 'http://example.com/cv/taufik_cv.pdf', 'http://example.com/transkrip/taufik_trans.pdf', 4, '1212121212'),
('2025-05-05 17:00:00', 'Ditolak', 'http://example.com/cv/taufik_cv.pdf', 'http://example.com/transkrip/taufik_trans.pdf', 5, '1212121212'),
('2025-05-05 08:10:00', 'Diterima', 'http://example.com/cv/taufik_cv.pdf', 'http://example.com/transkrip/taufik_trans.pdf', 6, '1212121212'),
('2025-05-07 17:00:00', 'Diterima', 'http://example.com/cv/sari_cv.pdf', 'http://example.com/transkrip/sari_trans.pdf', 7, '9999999999'),
('2025-05-08 17:00:00', 'Diterima', 'http://example.com/cv/sari_cv.pdf', 'http://example.com/transkrip/sari_trans.pdf', 8, '9999999999'),
('2025-05-08 07:10:00', 'Ditolak', 'http://example.com/cv/sari_cv.pdf', 'http://example.com/transkrip/sari_trans.pdf', 9, '9999999999'),
('2025-05-10 17:00:00', 'Ditolak', 'http://example.com/cv/rizky_cv.pdf', 'http://example.com/transkrip/rizky_trans.pdf', 10, '8888888888'),
('2025-05-11 17:00:00', 'Diterima', 'http://example.com/cv/rizky_cv.pdf', 'http://example.com/transkrip/rizky_trans.pdf', 11, '8888888888'),
('2025-05-11 06:10:00', 'Diterima', 'http://example.com/cv/rizky_cv.pdf', 'http://example.com/transkrip/rizky_trans.pdf', 12, '8888888888'),
('2025-05-13 17:00:00', 'Diterima', 'http://example.com/cv/putri_cv.pdf', 'http://example.com/transkrip/putri_trans.pdf', 13, '7777777777'),
('2025-05-14 17:00:00', 'Ditolak', 'http://example.com/cv/putri_cv.pdf', 'http://example.com/transkrip/putri_trans.pdf', 14, '7777777777'),
('2025-05-14 05:10:00', 'Ditolak', 'http://example.com/cv/putri_cv.pdf', 'http://example.com/transkrip/putri_trans.pdf', 15, '7777777777'),
('2025-05-16 17:00:00', 'Ditolak', 'http://example.com/cv/oka_cv.pdf', 'http://example.com/transkrip/oka_trans.pdf', 16, '6666666666'),
('2025-05-17 17:00:00', 'Diterima', 'http://example.com/cv/oka_cv.pdf', 'http://example.com/transkrip/oka_trans.pdf', 17, '6666666666'),
('2025-05-17 04:10:00', 'Diterima', 'http://example.com/cv/oka_cv.pdf', 'http://example.com/transkrip/oka_trans.pdf', 18, '6666666666'),
('2025-05-19 17:00:00', 'Diterima', 'http://example.com/cv/nina_cv.pdf', 'http://example.com/transkrip/nina_trans.pdf', 19, '5555555555'),
('2025-05-20 17:00:00', 'Diterima', 'http://example.com/cv/nina_cv.pdf', 'http://example.com/transkrip/nina_trans.pdf', 20, '5555555555'),
('2025-05-20 03:10:00', 'Diterima', 'http://example.com/cv/nina_cv.pdf', 'http://example.com/transkrip/nina_trans.pdf', 21, '5555555555'),
('2025-05-22 17:00:00', 'Diterima', 'http://example.com/cv/miko_cv.pdf', 'http://example.com/transkrip/miko_trans.pdf', 22, '4444444444'),
('2025-05-23 17:00:00', 'Ditolak', 'http://example.com/cv/miko_cv.pdf', 'http://example.com/transkrip/miko_trans.pdf', 23, '4444444444'),
('2025-05-23 02:10:00', 'Ditolak', 'http://example.com/cv/miko_cv.pdf', 'http://example.com/transkrip/miko_trans.pdf', 24, '4444444444'),
('2025-05-25 17:00:00', 'Diterima', 'http://example.com/cv/lia_cv.pdf', 'http://example.com/transkrip/lia_trans.pdf', 25, '3333333333'),
('2025-05-26 17:00:00', 'Diterima', 'http://example.com/cv/lia_cv.pdf', 'http://example.com/transkrip/lia_trans.pdf', 26, '3333333333'),
('2025-05-26 11:10:00', 'Ditolak', 'http://example.com/cv/lia_cv.pdf', 'http://example.com/transkrip/lia_trans.pdf', 27, '3333333333'),
('2025-05-28 17:00:00', 'Diterima', 'http://example.com/cv/kevin_cv.pdf', 'http://example.com/transkrip/kevin_trans.pdf', 28, '2222222222'),
('2025-05-29 17:00:00', 'Diterima', 'http://example.com/cv/kevin_cv.pdf', 'http://example.com/transkrip/kevin_trans.pdf', 29, '2222222222'),
('2025-05-29 10:10:00', 'Diterima', 'http://example.com/cv/kevin_cv.pdf', 'http://example.com/transkrip/kevin_trans.pdf', 30, '2222222222'),
('2025-05-31 17:00:00', 'Diterima', 'http://example.com/cv/jihan_cv.pdf', 'http://example.com/transkrip/jihan_trans.pdf', 31, '1111111111'),
('2025-06-01 17:00:00', 'Ditolak', 'http://example.com/cv/jihan_cv.pdf', 'http://example.com/transkrip/jihan_trans.pdf', 32, '1111111111'),
('2025-06-01 09:10:00', 'Diterima', 'http://example.com/cv/jihan_cv.pdf', 'http://example.com/transkrip/jihan_trans.pdf', 33, '1111111111'),
('2025-06-03 17:00:00', 'Ditolak', 'http://example.com/cv/iqbal_cv.pdf', 'http://example.com/transkrip/iqbal_trans.pdf', 34, '9090909090'),
('2025-06-04 17:00:00', 'Diterima', 'http://example.com/cv/iqbal_cv.pdf', 'http://example.com/transkrip/iqbal_trans.pdf', 35, '9090909090'),
('2025-06-04 08:10:00', 'Diterima', 'http://example.com/cv/iqbal_cv.pdf', 'http://example.com/transkrip/iqbal_trans.pdf', 36, '9090909090'),
('2025-06-06 17:00:00', 'Ditolak', 'http://example.com/cv/hana_cv.pdf', 'http://example.com/transkrip/hana_trans.pdf', 37, '8080808080'),
('2025-06-07 17:00:00', 'Diterima', 'http://example.com/cv/hana_cv.pdf', 'http://example.com/transkrip/hana_trans.pdf', 38, '8080808080'),
('2025-06-07 07:10:00', 'Diterima', 'http://example.com/cv/hana_cv.pdf', 'http://example.com/transkrip/hana_trans.pdf', 39, '8080808080'),
('2025-06-09 17:00:00', 'Diterima', 'http://example.com/cv/gilang_cv.pdf', 'http://example.com/transkrip/gilang_trans.pdf', 40, '7070707070'),
('2025-06-10 17:00:00', 'Ditolak', 'http://example.com/cv/gilang_cv.pdf', 'http://example.com/transkrip/gilang_trans.pdf', 41, '7070707070'),
('2025-06-10 06:10:00', 'Diterima', 'http://example.com/cv/gilang_cv.pdf', 'http://example.com/transkrip/gilang_trans.pdf', 42, '7070707070'),
('2025-06-12 17:00:00', 'Ditolak', 'http://example.com/cv/fany_cv.pdf', 'http://example.com/transkrip/fany_trans.pdf', 43, '6060606060'),
('2025-06-13 17:00:00', 'Ditolak', 'http://example.com/cv/fany_cv.pdf', 'http://example.com/transkrip/fany_trans.pdf', 44, '6060606060'),
('2025-06-13 05:10:00', 'Ditinjau', 'http://example.com/cv/fany_cv.pdf', 'http://example.com/transkrip/fany_trans.pdf', 45, '6060606060'),
('2025-06-16 04:00:00', 'Ditinjau', 'http://example.com/cv/eko_cv.pdf', 'http://example.com/transkrip/eko_trans.pdf', 46, '5050505050'),
('2025-06-16 04:05:00', 'Ditinjau', 'http://example.com/cv/eko_cv.pdf', 'http://example.com/transkrip/eko_trans.pdf', 47, '5050505050'),
('2025-06-16 04:10:00', 'Ditinjau', 'http://example.com/cv/eko_cv.pdf', 'http://example.com/transkrip/eko_trans.pdf', 48, '5050505050'),
('2025-06-16 03:45:00', 'Ditinjau', 'http://example.com/cv/dina_cv.pdf', 'http://example.com/transkrip/dina_trans.pdf', 49, '4040404040'),
('2025-06-16 03:50:00', 'Ditinjau', 'http://example.com/cv/dina_cv.pdf', 'http://example.com/transkrip/dina_trans.pdf', 50, '4040404040'),
('2025-06-16 03:55:00', 'Ditinjau', 'http://example.com/cv/dina_cv.pdf', 'http://example.com/transkrip/dina_trans.pdf', 51, '4040404040'),
('2025-06-16 03:30:00', 'Ditinjau', 'http://example.com/cv/candra_cv.pdf', 'http://example.com/transkrip/candra_trans.pdf', 52, '3030303030'),
('2025-06-16 03:35:00', 'Ditinjau', 'http://example.com/cv/candra_cv.pdf', 'http://example.com/transkrip/candra_trans.pdf', 53, '3030303030'),
('2025-06-16 03:40:00', 'Ditinjau', 'http://example.com/cv/candra_cv.pdf', 'http://example.com/transkrip/candra_trans.pdf', 54, '3030303030'),
('2025-06-16 03:15:00', 'Ditinjau', 'http://example.com/cv/bella_cv.pdf', 'http://example.com/transkrip/bella_trans.pdf', 55, '2020202020'),
('2025-06-16 03:20:00', 'Ditinjau', 'http://example.com/cv/bella_cv.pdf', 'http://example.com/transkrip/bella_trans.pdf', 56, '2020202020'),
('2025-06-16 03:25:00', 'Ditinjau', 'http://example.com/cv/bella_cv.pdf', 'http://example.com/transkrip/bella_trans.pdf', 57, '2020202020'),
('2025-06-16 03:00:00', 'Ditinjau', 'http://example.com/cv/aldo_cv.pdf', 'http://example.com/transkrip/aldo_trans.pdf', 58, '1010101010'),
('2025-06-16 03:05:00', 'Ditinjau', 'http://example.com/cv/aldo_cv.pdf', 'http://example.com/transkrip/aldo_trans.pdf', 59, '1010101010'),
('2025-06-16 03:10:00', 'Ditinjau', 'http://example.com/cv/aldo_cv.pdf', 'http://example.com/transkrip/aldo_trans.pdf', 60, '1010101010');