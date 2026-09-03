# MODULAR PRD 01: AUTHENTICATION & KYC

Document Version: 1.0.0  
Status: Approved / Implementation Ready  
Target Module: Authentication, User Profile & Climber KYC Verification  
Target Path: `docs/prd/modules/01-authentication-kyc.md`

---

## GLOBAL API STANDARDIZATION & RESPONSE RULES

Aturan standar ini berlaku secara global di seluruh endpoint modul API Summit v2 (termasuk Auth, KYC, dan Master Data).

### 1. Standard URL Structure
* **API Prefix:** Seluruh API wajib diawali dengan prefix `/api/v1/`.
* **Resource Naming:** Naming convention menggunakan `kebab-case` dan kata benda jamak (*plural nouns*) untuk koleksi resource (misal: `/api/v1/auth/...`, `/api/v1/admin/kyc`, `/api/v1/mountains`).
* **Parameter Handling:**
  * **Query Params:** Digunakan untuk filtering, sorting, dan paginasi (misal: `?status=pending&page=1&per_page=15`).
  * **Path Params:** Digunakan untuk mengidentifikasi resource tunggal berdasarkan ID numerik (misal: `/api/v1/admin/kyc/{id}`).

### 2. Standard HTTP Status Codes & Error Handling

| Status Code | Label | Skenario Penggunaan |
| :--- | :--- | :--- |
| `200 OK` | OK | Permintaan berhasil memproses data (Get, Put, Patch, Delete). |
| `201 Created` | Created | Resource baru berhasil dibuat (Post register, create resource). |
| `400 Bad Request` | Bad Request | Parameter/payload secara logika bisnis tidak valid (OTP salah/expired, email sudah terverifikasi). |
| `401 Unauthorized` | Unauthorized | Token Sanctum missing, invalid, atau expired. |
| `403 Forbidden` | Forbidden | Pengguna terautentikasi tetapi tidak memiliki hak akses (role mismatch). |
| `404 Not Found` | Not Found | Resource yang diminta tidak ditemukan di database. |
| `422 Unprocessable Entity` | Validation Error | Form request validation Laravel gagal. |
| `500 Internal Server Error` | Server Error | Unhandled exception atau kegagalan internal pada server. |

### 3. Standard Response Schema (JSON Envelope)

#### A. Single Data Success Envelope (`200 OK` / `201 Created`)
```json
{
  "status": "success",
  "message": "Deskripsi pesan sukses yang jelas.",
  "data": {
    "id": 1,
    "name": "Nama User",
    "email": "user@example.com"
  }
}
```

#### B. Paginated Array List Success Envelope (`200 OK`)
```json
{
  "status": "success",
  "message": "Deskripsi daftar data yang diambil.",
  "data": [
    {
      "id": 1,
      "nama_lengkap": "John Doe",
      "status_verifikasi": "pending"
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
    "first": "http://api.summit.test/api/v1/admin/kyc?page=1",
    "last": "http://api.summit.test/api/v1/admin/kyc?page=5",
    "prev": null,
    "next": "http://api.summit.test/api/v1/admin/kyc?page=2"
  }
}
```

#### C. General Error Response Envelope (`400`, `401`, `403`, `404`, `500`)
```json
{
  "status": "error",
  "message": "Pesan deskripsi error yang ramah pengguna.",
  "error_code": "ERR_INVALID_OTP"
}
```

#### D. Validation Error Response Envelope (`422 Unprocessable Entity`)
```json
{
  "status": "error",
  "message": "The given data was invalid.",
  "error_code": "ERR_VALIDATION_FAILED",
  "errors": {
    "email": [
      "The email field is required.",
      "The email must be a valid email address."
    ],
    "password": [
      "The password field must be at least 8 characters."
    ]
  }
}
```

---

## 1. MODULE OVERVIEW

Modul **Authentication & KYC (Know Your Customer)** merupakan fondasi keamanan dan legalitas platform **Summit v2**. Modul ini melayani tiga entitas utama pengguna:
1. **Pendaki (Climber / End-User):** Melakukan pendaftaran akun, verifikasi email menggunakan kode OTP 6 digit, autentikasi berbasis Laravel Sanctum (token diset ke `@capacitor/preferences`), manajemen kata sandi, serta pengunggahan dokumen identitas resmi (KTP/Paspor via `@capacitor/camera`) beserta data kontak darurat.
2. **Mitra Basecamp (Partner):** Autentikasi akun mitra untuk mengelola operasional jalur dan produk.
3. **Admin Platform:** Supervisor dengan otorisasi tertinggi untuk meninjau, menyetujui (*approve*), atau menolak (*reject*) dokumen KYC Pendaki, serta mengunduh dokumen identitas melalui *private storage stream* secara aman.

### Technical & UI Stack Implementation Details
* **Web Admin & Dashboard Mitra**: Built with **Vue.js 3** + **shadcn-vue** (Tailwind CSS based).
  - Target UI Components: `Form`, `FormItem`, `FormField`, `Input`, `Button`, `Data-Table` (untuk list KYC), `Dialog` (modal review detail KYC & penolakan), `Badge` (status pending/approved/rejected), `Toast` (notifikasi respon).
* **Mobile Pendaki App**: Built with **Quasar Framework (Vue 3)** + **Capacitor JS**.
  - Target UI Components: `q-page`, `q-form`, `q-input` (OTP & form data diri), `q-btn`, `q-uploader` / `@capacitor/camera` (pengambil & kompresi foto KTP/Paspor), `q-chip` (status KYC).
  - Native Engine: Token otentikasi disimpan aman menggunakan `@capacitor/preferences`.


## 2. USER STORIES

| ID | Persona | User Story | Prioritas |
| :--- | :--- | :--- | :---: |
| **US-AUTH-01** | Pendaki | Sebagai Pendaki baru, saya ingin mendaftar akun dengan nama, email, dan password, agar saya menerima kode OTP verifikasi email. | **P1** |
| **US-AUTH-02** | Pendaki | Sebagai Pendaki, saya ingin memverifikasi kode OTP 6 digit yang dikirim ke email, agar akun saya aktif dan saya memperoleh access token untuk login. | **P1** |
| **US-AUTH-03** | Pendaki | Sebagai Pendaki, saya ingin meminta ulang kode OTP jika OTP sebelumnya kadaluarsa/tidak diterima, agar saya tetap dapat memverifikasi email saya. | **P1** |
| **US-AUTH-04** | All Roles | Sebagai Pengguna terdaftar, saya ingin melakukan login dengan email dan password, agar saya dapat mengakses fitur sesuai role saya (Pendaki/Mitra/Admin). | **P1** |
| **US-AUTH-05** | All Roles | Sebagai Pengguna terautentikasi, saya ingin melihat profil saya dan memperbarui password secara berkala agar keamanan akun saya terjamin. | **P1** |
| **US-AUTH-06** | All Roles | Sebagai Pengguna terautentikasi, saya ingin melakukan logout untuk menghapus token dan cookie sesi. | **P1** |
| **US-KYC-01** | Pendaki | Sebagai Pendaki, saya ingin mengunggah foto KTP/Paspor, mengisi data diri, dan data kontak darurat, agar identitas saya diverifikasi (KYC) sebelum mendaki. | **P1** |
| **US-KYC-02** | Pendaki | Sebagai Pendaki, saya ingin mengecek status pengajuan KYC saya (pending, disetujui, ditolak) beserta alasan penolakan jika ada. | **P1** |
| **US-KYC-03** | Admin | Sebagai Admin, saya ingin melihat daftar seluruh pengajuan KYC pendaki beserta filter status verifikasi, agar proses review lebih terstruktur. | **P1** |
| **US-KYC-04** | Admin | Sebagai Admin, saya ingin mengunduh dokumen identitas pendaki secara aman dari storage privat untuk memverifikasi keaslian dokumen. | **P1** |
| **US-KYC-05** | Admin | Sebagai Admin, saya ingin menyetujui atau menolak pengajuan KYC pendaki beserta memberikan alasan penolakan jika dokumen tidak valid. | **P1** |

---

## 3. FUNCTIONAL REQUIREMENTS (FR) & API CONTRACT MAPPING

| FR ID | Nama Fitur | HTTP Method & URL Endpoint (Refactored) | Request Payload / Headers | Expected Response Schema | Middleware / Guard |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **FR-AUTH-01** | Registrasi Akun Pendaki | `POST /api/v1/auth/register` | **Body (JSON):**<br>`name` (string, req)<br>`email` (email, req, unique)<br>`password` (string, min:8, req)<br>`password_confirmation` (string, req) | `201 Created`<br>Single Data Envelope (`UserResource`) | `guest` |
| **FR-AUTH-02** | Verifikasi OTP Email | `POST /api/v1/auth/verify-otp` | **Body (JSON):**<br>`email` (email, req)<br>`otp` (string, 6 digit, req) | `200 OK`<br>Single Data Envelope + `token` + HttpOnly Cookie | `guest` |
| **FR-AUTH-03** | Resend OTP Code | `POST /api/v1/auth/resend-otp` | **Body (JSON):**<br>`email` (email, req) | `200 OK`<br>Message Envelope | `throttle:api` (5 req/min) |
| **FR-AUTH-04** | Login Pengguna | `POST /api/v1/auth/login` | **Body (JSON):**<br>`email` (email, req)<br>`password` (string, req) | `200 OK`<br>Single Data Envelope + `token` + HttpOnly Cookie | `throttle:api` |
| **FR-AUTH-05** | Logout Pengguna | `POST /api/v1/auth/logout` | **Headers:** `Authorization: Bearer <token>` | `200 OK`<br>Message Envelope + Clear Cookie | `auth:sanctum` |
| **FR-AUTH-06** | Detail Profil Pengguna | `GET /api/v1/profile` | **Headers:** `Authorization: Bearer <token>` | `200 OK`<br>Single Data Envelope (`ProfileResource`) | `auth:sanctum` |
| **FR-AUTH-07** | Ubah Password | `PUT /api/v1/profile/password` | **Headers:** `Authorization: Bearer <token>`<br>**Body (JSON):**<br>`current_password` (req)<br>`new_password` (req, min:8)<br>`new_password_confirmation` (req) | `200 OK`<br>Message Envelope | `auth:sanctum` |
| **FR-KYC-01** | Submit Dokumentasi KYC | `POST /api/v1/kyc/submit` | **Headers:** `Authorization: Bearer <token>`<br>**Body (multipart/form-data):**<br>`nama_lengkap` (string)<br>`jenis_identitas` (enum: ktp, paspor, sim, lainnya)<br>`nomor_identitas` (string)<br>`foto_identitas` (file: image, max 2MB)<br>`tanggal_lahir` (date)<br>`jenis_kelamin` (enum: l, p)<br>`alamat` (text)<br>`telepon` (string)<br>`nama_kontak_darurat` (string)<br>`telepon_darurat` (string)<br>`hubungan_darurat` (string) | `200 OK`<br>Single Data Envelope (`PendakiResource`) | `auth:sanctum`, `role:pendaki` |
| **FR-KYC-02** | Status KYC Pendaki | `GET /api/v1/kyc/status` | **Headers:** `Authorization: Bearer <token>` | `200 OK`<br>Single Data Envelope (`PendakiResource` / null) | `auth:sanctum` |
| **FR-KYC-03** | List KYC Pendaki (Admin) | `GET /api/v1/admin/kyc` | **Headers:** `Authorization: Bearer <token>`<br>**Query:** `status` (optional: pending, disetujui, ditolak), `page` (int) | `200 OK`<br>Paginated List Envelope (`PendakiResource`) | `auth:sanctum`, `role:admin` |
| **FR-KYC-04** | Detail KYC Pendaki (Admin) | `GET /api/v1/admin/kyc/{id}` | **Headers:** `Authorization: Bearer <token>`<br>**Path:** `id` (int) | `200 OK`<br>Single Data Envelope (`PendakiResource`) | `auth:sanctum`, `role:admin` |
| **FR-KYC-05** | Download Dokumen KTP/Paspor | `GET /api/v1/admin/kyc/{id}/download-document` | **Headers:** `Authorization: Bearer <token>`<br>**Path:** `id` (int) | `200 OK`<br>Binary File Stream (`application/octet-stream`) | `auth:sanctum`, `role:admin` |
| **FR-KYC-06** | Approval / Rejection KYC | `POST /api/v1/admin/kyc/{id}/verify` | **Headers:** `Authorization: Bearer <token>`<br>**Body (JSON):**<br>`status_verifikasi` (enum: disetujui, ditolak)<br>`alasan_penolakan` (string, req jika status_verifikasi=ditolak) | `200 OK`<br>Single Data Envelope (`PendakiResource`) | `auth:sanctum`, `role:admin` |

---

## 4. NON-FUNCTIONAL REQUIREMENTS (NFR)

1. **Token Security & Authentication:**
   * Penggunaan Laravel Sanctum bearer token dengan *expiration policy* sesuai konfigurasi sesi.
   * Sertakan pengiriman HttpOnly Cookie (`auth_token`) untuk mengamankan platform Web dari serangan Cross-Site Scripting (XSS).
2. **Dokumen KYC Storage & Enkripsi:**
   * Foto KTP/Paspor **WAJIB** disimpan pada disk privat (`Storage::disk('local')` di folder `backend/storage/app/kyc_documents`) dan **TIDAK BOLEH** diakses secara publik via URL statis.
   * File disimpan dengan UUID/random hash nama file untuk mencegah enumerasi nama file.
3. **Rate Limiting & Anti-Bruteforce:**
   * Endpoint `POST /api/v1/auth/login` dan `POST /api/v1/auth/resend-otp` dilindungi middleware `throttle:api` (maksimal 5-10 percobaan per menit).
4. **OTP Expiration:**
   * Kode OTP 6 digit berlaku maksimal 10 menit sejak diterbitkan. Setelah kadaluarsa, OTP otomatis tidak berlaku.
5. **Performa Response API:**
   * Response latency P95 untuk seluruh endpoint Auth & KYC < 300 ms.

---

## 5. EDGE CASES & ERROR HANDLING

1. **Login Sebelum Verifikasi OTP:**
   * Jika pengguna belum verifikasi email via OTP, login mengembalikan status `403 Forbidden` dengan pesan `"Your email address is not verified."`.
2. **Kode OTP Kadaluarsa / Salah:**
   * Pengisian OTP yang salah atau sudah kadaluarsa mengembalikan `400 Bad Request` dengan pesan `"Invalid or expired OTP code."`.
3. **Re-submit KYC Setelah Ditolak:**
   * Pendaki yang pengajuan KYC-nya status `ditolak` diizinkan melakukan submit ulang via `POST /api/v1/kyc/submit`. Sistem akan memperbarui data dan mengembalikan `status_verifikasi` menjadi `pending` serta menghapus `alasan_penolakan` lama.
4. **Ukuran File Dokumentasi KYC Exceeding Limit:**
   * Upload file > 2MB atau format non-image mengembalikan `422 Unprocessable Entity` dengan error key `foto_identitas`.
5. **Akses Dokumen KTP Oleh Non-Admin:**
   * Pengguna tanpa role `admin` yang mengakses `/api/v1/admin/kyc/{id}/download-document` ditolak dengan response `403 Forbidden`.

---

## 6. SCOPE BOUNDARY

### In-Scope (v1.0 - Core Release)
* Registrasi, OTP email verification, Login, Logout, Profile Show, dan Update Password.
* Pengunggahan KYC Pendaki (KTP/Paspor/SIM) & data kontak darurat.
* Verifikasi KYC Admin (List, Show Detail, Secure File Download Stream, Verify Approve/Reject).
* Storing file KYC di private storage disk local.

### Out-of-Scope (Fase / Version Berikutnya)
* Social OAuth Login (Google, Facebook, Apple Sign-In).
* Multi-Factor Authentication (2FA via Authenticator App / SMS TOTP).
* Automatic OCR Scanning untuk KTP/Paspor.
* Verifikasi Biometrik Liveness Detection (Face Recognition Match).
