# Aplikasi Penjualan

Aplikasi penjualan sederhana yang responsif untuk tampilan mobile menggunakan HTML, CSS, JavaScript (frontend) dan PHP + MySQL (backend).

## Fitur Utama

### Manajemen Produk (Admin)
- Tambah / Edit / Hapus produk
- Field produk: id, sku (unik), nama, kategori, deskripsi, harga_jual, harga_beli (opsional), stok, gambar (upload dan disimpan di server), created_at, updated_at
- Validasi: SKU unik; gambar hanya JPG/PNG, max 2MB

### Transaksi Penjualan
- Form transaksi: pilih produk (search/autocomplete), masukkan kuantitas, menambahkan beberapa item per transaksi
- Saat transaksi disimpan, kurangi stok produk sesuai qty; jika stok tidak cukup tampilkan error
- Simpan data transaksi ke tabel sales dan rincian ke sale_items
- Tampilkan nomor invoice otomatis (unik)

### Pelanggan (opsional)
- Bisa menyimpan data pelanggan: id, nama, email, telepon, alamat
- Transaksi dapat dikaitkan ke pelanggan

### Login & Keamanan
- Login admin (email & password)
- Password disimpan menggunakan password_hash() PHP
- Proteksi halaman admin/dashboard dengan session

### Dashboard Admin
- Melihat ringkasan: total penjualan hari ini, total pendapatan, jumlah produk habis/stok rendah
- Daftar transaksi terakhir
- Export data transaksi atau daftar produk ke CSV
- Melihat dan mengunduh gambar produk

### Frontend (Catalog / Kasir sederhana)
- Halaman katalog produk responsif (grid/list), dengan pencarian & filter kategori
- Halaman kasir/pos untuk memasukkan transaksi cepat (mobile friendly)

## Instalasi

### Persyaratan
- XAMPP (atau server web lain dengan PHP dan MySQL)
- PHP 7.0 atau lebih tinggi
- MySQL 5.6 atau lebih tinggi

### Langkah-langkah instalasi

1. Ekstrak file aplikasi ke direktori htdocs di XAMPP (misal: `C:\xampp\htdocs\penjualan`)

2. Buat folder untuk upload gambar dan berikan permission:
   ```bash
   mkdir -p uploads/products
   chmod 755 uploads/products