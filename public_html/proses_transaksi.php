<?php
// 1. MULAI SESSION (WAJIB DI BARIS PERTAMA)
session_start();

// 2. HUBUNGKAN KE KONEKSI
include 'koneksi.php'; // Menyediakan variabel $koneksi

// 3. KEAMANAN: Cek login
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

// 4. Pastikan hanya dijalankan jika form disubmit via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 5. Ambil data utama dari form
    $tipe_transaksi = $_POST['tipe_transaksi'];
    $pelanggan_id = $_POST['pelanggan_id']; 
    $metode_bayar_nama = $_POST['metode_bayar'];
    $jumlah_bayar = $_POST['jumlah_bayar']; 
    $detail_items_json = $_POST['detail_items_json']; 

    // 6. Ambil data dari SESSION
    $karyawanID = $_SESSION['KaryawanID']; 

    // 7. DECODE JSON dan HITUNG TOTAL
    $items_array = json_decode($detail_items_json, true);
    if (empty($items_array)) {
        $_SESSION['error_message'] = "Error: Keranjang tidak boleh kosong.";
        header("Location: transaksi_baru.php");
        exit;
    }

    $total_transaksi = 0; // Total harga barang/jasa
    $total_ongkos = 0; // Total ongkos

    foreach ($items_array as $item) {
        if ($item['tipe'] == 'barang') {
            $total_transaksi += $item['harga'];
            $total_ongkos += $item['ongkos'];
        } elseif ($item['tipe'] == 'jasa') {
            $total_transaksi += $item['harga'];
        }
    }
    
    if (strtolower($tipe_transaksi) == 'buyback') {
        $total_transaksi = -abs($total_transaksi);
        $total_ongkos = -abs($total_ongkos);
    }
    
    $pelanggan_id_db = !empty($pelanggan_id) ? $pelanggan_id : NULL;

    // --- MULAI DATABASE TRANSACTION ---
    $koneksi->begin_transaction();

    try {
        // === KUERI 1: INSERT KE TABEL TRANSAKSI (Header) ===
        $sql_trans = "INSERT INTO TRANSAKSI (PelangganID, KaryawanID, TipeTransaksi, TotalTransaksi, TotalOngkos) 
                      VALUES (?, ?, ?, ?, ?)";
        $stmt_trans = $koneksi->prepare($sql_trans);
        $stmt_trans->bind_param("iisdd", 
            $pelanggan_id_db, $karyawanID, $tipe_transaksi, 
            $total_transaksi, $total_ongkos
        );
        if (!$stmt_trans->execute()) {
            throw new Exception("Gagal menyimpan header transaksi: " . $stmt_trans->error);
        }
        $transaksiID = $koneksi->insert_id;
        $stmt_trans->close();

        
        // === KUERI 2 & 3: INSERT DETAIL (Barang & Jasa) ===
        $sql_detail_brg = "INSERT INTO DETAIL_TRANSAKSI_BARANG (TransaksiID, BarangID, HargaSatuanSaatItu, Ongkos) VALUES (?, ?, ?, ?)";
        $stmt_brg = $koneksi->prepare($sql_detail_brg);

        $sql_detail_jasa = "INSERT INTO DETAIL_TRANSAKSI_JASA (TransaksiID, JenisJasa, BiayaJasa) VALUES (?, ?, ?)";
        $stmt_jasa = $koneksi->prepare($sql_detail_jasa);

        foreach ($items_array as $item) {
            if ($item['tipe'] == 'barang') {
                $barang_id_int = $item['barang_id_int'];
                $stmt_brg->bind_param("iidd", $transaksiID, $barang_id_int, $item['harga'], $item['ongkos']);
                if (!$stmt_brg->execute()) {
                    throw new Exception("Gagal menyimpan detail barang: " . $stmt_brg->error);
                }
                
                // === KUERI 4: UPDATE STATUS BARANG_STOK ===
                $status_baru = (strtolower($tipe_transaksi) == 'penjualan') ? 'Terjual' : 'Masuk (Buyback)';
                $sql_update_stok = "UPDATE BARANG_STOK SET Status = ? WHERE BarangID = ?";
                $stmt_stok = $koneksi->prepare($sql_update_stok);
                $stmt_stok->bind_param("si", $status_baru, $barang_id_int);
                if (!$stmt_stok->execute()) {
                     throw new Exception("Gagal update status stok: " . $stmt_stok->error);
                }
                $stmt_stok->close();

            } elseif ($item['tipe'] == 'jasa') {
                $stmt_jasa->bind_param("isd", $transaksiID, $item['nama'], $item['harga']);
                if (!$stmt_jasa->execute()) {
                    throw new Exception("Gagal menyimpan detail jasa: " . $stmt_jasa->error);
                }
            }
        }
        $stmt_brg->close();
        $stmt_jasa->close();

        // === KUERI 5: INSERT KE TABEL PEMBAYARAN ===
        $metodeID = 1; // Default "Tunai"
        if ($metode_bayar_nama == 'QRIS') $metodeID = 2;
        if ($metode_bayar_nama == 'Transfer') $metodeID = 3;
        
        $sql_bayar = "INSERT INTO PEMBAYARAN (TransaksiID, MetodeID, JumlahBayar) VALUES (?, ?, ?)";
        $stmt_bayar = $koneksi->prepare($sql_bayar);
        $stmt_bayar->bind_param("iid", $transaksiID, $metodeID, $jumlah_bayar);
        if (!$stmt_bayar->execute()) {
            throw new Exception("Gagal menyimpan pembayaran: " . $stmt_bayar->error);
        }
        $stmt_bayar->close();

        // Jika semua berhasil, commit transaksi
        $koneksi->commit();
        
        // --- PERBAIKAN 1: Gunakan SESSION, bukan echo ---
        $_SESSION['success_message'] = "Transaksi berhasil disimpan! ID Transaksi: $transaksiID";
        
    } catch (Exception $e) {
        // Jika terjadi error, batalkan semua
        $koneksi->rollback();
        
        // --- PERBAIKAN 2: Gunakan SESSION, bukan echo ---
        // Kita gunakan addslashes untuk menangani kutip di pesan error
        $_SESSION['error_message'] = "TRANSAKSI GAGAL! Terjadi kesalahan: " . addslashes($e->getMessage());
    }
    
    // --- PERBAIKAN 3: Tutup koneksi di sini ---
    // Kode ini SEKARANG TERJANGKAU (reachable)
    $koneksi->close();

    // Lakukan redirect di akhir
    header("Location: transaksi_baru.php");
    exit;

} else {
    // Jika file diakses langsung (bukan via POST)
    header("Location: transaksi_baru.php");
    exit;
}
?>