# Technical Specification: API Standards & Guidelines (`backend/`)

Dokumen spesifikasi teknis ini mendefinisikan standar API komprehensif untuk backend **Summit v2** (`backend/`). Dokumen ini disusun berdasarkan kesepakatan spesifikasi API pada Module PRD 01 hingga 08 dan berfungsi sebagai panduan utama bagi pengembang serta **AI Agent (Coder)** dalam membangun REST API yang konsisten, aman, dan *production-ready*.

---

## 1. Arsitektur Global & Konvensi URL

- **API Prefix**: Seluruh URL API wajib diawali dengan prefix `/api/v1/`.
- **Resource Naming**:
  - Menggunakan format `kebab-case` dan kata benda jamak (*plural nouns*) untuk koleksi resource (misal: `/api/v1/mountains`, `/api/v1/orders`, `/api/v1/mitra/products`).
  - Prefix area role untuk endpoint terproteksi: `/api/v1/admin/...` untuk Admin, `/api/v1/mitra/...` untuk Mitra, dan `/api/v1/pendaki/...` untuk Pendaki.
- **Handling Parameters**:
  - **Query Params**: Digunakan untuk filter, pencarian, pengurutan, dan paginasi (misal: `?kategori=rental&is_active=1&page=1&per_page=15`).
  - **Path Params**: Digunakan untuk mengidentifikasi resource tunggal menggunakan ID numerik atau string Invoice (misal: `/api/v1/mountains/{id}`, `/api/v1/orders/{invoice}`).
- **HTTP Header Standar**:
  - `Accept`: `application/json` (Wajib untuk semua request).
  - `Content-Type`: `application/json` atau `multipart/form-data` (untuk upload berkas).
  - `Authorization`: `Bearer <sanctum_token>` (Wajib untuk endpoint terautentikasi).

---

## 2. Matriks HTTP Status Code & Handling Exception

| Status Code | Label | Skenario Penggunaan |
| :--- | :--- | :--- |
| `200 OK` | OK | Request GET, PUT, PATCH, DELETE berhasil diproses. |
| `201 Created` | Created | Resource baru (POST register, order, product, logbook) berhasil dibuat. |
| `400 Bad Request` | Bad Request | Pelanggaran logika bisnis (misal: OTP kadaluarsa, kuota habis, saldo withdrawal tidak cukup). |
| `401 Unauthorized` | Unauthorized | Token Sanctum missing, invalid, expired, atau webhook token invalid. |
| `403 Forbidden` | Forbidden | User terautentikasi tetapi tidak memiliki role/hak akses (role mismatch). |
| `404 Not Found` | Not Found | Resource (Gunung, Order, User) tidak ditemukan di database. |
| `422 Unprocessable Entity` | Validation Error | Form request validation Laravel gagal (parameter input missing/invalid). |
| `500 Internal Server Error` | Server Error | Unhandled exception internal server atau kegagalan third-party API. |

---

## 3. Standard Response Schema (JSON Envelope)

Seluruh respon API backend **wajib** dibungkus dalam salah satu struktur JSON Envelope standar berikut:

### 3.1. Single Data Success Envelope (`200 OK` / `201 Created`)
```json
{
  "status": "success",
  "message": "Deskripsi pesan sukses yang jelas.",
  "data": {
    "id": 1,
    "key": "value"
  }
}
```

### 3.2. Paginated Array List Success Envelope (`200 OK`)
```json
{
  "status": "success",
  "message": "Daftar data berhasil diambil.",
  "data": [
    {
      "id": 1,
      "key": "value"
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  },
  "links": {
    "first": "http://api.summit.test/api/v1/resource?page=1",
    "last": "http://api.summit.test/api/v1/resource?page=5",
    "prev": null,
    "next": "http://api.summit.test/api/v1/resource?page=2"
  }
}
```

### 3.3. General Error Response Envelope (`400`, `401`, `403`, `404`, `500`)
```json
{
  "status": "error",
  "message": "Pesan deskripsi error yang ramah pengguna.",
  "error_code": "ERR_SPECIFIC_BUSINESS_CODE"
}
```

### 3.4. Validation Error Response Envelope (`422 Unprocessable Entity`)
```json
{
  "status": "error",
  "message": "The given data was invalid.",
  "error_code": "ERR_VALIDATION_FAILED",
  "errors": {
    "field_name": [
      "Pesan error spesifik validasi."
    ]
  }
}
```

---

## 4. Keamanan, Otorisasi, & Rate Limiting

1. **Otentikasi Token & Cookie (Laravel Sanctum):**
   - **Mobile App (Quasar + Capacitor JS)**: Menggunakan HTTP Header `Authorization: Bearer <token>`. Token disimpan secara terenkripsi di native device storage menggunakan plugin `@capacitor/preferences`.
   - **Web Platform (Admin/Mitra Vue.js 3 + shadcn-vue)**: Menyertakan HttpOnly Cookie (`auth_token`) atau Bearer Token terenkripsi pada State Pinia untuk mencegah Cross-Site Scripting (XSS).
2. **Otorisasi Berbasis Role & Policy:**
   - Role Enum: `'admin'`, `'mitra'`, `'pendaki'`.
   - Gunakan Middleware `role:admin`, `role:mitra`, `role:pendaki` dan Laravel Policy (`BasecampPolicy`, `OrderPolicy`) untuk memastikan Multi-Tenant Data Isolation (Mitra A tidak bisa mengedit produk Mitra B).
3. **Proteksi Rate Limiting & Bruteforce:**
   - Endpoint login & resend OTP dilindungi `throttle:api` (maksimal 5–10 percobaan per menit).

---

## 5. Katalog Endpoint Terintegrasi (Module 01 - 08 Sitemap)

Berikut adalah daftar seluruh endpoint API backend yang telah terdistribusi pada PRD Module 01 sampai 08:

| Modul | Method | URL Endpoint | Access Guard | Payload / Query | Fungsi & Deskripsi |
| :--- | :---: | :--- | :--- | :--- | :--- |
| **01. Auth & KYC** | `POST` | `/api/v1/auth/register` | Public (guest) | Body JSON | Registrasi akun pendaki baru & kirim OTP email |
| | `POST` | `/api/v1/auth/verify-otp` | Public (guest) | Body JSON | Verifikasi OTP 6 digit email pendaki |
| | `POST` | `/api/v1/auth/resend-otp` | Public (guest) | Body JSON | Kirim ulang kode OTP |
| | `POST` | `/api/v1/auth/login` | Public (guest) | Body JSON | Login pengguna (Admin, Mitra, Pendaki) |
| | `POST` | `/api/v1/auth/logout` | `auth:sanctum` | Header Token | Logout & hapus token/cookie sesi |
| | `GET` | `/api/v1/profile` | `auth:sanctum` | Header Token | Ambil detail profil pengguna aktif |
| | `PUT` | `/api/v1/profile/password` | `auth:sanctum` | Body JSON | Ubah kata sandi akun |
| | `POST` | `/api/v1/kyc/submit` | `role:pendaki` | multipart/form-data | Unggah berkas KTP/Paspor & kontak darurat |
| | `GET` | `/api/v1/kyc/status` | `auth:sanctum` | Header Token | Cek status verifikasi KYC pendaki |
| | `GET` | `/api/v1/admin/kyc` | `role:admin` | Query Params | List pengajuan KYC pendaki untuk Admin |
| | `GET` | `/api/v1/admin/kyc/{id}` | `role:admin` | Path Param | Detail pengajuan KYC pendaki |
| | `GET` | `/api/v1/admin/kyc/{id}/download-document` | `role:admin` | Path Param | Stream privat file KTP/Paspor pendaki |
| | `POST` | `/api/v1/admin/kyc/{id}/verify` | `role:admin` | Body JSON | Persetujuan (Approve/Reject) KYC oleh Admin |
| **02. Master Data** | `GET` | `/api/v1/mountains` | Public | Query Params | List gunung publik (Cached Redis 1 jam) |
| | `GET` | `/api/v1/mountains/{id}` | Public | Path Param | Detail gunung & jalur pendakiannya |
| | `POST` | `/api/v1/admin/mountains` | `role:admin` | multipart/form-data | Tambah data gunung baru oleh Admin |
| | `POST` | `/api/v1/admin/mountains/{id}` | `role:admin` | multipart (with `_method=PUT`) | Update data gunung |
| | `DELETE`| `/api/v1/admin/mountains/{id}` | `role:admin` | Path Param | Hapus data gunung |
| | `POST` | `/api/v1/admin/trails` | `role:admin` | Body JSON | Tambah jalur pendakian oleh Admin |
| | `PUT` | `/api/v1/admin/trails/{id}` | `role:admin` | Body JSON | Update data jalur pendakian |
| | `DELETE`| `/api/v1/admin/trails/{id}` | `role:admin` | Path Param | Hapus data jalur pendakian |
| | `GET` | `/api/v1/admin/basecamps` | `role:admin` | Query Params | List basecamp oleh Admin |
| | `POST` | `/api/v1/admin/basecamps` | `role:admin` | Body JSON | Pemetaan basecamp ke Mitra & Jalur |
| **03. Pemesanan** | `GET` | `/api/v1/packages/search` | Public | Query Params | Pencarian tiket, kuota & paket pendakian |
| | `POST` | `/api/v1/orders/checkout` | `role:pendaki` | Body JSON | Checkout reservasi tiket & layanan sewa/SDM |
| | `GET` | `/api/v1/orders` | `auth:sanctum` | Query Params | List riwayat pesanan pengguna |
| | `GET` | `/api/v1/orders/{invoice}` | `auth:sanctum` | Path Param | Detail pesanan & status pembayaran |
| | `POST` | `/api/v1/webhooks/xendit/pay-in` | Public (Webhook) | Header Token | Callback Xendit Invoice Payment |
| | `PUT` | `/api/v1/mitra/trails/{id}/status` | `role:mitra` | Body JSON | Update status Buka/Tutup jalur real-time |
| **04. Inventory** | `GET` | `/api/v1/mitra/products` | `role:mitra` | Query Params | List inventaris alat sewa & logistik mitra |
| | `POST` | `/api/v1/mitra/products` | `role:mitra` | Body JSON | Tambah produk alat sewa/logistik baru |
| | `PUT` | `/api/v1/mitra/products/{id}` | `role:mitra` | Body JSON | Update produk alat sewa/logistik |
| | `DELETE`| `/api/v1/mitra/products/{id}` | `role:mitra` | Path Param | Hapus produk dari inventaris mitra |
| | `GET` | `/api/v1/mitra/staff` | `role:mitra` | Query Params | List jadwal ketersediaan Porter & Guide |
| | `POST` | `/api/v1/mitra/staff` | `role:mitra` | Body JSON | Tambah data Porter/Guide & jadwal tugas |
| **05. Escrow & Payout** | `GET` | `/api/v1/mitra/wallet/balance` | `role:mitra` | Header Token | Detail saldo pending & saldo aktif mitra |
| | `GET` | `/api/v1/mitra/wallet/transactions` | `role:mitra` | Query Params | List mutasi transaksi ledger escrow mitra |
| | `POST` | `/api/v1/mitra/wallet/withdraw` | `role:mitra` | Body JSON | Pengajuan penarikan dana ke rekening mitra |
| | `POST` | `/api/v1/webhooks/xendit/disbursement` | Public (Webhook) | Header Token | Callback status Xendit Disbursement |
| | `GET` | `/api/v1/admin/escrow/ledger` | `role:admin` | Query Params | Laporan konsolidasi ledger escrow platform |
| | `POST` | `/api/v1/admin/refunds/{id}/process` | `role:admin` | Body JSON | Processing & eksekusi refund oleh Admin |
| **06. Logbook** | `POST` | `/api/v1/orders/{invoice}/logbook` | `role:pendaki` | multipart/form-data | Upload foto Summit Proof & catatan puncak |
| | `GET` | `/api/v1/orders/{invoice}/logbook` | `auth:sanctum` | Path Param | Detail logbook & status validasi summit |
| | `GET` | `/api/v1/basecamp/logbooks` | `role:mitra` | Query Params | List permohonan validasi logbook di basecamp |
| | `POST` | `/api/v1/basecamp/logbooks/{id}/verify` | `role:mitra` | Body JSON | Verifikasi Approve/Reject Logbook |
| | `GET` | `/api/v1/orders/{invoice}/certificate` | `auth:sanctum` | Path Param | Download PDF E-Certificate Pendakian |
| | `GET` | `/api/v1/pendaki/badges` | `role:pendaki` | Header Token | List Digital Badges koleksi pendaki |
| **07. In-App Chat** | `POST` | `/api/v1/chat/rooms` | `auth:sanctum` | Body JSON | Get / Create Chat Room terikat `pesanan_id` |
| | `GET` | `/api/v1/chat/rooms` | `auth:sanctum` | Query Params | List Chat Room aktif pengguna |
| | `GET` | `/api/v1/chat/rooms/{room_id}/messages` | `auth:sanctum` | Query Params | History pesan obrolan terpaginasi |
| | `POST` | `/api/v1/chat/rooms/{room_id}/messages` | `auth:sanctum` | multipart/form-data | Kirim pesan teks / lampiran gambar |
| | `POST` | `/api/v1/chat/rooms/{room_id}/read` | `auth:sanctum` | Path Param | Tandai pesan obrolan telah dibaca |
| **08. Ads Monetization**| `GET` | `/api/v1/ads/banners` | Public | Query Params | List banner iklan aktif (Cached Redis 15m) |
| | `POST` | `/api/v1/ads/banners/{id}/click` | Public | Path Param | Track click analytics banner iklan |
| | `GET` | `/api/v1/admin/ads/banners` | `role:admin` | Query Params | List seluruh banner iklan untuk Admin |
| | `POST` | `/api/v1/admin/ads/banners` | `role:admin` | multipart/form-data | Buat slot banner iklan baru oleh Admin |
| | `POST` | `/api/v1/admin/ads/banners/{id}` | `role:admin` | multipart (with `_method=PUT`) | Update banner iklan & periode tayang |
| | `DELETE`| `/api/v1/admin/ads/banners/{id}` | `role:admin` | Path Param | Hapus slot banner iklan |

---

## 6. Panduan Implementasi untuk AI Agent (Coder)

Saat AI Agent melakukan penulisan kode controller, service, atau form request pada `backend/`, wajib mematuhi aturan teknis berikut:

1. **Dokumentasi OpenAPI (L5-Swagger)**:
   - Setiap Controller wajib dilengkapi dengan anotasi OpenAPI (L5-Swagger / DarkaOnLine) `@OA\Get`, `@OA\Post`, `@OA\Response`, dan `@OA\JsonContent` sesuai skema response envelope.
2. **Gunakan Custom Form Request:**
   - Jangan melakukan validasi `$request->validate()` di dalam Controller. Buat class Form Request khusus di `backend/app/Http/Requests/` (misal: `StoreOrderRequest.php`, `SubmitKycRequest.php`).
3. **Transformasi Menggunakan API Resources:**
   - Seluruh respon JSON `data` wajib ditransformasikan menggunakan class Eloquent Resource di `backend/app/Http/Resources/` (misal: `GunungResource.php`, `PesananResource.php`). Jangan pernah mengembalikan Model Eloquent mentah.
4. **Presisi Decimal pada Finansial:**
   - Semua kolom keuangan wajib di-cast sebagai `decimal:2` di Model dan dihitung menggunakan fungsi presisi tanpa pembulatan floating-point.
5. **Pengecekan Otorisasi Berlapis:**
   - Setiap endpoint yang membutuhkan hak spesifik wajib memiliki pengecekan `FormRequest::authorize()` atau `$this->authorize('update', $model)` menggunakan Laravel Policy.
