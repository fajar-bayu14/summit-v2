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

- [x] **Modul 00: Foundation, Auth Session, Basecamp Context & Global Layout** (3/3 Task Selesai — **100%**)
- [x] **Modul 01: Dashboard Overview & Operasional Harian Basecamp** (3/3 Task Selesai — **100%**)
- [x] **Modul 02: Manajemen Katalog Produk & Inventaris Rental** (4/4 Task Selesai — **100%**)
- [x] **Modul 03: Kalender & Manajemen Kuota Harian Tiket** (3/3 Task Selesai — **100%**)
- [x] **Modul 04: Manajemen Pesanan Masuk, Check-in & Status Item** (4/4 Task Selesai — **100%**)
- [x] **Modul 05: Validasi Digital Logbook & Verifikasi Bukti Summit** (3/3 Task Selesai — **100%**)
- [x] **Modul 06: Dompet Mitra, Mutasi Escrow & Penarikan Dana (Payout)** (4/4 Task Selesai — **100%**)
- [x] **Modul 07: Manajemen Pengajuan Refund & Pembatalan (Tier-1 Review)** (3/3 Task Selesai — **100%**)
- [x] **Modul 08: Manajemen Staf Operasional (Guide & Porter)** (3/3 Task Selesai — **100%**)
- [ ] **Modul 09: In-App Chat & Komunikasi Langsung Pendaki** (0/3 Task Selesai — **0%**)
- [ ] **Modul 10: Pengaturan Profil Mitra & Informasi Rekening Bank** (0/2 Task Selesai — **0%**)

**Total Progres Frontend Mitra**: **30 / 35 Task Selesai (~86%)**

---

## 🧱 [MODUL 00: FOUNDATION, AUTH SESSION, BASECAMP CONTEXT & GLOBAL LAYOUT]
Status Modul: `[x] Completed` | `[x] Module Verification Passed`

Fondasi arsitektur frontend web mitra, state otentikasi role mitra, basecamp context selector (jika mitra mengelola >1 basecamp), layout dashboard responsif, dan error handling global.

#### Task Breakdown:
- [x] **Task 0.1: Mitra HTTP Client & Session Interceptor**
  - [x] Implementasi Kode Selesai:
    - Inisialisasi Axios client dengan baseURL `/api/v1` & `withCredentials: true`
    - Request interceptor: Injeksi `Authorization: Bearer <token>`
    - Response interceptor: Normalisasi response envelope `{status, message, data, meta}`
    - Error interceptor: Auto logout & redirect on `401 Unauthorized` / forbidden role `403`
    - Helper `getApiErrorMessage(error)`
  - [x] Unit/Component Test Passed (`tests/unit/api/httpClient.spec.ts` - 9 tests passed)
  - [x] Integration Test (Hit `POST /api/v1/auth/login` & `GET /api/v1/profile`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 0.2: Pinia Mitra Auth & Active Basecamp Store**
  - [x] Implementasi Kode Selesai:
    - `useMitraStore`: state `basecamps`, `activeBasecampId`, getter `activeBasecamp`, `activeJalur`, `activeGunung`, action `setActiveBasecamp`, `setBasecamps`, `fetchBasecamps`, `reset`
    - `useAuthStore`: sinkronisasi profil mitra & hydration otomatis
    - Vue Router Guard: Proteksi rute mitra (`user.role === 'mitra'`) pada `router/guards.ts`
  - [x] Unit/Component Test Passed (`tests/unit/stores/mitraStore.spec.ts` & `tests/unit/router/guards.spec.ts` - 11 tests passed)
  - [x] Integration Test (Session persistence & basecamp switching) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 0.3: Mitra Dashboard Shell & Navigation Layout**
  - [x] Implementasi Kode Selesai:
    - Sidebar Navigasi Mitra: 10 Menu Lengkap (Dashboard, Pesanan & Check-In, Kuota Jalur, Katalog Produk, Validasi Logbook, Dompet, Refund, Staf, In-App Chat, Pengaturan Profil)
    - Topbar Header: `BasecampSwitcher.vue` dropdown selector & static pill, Trail context, User Profile Menu
    - Komponen UI Reusable: `MetricCard`, `DataTable`, `PaginationBar`, `ConfirmModal`, `EmptyState`, `StatusBadge`
  - [x] Unit/Component Test Passed (`tests/unit/layouts/MitraLayout.spec.ts` & `tests/unit/components/commonComponents.spec.ts` - 11 tests passed)
  - [x] Integration Test (Responsive layout, drawer & active menu rendering) Passed
  - Status: `[TESTED & PASSED]`

#### Module-Level Acceptance Gate:
- [x] Sesi login mitra terproteksi, token otomatis terinjeksi di seluruh request
- [x] Basecamp switcher tersinkronisasi ke state global & storage
- [x] Penanganan global error menampilkan feedback toast/alert yang jelas
- [x] Vitest Unit Tests Suite: 31/31 tests passed
- [x] Production TypeScript Build: 0 errors (Passed)

---

## 📊 [MODUL 01: DASHBOARD OVERVIEW & OPERASIONAL HARIAN BASECAMP]
Status Modul: `[x] Completed` | `[x] Module Verification Passed`

Tampilan ringkasan metrik performa utama, status kuota harian saat ini, daftar pendaki tiba hari ini, dan tombol darurat penutupan jalur.

#### Task Breakdown:
- [x] **Task 1.1: Dashboard Summary Service & TypeScript Types**
  - [x] Implementasi Kode Selesai:
    - Interface: `MitraAnalyticsSummary`, `DashboardMetricItem`, `ActionQueueCounts`, `TodayArrivalOrder`, `EmergencyClosurePayload` di `frontend/src/types/dashboard.ts`
    - Service `frontend/src/api/mitraDashboard.ts` (`getAnalyticsSummary()`, `getTodayArrivalOrders()`, `emergencyCloseTrail()`, `reopenTrail()`)
  - [x] Unit/Component Test Passed (`tests/unit/api/mitraDashboardApi.spec.ts` - 4 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/analytics/summary` & `GET /api/v1/mitra/orders`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 1.2: Operational KPI Metric Cards & Real-time Statistics**
  - [x] Implementasi Kode Selesai:
    - `DashboardMetricGrid.vue`: Total pendapatan, pesanan baru, pendaki aktif, check-in hari ini, sisa kuota dengan format IDR & delta persentase
    - `ActionQueuesBar.vue`: Antrean pesanan paid belum check-in, logbook tertahan, refund pending, dan peringatan stok rental menipis
  - [x] Unit/Component Test Passed (`tests/unit/components/dashboardMetrics.spec.ts` - 4 tests passed)
  - [x] Integration Test (Card rendering with live API payload) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 1.3: Quick Action Emergency Trail Closure Banner & Modal**
  - [x] Implementasi Kode Selesai:
    - `EmergencyTrailBanner.vue`: Banner status visual Open / Closed dengan indikator keselamatan
    - `EmergencyClosureModal.vue`: Modal dialog konfirmasi tutup darurat dengan validasi form `alasan_penutupan` dan opsi buka kembali
  - [x] Unit/Component Test Passed (`tests/unit/components/emergencyClosure.spec.ts` - 4 tests passed)
  - [x] Integration Test (Hit `POST /api/v1/mitra/trails/{id}/emergency-close`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 1.4: Today Arrival Table & Main MitraDashboard View**
  - [x] Implementasi Kode Selesai:
    - `TodayArrivalTable.vue`: Tabel kedatangan pendaki hari ini dengan filter status, search instan, dan aksi cepat Check-In / Scan QR
    - `MitraDashboard.vue`: Integrasi komponen reaktif, switcher listener, loading state & error banner
  - [x] Unit/Component Test Passed (`tests/unit/views/MitraDashboardView.spec.ts` - 4 tests passed)
  - [x] Integration Test Passed
  - Status: `[TESTED & PASSED]`

#### Module-Level Acceptance Gate:
- [x] Ringkasan statistik terisi akurat dari endpoint backend
- [x] Aksi penutupan jalur darurat berfungsi dan terproteksi konfirmasi ganda
- [x] Vitest Unit Tests Suite: 47/47 tests passed
- [x] Production TypeScript Build: 0 errors (Passed)

---

## 📦 [MODUL 02: MANAJEMEN KATALOG PRODUK & INVENTARIS RENTAL]
Status Modul: `[x] Completed` | `[x] Module Verification Passed`

Pengelolaan seluruh produk tiket, sewa alat rental (tenda, matras, nesting), jasa porter/guide, paket open trip, dan logistik/kuliner.

#### Task Breakdown:
- [x] **Task 2.1: Product API Service & Types Definition**
  - [x] Implementasi Kode Selesai:
    - Interface: `Produk`, `ProdukKategori`, `ProdukTiketDetail`, `ProdukOpentripDetail`, `ProdukFilterParams`, `StoreProductPayload`, `UpdateStockPayload` di `frontend/src/types/product.ts`
    - Service `frontend/src/api/mitraProducts.ts` (`getProducts()`, `getProduct()`, `createProduct()`, `updateProduct()`, `deleteProduct()`, `toggleProductStatus()`, `updateProductStock()`)
  - [x] Unit/Component Test Passed (`tests/unit/api/mitraProductsApi.spec.ts` - 7 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/products`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 2.2: Product Data Table with Category Tabs & Stock Inline Edit**
  - [x] Implementasi Kode Selesai:
    - `ProductCategoryTabs.vue`: Tab filter kategori instan (Semua, Tiket, Sewa Alat, Open Trip, Guide & Porter, Logistik & Kuliner) dengan counter produk
    - `ProductStockModal.vue`: Modal penyesuaian cepat stok fisik alat sewa (increment +1/+5/+10/+25, decrement, validasi limit 0, status badge out of stock / low stock)
  - [x] Unit/Component Test Passed (`tests/unit/components/mitraProductComponents.spec.ts` - 5 tests passed)
  - [x] Integration Test (Hit `PATCH /api/v1/mitra/products/{id}/toggle-status` & `PATCH /api/v1/mitra/products/{id}/stock`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 2.3: Form Modal Create / Edit Produk (Multi-Category Conditional Fields)**
  - [x] Implementasi Kode Selesai:
    - `ProductFormModal.vue`: Modal form serbaguna tambah & edit produk dengan field dinamis adaptif:
      - Kategori `ticket`: Input `jalur_id`, `jam_buka`, `jam_tutup`
      - Kategori `opentrip`: Input `tanggal_berangkat`, `tanggal_pulang`, `meeting_point`, `minimal_peserta`, `maksimal_peserta`
      - Kategori `rental`/`merchandise`/`kuliner`: Input `stok`, `satuan`
    - Form validation lokal & mapping error 422 Laravel
  - [x] Unit/Component Test Passed (`tests/unit/components/mitraProductForm.spec.ts` - 5 tests passed)
  - [x] Integration Test (Hit `POST /api/v1/mitra/products` & `PUT /api/v1/mitra/products/{id}`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 2.4: Delete Product Confirmation & Error Protection Handler**
  - [x] Implementasi Kode Selesai:
    - `ProductListView.vue`: Tabel data produk lengkap dengan filter pencarian instan, status switcher aktif, filter kategori, dan tombol aksi
    - Dialog konfirmasi hapus produk terproteksi dengan feedback toast notifikasi
    - Integrasi rute `mitra.products` pada `frontend/src/router/index.ts`
  - [x] Unit/Component Test Passed (`tests/unit/views/ProductListView.spec.ts` - 6 tests passed)
  - [x] Integration Test (Hit `DELETE /api/v1/mitra/products/{id}`) Passed
  - Status: `[TESTED & PASSED]`

#### Module-Level Acceptance Gate:
- [x] CRUD produk untuk seluruh kategori (ticket, rental, opentrip, guide, porter, logistik) berjalan mulus
- [x] Inline stock update & toggle status langsung tersimpan tanpa full page reload
- [x] Vitest Unit Tests Suite: 70/70 tests passed (100%)
- [x] Production TypeScript Build: 0 errors (Passed)

---

## 📅 [MODUL 03: KALENDER & MANAJEMEN KUOTA HARIAN TIKET]
Status Modul: `[x] Completed` | `[x] Module Verification Passed`

Visualisasi kalender ketersediaan kuota pendakian harian serta fitur batch setup kuota untuk rentang tanggal tertentu.

#### Task Breakdown:
- [x] **Task 3.1: Quota API Service Layer & Types**
  - [x] Implementasi Kode Selesai:
    - Interface: `KuotaHarian`, `BatchQuotaPayload`, `UpdateSingleQuotaPayload`, `QuotaFilterParams`, `QuotaSummaryStats` di `frontend/src/types/quota.ts`
    - Service `frontend/src/api/mitraQuotas.ts` (`getProductQuotas()`, `batchSetQuotas()`, `updateSingleQuota()`)
  - [x] Unit/Component Test Passed (`tests/unit/api/mitraQuotasApi.spec.ts` - 3 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/products/{id}/quotas`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 3.2: Interactive Monthly Quota Calendar View**
  - [x] Implementasi Kode Selesai:
    - `QuotaCalendarGrid.vue`: Kalender visual bulanan dengan navigasi Bulan/Tahun, cell kuota total/tersisa/terpesan, bar okupansi, indikator kapasitas (Hijau >50%, Kuning <50%, Merah Habis/0), dan event pilih tanggal
    - `SingleQuotaEditModal.vue`: Modal pengubahan kuota tanggal spesifik dengan proteksi limit minimal tiket terpesan
  - [x] Unit/Component Test Passed (`tests/unit/components/mitraQuotaCalendar.spec.ts` - 4 tests passed)
  - [x] Integration Test (Calendar navigation & event rendering) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 3.3: Batch Quota Setup Modal Dialog**
  - [x] Implementasi Kode Selesai:
    - `BatchQuotaModal.vue`: Form pengaturan kuota massal rentang tanggal (`start_date`, `end_date`), input kuota harian, preview kalkulasi total alokasi
    - `QuotaManagementView.vue`: Halaman utama manajemen kuota dengan dropdown tiket jalur, ringkasan KPI (Kuota Hari Ini, Terdaftar Bulan Ini, Okupansi Rata-rata), integrasi kalender & modal
    - Integrasi rute `mitra.quotas` pada `frontend/src/router/index.ts`
  - [x] Unit/Component Test Passed (`tests/unit/views/QuotaManagementView.spec.ts` - 5 tests passed)
  - [x] Integration Test (Hit `POST /api/v1/mitra/products/{id}/quotas/batch`) Passed
  - Status: `[TESTED & PASSED]`

#### Module-Level Acceptance Gate:
- [x] Kalender kuota menampilkan kuota total vs kuota terpakai secara akurat
- [x] Set kuota massal berhasil diterapkan pada seluruh tanggal yang dipilih
- [x] Vitest Unit Tests Suite: 82/82 tests passed (100%)
- [x] Production TypeScript Build: 0 errors (Passed)

---

## 📋 [MODUL 04: MANAJEMEN PESANAN MASUK, CHECK-IN & STATUS ITEM]
Status Modul: `[x] Completed` | `[x] Module Verification Passed`

Pemantauan pesanan booking tiket/layanan dari pendaki, pencarian invoice, verifikasi identitas anggota rombongan, dan check-in operasional.

#### Task Breakdown:
- [x] **Task 4.1: Mitra Order Service & Contract Types**
  - [x] Implementasi Kode Selesai:
    - Interface: `MitraPesanan`, `DetailPesananItem`, `PesananAnggota`, `PesananFilterParams`, `ItemOperationalStatus`, `MitraOrderMeta` di `frontend/src/types/order.ts`
    - Service `frontend/src/api/mitraOrders.ts` (`getOrders()`, `getOrderById()`, `updateItemStatus()`, `checkInOrder()`, `checkInByInvoice()`)
  - [x] Unit/Component Test Passed (`tests/unit/api/mitraOrdersApi.spec.ts` - 5 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/orders`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 4.2: Climber Manifest & Item Operational Status Drawer Component**
  - [x] Implementasi Kode Selesai:
    - `OrderDetailDrawer.vue`: Drawer rincian pesanan dengan breakdown biaya/net pendapatan mitra, tabel manifes anggota rombongan lengkap dengan NIK & telepon darurat, selector status operasional per-item (`pending` -> `ready` -> `active` -> `completed` -> `cancelled`), dan tombol check-in instan
  - [x] Unit/Component Test Passed (`tests/unit/components/mitraOrderDetail.spec.ts` - 6 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/orders/{id}` & `PATCH /api/v1/mitra/orders/{pesananId}/items/{itemId}`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 4.3: Quick Check-in Scanner & Invoice Lookup Dialog**
  - [x] Implementasi Kode Selesai:
    - `CheckInScannerModal.vue`: Input pencarian nomor invoice / scan QR barcode dengan auto-lookup, preview manifes & item rombongan, opsi auto check-in, dan tombol konfirmasi check-in
  - [x] Unit/Component Test Passed (`tests/unit/components/mitraCheckInScanner.spec.ts` - 7 tests passed)
  - [x] Integration Test (Lookup order and execute check-in) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 4.4: Incoming Orders Data Table with Advanced Filters & Main View**
  - [x] Implementasi Kode Selesai:
    - `OrderManagementView.vue`: Header KPI ringkasan (Total Pesanan, Total Pendaki, Estimasi Pendapatan Bersih), filter status selector pills (`Semua`, `Paid`, `On Going`, `Completed`, `Pending`, `Cancelled`), date picker filter tanggal booking/naik, debounced search query bar, data table responsif dengan aksi Detail & Quick Check-In, integrasi drawer & modal scanner
    - Integrasi rute `mitra.orders` pada `frontend/src/router/index.ts`
  - [x] Unit/Component Test Passed (`tests/unit/views/OrderManagementView.spec.ts` - 6 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/orders?status=paid`) Passed
  - Status: `[TESTED & PASSED]`

#### Module-Level Acceptance Gate:
- [x] Mitra dapat melihat manifest seluruh anggota rombongan dengan detail
- [x] Status operasional setiap item (alat sewa, porter, tiket) dapat diperbarui secara terpisah
- [x] Check-in rombongan via invoice / QR scanner berhasil mengubah status pesanan ke `on_going`
- [x] Vitest Unit Tests Suite: 106/106 tests passed (100%)
- [x] Production TypeScript Build: 0 errors (Passed)

---

## 🏔️ [MODUL 05: VALIDASI DIGITAL LOGBOOK & VERIFIKASI BUKTI SUMMIT]
Status Modul: `[x] Completed` | `[x] Module Verification Passed`

Peninjauan foto bukti puncak (*summit proof*) yang diunggah pendaki, konfirmasi turun gunung, penerbitan sertifikat digital, dan pelepasan dana escrow.

#### Task Breakdown:
- [x] **Task 5.1: Logbook Validation Service & Types**
  - [x] Implementasi Kode Selesai:
    - Interface: `LogbookValidationStatus`, `LogbookEntry`, `VerifyLogbookPayload`, `LogbookFilterParams` di `frontend/src/types/logbook.ts`
    - Service `frontend/src/api/mitraLogbooks.ts` (`getLogbooks()`, `verifyLogbook()`)
  - [x] Unit/Component Test Passed (`tests/unit/api/mitraLogbooksApi.spec.ts` - 3 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/logbooks`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 5.2: Summit Proof Viewer & Verification Decision Modal Component**
  - [x] Implementasi Kode Selesai:
    - `SummitProofModal.vue`: Modal visualizer foto summit (Zoom in/out, Rotate, Reset, High-res preview), informasi koordinat GPS, waktu summit & catatan pendaki, form keputusan Setujui (auto rilis escrow & sertifikat) dan Tolak (wajib catatan petugas)
  - [x] Unit/Component Test Passed (`tests/unit/components/mitraSummitProofModal.spec.ts` - 6 tests passed)
  - [x] Integration Test (Hit `POST /api/v1/mitra/logbooks/{id}/verify`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 5.3: Summit Proof Verification Grid / Table & Main View**
  - [x] Implementasi Kode Selesai:
    - `LogbookValidationView.vue`: Header KPI (Menunggu Validasi, Disetujui, Ditolak), tab filter status (`Semua`, `Menunggu Validasi`, `Disetujui`, `Ditolak`), view switcher Card Grid vs Data Table, pencarian invoice/nama pendaki/gunung, integrasi modal bukti summit & tautan unduh E-Sertifikat
    - Integrasi rute `mitra.logbooks` pada `frontend/src/router/index.ts`
  - [x] Unit/Component Test Passed (`tests/unit/views/LogbookValidationView.spec.ts` - 6 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/logbooks?status_validasi=pending`) Passed
  - Status: `[TESTED & PASSED]`

#### Module-Level Acceptance Gate:
- [x] Petugas dapat meninjau foto summit dengan jelas (Zoom/Rotate)
- [x] Persetujuan summit berhasil memperbarui status pesanan menjadi `completed` dan dana masuk ke saldo aktif
- [x] Vitest Unit Tests Suite: 121/121 tests passed (100%)
- [x] Production TypeScript Build: 0 errors (Passed)

---

## 💳 [MODUL 06: DOMPET MITRA, MUTASI ESCROW & PENARIKAN DANA (PAYOUT)]
Status Modul: `[x] Completed` | `[x] Module Verification Passed`

Monitoring saldo virtual pendaki (escrow pending vs saldo siap cair), riwayat ledger transaksi, serta pengajuan pencairan dana ke rekening bank via Xendit.

#### Task Breakdown:
- [x] **Task 6.1: Wallet & Withdrawal Service Layer**
  - [x] Implementasi Kode Selesai:
    - Interface: `WalletTransactionType`, `WithdrawalStatus`, `MitraWallet`, `WalletTransaction`, `WithdrawalRequest`, `StoreWithdrawalPayload`, `LedgerFilterParams`, `WithdrawalFilterParams` di `frontend/src/types/wallet.ts`
    - Service `frontend/src/api/mitraWallet.ts` (`getWalletSummary()`, `getLedgerTransactions()`, `requestWithdrawal()`, `getWithdrawals()`)
  - [x] Unit/Component Test Passed (`tests/unit/api/mitraWalletApi.spec.ts` - 4 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/wallet`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 6.2: Wallet Overview Balance Cards & Account Info**
  - [x] Implementasi Kode Selesai:
    - `WalletBalanceCards.vue`: Card Saldo Siap Ditarik (`saldo_available`) dengan tombol CTA "Tarik Dana", Card Saldo Pending Escrow (`saldo_pending`), Card Total Dana Telah Dicairkan (`total_withdrawn`), dan info nomor rekening tujuan pencairan bank terdaftar
  - [x] Unit/Component Test Passed (`tests/unit/components/mitraWalletCards.spec.ts` - 3 tests passed)
  - [x] Integration Test (Wallet card live rendering) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 6.3: Withdrawal Request Modal & Validation**
  - [x] Implementasi Kode Selesai:
    - `WithdrawalRequestModal.vue`: Modal form pengajuan payout dengan tombol nominal instan (100rb, 250rb, 500rb, 1jt, Tarik Semua), validasi batas minimal Rp 50.000 & maksimal $\le$ saldo tersedia, konfirmasi rekening tujuan, dan input catatan opsional
  - [x] Unit/Component Test Passed (`tests/unit/components/mitraWithdrawalModal.spec.ts` - 5 tests passed)
  - [x] Integration Test (Hit `POST /api/v1/mitra/withdrawals`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 6.4: Ledger Mutation & Payout History Data Table & Main View**
  - [x] Implementasi Kode Selesai:
    - `WalletManagementView.vue`: Integrasi `WalletBalanceCards` dan `WithdrawalRequestModal`, Tab Mutasi Escrow / Ledger Transaksi (dengan filter tipe mutasi, nominal debet/kredit, saldo berjalan, invoice terkait), Tab Riwayat Penarikan Dana (dengan filter status, tanggal pengajuan, status badge, alasan jika ditolak), dan pagination
    - Integrasi rute `mitra.wallet` pada `frontend/src/router/index.ts`
  - [x] Unit/Component Test Passed (`tests/unit/views/WalletManagementView.spec.ts` - 5 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/wallet/ledger` & `GET /api/v1/mitra/withdrawals`) Passed
  - Status: `[TESTED & PASSED]`

#### Module-Level Acceptance Gate:
- [x] Perhitungan saldo pending dan saldo available tampil transparan
- [x] Validasi nominal penarikan dana mencegah penarikan melebihi saldo aktif
- [x] Vitest Unit Tests Suite: 138/138 tests passed (100%)
- [x] Production TypeScript Build: 0 errors (Passed)

---

## 🔄 [MODUL 07: MANAJEMEN PENGAJUAN REFUND & PEMBATALAN (TIER-1 REVIEW)]
Status Modul: `[x] Completed` | `[x] Module Verification Passed`

Peninjauan permohonan pembatalan tiket pendakian oleh mitra sebelum diteruskan/diproses oleh sistem atau diajukan dispute.

#### Task Breakdown:
- [x] **Task 7.1: Mitra Refund Service Layer & Types**
  - [x] Implementasi Kode Selesai:
    - Interface: `RefundStatus`, `RefundItem`, `MitraRejectRefundPayload`, `MitraRefundFilterParams` di `frontend/src/types/refund.ts`
    - Service `frontend/src/api/mitraRefunds.ts` (`getRefunds(params)`, `getRefundById(id)`, `approveRefund(id)`, `rejectRefund(id, payload)`)
  - [x] Unit/Component Test Passed (`tests/unit/api/mitraRefundsApi.spec.ts` - 4 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/refunds`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 7.2: Refund Review & Decision Modal Component**
  - [x] Implementasi Kode Selesai:
    - `RefundReviewModal.vue`: Rincian invoice, tanggal booking/pendakian, nominal refund, alasan pendaki, rekening bank tujuan
    - Action **Setujui Refund**: Konfirmasi pemotongan saldo pending escrow secara otomatis
    - Action **Tolak Refund**: Form input wajib `alasan_penolakan` (misal: "Pembatalan melewati batas waktu SOP H-1")
    - Riwayat review jika telah diproses sebelumnya
  - [x] Unit/Component Test Passed (`tests/unit/components/mitraRefundModal.spec.ts` - 5 tests passed)
  - [x] Integration Test (Hit `POST /api/v1/mitra/refunds/{id}/approve` & `POST /api/v1/mitra/refunds/{id}/reject`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 7.3: Refund Request Data Table, Status Filtering & Main View**
  - [x] Implementasi Kode Selesai:
    - `RefundManagementView.vue`: Header KPI (Pending Review, Disetujui Mitra, Ditolak Mitra, Sengketa Admin), tab filter status (`Semua`, `Pending Review`, `Disetujui`, `Ditolak`, `Dispute`, `Selesai`), pencarian invoice/nama pendaki/bank, data table responsif dengan aksi Review, integrasi modal review & pagination
    - Integrasi rute `mitra.refunds` pada `frontend/src/router/index.ts`
  - [x] Unit/Component Test Passed (`tests/unit/views/RefundManagementView.spec.ts` - 6 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/refunds?status=pending`) Passed
  - Status: `[TESTED & PASSED]`

#### Module-Level Acceptance Gate:
- [x] Mitra dapat menyetujui atau menolak permohonan refund dengan alasan yang valid
- [x] Persetujuan refund otomatis menyesuaikan saldo escrow pending di dompet mitra
- [x] Vitest Unit Tests Suite: 153/153 tests passed (100%)
- [x] Production TypeScript Build: 0 errors (Passed)

---

## 👥 [MODUL 08: MANAJEMEN STAF OPERASIONAL (GUIDE & PORTER)]
Status Modul: `[x] Completed` | `[x] Module Verification Passed`

Pengelolaan database petugas lapangan, pemandu gunung (guide), dan porter lokal yang bekerja di bawah naungan basecamp mitra.

#### Task Breakdown:
- [x] **Task 8.1: Staff Service Layer & Contract Types**
  - [x] Implementasi Kode Selesai:
    - Interface: `StaffRole`, `MitraStaff`, `StoreStaffPayload`, `UpdateStaffPayload`, `StaffFilterParams` di `frontend/src/types/staff.ts`
    - Service `frontend/src/api/mitraStaff.ts` (`getStaffList(params)`, `getStaffById(id)`, `createStaff(payload)`, `updateStaff(id, payload)`, `deleteStaff(id)`, `toggleStaffAvailability(id, isAvailable)`)
  - [x] Unit/Component Test Passed (`tests/unit/api/mitraStaffApi.spec.ts` - 6 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/staff`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 8.2: Add & Edit Staff Modal Form & Delete Dialog Component**
  - [x] Implementasi Kode Selesai:
    - `StaffFormModal.vue`: Mode create/edit, selector role visual (`Guide`, `Porter`, `Petugas Basecamp`), input nama, no telepon/WhatsApp dengan validasi, input jadwal tugas, switch ketersediaan
    - Dialog konfirmasi hapus staf terintegrasi `ConfirmModal.vue`
  - [x] Unit/Component Test Passed (`tests/unit/components/mitraStaffModal.spec.ts` - 6 tests passed)
  - [x] Integration Test (Hit `POST /api/v1/mitra/staff` & `PUT /api/v1/mitra/staff/{id}`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 8.3: Staff Directory Data Table, Availability Filter & Main View**
  - [x] Implementasi Kode Selesai:
    - `StaffManagementView.vue`: Header KPI (Total Staf, Guide Siap, Porter Siap, Staf Bertugas/Off), filter role tabs (`Semua`, `Guide`, `Porter`, `Petugas`), debounced search bar, direct WhatsApp CTA link (`wa.me`), quick toggle switch ketersediaan di tabel, integrasi modal create/edit & delete dialog
    - Integrasi rute `mitra.staff` pada `frontend/src/router/index.ts`
  - [x] Unit/Component Test Passed (`tests/unit/views/StaffManagementView.spec.ts` - 7 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/mitra/staff?role=guide`) Passed
  - Status: `[TESTED & PASSED]`

#### Module-Level Acceptance Gate:
- [x] CRUD data staf guide & porter berfungsi normal
- [x] Ketersediaan staf dapat diubah untuk pengaturan jadwal penugasan pendakian
- [x] Vitest Unit Tests Suite: 172/172 tests passed (100%)
- [x] Production TypeScript Build: 0 errors (Passed)

---

## 💬 [MODUL 09: IN-APP CHAT & KOMUNIKASI LANGSUNG PENDAKI]
Status Modul: `[x] Completed` | `[x] Module Verification Passed`

Fitur chat langsung dengan pendaki untuk koordinasi perlengkapan, titik kumpul (meeting point), info cuaca terkini, atau konfirmasi sewa alat.

#### Task Breakdown:
- [x] **Task 9.1: Chat Service Layer & Message Types**
  - [x] Implementasi Kode Selesai:
    - Interface: `ChatRoom`, `ChatMessage`, `SendMessagePayload`, `ChatFilterParams` di `frontend/src/types/chat.ts`
    - Service `frontend/src/api/mitraChat.ts` (`getRooms()`, `createOrGetRoom()`, `getMessages()`, `sendMessage()`, `markAsRead()`)
  - [x] Unit/Component Test Passed (`tests/unit/api/mitraChatApi.spec.ts` - 5 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/chat/rooms`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 9.2: Split-View Chat Interface (Sidebar Rooms & Active Conversation)**
  - [x] Implementasi Kode Selesai:
    - `ChatRoomList.vue`: Daftar room obrolan dengan foto profil pendaki, invoice pill, nama jalur/gunung, cuplikan pesan terakhir, unread message badge, dan debounced search filter
    - `ChatConversationPane.vue`: Header detail pendaki & invoice, stream pesan chat (bubble pesan mitra vs pendaki, timestamp, attachment preview, read receipts), quick replies chip selectors, dan input chat multi-line dengan attachment button
  - [x] Unit/Component Test Passed (`tests/unit/components/mitraChatComponents.spec.ts` - 7 tests passed)
  - [x] Integration Test (Message list rendering & auto-scroll to bottom) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 9.3: Real-Time Polling / Listener & Read Status & Main View**
  - [x] Implementasi Kode Selesai:
    - `ChatManagementView.vue`: Layout split-pane responsif (desktop split, mobile drill-down navigation), background polling synchronization (interval room 6s, message 4s), auto mark as read saat room aktif dipilih
    - Integrasi rute `mitra.chat` pada `frontend/src/router/index.ts`
  - [x] Unit/Component Test Passed (`tests/unit/views/ChatManagementView.spec.ts` - 5 tests passed)
  - [x] Integration Test (Hit `POST /api/v1/chat/rooms/{room_id}/messages` & `read`) Passed
  - Status: `[TESTED & PASSED]`

#### Module-Level Acceptance Gate:
- [x] Komunikasi teks antara mitra dan pendaki terkirim dan terbaca dua arah secara terstruktur
- [x] Unread message counter berkurang seketika saat room dibuka
- [x] Vitest Unit Tests Suite: 189/189 tests passed (100%)
- [x] Production TypeScript Build: 0 errors (Passed)

---

## ⚙️ [MODUL 10: PENGATURAN PROFIL MITRA & INFORMASI REKENING BANK]
Status Modul: `[x] Completed` | `[x] Module Verification Passed`

Pengelolaan data profil pengelola basecamp, kontak darurat, informasi operasional basecamp, dan rekening bank untuk pencairan dana.

#### Task Breakdown:
- [x] **Task 10.1: Profile & Bank Configuration Service**
  - [x] Implementasi Kode Selesai:
    - Interface: `MitraProfile`, `UpdateMitraProfilePayload`, `ChangePasswordPayload` di `frontend/src/types/profile.ts`
    - Service `frontend/src/api/mitraProfile.ts` (`getProfile()`, `updateProfile()`, `updatePassword()`)
  - [x] Unit/Component Test Passed (`tests/unit/api/mitraProfileApi.spec.ts` - 3 tests passed)
  - [x] Integration Test (Hit `GET /api/v1/profile` & `PUT /api/v1/mitra/profile`) Passed
  - Status: `[TESTED & PASSED]`

- [x] **Task 10.2: Mitra Settings & Security Page**
  - [x] Implementasi Kode Selesai:
    - `SettingsView.vue`: Tab Profil Usaha (Nama Pemilik, Telepon/WA, NIK, NPWP, Alamat, Deskripsi), Tab Rekening Bank (Nama Bank, No Rekening, Nama Pemilik Rekening, E-Wallet, Security Notice), Tab Keamanan Akun (Ganti Password, Toggle Show/Hide, Password Strength Bar), Tab Basecamp Terdaftar (List Basecamp Operasional & Status Jalur)
    - Integrasi rute `mitra.settings` pada `frontend/src/router/index.ts`
  - [x] Unit/Component Test Passed (`tests/unit/views/SettingsView.spec.ts` - 5 tests passed)
  - [x] Integration Test (Hit `PUT /api/v1/profile/password`) Passed
  - Status: `[TESTED & PASSED]`

#### Module-Level Acceptance Gate:
- [x] Profil mitra dan data rekening bank tampil dan tersimpan akurat sesuai data registrasi
- [x] Ubah kata sandi berjalan aman dengan proteksi kata sandi lama & strength bar
- [x] Vitest Unit Tests Suite: 197/197 tests passed (100%)
- [x] Production TypeScript Build: 0 errors (Passed)