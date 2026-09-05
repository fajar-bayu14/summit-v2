# TASK LIST & WBS: FRONTEND WEB PORTAL ROLE MITRA

Dokumen ini memuat Work Breakdown Structure (WBS), spesifikasi antarmuka komponen, kontrak TypeScript, dan sistem verifikasi kualitas bertingkat (*Quality Checklist & Gate System*) untuk implementasi **Frontend Web Portal Role Mitra (Pengelola Basecamp / Operator Lokal)** pada platform **Summit v2** (`frontend/`).

---

## 🛠️ Stack & Standar Arsitektur Frontend
- **Framework & Core**: Vue 3 (Composition API, `<script setup>`), TypeScript Strict Mode, Vite
- **UI Components & Styling**: Tailwind CSS, shadcn-vue (Radix Vue primitives), Lucide Icons
- **State Management & Caching**: Pinia, VueUse (`useFetch` / Axios + TanStack Query Adapter)
- **Table & Data Grid**: `@tanstack/vue-table` (Server-side pagination, multi-sort, dynamic filters)
- **Routing & Auth Guard**: Vue Router (Navigation guards with role verification `role === 'mitra'` & token expiration checks)
- **API Target**: `http://localhost:8000/api/v1`

---

## 📈 Ringkasan Progres Implementasi Frontend Mitra

- [ ] **Modul 00: Foundation, Auth Session, Basecamp Context & Global Layout** (0/3 Task Selesai — **0%**)
- [ ] **Modul 01: Dashboard Overview & Operasional Harian Basecamp** (0/3 Task Selesai — **0%**)
- [ ] **Modul 02: Manajemen Katalog Produk & Inventaris Rental** (0/4 Task Selesai — **0%**)
- [ ] **Modul 03: Kalender & Manajemen Kuota Harian Tiket** (0/3 Task Selesai — **0%**)
- [ ] **Modul 04: Manajemen Pesanan Masuk, Check-in & Status Item** (0/4 Task Selesai — **0%**)
- [ ] **Modul 05: Validasi Digital Logbook & Verifikasi Bukti Summit** (0/3 Task Selesai — **0%**)
- [ ] **Modul 06: Dompet Mitra, Mutasi Escrow & Penarikan Dana (Payout)** (0/4 Task Selesai — **0%**)
- [ ] **Modul 07: Manajemen Pengajuan Refund & Pembatalan (Tier-1 Review)** (0/3 Task Selesai — **0%**)
- [ ] **Modul 08: Manajemen Staf Operasional (Guide & Porter)** (0/3 Task Selesai — **0%**)
- [ ] **Modul 09: In-App Chat & Komunikasi Langsung Pendaki** (0/3 Task Selesai — **0%**)
- [ ] **Modul 10: Pengaturan Profil Mitra & Informasi Rekening Bank** (0/2 Task Selesai — **0%**)

**Total Progres Frontend Mitra**: **0 / 35 Task Selesai (0%)**

---

## 🧱 [MODUL 00: FOUNDATION, AUTH SESSION, BASECAMP CONTEXT & GLOBAL LAYOUT]
Status Modul: `[ ] Planned` | `[ ] Module Verification Passed`

Fondasi arsitektur frontend web mitra, state otentikasi role mitra, basecamp context selector (jika mitra mengelola >1 basecamp), layout dashboard responsif, dan error handling global.

#### Task Breakdown:
- [ ] **Task 0.1: Mitra HTTP Client & Session Interceptor**
  - [ ] Implementasi Kode Selesai:
    - Inisialisasi Axios client dengan baseURL `/api/v1` & `withCredentials: true`
    - Request interceptor: Injeksi `Authorization: Bearer <token>`
    - Response interceptor: Normalisasi response envelope `{status, message, data, meta}`
    - Error interceptor: Auto logout & redirect on `401 Unauthorized` / forbidden role `403`
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `POST /api/v1/auth/login` & `GET /api/v1/profile`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 0.2: Pinia Mitra Auth & Active Basecamp Store**
  - [ ] Implementasi Kode Selesai:
    - `useMitraAuthStore`: state `user`, `mitra`, `basecamps`, `activeBasecampId`
    - Getter untuk active basecamp info (nama basecamp, gunung, jalur)
    - Vue Router Guard: Proteksi seluruh rute mitra (`user.role === 'mitra'`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Session persistence & basecamp switching) Passed
  - Status: `[PLANNED]`

- [ ] **Task 0.3: Mitra Dashboard Shell & Navigation Layout**
  - [ ] Implementasi Kode Selesai:
    - Sidebar Navigasi Mitra: Dashboard, Katalog Produk, Kuota Tiket, Pesanan & Check-in, Logbook Summit, Dompet & Payout, Refund, Staf, In-App Chat, Pengaturan Profil
    - Topbar Header: Active Basecamp Dropdown Switcher, Quick Emergency Trail Closure Action, Badge Notifikasi Pesanan Baru, User Profile Menu
    - Komponen UI Reusable: `MetricCard`, `DataTable`, `FilterBar`, `PaginationBar`, `ConfirmModal`, `EmptyState`, `StatusBadge`
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Responsive layout & active menu rendering) Passed
  - Status: `[PLANNED]`

#### Module-Level Acceptance Gate:
- [ ] Sesi login mitra terproteksi, token otomatis terinjeksi di seluruh request
- [ ] Basecamp switcher tersinkronisasi ke filter global
- [ ] Penanganan global error menampilkan feedback toast/alert yang jelas

---

## 📊 [MODUL 01: DASHBOARD OVERVIEW & OPERASIONAL HARIAN BASECAMP]
Status Modul: `[ ] Planned` | `[ ] Module Verification Passed`

Tampilan ringkasan metrik performa utama, status kuota harian saat ini, daftar pendaki tiba hari ini, dan tombol darurat penutupan jalur.

#### Task Breakdown:
- [ ] **Task 1.1: Dashboard Summary Service & TypeScript Types**
  - [ ] Implementasi Kode Selesai:
    - Interface: `DashboardSummaryMetrics`, `DailyClimberArrival`, `EmergencyClosurePayload`
    - Service `dashboardService.ts` (`fetchSummaryMetrics()`, `emergencyCloseTrail(jalurId, payload)`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/orders` & `GET /api/v1/mitra/wallet`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 1.2: Operational KPI Metric Cards & Real-time Statistics**
  - [ ] Implementasi Kode Selesai:
    - Card 1: Total Pendapatan Bersih & Saldo Siap Tarik (dari `wallet.summary`)
    - Card 2: Jumlah Transaksi Paid / Aktif
    - Card 3: Sisa Kuota Pendakian Hari Ini
    - Card 4: Antrean Verifikasi Summit Proof & Refund Pending
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Card rendering with live API payload) Passed
  - Status: `[PLANNED]`

- [ ] **Task 1.3: Quick Action Emergency Trail Closure Banner & Modal**
  - [ ] Implementasi Kode Selesai:
    - Status Banner Jalur (Open / Closed)
    - Modal Dialog Konfirmasi Buka/Tutup Darurat Jalur dengan form `alasan_penutupan` (misal: Badai, Pohon Tumbang, Erupsi)
    - Toast feedback & immediate status sync ke dashboard
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `POST /api/v1/mitra/trails/{id}/emergency-close`) Passed
  - Status: `[PLANNED]`

#### Module-Level Acceptance Gate:
- [ ] Ringkasan statistik terisi akurat dari endpoint backend
- [ ] Aksi penutupan jalur darurat berfungsi dan terproteksi konfirmasi ganda

---

## 📦 [MODUL 02: MANAJEMEN KATALOG PRODUK & INVENTARIS RENTAL]
Status Modul: `[ ] Planned` | `[ ] Module Verification Passed`

Pengelolaan seluruh produk tiket, sewa alat rental (tenda, matras, nesting), jasa porter/guide, paket open trip, dan logistik/kuliner.

#### Task Breakdown:
- [ ] **Task 2.1: Product API Service & Types Definition**
  - [ ] Implementasi Kode Selesai:
    - Interface: `Produk`, `ProdukFilterParams`, `StoreProductPayload`, `UpdateStockPayload`
    - Service `productService.ts` (`getProducts(params)`, `getProductById(id)`, `createProduct(payload)`, `updateProduct(id, payload)`, `toggleProductStatus(id, isActive)`, `updateStock(id, stock)`, `deleteProduct(id)`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/products`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 2.2: Product Data Table with Category Tabs & Stock Inline Edit**
  - [ ] Implementasi Kode Selesai:
    - Tab Filter Kategori Cepat: `Semua`, `Tiket`, `Sewa Alat (Rental)`, `Open Trip`, `Guide / Porter`, `Logistik`
    - Kolom Tabel: Foto/Ikon, Nama Produk, Kategori, Harga Satuan, Sisa Stok Fisik, Status Aktif (Switch), Aksi
    - Quick Action: Inline update stok alat sewa (Direct increment/decrement & modal input)
    - Quick Action: Switch toggle active/inactive seketika
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `PATCH /api/v1/mitra/products/{id}/toggle-status` & `PATCH /api/v1/mitra/products/{id}/stock`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 2.3: Form Modal Create / Edit Produk (Multi-Category Conditional Fields)**
  - [ ] Implementasi Kode Selesai:
    - Dynamic Form Input berdasarkan kategori:
      - Jika Kategori `ticket`: Input `jalur_id`, `jam_buka`, `jam_tutup`
      - Jika Kategori `opentrip`: Input `tanggal_berangkat`, `tanggal_pulang`, `meeting_point`, `minimal_peserta`, `maksimal_peserta`
      - Jika Kategori `rental`/`merchandise`: Input `stok`, `satuan`
    - Form validation (Zod / VeeValidate) dengan handling error 422 Laravel
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `POST /api/v1/mitra/products` & `PUT /api/v1/mitra/products/{id}`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 2.4: Delete Product Confirmation & Error Protection Handler**
  - [ ] Implementasi Kode Selesai:
    - Dialog konfirmasi hapus produk
    - Handling proteksi jika produk pernah memiliki riwayat transaksi pesanan
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `DELETE /api/v1/mitra/products/{id}`) Passed
  - Status: `[PLANNED]`

#### Module-Level Acceptance Gate:
- [ ] CRUD produk untuk seluruh kategori (ticket, rental, opentrip, guide, porter, logistik) berjalan mulus
- [ ] Inline stock update & toggle status langsung tersimpan tanpa full page reload

---

## 📅 [MODUL 03: KALENDER & MANAJEMEN KUOTA HARIAN TIKET]
Status Modul: `[ ] Planned` | `[ ] Module Verification Passed`

Visualisasi kalender ketersediaan kuota pendakian harian serta fitur batch setup kuota untuk rentang tanggal tertentu.

#### Task Breakdown:
- [ ] **Task 3.1: Quota API Service Layer & Types**
  - [ ] Implementasi Kode Selesai:
    - Interface: `KuotaHarian`, `BatchQuotaPayload`, `UpdateSingleQuotaPayload`
    - Service `quotaService.ts` (`getQuotas(productId, startDate, endDate)`, `batchSetQuotas(productId, payload)`, `updateQuota(quotaId, payload)`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/products/{id}/quotas`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 3.2: Interactive Monthly Quota Calendar View**
  - [ ] Implementasi Kode Selesai:
    - Kalender visual bulanan dengan navigasi Bulan/Tahun
    - Setiap sel tanggal menampilkan: `Kuota Total`, `Kuota Terpakai / Terpesan`, `Sisa Kuota`
    - Color indicator: Hijau (>50% sisa), Kuning (<50% sisa), Merah (Habis / 0), Abu-abu (Lewat/Tutup)
    - Klik tanggal untuk membuka Quick Edit Modal kuota hari tersebut
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Calendar navigation & event rendering) Passed
  - Status: `[PLANNED]`

- [ ] **Task 3.3: Batch Quota Setup Modal Dialog**
  - [ ] Implementasi Kode Selesai:
    - Form pengaturan kuota massal: `Rentang Tanggal Mulai & Selesai` (Date Range Picker), `Batas Kuota Total per Hari`
    - Tombol simpan dengan preview jumlah hari yang akan diperbarui
    - Optimistic update atau re-fetching data kalender otomatis
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `POST /api/v1/mitra/products/{id}/quotas/batch`) Passed
  - Status: `[PLANNED]`

#### Module-Level Acceptance Gate:
- [ ] Kalender kuota menampilkan kuota total vs kuota terpakai secara akurat
- [ ] Set kuota massal berhasil diterapkan pada seluruh tanggal yang dipilih

---

## 📋 [MODUL 04: MANAJEMEN PESANAN MASUK, CHECK-IN & STATUS ITEM]
Status Modul: `[ ] Planned` | `[ ] Module Verification Passed`

Pemantauan pesanan booking tiket/layanan dari pendaki, pencarian invoice, verifikasi identitas anggota rombongan, dan check-in operasional.

#### Task Breakdown:
- [ ] **Task 4.1: Mitra Order Service & Contract Types**
  - [ ] Implementasi Kode Selesai:
    - Interface: `MitraPesanan`, `DetailPesananItem`, `PesananAnggota`, `PesananFilterParams`
    - Service `mitraOrderService.ts` (`getOrders(params)`, `getOrderById(id)`, `updateItemOperationalStatus(pesananId, itemId, status)`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/orders`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 4.2: Incoming Orders Data Table with Advanced Filters**
  - [ ] Implementasi Kode Selesai:
    - Filter Bar: Status (`Semua`, `Paid`, `Pending`, `Cancelled`, `Refunded`), Tanggal Pendakian, Search (Invoice, Nama Pemesan, Nama Anggota)
    - Kolom Data: No Invoice, Tanggal Naik, Nama Pemesan & Kontak, Jumlah Rombongan, Total Bayar, Status Pembayaran, Aksi
    - Ringkasan header: Total Pendapatan Bersih & Total Transaksi Paid
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/orders?status=paid`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 4.3: Order Detail Drawer / Modal (Climber Manifest & Items Checklist)**
  - [ ] Implementasi Kode Selesai:
    - Rincian Informasi Pemesan & Status Pembayaran
    - Tabel Manifest Anggota Rombongan: Nama Lengkap, NIK, No Telepon Darurat, Jenis Kelamin
    - Daftar Item Pesanan (Tiket, Tenda, Guide, dsb.) dengan pemilih status operasional:
      - `pending` -> `ready` (Barang/guide siap) -> `active` (Sedang dipakai mendaki) -> `completed` (Alat kembali) -> `cancelled`
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/orders/{id}` & `PATCH /api/v1/mitra/orders/{pesananId}/items/{itemId}`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 4.4: Quick Check-in Scanner & Invoice Lookup Dialog**
  - [ ] Implementasi Kode Selesai:
    - Input pencarian cepat nomor invoice / scan QR code E-Ticket pendaki
    - Verifikasi seketika manifest rombongan dan tombol satu klik "Tandai Check-In / Semua Siap"
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Lookup order and update items) Passed
  - Status: `[PLANNED]`

#### Module-Level Acceptance Gate:
- [ ] Mitra dapat melihat manifest seluruh anggota rombongan dengan detail
- [ ] Status operasional setiap item (alat sewa, porter, tiket) dapat diperbarui secara terpisah

---

## 🏔️ [MODUL 05: VALIDASI DIGITAL LOGBOOK & VERIFIKASI BUKTI SUMMIT]
Status Modul: `[ ] Planned` | `[ ] Module Verification Passed`

Peninjauan foto bukti puncak (*summit proof*) yang diunggah pendaki, konfirmasi turun gunung, penerbitan sertifikat digital, dan pelepasan dana escrow.

#### Task Breakdown:
- [ ] **Task 5.1: Logbook Validation Service & Types**
  - [ ] Implementasi Kode Selesai:
    - Interface: `LogbookEntry`, `VerifyLogbookPayload`, `LogbookFilterParams`
    - Service `logbookService.ts` (`getLogbooks(params)`, `verifyLogbook(id, payload)`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/logbooks`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 5.2: Summit Proof Verification Grid / Table**
  - [ ] Implementasi Kode Selesai:
    - Tab Filter: `Menunggu Validasi (Pending)`, `Disetujui (Approved)`, `Ditolak (Rejected)`
    - Tampilan Card / Table: Foto Thumbnail Summit, Nama Pendaki, Gunung/Jalur, Tanggal Pendakian, Status Badge, Aksi Verifikasi
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/logbooks?status_validasi=pending`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 5.3: Summit Photo Modal Viewer & Approval Dialog**
  - [ ] Implementasi Kode Selesai:
    - Modal visualizer foto summit (Zoom, Rotate, Fullscreen preview)
    - Aksi **Setujui (Approve)**: Munculkan notifikasi konfirmasi bahwa pesanan akan berstatus `completed` dan dana escrow akan otomatis masuk ke `saldo_available`
    - Aksi **Tolak (Reject)**: Form input wajib `catatan_petugas` alasan foto tidak sah
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `POST /api/v1/mitra/logbooks/{id}/verify`) Passed
  - Status: `[PLANNED]`

#### Module-Level Acceptance Gate:
- [ ] Petugas dapat meninjau foto summit dengan jelas
- [ ] Persetujuan summit berhasil memperbarui status pesanan menjadi `completed` dan dana masuk ke saldo aktif

---

## 💳 [MODUL 06: DOMPET MITRA, MUTASI ESCROW & PENARIKAN DANA (PAYOUT)]
Status Modul: `[ ] Planned` | `[ ] Module Verification Passed`

Monitoring saldo virtual pendaki (escrow pending vs saldo siap cair), riwayat ledger transaksi, serta pengajuan pencairan dana ke rekening bank via Xendit.

#### Task Breakdown:
- [ ] **Task 6.1: Wallet & Withdrawal Service Layer**
  - [ ] Implementasi Kode Selesai:
    - Interface: `MitraWallet`, `WalletTransaction`, `WithdrawalRequest`, `StoreWithdrawalPayload`
    - Service `walletService.ts` (`getWalletSummary()`, `getLedgerTransactions(params)`, `requestWithdrawal(payload)`, `getWithdrawalHistory(params)`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/wallet`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 6.2: Wallet Overview Balance Cards & Account Info**
  - [ ] Implementasi Kode Selesai:
    - Card Saldo Siap Ditarik (`saldo_available`) dengan tombol CTA "Tarik Dana"
    - Card Saldo Pending Escrow (`saldo_pending`) dengan tooltip penjelasan (dana pesanan on-going)
    - Card Total Dana Telah Dicairkan (`total_penarikan`)
    - Ringkasan nomor rekening tujuan pencairan bank terdaftar
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Wallet card live rendering) Passed
  - Status: `[PLANNED]`

- [ ] **Task 6.3: Ledger Mutation Data Table with Transaction Types**
  - [ ] Implementasi Kode Selesai:
    - Filter tipe transaksi: `Semua`, `Escrow Masuk`, `Escrow Release`, `Penarikan (Withdrawal)`, `Refund Deduction`
    - Kolom: Tanggal & Waktu, Invoice Terkait, Tipe Mutasi, Nominal (+ / -), Saldo Akhir, Catatan
    - Pagination & server-side sorting
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/wallet/ledger`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 6.4: Withdrawal Request Modal & Timeline Status Tracker**
  - [ ] Implementasi Kode Selesai:
    - Form Input Nominal Penarikan (dengan validasi `nominal <= saldo_available` & minimal Rp 50.000)
    - Input catatan penarikan
    - Tabel riwayat penarikan dana dengan status badge (`pending`, `approved`, `processing`, `completed`, `rejected`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `POST /api/v1/mitra/withdrawals` & `GET /api/v1/mitra/withdrawals`) Passed
  - Status: `[PLANNED]`

#### Module-Level Acceptance Gate:
- [ ] Perhitungan saldo pending dan saldo available tampil transparan
- [ ] Validasi nominal penarikan dana mencegah penarikan melebihi saldo aktif

---

## 🔄 [MODUL 07: MANAJEMEN PENGAJUAN REFUND & PEMBATALAN (TIER-1 REVIEW)]
Status Modul: `[ ] Planned` | `[ ] Module Verification Passed`

Peninjauan permohonan pembatalan tiket pendakian oleh mitra sebelum diteruskan/diproses oleh sistem atau diajukan dispute.

#### Task Breakdown:
- [ ] **Task 7.1: Mitra Refund Service Layer & Types**
  - [ ] Implementasi Kode Selesai:
    - Interface: `RefundItem`, `MitraRejectRefundPayload`
    - Service `mitraRefundService.ts` (`getRefunds(params)`, `getRefundById(id)`, `approveRefund(id)`, `rejectRefund(id, payload)`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/refunds`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 7.2: Refund Request Data Table & Status Filtering**
  - [ ] Implementasi Kode Selesai:
    - Tab filter: `Menunggu Review (Pending)`, `Disetujui Mitra`, `Ditolak Mitra`, `Dispute/Banding`
    - Kolom Tabel: ID Refund, No Invoice, Nama Pendaki, Tanggal Pengajuan, Alasan Pembatalan, Nominal Refund, Status, Aksi
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/refunds?status=pending`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 7.3: Refund Review Modal & Approve / Reject Action Handler**
  - [ ] Implementasi Kode Selesai:
    - Modal detail pengajuan refund (Rincian pesanan, tanggal pendakian, catatan pendaki)
    - Action **Setujui Refund**: Konfirmasi pemotongan saldo pending escrow secara otomatis
    - Action **Tolak Refund**: Form input wajib `alasan_penolakan` (misal: "Pembatalan melewati batas waktu SOP H-1")
    - Refresh data list seketika tanpa reload
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `POST /api/v1/mitra/refunds/{id}/approve` & `POST /api/v1/mitra/refunds/{id}/reject`) Passed
  - Status: `[PLANNED]`

#### Module-Level Acceptance Gate:
- [ ] Mitra dapat menyetujui atau menolak permohonan refund dengan alasan yang valid
- [ ] Persetujuan refund otomatis menyesuaikan saldo escrow pending di dompet mitra

---

## 👥 [MODUL 08: MANAJEMEN STAF OPERASIONAL (GUIDE & PORTER)]
Status Modul: `[ ] Planned` | `[ ] Module Verification Passed`

Pengelolaan database petugas lapangan, pemandu gunung (guide), dan porter lokal yang bekerja di bawah naungan basecamp mitra.

#### Task Breakdown:
- [ ] **Task 8.1: Staff Service Layer & Contract Types**
  - [ ] Implementasi Kode Selesai:
    - Interface: `MitraStaff`, `StoreStaffPayload`, `UpdateStaffPayload`, `StaffFilterParams`
    - Service `staffService.ts` (`getStaffList(params)`, `getStaffById(id)`, `createStaff(payload)`, `updateStaff(id, payload)`, `deleteStaff(id)`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/staff`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 8.2: Staff Directory Data Table & Availability Filter**
  - [ ] Implementasi Kode Selesai:
    - Filter Role: `Semua`, `Guide`, `Porter`, `Petugas Basecamp`
    - Kolom: Nama Lengkap, Peran/Role Badge, Nomor Telepon/WhatsApp, Basecamp, Status Ketersediaan (`Tersedia` / `Bertugas`), Jadwal Tugas, Aksi
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/mitra/staff?role=guide`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 8.3: Add & Edit Staff Modal Form**
  - [ ] Implementasi Kode Selesai:
    - Form Input: Nama Lengkap, Role (`guide`, `porter`, `petugas`), No Telepon, Basecamp Penugasan, Status Ketersediaan (Switch), Jadwal Tugas
    - Validasi nomor telepon Indonesia
    - Delete staff confirmation dialog
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `POST /api/v1/mitra/staff` & `PUT /api/v1/mitra/staff/{id}`) Passed
  - Status: `[PLANNED]`

#### Module-Level Acceptance Gate:
- [ ] CRUD data staf guide & porter berfungsi normal
- [ ] Ketersediaan staf dapat diubah untuk pengaturan jadwal penugasan pendakian

---

## 💬 [MODUL 09: IN-APP CHAT & KOMUNIKASI LANGSUNG PENDAKI]
Status Modul: `[ ] Planned` | `[ ] Module Verification Passed`

Fitur chat langsung dengan pendaki untuk koordinasi perlengkapan, titik kumpul (meeting point), info cuaca terkini, atau konfirmasi sewa alat.

#### Task Breakdown:
- [ ] **Task 9.1: Chat Service Layer & Message Types**
  - [ ] Implementasi Kode Selesai:
    - Interface: `ChatRoom`, `ChatMessage`, `SendMessagePayload`
    - Service `chatService.ts` (`getRooms()`, `getMessages(roomId)`, `sendMessage(roomId, payload)`, `markAsRead(roomId)`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/chat/rooms`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 9.2: Split-View Chat Interface (Sidebar Rooms & Active Conversation)**
  - [ ] Implementasi Kode Selesai:
    - Left Pane: Daftar room obrolan dengan foto profil pendaki, nama pemesan, cuplikan pesan terakhir, unread message badge
    - Right Pane: Header detail pendaki & invoice, stream pesan chat (bubble pesan mitra vs pendaki, timestamp)
    - Input chat bar dengan emoji support & trigger tombol enter
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Message list rendering & auto-scroll to bottom) Passed
  - Status: `[PLANNED]`

- [ ] **Task 9.3: Real-Time Polling / WebSocket Listener & Read Status**
  - [ ] Implementasi Kode Selesai:
    - Polling teratur (atau Reverb/Pusher integration) untuk pesan baru
    - Auto mark as read saat room dibuka
    - Sound / toast notification untuk pesan baru yang masuk
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `POST /api/v1/chat/rooms/{room_id}/messages` & `read`) Passed
  - Status: `[PLANNED]`

#### Module-Level Acceptance Gate:
- [ ] Komunikasi teks antara mitra dan pendaki terkirim dan terbaca dua arah secara real-time
- [ ] Unread message counter berkurang seketika saat room dibuka

---

## ⚙️ [MODUL 10: PENGATURAN PROFIL MITRA & INFORMASI REKENING BANK]
Status Modul: `[ ] Planned` | `[ ] Module Verification Passed`

Pengelolaan data profil pengelola basecamp, kontak darurat, informasi operasional basecamp, dan rekening bank untuk pencairan dana.

#### Task Breakdown:
- [ ] **Task 10.1: Profile & Bank Configuration Service**
  - [ ] Implementasi Kode Selesai:
    - Interface: `MitraProfile`, `UpdatePasswordPayload`
    - Service `profileService.ts` (`getProfile()`, `updatePassword(payload)`)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `GET /api/v1/profile`) Passed
  - Status: `[PLANNED]`

- [ ] **Task 10.2: Mitra Settings & Security Page**
  - [ ] Implementasi Kode Selesai:
    - Tab 1: Profil Usaha & Basecamp (Nama Pemilik, NIK, NPWP, Alamat, No Telepon, Jam Operasional)
    - Tab 2: Informasi Rekening Pencairan (Nama Bank, Nomor Rekening, Nama Pemilik Rekening, E-Wallet)
    - Tab 3: Keamanan Akun (Form Ganti Kata Sandi dengan validasi konfirmasi password baru)
  - [ ] Unit/Component Test Passed
  - [ ] Integration Test (Hit `PUT /api/v1/profile/password`) Passed
  - Status: `[PLANNED]`

#### Module-Level Acceptance Gate:
- [ ] Profil mitra dan data rekening bank tampil sesuai data registrasi
- [ ] Ubah kata sandi berjalan aman dengan proteksi kata sandi lama