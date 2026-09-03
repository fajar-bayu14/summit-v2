# TASK LIST: ENDPOINT & FITUR BACKEND ROLE ADMIN

Dokumen ini memuat daftar tugas (*checklist tasks*) lengkap untuk pembuatan dan penyempurnaan seluruh endpoint backend/API untuk **Role Admin** pada platform **Summit v2** (`backend/`).

---

## 📈 Ringkasan Progres Implementasi Admin API

- [x] **Modul 1: Autentikasi & Akun Admin** (4/4 Task Selesai — **100%**)
- [x] **Modul 2: Master Destinasi — Gunung** (4/4 Task Selesai — **100%**)
- [x] **Modul 3: Master Destinasi — Jalur Pendakian** (6/6 Task Selesai — **100%**)
- [x] **Modul 4: Master Kemitraan — Mitra Pengelola** (5/5 Task Selesai — **100%**)
- [x] **Modul 5: Master Kemitraan — Basecamp** (5/5 Task Selesai — **100%**)
- [x] **Modul 6: Verifikasi KYC Pendaki** (4/4 Task Selesai — **100%**)
- [ ] **Modul 7: Manajemen Biaya Admin & Platform Fee** (0/4 Task Selesai — **0%**)
- [ ] **Modul 8: Monitoring Transaksi & Pesanan Lintas Basecamp** (0/3 Task Selesai — **0%**)
- [x] **Modul 9: Monitoring Katalog Produk Lintas Basecamp** (2/2 Task Selesai — **100%**)
- [ ] **Modul 10: Escrow Payout, Penarikan Dana & Refund** (0/5 Task Selesai — **0%**)
- [ ] **Modul 11: Iklan & Monetisasi Banner Promosi** (0/4 Task Selesai — **0%**)
- [ ] **Modul 12: Dashboard Analytics & Ringkasan Metrik** (0/2 Task Selesai — **0%**)

**Total Progres Admin API**: **30 / 44 Task Selesai (~68%)**

---

## 🔐 MODUL 1: AUTENTIKASI & AKUN ADMIN

Modul autentikasi platform untuk role Admin menggunakan Laravel Sanctum Bearer Token dan HttpOnly Cookie.

- [x] **`POST /api/v1/auth/login`** — Login akun Admin
  - **Route Name**: `login`
  - **Controller**: [`AuthController@login`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/AuthController.php#L108-L140)
  - **Request**: [`LoginRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Auth/LoginRequest.php) (`email`, `password`)
  - **Response**: `UserResource` + Bearer Token + HttpOnly Cookie `auth_token`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/profile`** — Mendapatkan data profil Admin yang sedang login
  - **Route Name**: `profile.show`
  - **Controller**: [`ProfileController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/ProfileController.php)
  - **Middleware**: `auth:sanctum`
  - **Response**: `ProfileResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PUT /api/v1/profile/password`** — Memperbarui kata sandi Admin
  - **Route Name**: `profile.password.update`
  - **Controller**: [`ProfileController@updatePassword`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/ProfileController.php)
  - **Request**: [`ChangePasswordRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Auth/ChangePasswordRequest.php) (`current_password`, `new_password`, `new_password_confirmation`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/auth/logout`** — Logout dan revoke token sesi Admin
  - **Route Name**: `logout`
  - **Controller**: [`AuthController@logout`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/AuthController.php#L265-L280)
  - **Middleware**: `auth:sanctum`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## ⛰️ MODUL 2: MASTER DESTINASI — GUNUNG

Manajemen data katalog gunung di seluruh Indonesia oleh Admin.

- [x] **`GET /api/v1/mountains`** — List seluruh katalog gunung (dengan eager load jalurs)
  - **Route Name**: `gunung.index`
  - **Controller**: [`PendakiGunungController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Pendaki/GunungController.php#L37-L47)
  - **Response**: Paginated list `GunungResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/mountains/{id}`** — Detail informasi gunung spesifik
  - **Route Name**: `gunung.show`
  - **Controller**: [`PendakiGunungController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Pendaki/GunungController.php#L73-L82)
  - **Response**: Single `GunungResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/admin/mountains`** — Tambah data gunung baru (dengan upload foto)
  - **Route Name**: `admin.gunung.store`
  - **Controller**: [`AdminGunungController@store`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/GunungController.php#L55-L71)
  - **Request**: [`StoreGunungRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Gunung/StoreGunungRequest.php) (`nama_gunung`, `deskripsi`, `tinggi_mdpl`, `lokasi`, `foto`, `status`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/admin/mountains/{id}` (`_method=PUT`) / `PUT`** — Update profil data gunung & foto
  - **Route Name**: `admin.gunung.update`
  - **Controller**: [`AdminGunungController@update`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/GunungController.php#L117-L139)
  - **Request**: [`UpdateGunungRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Gunung/UpdateGunungRequest.php)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`DELETE /api/v1/admin/mountains/{id}`** — Hapus data gunung beserta file foto dari storage
  - **Route Name**: `admin.gunung.destroy`
  - **Controller**: [`AdminGunungController@destroy`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/GunungController.php#L166-L182)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 🥾 MODUL 3: MASTER DESTINASI — JALUR PENDAKIAN

Manajemen rute dan jalur pendakian resmi yang terhubung ke gunung tertentu.

- [x] **`GET /api/v1/admin/trails`** — List semua jalur pendakian (mendukung filter `?gunung_id=`, `?status=`, `?tingkat_kesulitan=`, `?search=`)
  - **Route Name**: `admin.jalur.index`
  - **Controller**: [`AdminJalurController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/JalurController.php)
  - **Middleware**: `auth:sanctum`, `role:admin`
  - **Response**: Paginated list `JalurPendakianResource` (with `gunung`, count `basecamps`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/admin/trails/{id}`** — Detail satu jalur pendakian
  - **Route Name**: `admin.jalur.show`
  - **Controller**: [`AdminJalurController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/JalurController.php)
  - **Middleware**: `auth:sanctum`, `role:admin`
  - **Response**: Single `JalurPendakianResource` (with `gunung`, `basecamps.mitra.user`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/admin/trails`** — Tambah jalur pendakian baru
  - **Route Name**: `admin.jalur.store`
  - **Controller**: [`AdminJalurController@store`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/JalurController.php)
  - **Request**: [`StoreJalurRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Jalur/StoreJalurRequest.php)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PUT /api/v1/admin/trails/{id}`** — Update detail jalur pendakian
  - **Route Name**: `admin.jalur.update`
  - **Controller**: [`AdminJalurController@update`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/JalurController.php)
  - **Request**: [`UpdateJalurRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Jalur/UpdateJalurRequest.php)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PATCH /api/v1/admin/trails/{id}/status`** — Toggle status cepat Buka/Tutup darurat (open/close)
  - **Route Name**: `admin.jalur.update-status`
  - **Controller**: [`AdminJalurController@updateStatus`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/JalurController.php)
  - **Request**: [`UpdateStatusJalurRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Jalur/UpdateStatusJalurRequest.php) (`status`: `open` | `close`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`DELETE /api/v1/admin/trails/{id}`** — Hapus jalur pendakian (dengan proteksi jika memiliki basecamp aktif)
  - **Route Name**: `admin.jalur.destroy`
  - **Controller**: [`AdminJalurController@destroy`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/JalurController.php)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 🤝 MODUL 4: MASTER KEMITRAAN — MITRA PENGELOLA

Pendaftaran dan pengelolaan akun serta profil operasional Mitra Basecamp.

- [x] **`GET /api/v1/admin/partners`** — List seluruh profil mitra (with user account)
  - **Route Name**: `admin.mitra.index`
  - **Controller**: [`AdminMitraController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/MitraController.php#L42-L51)
  - **Response**: Paginated list `MitraResource`
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`POST /api/v1/admin/partners`** — Buat akun Mitra dan profil bisnis secara atomik
  - **Route Name**: `admin.mitra.store`
  - **Controller**: [`AdminMitraController@store`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/MitraController.php#L96-L123)
  - **Request**: [`StoreMitraRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Mitra/StoreMitraRequest.php) (`email`, `password`, `nama_pemilik`, `telepon`, `alamat`, `nik`, `rekening_bank`, `nama_rekening`, `bank`, `status`)
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`GET /api/v1/admin/partners/{id}`** — Detail profil dan rekening mitra
  - **Route Name**: `admin.mitra.show`
  - **Controller**: [`AdminMitraController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/MitraController.php#L150-L159)
  - **Response**: Single `MitraResource`
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`PUT /api/v1/admin/partners/{id}`** — Perbarui data mitra & kredensial login
  - **Route Name**: `admin.mitra.update`
  - **Controller**: [`AdminMitraController@update`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/MitraController.php#L208-L238)
  - **Request**: [`UpdateMitraRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Mitra/UpdateMitraRequest.php)
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`DELETE /api/v1/admin/partners/{id}`** — Hapus akun dan profil mitra (cascade)
  - **Route Name**: `admin.mitra.destroy`
  - **Controller**: [`AdminMitraController@destroy`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/MitraController.php#L265-L279)
  - **Status**: ✅ *Selesai & Teruji*

---

## 🏕️ MODUL 5: MASTER KEMITRAAN — BASECAMP

Pengelolaan pos/basecamp fisik dan pemetaannya ke mitra serta jalur pendakian.

- [x] **`GET /api/v1/admin/basecamps`** — List seluruh basecamp (with `mitra.user`, `jalur`)
  - **Route Name**: `admin.basecamp.index`
  - **Controller**: [`AdminBasecampController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/BasecampController.php#L40-L49)
  - **Response**: Paginated list `BasecampResource`
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`POST /api/v1/admin/basecamps`** — Tambah titik basecamp operasional baru
  - **Route Name**: `admin.basecamp.store`
  - **Controller**: [`AdminBasecampController@store`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/BasecampController.php#L87-L96)
  - **Request**: [`StoreBasecampRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Basecamp/StoreBasecampRequest.php) (`mitra_id`, `jalur_id`, `nama_basecamp`, `latitude`, `longitude`, `jam_operasional`)
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`GET /api/v1/admin/basecamps/{id}`** — Detail basecamp (with katalog produk & tiket)
  - **Route Name**: `admin.basecamp.show`
  - **Controller**: [`AdminBasecampController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/BasecampController.php#L123-L132)
  - **Response**: Single `BasecampResource`
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`PUT /api/v1/admin/basecamps/{id}`** — Perbarui data basecamp
  - **Route Name**: `admin.basecamp.update`
  - **Controller**: [`AdminBasecampController@update`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/BasecampController.php#L174-L184)
  - **Request**: [`UpdateBasecampRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Basecamp/UpdateBasecampRequest.php)
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`DELETE /api/v1/admin/basecamps/{id}`** — Hapus data basecamp
  - **Route Name**: `admin.basecamp.destroy`
  - **Controller**: [`AdminBasecampController@destroy`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/BasecampController.php#L211-L221)
  - **Status**: ✅ *Selesai & Teruji*

---

## 🪪 MODUL 6: VERIFIKASI KYC PENDAKI

Proses peninjauan dokumen resmi identitas pendaki (KTP/Paspor/SIM) dan verifikasi data kontak darurat.

- [x] **`GET /api/v1/admin/kyc`** — List antrean pengajuan KYC (mendukung filter `?status=pending|disetujui|ditolak`)
  - **Route Name**: `admin.kyc.index`
  - **Controller**: [`AdminKycController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/KycController.php#L42-L57)
  - **Response**: Paginated list `PendakiResource`
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`GET /api/v1/admin/kyc/{id}`** — Detail data pengajuan KYC dan kontak darurat
  - **Route Name**: `admin.kyc.show`
  - **Controller**: [`AdminKycController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/KycController.php#L84-L93)
  - **Response**: Single `PendakiResource`
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`GET /api/v1/admin/kyc/{id}/download-document`** — Stream unduh/preview dokumen KTP/Paspor secara aman dari disk privat
  - **Route Name**: `admin.kyc.download`
  - **Controller**: [`AdminKycController@downloadDocument`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/KycController.php#L114-L123)
  - **Response**: `BinaryFileResponse` (`application/octet-stream`)
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`POST /api/v1/admin/kyc/{id}/verify`** — Approve atau Reject pengajuan KYC
  - **Route Name**: `admin.kyc.verify`
  - **Controller**: [`AdminKycController@verify`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/KycController.php#L161-L187)
  - **Request**: [`VerifyKycRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Kyc/VerifyKycRequest.php) (`status_verifikasi`: `disetujui` | `ditolak`, `alasan_penolakan`)
  - **Status**: ✅ *Selesai & Teruji*

---

## 💰 MODUL 7: MANAJEMEN BIAYA ADMIN & PLATFORM FEE

Pengaturan skema bagi hasil/komisi platform dan biaya layanan per transaksi secara dinamis.

- [ ] **Database Migration: Tabel Pengaturan Biaya (`platform_settings`)**
  - **Tugas Backend**:
    - [ ] Buat migration `create_platform_settings_table`:
      - `id` (`BIGINT UNSIGNED PK`)
      - `key` (`VARCHAR(255) UNIQUE`) — misal `'biaya_layanan_user'`, `'komisi_admin_persen'`
      - `value` (`VARCHAR(255)`)
      - `tipe` (`VARCHAR(50)`) — misal `'decimal'`, `'integer'`, `'boolean'`
      - `deskripsi` (`TEXT NULLABLE`)
    - [ ] Buat Model [`PlatformSetting.php`](file:///D:/laragon/www/summit-v2/backend/app/Models/) dan seeder default (`biaya_layanan_user = 5000.00`, `komisi_admin_persen = 10.00`).

- [ ] **`GET /api/v1/admin/settings/fees`** — Mengambil konfigurasi tarif fee aktif
  - **Route Name**: `admin.settings.fees.show`
  - **Controller**: `AdminSettingController@getFees` *(Perlu Dibuat)*
  - **Middleware**: `auth:sanctum`, `role:admin`
  - **Response Schema**:
    ```json
    {
      "status": "success",
      "message": "Pengaturan biaya berhasil diambil.",
      "data": {
        "biaya_layanan_user": 5000.00,
        "komisi_admin_persen": 10.00
      }
    }
    ```

- [ ] **`PUT /api/v1/admin/settings/fees`** — Memperbarui konfigurasi tarif biaya platform
  - **Route Name**: `admin.settings.fees.update`
  - **Controller**: `AdminSettingController@updateFees` *(Perlu Dibuat)*
  - **Request Validation**:
    - `biaya_layanan_user`: `required|numeric|min:0`
    - `komisi_admin_persen`: `required|numeric|min:0|max:100`

- [ ] **Refactor `PesananService.php` untuk Penggunaan Fee Dinamis**
  - **Tugas Backend**:
    - [ ] Ubah persistensi pesanan di [`PesananService@persistPesanan`](file:///D:/laragon/www/summit-v2/backend/app/Services/PesananService.php#L248-L251) agar membaca tarif dari cache/database `PlatformSetting` (dengan fallback nilai aman).

---

## 📋 MODUL 8: MONITORING TRANSAKSI & PESANAN LINTAS BASECAMP

Pengawasan seluruh transaksi pemesanan tiket, rental, dan pemandu dari semua basecamp.

- [ ] **`GET /api/v1/admin/orders`** — List semua pesanan/transaksi di platform
  - **Route Name**: `admin.pesanan.index`
  - **Controller**: `AdminPesananController@index` *(Perlu Dibuat)*
  - **Middleware**: `auth:sanctum`, `role:admin`
  - **Query Params**:
    - `status` (enum: `pending`, `paid`, `on_going`, `completed`, `cancelled`, `expired`, `refunded`)
    - `basecamp_id` (integer)
    - `gunung_id` (integer)
    - `tanggal_booking` (date: `YYYY-MM-DD`)
    - `search` (string: cari invoice / nama pemesan)
    - `page` & `per_page`
  - **Response**: Paginated array `PesananResource`

- [ ] **`GET /api/v1/admin/orders/{invoice}`** — Detail lengkap transaksi pesanan
  - **Route Name**: `admin.pesanan.show`
  - **Controller**: `AdminPesananController@show` *(Perlu Dibuat)*
  - **Middleware**: `auth:sanctum`, `role:admin`
  - **Response**: Single `PesananResource` (with `user`, `basecamp`, `jalur`, `anggotas`, `details.produk`, `pembayaran`)

- [ ] **`POST /api/v1/admin/orders/{invoice}/cancel`** — Pembatalan transaksi darurat oleh Admin
  - **Route Name**: `admin.pesanan.cancel`
  - **Controller**: `AdminPesananController@cancel` *(Perlu Dibuat)*
  - **Deskripsi**: Membatalkan pesanan pending dan mengembalikan kuota/stok inventaris.

---

## 📦 MODUL 9: MONITORING KATALOG PRODUK LINTAS BASECAMP

Pemantauan seluruh produk tiket, alat sewa, open trip, porter, dan logistik dari semua basecamp.

- [x] **`GET /api/v1/admin/products`** — List seluruh produk (mendukung filter `?basecamp_id=`)
  - **Route Name**: `admin.products.index`
  - **Controller**: [`AdminProductController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/ProductController.php#L39-L54)
  - **Response**: Paginated list `ProdukResource`
  - **Status**: ✅ *Selesai & Teruji*

- [x] **`GET /api/v1/admin/products/{id}`** — Detail produk spesifik
  - **Route Name**: `admin.products.show`
  - **Controller**: [`AdminProductController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Admin/ProductController.php#L81-L90)
  - **Response**: Single `ProdukResource`
  - **Status**: ✅ *Selesai & Teruji*

---

## 🏦 MODUL 10: ESCROW PAYOUT, PENARIKAN DANA & REFUND (PRD 05)

Manajemen persetujuan penarikan dana dompet mitra dan pemrosesan refund.

- [ ] **Database Migration: Tabel Penarikan Dana (`withdrawals`)**
  - **Tugas Backend**:
    - [ ] Buat migration `create_withdrawals_table`:
      - `id` (`BIGINT UNSIGNED PK`)
      - `mitra_id` (`BIGINT UNSIGNED FK -> mitras.id`)
      - `nominal` (`DECIMAL(15,2)`)
      - `bank_tujuan` (`VARCHAR(255)`)
      - `rekening_tujuan` (`VARCHAR(255)`)
      - `nama_rekening` (`VARCHAR(255)`)
      - `status` (`ENUM: pending, approved, processing, completed, rejected, failed`)
      - `xendit_disbursement_id` (`VARCHAR(255) NULLABLE`)
      - `alasan_penolakan` (`TEXT NULLABLE`)
      - `approved_by` (`BIGINT UNSIGNED FK -> users.id NULLABLE`)
      - `approved_at` (`TIMESTAMP NULLABLE`)
    - [ ] Buat Model [`Withdrawal.php`](file:///D:/laragon/www/summit-v2/backend/app/Models/) dan Resource [`WithdrawalResource.php`](file:///D:/laragon/www/summit-v2/backend/app/Http/Resources/).

- [ ] **`GET /api/v1/admin/withdrawals`** — List permohonan penarikan dana Mitra
  - **Route Name**: `admin.withdrawals.index`
  - **Query**: `?status=pending|approved|completed|rejected`

- [ ] **`POST /api/v1/admin/withdrawals/{id}/approve`** — Setujui penarikan & trigger Xendit Disbursement API
  - **Route Name**: `admin.withdrawals.approve`
  - **Deskripsi**: Mengubah status menjadi `processing`, memanggil service Xendit Disbursement, dan memperbarui status saat payout sukses.

- [ ] **`POST /api/v1/admin/withdrawals/{id}/reject`** — Tolak pengajuan penarikan dana
  - **Route Name**: `admin.withdrawals.reject`
  - **Request Body**: `{ "alasan_penolakan": "Nomor rekening tidak valid" }`
  - **Deskripsi**: Mengembalikan dana yang terkunci ke Saldo Available Mitra.

- [ ] **`POST /api/v1/admin/refunds/{id}/process`** — Eksekusi persetujuan/penolakan refund pesanan
  - **Route Name**: `admin.refunds.process`
  - **Request Body**: `{ "status": "approved|rejected", "catatan": "Pengembalian dana disetujui" }`

---

## 📢 MODUL 11: IKLAN & MONETISASI BANNER PROMOSI (PRD 08)

Manajemen ruang iklan berbayar dan banner promosi aplikasi.

- [ ] **Database Migration: Tabel Banner Iklan (`banner_ads`)**
  - **Tugas Backend**:
    - [ ] Buat migration `create_banner_ads_table`:
      - `id` (`BIGINT UNSIGNED PK`)
      - `mitra_id` (`BIGINT UNSIGNED FK -> mitras.id NULLABLE`)
      - `judul` (`VARCHAR(255)`)
      - `gambar` (`VARCHAR(255)`)
      - `link_url` (`VARCHAR(255) NULLABLE`)
      - `posisi` (`ENUM: home_top, mountain_detail, search_sidebar`)
      - `tanggal_mulai` (`DATE`)
      - `tanggal_selesai` (`DATE`)
      - `total_impressions` (`INT DEFAULT 0`)
      - `total_clicks` (`INT DEFAULT 0`)
      - `is_active` (`BOOLEAN DEFAULT 1`)

- [ ] **`GET /api/v1/admin/ads/banners`** — List banner iklan
  - **Route Name**: `admin.ads.banners.index`

- [ ] **`POST /api/v1/admin/ads/banners`** — Tambah slot banner iklan baru (upload banner image)
  - **Route Name**: `admin.ads.banners.store`

- [ ] **`PUT /api/v1/admin/ads/banners/{id}`** & **`DELETE /api/v1/admin/ads/banners/{id}`** — Edit & hapus banner iklan
  - **Route Names**: `admin.ads.banners.update`, `admin.ads.banners.destroy`

---

## 📊 MODUL 12: DASHBOARD ANALYTICS & RINGKASAN METRIK

Endpoint ringkasan metrik performa platform untuk tampilan beranda Admin Panel.

- [ ] **`GET /api/v1/admin/dashboard/stats`** — Mengambil statistik ringkasan operasional & finansial
  - **Route Name**: `admin.dashboard.stats`
  - **Controller**: `AdminDashboardController@stats` *(Perlu Dibuat)*
  - **Middleware**: `auth:sanctum`, `role:admin`
  - **Data Metrik yang Dihasilkan**:
    - `total_pendaki`: Total akun pendaki terverifikasi
    - `total_mitra_aktif`: Total mitra basecamp aktif
    - `total_gunung`: Total gunung aktif di katalog
    - `total_basecamp`: Total basecamp terdaftar
    - `kyc_pending_count`: Jumlah antrean KYC menunggu verifikasi
    - `total_transaksi_selesai`: Total pesanan dengan status `completed`
    - `gross_merchandise_value`: Total omset bruto seluruh transaksi (`SUM(total_bayar)`)
    - `total_komisi_platform`: Total komisi admin yang telah terealisasi (`SUM(komisi_admin)`)
    - `withdrawal_pending_count`: Total permohonan penarikan dana menunggu persetujuan

- [ ] **`GET /api/v1/admin/dashboard/chart-transactions`** — Data grafik transaksi bulanan
  - **Route Name**: `admin.dashboard.chart`
  - **Deskripsi**: Data series volume transaksi dan pendapatan komisi per bulan/minggu untuk visualisasi grafik.

---

## 🧪 MODUL 13: PEST TESTING & OPENAPI DOCUMENTATION

- [x] **Pest Feature Tests untuk Admin Controllers yang sudah ada**
  - [`Feature/Admin/BasecampControllerTest.php`](file:///D:/laragon/www/summit-v2/backend/tests/Feature/Admin/BasecampControllerTest.php) ✅
  - [`Feature/Admin/MitraControllerTest.php`](file:///D:/laragon/www/summit-v2/backend/tests/Feature/Admin/MitraControllerTest.php) ✅
  - [`Feature/GunungControllerTest.php`](file:///D:/laragon/www/summit-v2/backend/tests/Feature/GunungControllerTest.php) ✅
  - [`Feature/KycControllerTest.php`](file:///D:/laragon/www/summit-v2/backend/tests/Feature/KycControllerTest.php) ✅

- [ ] **Pest Feature Tests untuk Endpoint Admin Baru**
  - [ ] Test `AdminJalurController` (`index` & `show`)
  - [ ] Test `AdminSettingController` (`getFees` & `updateFees`)
  - [ ] Test `AdminPesananController` (`index`, `show`, `cancel`)
  - [ ] Test `AdminDashboardController` (`stats`)
  - [ ] Test `AdminWithdrawalController` (jika modul payout diaktifkan)

- [ ] **Regenerasi Dokumentasi OpenAPI Swagger**
  - [ ] Jalankan `php artisan l5-swagger:generate` setelah menambahkan attribute `#[OA\*]` pada controller baru.
