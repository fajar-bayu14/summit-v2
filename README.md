# Summit — Platform Manajemen & Reservasi Tiket Pendakian Gunung

Platform ekosistem terpadu yang dirancang untuk mendigitalkan dan mengotomatisasi seluruh siklus perizinan pendakian gunung, operasional basecamp, dan pengelolaan logistik luar ruang secara aman dan transparan.

---

## 🏔️ Latar Belakang & Masalah yang Diselesaikan

Proses perizinan pendakian gunung dan layanan pendukung di lapangan sering kali menghadapi berbagai tantangan operasional dan risiko keamanan:

1. **Fragmentasi & Risiko Transaksi Pendaki**
   - Pendaki harus mengurus administrasi dan syarat identitas secara manual atau melalui saluran terpisah.
   - Pembayaran tiket dan logistik melalui transfer manual tidak memiliki jaminan keamanan dan rawan terhadap penipuan.
   - Kesulitan memesan tiket masuk (simaksi), alat sewa, serta pemandu (*guide*/*porter*) dalam satu transaksi terpadu.

2. **Pengelolaan Operasional Manual oleh Pengelola Basecamp (Mitra)**
   - Kuota harian jalur pendakian, status buka/tutup jalur akibat cuaca darurat, serta stok inventaris sewa masih dicatat manual.
   - Ketidakpastian jadwal pencairan pendapatan operasional dari tiket dan layanan yang telah diselesaikan.

3. **Pengawasan & Validasi Terpusat bagi Pengelola Platform (Admin)**
   - Kebutuhan verifikasi legalitas mitra pengelola dan dokumen identitas pendaki secara terstandar.
   - Perlunya sistem penampungan dana bersama (*escrow*) yang transparan agar dana transaksi aman sampai pendakian dinyatakan selesai.

---

## 🎯 Solusi yang Dihadirkan

Summit menghadirkan sistem terintegrasi yang menyelesaikan masalah di atas melalui:

- **Perizinan & Registrasi Terpusat:** Verifikasi identitas (*KYC*) pendaki dan mitra untuk memastikan legalitas dan keselamatan di jalur pendakian.
- **Manajemen Kuota & Jalur Real-Time:** Pengaturan kuota harian otomatis guna mencegah penumpukan (*overcapacity*) serta kontrol buka/tutup jalur darurat seketika.
- **Pemesanan Terpadu (*All-in-One*):** Reservasi tiket masuk, penyewaan perlengkapan tenda/alat gunung, dan layanan pemandu dalam satu keranjang belanja.
- **Sistem Penjaminan Dana (*Escrow*):** Dana pembayaran pendaki diamankan dalam status tertahan (*pending escrow*) dan baru diteruskan ke saldo dompet mitra setelah pendakian selesai divalidasi atau melalui check-out basecamp.
- **Logbook Digital & Sertifikat Pendakian:** Validasi kepulangan pendaki melalui unggah bukti puncak (*summit proof*) atau check-out mandiri di basecamp, disertai penerbitan sertifikat digital resmi.

---

## ⚙️ Panduan Instalasi & Menjalankan Sistem

### 1. Layanan Backend (API & Database)

Pastikan lingkungan telah mendukung dependensi paket aplikasi, lalu jalankan perintah berikut:

```bash
# Masuk ke direktori backend
cd backend

# Instalasi dependensi
composer install

# Salin file konfigurasi lingkungan
cp .env.example .env

# Generate application key
php artisan key:generate

# Jalankan migrasi database
php artisan migrate

# Jalankan server lokal
php artisan serve
```

---

### 2. Antarmuka Web (Dashboard Admin & Mitra)

```bash
# Masuk ke direktori frontend
cd frontend

# Instalasi dependensi
npm install

# Menjalankan server pengembangan
npm run dev

# Membangun file produksi
npm run build
```

---

### 3. Aplikasi Mobile (Pendaki)

```bash
# Masuk ke direktori mobile
cd mobile

# Instalasi dependensi
pnpm install

# Menjalankan aplikasi dalam mode pengembangan
pnpm dev
```

---

## 📦 Data Dummy & Perintah Seeder

Untuk mengisi database dengan struktur data awal atau dataset simulasi lengkap (master data gunung, jalur, basecamp, produk tiket, dan sewa alat):

### 1. Seeder Standar (Pengguna Dasar & Admin)
```bash
cd backend
php artisan db:seed
```

### 2. Seeder Demo Lengkap (Katalog, Jalur, Produk, & Pesanan)
```bash
cd backend
php artisan db:seed --class=DemoSeeder
```
*Perintah ini akan membuat data simulasi Gunung Gede, Jalur Cibodas, Basecamp Cibodas Indah, Produk Tiket Simaksi, serta Produk Sewa Tenda.*

---

## 👤 Akun Pengguna Dummy (Credentials)

Setelah menjalankan seeder, Anda dapat menggunakan akun-akun berikut untuk masuk ke sistem:

| Role | Email | Password | Hak Akses & Fungsi |
| :--- | :--- | :--- | :--- |
| **Admin Platform** | `admin@example.com` | `password` | Pengawasan transaksi global, verifikasi identitas (KYC), kelola master data gunung & jalur, persetujuan legalitas mitra. |
| **Mitra Basecamp** | `mitra@example.com` | `password` | Manajemen kuota & status jalur, pengaturan harga tiket & sewa alat, verifikasi pendaki, penyelesaian pendakian (*check-out*), validasi logbook, dompet & penarikan saldo. |
| **Pendaki** | `pendaki@example.com` | `password` | Pencarian tiket & jalur, pemesanan paket & sewa alat, unggah bukti puncak (*logbook digital*), klaim sertifikat pendakian. |
