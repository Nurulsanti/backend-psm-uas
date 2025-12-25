## Backend UAS PSM-BI

Proyek ini merupakan **API Backend** yang dibangun menggunakan **Laravel 12** untuk mendukung aplikasi mobile pada Ujian Akhir Semester (UAS). Backend ini bertugas mengelola data produk, transaksi, serta menyediakan berbagai metrik untuk kebutuhan dashboard Business Intelligence.

## Fitur Utama

**Product Management**  
  API untuk menampilkan daftar produk, kategori produk, dan detail produk.

**Transaction Management**  
  API untuk mencatat transaksi dan menampilkan riwayat transaksi.

**Dashboard Analytics**  
  Menyediakan berbagai endpoint analitik, seperti:
  - Ringkasan penjualan
  - Tren penjualan bulanan dan harian
  - Produk terlaris
  - Performa berdasarkan kategori, wilayah, dan segmen

## Persyaratan Sistem
- PHP ^8.2  
- Composer  
- SQLite (sebagai database default)

## Instalasi
1. Clone Repository
```bash
git clone <url-repository>
cd backend-psm-uas

2. Instal Dependensi
composer install

3. Setup Lingkungan
cp .env.example .env
php artisan key:generate

4. Migrasi Database
php artisan migrate

5. Jalankan Server
php artisan serve
