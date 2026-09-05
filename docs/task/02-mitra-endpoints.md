# TASK LIST: ENDPOINT & FITUR BACKEND ROLE MITRA

Dokumen ini memuat daftar tugas (*checklist tasks*) lengkap untuk pembuatan dan penyempurnaan seluruh endpoint backend/API untuk **Role Mitra (Pengelola Basecamp / Operator Lokal)** pada platform **Summit v2** (`backend/`).

---

## 📈 Ringkasan Progres Implementasi Mitra API

- [x] **Modul 1: Autentikasi & Akun Mitra** (5/5 Task Selesai — **100%**)
- [x] **Modul 2: Manajemen Basecamp & Kontrol Jalur Darurat** (4/4 Task Selesai — **100%**)
- [x] **Modul 3: Manajemen Katalog Produk & Layanan** (6/6 Task Selesai — **100%**)
- [x] **Modul 4: Manajemen Kuota Harian Tiket & Stok Inventaris** (4/4 Task Selesai — **100%**)
- [x] **Modul 5: Manajemen Staf Operasional (Guide & Porter)** (5/5 Task Selesai — **100%**)
- [x] **Modul 6: Manajemen Pesanan Masuk & Operasional Lapangan** (5/5 Task Selesai — **100%**)
- [x] **Modul 7: Validasi Digital Logbook & Bukti Summit** (2/2 Task Selesai — **100%**)
- [x] **Modul 8: Dompet Escrow, Penarikan Dana & Mutasi Ledger** (4/4 Task Selesai — **100%**)
- [x] **Modul 9: Manajemen Klaim & Pengajuan Refund (Tier-1 Review)** (4/4 Task Selesai — **100%**)
- [x] **Modul 10: In-App Chat & Koordinasi Pendaki** (5/5 Task Selesai — **100%**)
- [x] **Modul 11: Ringkasan Metrik & Dashboard Analytics Mitra** (1/1 Task Selesai — **100%**)

**Total Progres Mitra API**: **41 / 41 Task Selesai (100% Siap)**

---

## 🔐 MODUL 1: AUTENTIKASI & AKUN MITRA

Modul sesi otentikasi akun pengelola mitra menggunakan Laravel Sanctum Bearer Token dan HttpOnly Cookie.

- [x] **`POST /api/v1/auth/login`** — Login akun Mitra (Email/Password)
  - **Route Name**: `login`
  - **Controller**: [`AuthController@login`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/AuthController.php#L108-L140)
  - **Request**: [`LoginRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Auth/LoginRequest.php) (`email`, `password`)
  - **Response**: `UserResource` + Bearer Token + HttpOnly Cookie `auth_token`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/profile`** — Mendapatkan data profil akun Mitra & entitas bisnis yang terhubung
  - **Route Name**: `profile.show`
  - **Controller**: [`ProfileController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/ProfileController.php)
  - **Middleware**: `auth:sanctum`
  - **Response**: `ProfileResource` (dengan relasi `mitra.basecamps`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PUT /api/v1/mitra/profile`** — Memperbarui data profil bisnis mitra (No telp, alamat, rekening pencairan)
  - **Route Name**: `mitra.profile.update`
  - **Controller**: [`MitraProfileController@update`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/ProfileController.php)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Request**: [`UpdateMitraProfileRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Mitra/UpdateMitraProfileRequest.php) (`nama_pemilik`, `telepon`, `alamat`, `deskripsi`, `npwp`, `nik`, `rekening_bank`, `nama_rekening`, `bank`, `ewallet`)
  - **Response**: Single `ProfileResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PUT /api/v1/profile/password`** — Memperbarui kata sandi akun Mitra
  - **Route Name**: `profile.password.update`
  - **Controller**: [`ProfileController@updatePassword`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/ProfileController.php)
  - **Request**: [`ChangePasswordRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Auth/ChangePasswordRequest.php) (`current_password`, `new_password`, `new_password_confirmation`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/auth/logout`** — Logout dan revoke token sesi Mitra
  - **Route Name**: `logout`
  - **Controller**: [`AuthController@logout`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/AuthController.php#L265-L280)
  - **Middleware**: `auth:sanctum`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 🏕️ MODUL 2: MANAJEMEN BASECAMP & KONTROL JALUR DARURAT

Pengelolaan informasi basecamp yang dioperasikan oleh Mitra serta tindakan cepat penutupan jalur saat darurat/badai.

- [x] **`GET /api/v1/mitra/basecamps`** — List seluruh basecamp yang berada di bawah naungan akun Mitra ini
  - **Route Name**: `mitra.basecamp.index`
  - **Controller**: [`MitraBasecampController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/BasecampController.php)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Response**: Array of `BasecampResource` (with `jalur.gunung`, count of `staff`, count of `produks`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/mitra/basecamps/{id}`** — Detail informasi operasional basecamp spesifik
  - **Route Name**: `mitra.basecamp.show`
  - **Controller**: [`MitraBasecampController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/BasecampController.php)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Response**: Single `BasecampResource` (with `jalur.gunung`, `staff`, `produks`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PUT /api/v1/mitra/basecamps/{id}`** — Update informasi operasional basecamp (nama, koordinat GPS, jam operasional)
  - **Route Name**: `mitra.basecamp.update`
  - **Controller**: [`MitraBasecampController@update`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/BasecampController.php)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Request**: [`UpdateMitraBasecampRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Mitra/UpdateMitraBasecampRequest.php) (`nama_basecamp`, `latitude`, `longitude`, `jam_operasional`)
  - **Response**: Single `BasecampResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/mitra/trails/{id}/emergency-close`** — Penutupan atau pembukaan darurat jalur pendakian
  - **Route Name**: `mitra.trails.emergency-close`
  - **Controller**: [`MitraTrailController@emergencyClose`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/TrailController.php)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Request**: `status` (`open` | `close`), `alasan_penutupan` (string)
  - **Response**: `JalurPendakianResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 📦 MODUL 3: MANAJEMEN KATALOG PRODUK & LAYANAN

Pengelolaan produk tiket pendakian, sewa alat rental, open trip, logistik, parkir, dan jasa porter/guide.

- [x] **`GET /api/v1/mitra/products`** — List semua katalog produk yang dimiliki basecamp Mitra
  - **Route Name**: `mitra.products.index`
  - **Controller**: [`MitraProductController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/ProductController.php#L47-L68)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Response**: Paginated list `ProdukResource` (with `basecamp`, `opentrip`, `tiket.kuotas`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/mitra/products`** — Tambah produk baru (Tiket, Rental, OpenTrip, Guide, Porter, Logistik, Parkir)
  - **Route Name**: `mitra.products.store`
  - **Controller**: [`MitraProductController@store`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/ProductController.php#L118-L146)
  - **Request**: [`StoreProductRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Product/StoreProductRequest.php)
  - **Response**: Single `ProdukResource` (Status 201)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/mitra/products/{id}`** — Detail spesifik produk beserta konfigurasi tiket/opentrip
  - **Route Name**: `mitra.products.show`
  - **Controller**: [`MitraProductController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/ProductController.php)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Response**: Single `ProdukResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PUT /api/v1/mitra/products/{id}` / `PATCH`** — Perbarui informasi produk, harga, & deskripsi
  - **Route Name**: `mitra.products.update`
  - **Controller**: [`MitraProductController@update`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/ProductController.php)
  - **Request**: [`UpdateProductRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Product/UpdateProductRequest.php)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PATCH /api/v1/mitra/products/{id}/toggle-status`** — Aktifkan / Nonaktifkan produk secara instan
  - **Route Name**: `mitra.products.toggle-status`
  - **Controller**: [`MitraProductController@toggleStatus`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/ProductController.php)
  - **Request**: `is_active` (`boolean`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`DELETE /api/v1/mitra/products/{id}`** — Hapus produk dari katalog inventaris
  - **Route Name**: `mitra.products.destroy`
  - **Controller**: [`MitraProductController@destroy`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/ProductController.php)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 📅 MODUL 4: MANAJEMEN KUOTA HARIAN TIKET & STOK INVENTARIS

Pengaturan batasan kuota pendakian harian serta stok fisik barang sewaan.

- [x] **`GET /api/v1/mitra/products/{id}/quotas`** — Ambil daftar kuota harian produk tiket dalam rentang tanggal
  - **Route Name**: `mitra.products.quotas`
  - **Controller**: [`MitraProductController@quotas`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/ProductController.php)
  - **Query**: `start_date` (Y-m-d), `end_date` (Y-m-d)
  - **Response**: Array of `KuotaHarianTiketResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/mitra/products/{id}/quotas/batch`** — Set kuota harian serentak (*batch date range*)
  - **Route Name**: `mitra.products.quotas.batch`
  - **Controller**: [`MitraProductController@batchQuotas`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/ProductController.php)
  - **Request**: `start_date` (date), `end_date` (date), `kuota_total` (integer min:1)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PUT /api/v1/mitra/quotas/{quota_id}`** — Perbarui kuota total pada tanggal spesifik
  - **Route Name**: `mitra.quotas.update`
  - **Controller**: [`MitraProductController@updateQuota`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/ProductController.php)
  - **Request**: `kuota_total` (integer min:0)
  - **Response**: Single `KuotaHarianTiketResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PATCH /api/v1/mitra/products/{id}/stock`** — Update jumlah stok fisik produk rental secara instan
  - **Route Name**: `mitra.products.stock`
  - **Controller**: [`MitraProductController@updateStock`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/ProductController.php)
  - **Request**: `stok` (integer min:0)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 👥 MODUL 5: MANAJEMEN STAF OPERASIONAL (GUIDE & PORTER)

Pendaftaran dan pengelolaan data petugas basecamp, guide, dan porter lokal.

- [x] **`GET /api/v1/mitra/staff`** — List seluruh staf operasional mitra (mendukung filter `?role=` dan `?basecamp_id=`)
  - **Route Name**: `mitra.staff.index`
  - **Controller**: [`MitraStaffController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/StaffController.php#L41-L69)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Response**: Paginated list `MitraStaffResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/mitra/staff`** — Tambah staf/guide/porter baru
  - **Route Name**: `mitra.staff.store`
  - **Controller**: [`MitraStaffController@store`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/StaffController.php#L105-L126)
  - **Request**: [`StoreMitraStaffRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Staff/StoreMitraStaffRequest.php) (`nama`, `role`, `telepon`, `basecamp_id`, `is_available`, `jadwal_tugas`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/mitra/staff/{id}`** — Detail data profil staf operasional
  - **Route Name**: `mitra.staff.show`
  - **Controller**: [`MitraStaffController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/StaffController.php#L151-L160)
  - **Response**: Single `MitraStaffResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PUT /api/v1/mitra/staff/{id}`** — Perbarui data staf, ketersediaan, atau nomor kontak
  - **Route Name**: `mitra.staff.update`
  - **Controller**: [`MitraStaffController@update`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/StaffController.php#L185-L197)
  - **Request**: [`UpdateMitraStaffRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Staff/UpdateMitraStaffRequest.php)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`DELETE /api/v1/mitra/staff/{id}`** — Hapus staf dari daftar operasional
  - **Route Name**: `mitra.staff.destroy`
  - **Controller**: [`MitraStaffController@destroy`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/StaffController.php#L221-L233)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 📋 MODUL 6: MANAJEMEN PESANAN MASUK & OPERASIONAL LAPANGAN

Pemantauan pesanan booking dari pendaki, validasi anggota rombongan, check-in kedatangan di basecamp, dan perubahan status operasional barang/tiket.

- [x] **`GET /api/v1/mitra/orders`** — List seluruh pesanan masuk ke basecamp (filter: `status`, `basecamp_id`, `tanggal_booking`, `search`) + Ringkasan Total Pendapatan Bersih
  - **Route Name**: `mitra.pesanan.index`
  - **Controller**: [`MitraPesananController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/PesananController.php#L48-L117)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Response**: Paginated `PesananResource` dengan `meta.total_pendapatan_bersih` & `meta.total_transaksi_paid`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/mitra/orders/{id}`** — Detail rincian pesanan (Data Pemesan, Rombongan Anggota, List Item Produk, Informasi Pembayaran)
  - **Route Name**: `mitra.pesanan.show`
  - **Controller**: [`MitraPesananController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/PesananController.php#L144-L156)
  - **Response**: Single `PesananResource` (with `user`, `basecamp`, `jalur`, `anggotas`, `details.produk`, `pembayaran`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`PATCH /api/v1/mitra/orders/{pesananId}/items/{itemId}`** — Update status operasional item (Tiket/Rental/Porter)
  - **Route Name**: `mitra.pesanan.update-item`
  - **Controller**: [`MitraPesananController@updateItemStatus`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/PesananController.php#L193-L217)
  - **Request**: `status_operasional` (`pending`, `ready`, `active`, `completed`, `cancelled`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/mitra/orders/{id}/check-in`** — Verifikasi Check-In rombongan di Basecamp berdasarkan Order ID (transisi status ke `on_going` dan aktivasi item)
  - **Route Name**: `mitra.pesanan.check-in`
  - **Controller**: [`MitraPesananController@checkIn`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/PesananController.php)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Response**: Single `PesananResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/mitra/orders/check-in`** — Verifikasi Check-In rombongan via pemindaian QR / input kode invoice E-Ticket
  - **Route Name**: `mitra.pesanan.check-in-code`
  - **Controller**: [`MitraPesananController@checkInByCode`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/PesananController.php)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Request**: `invoice` (string)
  - **Response**: Single `PesananResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 🏔️ MODUL 7: VALIDASI DIGITAL LOGBOOK & BUKTI SUMMIT

Pengecekan foto bukti pendaki di puncak (*summit proof*) sebelum merilis penyelesaian pesanan dan pencairan dana escrow.

- [x] **`GET /api/v1/mitra/logbooks`** — List pengajuan verifikasi bukti summit di basecamp (filter: `?status_validasi=pending|approved|rejected`)
  - **Route Name**: `mitra.logbooks.index`
  - **Controller**: [`MitraLogbookController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/LogbookController.php#L39-L69)
  - **Response**: Paginated list `LogbookResource` (with `pesanan.jalur.gunung`, `user`, `validator`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/mitra/logbooks/{id}/verify`** — Validasi & Setujui/Tolak bukti summit (Otomatis menyelesaikan pesanan & rilis escrow ke saldo available)
  - **Route Name**: `mitra.logbooks.verify`
  - **Controller**: [`MitraLogbookController@verify`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/LogbookController.php#L103-L147)
  - **Request**: [`VerifyLogbookRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Logbook/VerifyLogbookRequest.php) (`status_validasi`: `approved` | `rejected`, `catatan_petugas`)
  - **Side Effect**: Pesanan diubah ke `completed`, `status_escrow` ke `released`, saldo pending masuk ke `saldo_available`.
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 💳 MODUL 8: DOMPET ESCROW, PENARIKAN DANA & MUTASI LEDGER

Manajemen saldo virtual mitra, riwayat pergerakan dana pesanan, serta pengajuan penarikan dana payout via Xendit.

- [x] **`GET /api/v1/mitra/wallet`** — Ringkasan saldo dompet escrow (`saldo_pending`, `saldo_available`, `total_penarikan`)
  - **Route Name**: `mitra.wallet.summary`
  - **Controller**: [`MitraWalletController@summary`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/WalletController.php#L37-L55)
  - **Response**: `MitraWalletResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/mitra/wallet/ledger`** — Riwayat seluruh mutasi transaksi dompet mitra (filter: `?type=`, `?page=`)
  - **Route Name**: `mitra.wallet.ledger`
  - **Controller**: [`MitraWalletController@ledger`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/WalletController.php#L79-L104)
  - **Response**: Paginated list `WalletTransactionResource` (with `pesanan`)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/mitra/withdrawals`** — Pengajuan pencairan dana (*withdrawal payout*) dari `saldo_available`
  - **Route Name**: `mitra.withdrawals.store`
  - **Controller**: [`MitraWalletController@withdraw`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/WalletController.php#L136-L168)
  - **Request**: [`StoreWithdrawalRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Wallet/StoreWithdrawalRequest.php) (`nominal`, `catatan`)
  - **Response**: Single `WithdrawalResource` (Status 201)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/mitra/withdrawals`** — Riwayat pengajuan pencairan dana dan status verifikasinya
  - **Route Name**: `mitra.withdrawals.index`
  - **Controller**: [`MitraWalletController@withdrawals`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/WalletController.php#L192-L216)
  - **Response**: Paginated list `WithdrawalResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 🔄 MODUL 9: MANAJEMEN KLAIM & PENGAJUAN REFUND (TIER-1 REVIEW)

Proses peninjauan pengajuan pembatalan & pengembalian dana tiket oleh pendaki di tingkat mitra pengelola.

- [x] **`GET /api/v1/mitra/refunds`** — List seluruh permohonan refund untuk pesanan basecamp mitra (filter: `?status=`)
  - **Route Name**: `mitra.refunds.index`
  - **Controller**: [`MitraRefundController@index`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/RefundController.php#L40-L59)
  - **Response**: Paginated list `RefundResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/mitra/refunds/{id}`** — Detail informasi pengajuan refund dan alasan pendaki
  - **Route Name**: `mitra.refunds.show`
  - **Controller**: [`MitraRefundController@show`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/RefundController.php#L74-L87)
  - **Response**: Single `RefundResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/mitra/refunds/{id}/approve`** — Setujui refund (Tier-1 Review) & kurangi saldo pending secara atomik
  - **Route Name**: `mitra.refunds.approve`
  - **Controller**: [`MitraRefundController@approve`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/RefundController.php#L103-L157)
  - **Response**: Single `RefundResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/mitra/refunds/{id}/reject`** — Tolak refund dengan menyertakan alasan penolakan
  - **Route Name**: `mitra.refunds.reject`
  - **Controller**: [`MitraRefundController@reject`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/RefundController.php#L181-L207)
  - **Request**: [`MitraRejectRefundRequest`](file:///D:/laragon/www/summit-v2/backend/app/Http/Requests/Refund/MitraRejectRefundRequest.php) (`alasan_penolakan`)
  - **Response**: Single `RefundResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 💬 MODUL 10: IN-APP CHAT & KOORDINASI PENDAKI

Komunikasi langsung antara pengelola basecamp/guide dengan pendaki sebelum dan selama pendakian.

- [x] **`GET /api/v1/chat/rooms`** — List ruang obrolan aktif milik mitra
  - **Route Name**: `chat.rooms.index`
  - **Controller**: [`ChatController@rooms`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/ChatController.php)
  - **Response**: Array of `ChatRoomResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/chat/rooms`** — Buat atau buka percakapan dengan pendaki berdasarkan ID Pesanan / User
  - **Route Name**: `chat.rooms.store`
  - **Controller**: [`ChatController@createOrGetRoom`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/ChatController.php)
  - **Request**: `user_id` atau `pesanan_id`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`GET /api/v1/chat/rooms/{room_id}/messages`** — Ambil riwayat percakapan di dalam ruangan obrolan
  - **Route Name**: `chat.messages.index`
  - **Controller**: [`ChatController@messages`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/ChatController.php)
  - **Response**: Array of `ChatMessageResource`
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/chat/rooms/{room_id}/messages`** — Kirim pesan baru ke pendaki
  - **Route Name**: `chat.messages.store`
  - **Controller**: [`ChatController@sendMessage`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/ChatController.php)
  - **Request**: `pesan` (string)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

- [x] **`POST /api/v1/chat/rooms/{room_id}/read`** — Tandai seluruh pesan dalam obrolan telah dibaca
  - **Route Name**: `chat.messages.read`
  - **Controller**: [`ChatController@markAsRead`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/ChatController.php)
  - **Status**: ✅ *Selesai & Teruji (Passed)*

---

## 📊 MODUL 11: RINGKASAN METRIK & DASHBOARD ANALYTICS MITRA

Ringkasan agregat kinerja operasional harian basecamp mitra.

- [x] **`GET /api/v1/mitra/analytics/summary`** — Ambil metrik performa komprehensif: Financial (Saldo, Penarikan, Pendapatan Bersih), Operations Today (Pendaki Berangkat, Sedang Mendaki, Kuota Harian, Status Jalur), Action Queues (Pesanan Paid, Logbook Pending, Refund Pending), dan Resources (Total Basecamp, Produk Aktif, Staf Tersedia, Stok Menipis).
  - **Route Name**: `mitra.analytics.summary`
  - **Controller**: [`MitraAnalyticsController@summary`](file:///D:/laragon/www/summit-v2/backend/app/Http/Controllers/Mitra/AnalyticsController.php)
  - **Middleware**: `auth:sanctum`, `role:mitra`
  - **Query Params**: `basecamp_id` (optional, integer)
  - **Response**: JSON Object dengan data agregat operasional & finansial
  - **Status**: ✅ *Selesai & Teruji (Passed)*