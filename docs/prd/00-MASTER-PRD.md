# Problem Statement & Context

### 1.1 Latar Belakang Masalah

Sistem pemesanan tiket dan layanan pendakian gunung saat ini masih terfragmentasi, manual, dan berrisiko tinggi dari sisi keamanan transaksi maupun operasional lapangan. Ketidakadaannya platform terintegrasi menciptakan ketimpangan proses bisnis bagi tiga entitas utama:

* **Sisi Pendaki (End-User):** Proses pendaftaran dan syarat administrasi (KYC/Identitas, aturan kesehatan) masih dilakukan secara manual atau melalui *platform* terpisah. Pembayaran yang mengandalkan transfer manual ke rekening pribadi mitra berrisiko tinggi terhadap penipuan (*fraud*), serta sulitnya memesan layanan pendukung (sewa alat, porter, guide) secara *all-in-one*.
* **Sisi Mitra (Pengelola Basecamp/Operator Lokal):** Pengelolaan kuota harian, pengaturan harga paket, status buka/tutup jalur akibat cuaca buruk, hingga stok inventaris rental dan jadwal porter/guide masih dicatat secara manual. Mitra juga menghadapi masalah kepastian pembayaran serta mekanisme pencairan dana yang tidak transparan.
* **Sisi Admin (Pemilik Platform):** Kesulitan dalam mengawasi arus transaksi secara *real-time*, mengelola skema komisi platform secara fleksibel, serta memvalidasi legalitas mitra dan dokumen identitas pendaki secara otomatis dan terpusat.

---

### 1.2 Rumusan Masalah (Core Problem Statement)

> **"Belum adanya platform *marketplace* terintegrasi yang mampu mengotomatiskan manajemen tiket pendakian, alokasi kuota & logistik mitra, serta mengamankan arus transaksi finansial *multi-party* secara transparan melalui skema *Escrow* dan penarikan dana otomatis."**

---

### 1.3 Solusi Sistem yang Diusulkan

Membangun platform *Marketplace Tiket Pendakian Gunung* berbasis *Web Dashboard* (Laravel & Vue.js 3 + shadcn-vue) dan *Mobile App* (Quasar Framework & Capacitor JS) dengan PostgreSQL sebagai basis data utama. Platform ini menghadirkan:

* **Sistem Manajemen Terpusat (Admin):** Kontrol penuh master data gunung/jalur, verifikasi KYC pengguna, penetapan syarat SOP pendakian, serta pemotongan fee komisi platform.
* **Sistem Operasional Digital (Mitra):** Manajemen kuota harian *real-time*, pembuatan paket tiket, pengelolaan stok sewa alat & logistik, serta penjadwalan SDM (Porter/Guide).
* **Sistem Transaksi & Escrow Terintegrasi (Xendit):**
  * **Inbound (Pay-in):** Penampungan dana otomatis menggunakan *Xendit Invoice* (VA, QRIS, e-Wallet) di mana dana pendaki dikunci sebagai *Saldo Virtual Pending*.
  * **Outbound (Disbursement):** Pencairan dana riil ke rekening/e-wallet Mitra menggunakan *Xendit Disbursement* secara otomatis setelah pendakian selesai divalidasi.
* **Aplikasi Mobile Pendaki (Quasar + Capacitor):** Fitur pencarian tiket *all-in-one*, *In-App Chat*, integrasi pembayaran, dan *Digital Logbook* (Upload *summit proof* via Capacitor Camera & validasi turun gunung).

---

# Goals & Metrik Kesuksesan

Section ini mendefinisikan tujuan terukur (*measurable goals*) yang menjadi kriteria utama keberhasilan pembangunan aplikasi *Marketplace Tiket Pendakian Gunung*.

| ID | Tujuan Sistem | Metrik Kesuksesan Terukur |
| --- | --- | --- |
| **G1** | **Keamanan Transaksi & Sistem Escrow** | 100% dana transaksi dari Pendaki terkelola secara otomatis via Xendit Invoice (inbound) & Xendit Disbursement (outbound) tanpa ada penanganan dana manual oleh Admin. |
| **G2** | **Otomatisasi Verifikasi Pengguna (KYC)** | Mengurangi waktu proses verifikasi identitas (KTP/Passport) Pendaki dan verifikasi legalitas Mitra menjadi <24 jam sejak dokumen diunggah. |
| **G3** | **Manajemen Kuota & Jalur Real-time** | Pembaruan status kuota harian dan penutupan jalur mendadak (misal: akibat cuaca buruk/badai) tersinkronisasi ke aplikasi mobile Pendaki dalam waktu <5 detik secara real-time. |
| **G4** | **Pencairan Dana Mitra (Disbursement)** | Pencairan dana dari *Saldo Aktif* ke rekening/e-wallet Mitra via Xendit Disbursement dapat diselesaikan secara otomatis dalam waktu <5 menit setelah pesanan divalidasi selesai. |
| **G5** | **Performa & Responsivitas API** | Respon API pada *Web Stack* (Laravel / Vue.js 3) dan *Mobile App* (Quasar / Capacitor JS) untuk transaksi checkout dan pencarian kuota <500ms pada kondisi koneksi internet normal (10 Mbps+).|
| **G6** | **Konsistensi Lintas Platform** | Fitur eksplorasi, pemesanan paket, sewa alat, dan *In-App Chat* berfungsi identik dan responsif baik di platform Web (Admin/Mitra via Vue.js 3 + shadcn-vue) maupun Mobile (Pendaki via Quasar + Capacitor JS). |

---

# Target Users / Personas

Bagian ini mendefinisikan tiga entitas utama yang akan menggunakan aplikasi, mencakup peran, kebutuhan operasional, serta *pain points* masing-masing.

| Persona | Deskripsi & Peran | Kebutuhan Utama |
| --- | --- | --- |
| **Persona 1**<br>**Admin Platform**<br>*(Pengelola Platform & Supervisor Transaksi)* | Tim internal dengan hak akses tertinggi yang bertanggung jawab atas validasi data, legalitas, serta pengawasan lalu lintas keuangan platform. | • **Verifikasi Cepat:** Memvalidasi identitas Pendaki (KTP/Paspor) dan legalitas Mitra secara mudah.<br><br>• **Manajemen Master Data:** Mengelola data gunung, jalur pendakian, serta keterhubungan jalur dengan Mitra secara terpusat.<br><br>• **Kontrol Keuangan & Escrow:** Memantau dana masuk (*pay-in*) dan dana keluar (*disbursement*) melalui Xendit, mengatur persentase komisi platform, serta memproses klaim dan refund.<br><br>• **Monetisasi:** Mengelola slot iklan di dalam aplikasi. |
| **Persona 2**<br>**Mitra Basecamp**<br>*(Pengelola Basecamp & Operator Lokal)* | Pengelola jalur pendakian atau penyedia layanan lokal yang bertindak sebagai penyedia produk dan jasa bagi Pendaki. | • **Manajemen Tiket & Kuota:** Mengatur harga paket pendakian, kuota harian, serta status buka/tutup jalur secara *real-time* (misalnya saat terjadi cuaca ekstrem).<br><br>• **Inventaris & SDM:** Mengelola stok alat sewa (tenda, matras, dan sebagainya), logistik, serta jadwal ketersediaan Porter dan Guide.<br><br>• **Dompet Virtual & Withdrawal:** Memantau saldo pendapatan (*pending* dan *available*) serta melakukan penarikan dana (*withdrawal*) ke rekening bank atau e-wallet. |
| **Persona 3**<br>**Pendaki**<br>*(End User / Pembeli)* | Pengguna akhir aplikasi mobile yang melakukan pencarian informasi, registrasi administrasi, pemesanan layanan, dan pembayaran. | • **Pendaftaran KYC yang Mudah:** Registrasi akun serta unggah identitas (KTP/Paspor) secara aman dan cepat.<br><br>• **Pemesanan All-in-One:** Mencari tiket berdasarkan gunung dan tanggal, kemudian menambahkan penyewaan alat atau jasa Porter/Guide dalam satu transaksi.<br><br>• **Pembayaran Aman & Praktis:** Melakukan pembayaran melalui Virtual Account, QRIS, atau e-wallet tanpa perlu transfer ke rekening pribadi.<br><br>• **Komunikasi & Logbook:** Menggunakan *In-App Chat* untuk berkomunikasi dengan Mitra atau Guide serta mengunggah *summit proof* sebagai bukti validasi selesai pendakian. |

---

# User Stories

Section ini memetakan kebutuhan seluruh entitas pengguna (Admin, Mitra, Pendaki) berdasarkan alur bisnis utama dan prioritas eksekusinya.

| Pri | ID | Role | User Story (Format: *Sebagai [User], Saya Ingin [Aksi], Supaya [Manfaat]*) |
| --- | --- | --- | --- |
| **P1** | **US-MST-01** | **Pendaki** | Sebagai Pendaki, saya ingin mendaftar dan mengunggah dokumen identitas (KTP/Passport) agar akun saya terverifikasi (KYC) dan memenuhi syarat legal pendakian. |
| **P1** | **US-MST-02** | **Admin** | Sebagai Admin, saya ingin melakukan verifikasi (*approval/rejection*) terhadap dokumen KYC Pendaki dan legalitas Mitra agar platform tetap aman dan legal. |
| **P1** | **US-MST-03** | **Admin** | Sebagai Admin, saya ingin mengelola master data Gunung dan Jalur Pendakian serta memetakannya ke Mitra Pengelola agar sistem memiliki data struktur lokasi yang akurat. |
| **P1** | **US-MST-04** | **Mitra** | Sebagai Mitra, saya ingin membuat paket pendakian, menetapkan harga dasar, dan mengatur kuota harian agar dapat membuka penjualan tiket secara terstruktur. |
| **P1** | **US-MST-05** | **Pendaki** | Sebagai Pendaki, saya ingin mencari ketersediaan tiket berdasarkan gunung, jalur, dan tanggal, serta menambahkan sewa alat/jasa porter ke dalam satu pesanan agar pemesanan lebih practical. |
| **P1** | **US-MST-06** | **Pendaki** | Sebagai Pendaki, saya ingin melakukan pembayaran instan via Virtual Account, QRIS, atau e-Wallet (Xendit) agar transaksi cepat dan tidak perlu konfirmasi transfer manual. |
| **P1** | **US-MST-07** | **Admin** | Sebagai Admin, saya ingin sistem menampung dana pembayaran Pendaki ke akun *Escrow* dan menguncinya sebagai "Saldo Virtual Pending" Mitra sampai pendakian divalidasi selesai. |
| **P1** | **US-MST-08** | **Mitra** | Sebagai Mitra, saya ingin mengubah status Buka/Tutup jalur pendakian secara *real-time* jika terjadi kondisi darurat (misal: badai) agar keselamatan pendaki terjaga. |
| **P1** | **US-MST-09** | **Mitra** | Sebagai Mitra, saya ingin mengajukan penarikan dana (*withdraw*) dari Saldo Aktif ke rekening/e-wallet yang terverifikasi via Xendit setelah pendakian selesai divalidasi. |
| **P2** | **US-MST-12** | **Pendaki** | Sebagai Pendaki, saya ingin berkomunikasi via *In-App Chat* dengan Mitra atau Guide agar bisa melakukan koordinasi persiapan sebelum hari pendakian. |
| **P2** | **US-MST-13** | **Pendaki** | Sebagai Pendaki, saya ingin mengunggah foto bukti *summit* (*Digital Logbook*) sebagai galeri personal sekaligus syarat validasi untuk proses checkout/turun gunung. |
| **P2** | **US-MST-14** | **Admin** | Sebagai Admin, saya ingin mengelola slot iklan dan promosi di dalam aplikasi agar platform mendapatkan sumber pendapatan tambahan (*monetisasi*). |

---

# Functional Requirements

Functional Requirements (FR) mendefinisikan fungsi-fungsi yang **wajib** disediakan oleh sistem. Setiap modul mengacu pada ID kebutuhan berikut sebagai acuan implementasi.

| ID | Modul / Area | Deskripsi Kebutuhan Fungsional | Prioritas | Contract / Technical Specification |
| --- | --- | --- | :---: | --- |
| **FR-MST-01** | Auth & KYC | Pendaki dapat melakukan registrasi dan proses KYC dengan mengunggah KTP/Paspor. Mitra diverifikasi oleh Admin sebelum dapat beroperasi. | **P1** | Endpoint `POST /api/v1/auth/kyc`. Dokumen identitas disimpan dalam penyimpanan terenkripsi. |
| **FR-MST-02** | Master Data | Admin dapat melakukan CRUD data Gunung, Jalur Pendakian, SOP, serta pemetaan Jalur ke Mitra. | **P1** | Relasi database: `Gunung` (1:N) `Jalur` (1:N) `Mitra`. |
| **FR-MST-03** | Tiket & Kuota | Mitra dapat membuat paket pendakian, menentukan harga, serta mengelola kuota harian. | **P1** | Menggunakan *atomic transaction* atau *database locking* saat reservasi kuota untuk mencegah *overbooking*. |
| **FR-MST-04** | Status Jalur | Mitra dapat mengubah status jalur menjadi buka atau tutup secara *real-time* pada kondisi darurat (misalnya cuaca ekstrem). | **P1** | Perubahan status jalur langsung memengaruhi hasil pencarian dan ketersediaan tiket. |
| **FR-MST-05** | Inventory & SDM | Mitra dapat mengelola stok alat sewa, logistik, serta jadwal ketersediaan Porter dan Guide. | **P2** | Ketersediaan stok dan jadwal SDM dikaitkan dengan tanggal pemesanan. |
| **FR-MST-06** | Checkout & Cart | Pendaki dapat memesan tiket, alat sewa, dan jasa Porter/Guide dalam satu transaksi. | **P1** | Stok alat sewa otomatis berkurang setelah transaksi berhasil dibuat. |
| **FR-MST-07** | Pay-in (Xendit) | Sistem terintegrasi dengan **Xendit Invoice/XenPlatform** untuk menerima pembayaran melalui Virtual Account, QRIS, dan e-wallet. | **P1** | Webhook `invoice.paid` mengubah status pesanan menjadi `PAID`. |
| **FR-MST-08** | Ledger & Escrow | Sistem mencatat dana dalam akun *escrow* serta saldo virtual Mitra secara otomatis. | **P1** | Rumus perhitungan: `Saldo Pending = Total Pembayaran - Fee Platform - Fee Payment Gateway`. |
| **FR-MST-09** | Logbook Digital | Pendaki dapat mengunggah foto bukti mencapai puncak (*summit proof*) dan menyelesaikan logbook pendakian. | **P2** | Mengubah status pesanan dari `ON_GOING` menjadi `COMPLETED` setelah validasi. |
| **FR-MST-10** | Payout (Xendit) | Mitra dapat melakukan penarikan saldo (*withdrawal*) melalui **Xendit Disbursements API**. | **P1** | Endpoint `POST /api/v1/payouts` memanggil Xendit Disbursement API secara otomatis. |
| **FR-MST-11** | In-App Chat | Pendaki dapat berkomunikasi secara *real-time* dengan Mitra atau Guide melalui fitur chat. | **P2** | Implementasi menggunakan Laravel Reverb / WebSockets / Pusher. |
| **FR-MST-12** | Ads & Monetization | Admin dapat mengelola slot banner iklan dan promosi Mitra pada aplikasi. | **P2** | CRUD slot iklan dengan pengaturan durasi tayang dan jumlah impresi. |

---

# Non-Functional Requirements

Non-Functional Requirements (NFR) mendefinisikan standar kualitas sistem, meliputi performa, keamanan, skalabilitas, ketersediaan, dan konsistensi aplikasi.

| ID | Kategori | Deskripsi Kebutuhan Non-Fungsional | Prioritas |
| --- | --- | --- | :---: |
| **NFR-MST-01** | Performa | Waktu respons API untuk pencarian tiket dan proses checkout maksimal **500 ms (P95)**. Transisi antarlayar pada aplikasi Quasar (Mobile) dan Vue.js 3 + shadcn-vue (Web) maksimal **300 ms**. | **P1** |
| **NFR-MST-02** | Keamanan Data | Dokumen sensitif (KTP/Paspor) harus disimpan menggunakan enkripsi **AES-256**. Seluruh komunikasi API wajib menggunakan **HTTPS (TLS 1.3)**. | **P1** |
| **NFR-MST-03** | Integrasi Pembayaran | Webhook Xendit wajib memvalidasi header `x-callback-token` untuk memastikan notifikasi pembayaran berasal dari sumber yang sah. | **P1** |
| **NFR-MST-04** | Skalabilitas Database | PostgreSQL harus menggunakan strategi *indexing* pada `jalur_id`, `tanggal_pendakian`, dan `status_pesanan` untuk menjaga performa saat terjadi lonjakan reservasi. | **P1** |
| **NFR-MST-05** | Ketersediaan | Sistem memiliki target **SLA uptime 99,5%**, terutama pada layanan transaksi pembayaran, webhook, dan proses pencairan dana. | **P1** |
| **NFR-MST-06** | Konsistensi Data & UI | Aplikasi Web Admin/Mitra (Vue.js 3 + shadcn-vue) dan Mobile Pendaki (Quasar + Capacitor JS) harus menampilkan data yang konsisten secara *real-time* melalui REST API, Webhook, dan mekanisme sinkronisasi data. | **P2** |
| **NFR-MST-07** | Mobile Native Capability (Capacitor JS) | Pengunggahan foto KTP (KYC) dan foto *Summit Proof* memanfaatkan Capacitor Camera Plugin dengan kompresi gambar sebelum dikirim ke API Laravel. Token otentikasi disimpan secara aman di Capacitor Preferences. | **P1** |
| **NFR-MST-08** | Responsive & Theme | Web Admin & Mitra dibangun menggunakan Tailwind CSS + shadcn-vue yang mendukung mode *responsive layout* dan konsistensi skema warna (*branding system*). | **P1** |

---

# Scope (In/Out v1.0)

Bagian ini mendefinisikan batasan fitur yang termasuk dalam Rilis Versi **1.0 (In Scope)** serta fitur yang ditunda ke rilis berikutnya **(Out of Scope)**.

## 7.1 In Scope (Rilis v1.0 – Core Launch)

Fitur-fitur berikut **wajib** tersedia dan terimplementasi pada peluncuran awal versi 1.0.

### Manajemen Pengguna & KYC
- Registrasi dan autentikasi untuk **Admin**, **Mitra**, dan **Pendaki**.
- Proses KYC Pendaki melalui unggah dokumen identitas (KTP/Paspor).
- Verifikasi legalitas Mitra oleh Admin.

### Master Data & Operasional Basecamp
- CRUD data Gunung, Jalur Pendakian, serta SOP dan peraturan pendakian oleh Admin.
- Manajemen paket pendakian, harga, dan kuota harian oleh Mitra.
- Perubahan status buka/tutup jalur secara *real-time* pada kondisi darurat.

### Layanan Mitra
- Manajemen inventaris alat sewa (tenda, *sleeping bag*, matras, dan perlengkapan lainnya).
- Manajemen stok logistik.
- Manajemen jadwal ketersediaan Porter dan Guide.

### Pemesanan & Integrasi Pembayaran
- Pencarian tiket berdasarkan gunung dan tanggal pendakian.
- Pemesanan paket bundling (tiket, alat sewa, dan jasa Porter/Guide) dalam satu transaksi.
- Integrasi pembayaran (*pay-in*) menggunakan **Xendit Invoice/XenPlatform** melalui Virtual Account, QRIS, dan e-wallet.
- Sistem *ledger* dan *escrow* internal untuk pencatatan saldo Mitra.
- Proses pencairan dana (*payout/disbursement*) ke rekening Mitra melalui API Xendit.

### Komunikasi & Validasi Pendakian
- Fitur **In-App Chat** berbasis teks antara Pendaki dan Mitra/Guide.
- **Digital Logbook** untuk unggah *summit proof* dan konfirmasi penyelesaian pendakian.

### Monetisasi
- Manajemen slot banner iklan dasar oleh Admin.

---

## 7.2 Out of Scope (Rilis v1.1 atau Fase Berikutnya)

Fitur-fitur berikut **tidak** termasuk dalam ruang lingkup rilis v1.0 dan direncanakan untuk dikembangkan pada fase berikutnya.

### Autentikasi & Sosial
- Social Login menggunakan Google OAuth dan Facebook Login.
- Sistem rating dan ulasan (review) publik untuk Mitra, Porter, dan Guide.

### AI & Otomatisasi
- Sistem rekomendasi gunung atau jalur berbasis AI sesuai tingkat pengalaman dan kebugaran Pendaki.
- Integrasi informasi cuaca *real-time* menggunakan BMKG API pada halaman detail gunung.

### Keuangan Lanjutan
- Fitur **PayLater** atau cicilan.
- Dukungan pembayaran **multi-currency** selain Rupiah (IDR).
- Sistem klaim asuransi otomatis berbasis polis digital.

### Komunitas & Komunikasi
- Forum komunitas dan pencarian teman mendaki (*Open Trip / Find Teammate*).
- Fitur panggilan suara dan video (*Voice Call* dan *Video Call*) pada In-App Chat.

### Platform & Integrasi Hardware
- Aplikasi desktop native untuk Admin dan Mitra (versi 1.0 hanya menyediakan aplikasi web responsif).
- Integrasi dengan *Smart Gate* atau *Smart Barrier* menggunakan pemindaian QR Code pada pintu masuk basecamp.

---

# Architecture & Tech Stack

Section ini mendefinisikan arsitektur global, spesifikasi pustaka (*libraries*), UI komponen, dan *framework* resmi yang wajib digunakan oleh AI Developer dalam mengeksekusi proyek.

## 8.1 Specification Matrix

| Component Layer | Technology / Framework | Standard Specifications & Libraries |
| :--- | :--- | :--- |
| **Backend Service** | **Laravel (v13)** | • RESTful API Architecture (JSON Response Envelope Standard)<br>• Dokumentasi API: **L5-Swagger (OpenAPI)**<br>• Authentication: **Laravel Sanctum** (Bearer Tokens & HttpOnly Cookies)<br>• Database: **PostgreSQL**<br>• Storage: Disk Private (`backend/storage/app/kyc_documents`) & Disk Public (`backend/storage/app/public`) |
| **Web Frontend** *(Admin Panel & Dashboard Mitra)* | **Vue.js 3** | • Architecture: Composition API with `<script setup>`<br>• UI Component Library: **shadcn-vue** (Tailwind CSS based)<br>• State Management: **Pinia**<br>• Router: **Vue Router**<br>• Icons: **Lucide Vue Next** / Tailwind UI Icons |
| **Mobile Application** *(Pendaki App)* | **Quasar Framework (Vue 3)** | • Cross-Platform Engine: **Capacitor JS** (Build target: Android & iOS)<br>• Target UI Components: **Quasar Mobile UI Components** (`q-page`, `q-list`, `q-uploader`, `q-pull-to-refresh`, `q-dialog`, `q-chip`)<br>• State Management: **Pinia**<br>• Native Plugins: Capacitor Camera (`@capacitor/camera`), Capacitor Preferences (`@capacitor/preferences`), Capacitor Geolocation (`@capacitor/geolocation`) |

## 8.2 Client-Server Communication & Security Standard

1. **Web Frontend (Admin & Mitra) Communication**:
   - Web application mengirimkan request ke backend Laravel melalui Axios/Fetch.
   - Sesi autentikasi menggunakan HttpOnly Cookies atau Sanctum Bearer Token yang disimpan di State Pinia.
   - Header request wajib menyertakan `Accept: application/json`.

2. **Mobile Application (Quasar + Capacitor JS) Communication**:
   - Mobile App berkomunikasi dengan backend via HTTPS RESTful API.
   - **Token Storage**: Sanctum Bearer Token disimpan menggunakan **Capacitor Preferences Plugin** (`@capacitor/preferences`), bukan `localStorage` biasa, untuk menjamin keamanan pada perangkat native (iOS Keychain & Android EncryptedSharedPreferences).
   - **CORS Configuration**: Backend Laravel diatur untuk mengizinkan asal request dari origin Capacitor (`capacitor://localhost`, `http://localhost`, serta custom scheme app).

3. **Capacitor Native Hardware Integration**:
   - **KYC & Summit Proof Camera Upload**:
     Menggunakan `@capacitor/camera` untuk mengambil foto KTP/Paspor atau foto *Summit Proof*. Sebelum pengunggahan ke endpoint `/api/v1/kyc/submit` atau `/api/v1/logbook/summit-proof`, aplikasi mobile secara otomatis melakukan kompresi gambar (kualitas max 80%, resolusi max 1920x1080, format JPEG) untuk menghemat bandwidth.
   - **Geolocation Plugin**:
     Menggunakan `@capacitor/geolocation` untuk verifikasi koordinat lokasi saat *check-in* di basecamp atau pengunggahan logbook digital.

---

## 8.3 Repository Structure

Berikut adalah struktur direktori utama repositori Monorepo / Multi-folder proyek:

```text
summit-marketplace/             <-- Root Repository
 docs/                       <-- Dokumentasi PRD & Spesifikasi Teknis
    prd/
       00-MASTER-PRD.md
       modules/
          01-authentication-kyc.md
          02-masterdata-basecamp.md
          03-order-transaction-status.md
       technical-specs/
           api-rules.md
 backend/                    <-- Laravel REST API Service (Eksis: be-summit)
    app/
    config/
    database/
    routes/
 frontend/                   <-- Web Admin & Mitra (Vue.js + shadcn-vue) [Kosong]
    .gitkeep
 mobile/                     <-- Mobile App Pendaki (Quasar + Capacitor) [Kosong]
     .gitkeep
```