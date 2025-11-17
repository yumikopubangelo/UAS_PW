

USE toko_emas_amanda;

CREATE TABLE KARYAWAN (
    KaryawanID INT AUTO_INCREMENT PRIMARY KEY,
    NamaKaryawan VARCHAR(100) NOT NULL,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Role VARCHAR(20) NOT NULL)
;

DESC KARYAWAN;
    
CREATE TABLE PELANGGAN (
    PelangganID INT AUTO_INCREMENT PRIMARY KEY,
    NamaPelanggan VARCHAR(100) NOT NULL,
    NoHP VARCHAR(20) UNIQUE,
    Alamat VARCHAR(255)
);

DESC PELANGGAN;
    
CREATE TABLE SUPPLIER (
    SupplierID INT AUTO_INCREMENT PRIMARY KEY,
    NamaSupplier VARCHAR(100) NOT NULL,
    Kontak VARCHAR(50),
    Alamat VARCHAR(255)
);

DESC SUPPLIER;

CREATE TABLE PRODUK_KATALOG (
    ProdukKatalogID INT AUTO_INCREMENT PRIMARY KEY,
    NamaProduk VARCHAR(100) NOT NULL,
    Tipe VARCHAR(50),
    Kadar VARCHAR(10),
    Satuan VARCHAR(10)
);

DESC PRODUK_KATALOG;

CREATE TABLE RIWAYAT_HARGA (
    HargaID INT AUTO_INCREMENT PRIMARY KEY,
    Tanggal DATE NOT NULL,
    Kadar VARCHAR(10) NOT NULL,
    HargaJualPerGram DECIMAL(14, 2) NOT NULL,
    HargaBeliPerGram DECIMAL(14, 2) NOT NULL
);

DESC RIWAYAT_HARGA;

CREATE TABLE BARANG_STOK (
    BarangID INT AUTO_INCREMENT PRIMARY KEY,
    KodeBarang VARCHAR(10) UNIQUE,
    ProdukKatalogID INT NOT NULL,
    SupplierID INT,
    BeratGram DECIMAL(10, 2) NOT NULL,
    HargaBeliModal DECIMAL(14, 2) NOT NULL,
    TanggalMasuk DATE NOT NULL,
    Status VARCHAR(30) NOT NULL DEFAULT 'Tersedia',
    AsalBarang VARCHAR(50),
    CONSTRAINT fk_stok_katalog FOREIGN KEY (ProdukKatalogID) REFERENCES PRODUK_KATALOG(ProdukKatalogID),
    CONSTRAINT fk_stok_supplier FOREIGN KEY (SupplierID) REFERENCES SUPPLIER(SupplierID)
);

DESC BARANG_STOK;

CREATE TABLE TRANSAKSI (
    TransaksiID INT AUTO_INCREMENT PRIMARY KEY,
    PelangganID INT,
    KaryawanID INT NOT NULL,
    TanggalWaktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    TipeTransaksi VARCHAR(20) NOT NULL,
    TotalTransaksi DECIMAL(14, 2) DEFAULT 0,
    TotalOngkos DECIMAL(14, 2) DEFAULT 0,
    CONSTRAINT fk_transaksi_pelanggan FOREIGN KEY (PelangganID) REFERENCES PELANGGAN(PelangganID),
    CONSTRAINT fk_transaksi_karyawan FOREIGN KEY (KaryawanID) REFERENCES KARYAWAN(KaryawanID)
);

DESC TRANSAKSI;

CREATE TABLE DETAIL_TRANSAKSI_BARANG (
    DetailTransaksiID INT AUTO_INCREMENT PRIMARY KEY,
    TransaksiID INT NOT NULL,
    BarangID INT NOT NULL,
    HargaSatuanSaatItu DECIMAL(14, 2) NOT NULL,
    Ongkos DECIMAL(14, 2) DEFAULT 0,
    CONSTRAINT fk_detailbarang_transaksi FOREIGN KEY (TransaksiID) REFERENCES TRANSAKSI(TransaksiID),
    CONSTRAINT fk_detailbarang_stok FOREIGN KEY (BarangID) REFERENCES BARANG_STOK(BarangID));

DESC DETAIL_TRANSAKSI_BARANG;

CREATE TABLE DETAIL_TRANSAKSI_JASA (
    DetailJasaID INT AUTO_INCREMENT PRIMARY KEY,
    TransaksiID INT NOT NULL,
    JenisJasa VARCHAR(100) NOT NULL,
    BiayaJasa DECIMAL(14, 2) NOT NULL,
    CONSTRAINT fk_detailjasa_transaksi FOREIGN KEY (TransaksiID) REFERENCES TRANSAKSI(TransaksiID));

DESC DETAIL_TRANSAKSI_JASA;

CREATE TABLE PEMBAYARAN (
    PembayaranID INT AUTO_INCREMENT PRIMARY KEY,
    TransaksiID INT NOT NULL,
    MetodeID INT NOT NULL,
    JumlahBayar DECIMAL(14, 2) NOT NULL);

ALTER TABLE PEMBAYARAN
    ADD CONSTRAINT fk_bayar_transaksi FOREIGN KEY (TransaksiID) REFERENCES TRANSAKSI(TransaksiID),
    ADD CONSTRAINT fk_bayar_metode FOREIGN KEY (MetodeID) REFERENCES METODE_PEMBAYARAN(MetodeID);

DESC PEMBAYARAN;

CREATE TABLE LOG_STATUS_BARANG (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    BarangID INT,
    KodeBarangLama VARCHAR(10),
    StatusLama VARCHAR(30),
    StatusBaru VARCHAR(30),
    WaktuPerubahan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Keterangan VARCHAR(255)
);

DESC LOG_STATUS_BARANG;

CREATE TABLE METODE_PEMBAYARAN (
    MetodeID INT AUTO_INCREMENT PRIMARY KEY,
    NamaMetode VARCHAR(50) NOT NULL
);

DESC METODE_PEMBAYARAN;

INSERT INTO KARYAWAN (NamaKaryawan, Username, Password, Role) VALUES
('Amanda Wijaya', 'amanda_owner', '*AA1420F182E88B9E5F874F6FBE7459291E8F4601', 'Owner'),
('Budi Hartono', 'budi_kasir', '*7438236BE048F242AA18D2EE074296F078CD701A', 'Kasir'),
('Citra Lestari', 'citra_admin', '*7438236BE048F242AA18D2EE074296F078CD701A', 'Admin');

SELECT * FROM KARYAWAN;

INSERT INTO PELANGGAN (NamaPelanggan, NoHP, Alamat) VALUES
('Rina Gunawan', '08123456789', 'Jl. Merdeka No. 10'),
('Agus Setiawan', '08567891234', 'Jl. Sudirman No. 5');

SELECT * FROM PELANGGAN;

INSERT INTO SUPPLIER (NamaSupplier, Kontak, Alamat) VALUES
('PT. Emas Murni Jaya', '021-5551234', 'Jakarta'),
('CV. Logam Mulia', '031-777888', 'Bandung');

SELECT * FROM SUPPLIER;

INSERT INTO PRODUK_KATALOG (NamaProduk, Tipe, Kadar, Satuan) VALUES
('Cincin Emas Polos', 'Cincin', '17K', 'Gram'),
('Kalung Emas Rantai', 'Kalung', '24K', 'Gram'),
('Anting Emas Mutiara', 'Anting', '9K', 'Gram'),
('Gelang Emas Anak', 'Gelang', 'Emas Muda', 'Gram');

SELECT * FROM PRODUK_KATALOG;

INSERT INTO METODE_PEMBAYARAN (NamaMetode) VALUES
('Tunai'), ('Transfer Bank'), ('QRIS'), ('Debit Card');

SELECT * FROM METODE_PEMBAYARAN;

INSERT INTO RIWAYAT_HARGA (Tanggal, Kadar, HargaJualPerGram, HargaBeliPerGram) VALUES
(CURDATE(), '17K', 950000.00, 890000.00),
(CURDATE(), '24K', 1300000.00, 1250000.00),
(CURDATE(), '9K', 500000.00, 450000.00),
(CURDATE(), 'Emas Muda', 400000.00, 350000.00);

SELECT * FROM RIWAYAT_HARGA;

DELIMITER $$
CREATE TRIGGER trg_GenerateKodeBarang
BEFORE INSERT ON BARANG_STOK
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    SELECT AUTO_INCREMENT INTO next_id
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = 'toko_emas_amanda'
    AND TABLE_NAME = 'BARANG_STOK';
    SET NEW.KodeBarang = CONCAT('BRG', LPAD(next_id, 5, '0'));
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_AfterUpdateStatusBarang
AFTER UPDATE ON BARANG_STOK
FOR EACH ROW
BEGIN
    IF OLD.Status <> NEW.Status THEN
        INSERT INTO LOG_STATUS_BARANG (BarangID, KodeBarangLama, StatusLama, StatusBaru, Keterangan)
        VALUES (OLD.BarangID, OLD.KodeBarang, OLD.Status, NEW.Status, 'Status barang diubah');
    END IF;
END$$
DELIMITER ;

SHOW TRIGGERS;

INSERT INTO BARANG_STOK (ProdukKatalogID, SupplierID, BeratGram, HargaBeliModal, TanggalMasuk, AsalBarang) VALUES
(1, 1, 5.20, 4500000.00, '2025-10-01', 'Supplier');

INSERT INTO BARANG_STOK (ProdukKatalogID, SupplierID, BeratGram, HargaBeliModal, TanggalMasuk, AsalBarang) VALUES
(2, 1, 10.50, 13000000.00, '2025-10-02', 'Supplier');

INSERT INTO BARANG_STOK (ProdukKatalogID, SupplierID, BeratGram, HargaBeliModal, TanggalMasuk, AsalBarang) VALUES
(1, NULL, 3.10, 2700000.00, '2025-10-03', 'Buyback');

INSERT INTO BARANG_STOK (ProdukKatalogID, SupplierID, BeratGram, HargaBeliModal, TanggalMasuk, AsalBarang) VALUES
(3, 2, 2.50, 1100000.00, '2025-10-05', 'Supplier');

SELECT * FROM BARANG_STOK;

INSERT INTO TRANSAKSI (PelangganID, KaryawanID, TipeTransaksi) VALUES (1, 2, 'Penjualan');
SET @TID_Jual = LAST_INSERT_ID();

INSERT INTO DETAIL_TRANSAKSI_BARANG (TransaksiID, BarangID, HargaSatuanSaatItu, Ongkos) VALUES
(@TID_Jual, 1, 4940000.00, 50000.00);

UPDATE TRANSAKSI SET TotalTransaksi = (4940000.00 + 50000.00), TotalOngkos = 50000.00 WHERE TransaksiID = @TID_Jual;

INSERT INTO PEMBAYARAN (TransaksiID, MetodeID, JumlahBayar) VALUES (@TID_Jual, 4, 4990000.00);

UPDATE BARANG_STOK SET Status = 'Terjual' WHERE BarangID = 1;

INSERT INTO TRANSAKSI (PelangganID, KaryawanID, TipeTransaksi) VALUES (2, 2, 'Buyback'); -- (Agus, dilayani Budi)
SET @TID_Buyback = LAST_INSERT_ID();

INSERT INTO DETAIL_TRANSAKSI_BARANG (TransaksiID, BarangID, HargaSatuanSaatItu, Ongkos) VALUES
(@TID_Buyback, 3, 2759000.00, 0.00);

UPDATE TRANSAKSI SET TotalTransaksi = 2759000.00 WHERE TransaksiID = @TID_Buyback;

INSERT INTO PEMBAYARAN (TransaksiID, MetodeID, JumlahBayar) VALUES (@TID_Buyback, 1, 2759000.00);

INSERT INTO TRANSAKSI (PelangganID, KaryawanID, TipeTransaksi, TotalTransaksi) VALUES (1, 2, 'Servis', 50000.00);
SET @TID_Jasa = LAST_INSERT_ID();

INSERT INTO DETAIL_TRANSAKSI_JASA (TransaksiID, JenisJasa, BiayaJasa) VALUES
(@TID_Jasa, 'Cuci Emas', 50000.00);

INSERT INTO PEMBAYARAN (TransaksiID, MetodeID, JumlahBayar) VALUES (@TID_Jasa, 3, 50000.00);

SELECT * FROM TRANSAKSI;
SELECT * FROM DETAIL_TRANSAKSI_BARANG;
SELECT * FROM DETAIL_TRANSAKSI_JASA;
SELECT * FROM PEMBAYARAN;

SELECT * FROM LOG_STATUS_BARANG;

SELECT
    BS.BarangID,
    PK.NamaProduk,
    PK.Kadar,
    BS.Status,
    CASE
        WHEN BS.Status = 'Tersedia' THEN 'Siap Dijual'
        WHEN BS.Status = 'Terjual' THEN 'Sudah Laku'
        ELSE 'Lainnya'
    END AS Keterangan,
    IF(BS.HargaBeliModal > 5000000, 'Stok Bernilai Tinggi', 'Stok Reguler') AS LevelStok
FROM BARANG_STOK BS
JOIN PRODUK_KATALOG PK ON BS.ProdukKatalogID = PK.ProdukKatalogID;

CREATE VIEW V_STOK_TERSEDIA AS
SELECT
    BS.BarangID,
    BS.KodeBarang,
    PK.NamaProduk,
    PK.Tipe,
    PK.Kadar,
    BS.BeratGram,
    BS.HargaBeliModal,
    BS.AsalBarang
FROM BARANG_STOK BS
JOIN PRODUK_KATALOG PK ON BS.ProdukKatalogID = PK.ProdukKatalogID
WHERE BS.Status = 'Tersedia';

SELECT * FROM V_STOK_TERSEDIA;

CREATE VIEW V_LAPORAN_PENJUALAN AS
SELECT
    T.TransaksiID,
    T.TanggalWaktu,
    PEL.NamaPelanggan,
    KAR.NamaKaryawan AS Kasir,
    PK.NamaProduk,
    BS.BeratGram,
    DTB.HargaSatuanSaatItu,
    DTB.Ongkos,
    T.TotalTransaksi
FROM TRANSAKSI T
JOIN DETAIL_TRANSAKSI_BARANG DTB ON T.TransaksiID = DTB.TransaksiID
JOIN BARANG_STOK BS ON DTB.BarangID = BS.BarangID
JOIN PRODUK_KATALOG PK ON BS.ProdukKatalogID = PK.ProdukKatalogID
JOIN KARYAWAN KAR ON T.KaryawanID = KAR.KaryawanID
LEFT JOIN PELANGGAN PEL ON T.PelangganID = PEL.PelangganID
WHERE T.TipeTransaksi = 'Penjualan';

SELECT * FROM V_LAPORAN_PENJUALAN;

DELIMITER $$
CREATE FUNCTION fn_GetHargaJualHariIni (pKadar VARCHAR(10))
RETURNS DECIMAL(14, 2)
DETERMINISTIC
BEGIN
    DECLARE vHarga DECIMAL(14, 2);
    SELECT HargaJualPerGram
    INTO vHarga
    FROM RIWAYAT_HARGA
    WHERE Kadar = pKadar AND Tanggal = CURDATE()
    ORDER BY HargaID DESC
    LIMIT 1;
    RETURN IFNULL(vHarga, 0.00);
END$$
DELIMITER ;

SELECT fn_GetHargaJualHariIni('17K') AS HargaJual_17K_HariIni;

DELIMITER $$
CREATE PROCEDURE sp_InputStokBaru (
    IN pProdukKatalogID INT,
    IN pSupplierID INT,
    IN pBeratGram DECIMAL(10, 2),
    IN pHargaBeliModal DECIMAL(14, 2),
    IN pAsalBarang VARCHAR(50)
)
BEGIN
    INSERT INTO BARANG_STOK (
        ProdukKatalogID,
        SupplierID,
        BeratGram,
        HargaBeliModal,
        TanggalMasuk,
        AsalBarang
    ) VALUES (
        pProdukKatalogID,
        pSupplierID,
        pBeratGram,
        pHargaBeliModal,
        CURDATE(),
        pAsalBarang
    );
    SET @NewStokID = LAST_INSERT_ID();
    SELECT 'Stok baru berhasil ditambahkan' AS Status, @NewStokID AS BarangID, 
           (SELECT KodeBarang FROM BARANG_STOK WHERE BarangID = @NewStokID) AS KodeBarang;
END$$
DELIMITER ;

CALL sp_InputStokBaru(4, 2, 8.5, 3000000.00, 'Supplier'); -- Input Gelang Emas Anak

SELECT * FROM V_STOK_TERSEDIA;

CREATE USER 'kasir_amanda'@'localhost' IDENTIFIED BY 'Kasir123';

GRANT SELECT ON toko_emas_amanda.KARYAWAN TO 'kasir_amanda'@'localhost';
GRANT SELECT, INSERT ON toko_emas_amanda.PELANGGAN TO 'kasir_amanda'@'localhost';
GRANT SELECT ON toko_emas_amanda.PRODUK_KATALOG TO 'kasir_amanda'@'localhost';
GRANT SELECT, UPDATE ON toko_emas_amanda.BARANG_STOK TO 'kasir_amanda'@'localhost';
GRANT SELECT ON toko_emas_amanda.RIWAYAT_HARGA TO 'kasir_amanda'@'localhost';
GRANT SELECT ON toko_emas_amanda.METODE_PEMBAYARAN TO 'kasir_amanda'@'localhost';
GRANT SELECT, INSERT, UPDATE ON toko_emas_amanda.TRANSAKSI TO 'kasir_amanda'@'localhost';
GRANT SELECT, INSERT ON toko_emas_amanda.DETAIL_TRANSAKSI_BARANG TO 'kasir_amanda'@'localhost';
GRANT SELECT, INSERT ON toko_emas_amanda.DETAIL_TRANSAKSI_JASA TO 'kasir_amanda'@'localhost';
GRANT SELECT, INSERT ON toko_emas_amanda.PEMBAYARAN TO 'kasir_amanda'@'localhost';
GRANT SELECT ON toko_emas_amanda.V_STOK_TERSEDIA TO 'kasir_amanda'@'localhost';
GRANT EXECUTE ON FUNCTION toko_emas_amanda.fn_GetHargaJualHariIni TO 'kasir_amanda'@'localhost';
GRANT EXECUTE ON PROCEDURE toko_emas_amanda.sp_InputStokBaru TO 'kasir_amanda'@'localhost';

FLUSH PRIVILEGES;

SHOW GRANTS FOR 'kasir_amanda'@'localhost';

CREATE DATABASE toko_emas_amanda_restore;

SHOW DATABASES LIKE 'toko_emas_amanda%';