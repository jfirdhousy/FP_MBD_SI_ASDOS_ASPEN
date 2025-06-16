-- Function 1: Mengambil nama mahasiswa berdasarkan NRP
DELIMITER $$
CREATE FUNCTION GET_MAHASISWA_NAMA(p_nrp VARCHAR(10))
RETURNS VARCHAR(255)
READS SQL DATA
BEGIN
    DECLARE nama_mhs VARCHAR(255);
    SELECT nama_mahasiswa INTO nama_mhs FROM mahasiswa WHERE nrp = p_nrp;
    RETURN nama_mhs;
END$$
DELIMITER ;
    
