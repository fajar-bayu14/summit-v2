# TASK LIST & WBS: SISTEM QR SCAN & VERIFIKASI CHECK-IN BASECAMP

Dokumen ini memuat Work Breakdown Structure (WBS), spesifikasi arsitektur teknis, protokol keamanan, antarmuka komponen, dan kriteria penerimaan kualitas (*Quality Gates*) untuk implementasi **Sistem Pemindaian QR Code & Verifikasi Check-In Lapangan (End-to-End)** yang menghubungkan **Aplikasi Mobile Pendaki**, **Web Marketplace Pendaki**, **Web Dashboard Mitra**, dan **Backend API Laravel**.

---

## 📌 Ringkasan Masalah & Tujuan Fitur

### Kondisi Saat Ini (Current State):
1. **Dashboard Mitra (`frontend/`):** Modal `CheckInScannerModal.vue` saat ini hanya berupa form input teks biasa (`autofocus`). Mitra baru bisa memindai jika menghubungkan alat scanner fisik (USB/Bluetooth barcode scanner) atau mengetikkan kode invoice secara manual. Belum ada modul kamera video (*Webcam / Smartphone Camera Live Stream*) di peramban web.
2. **Aplikasi Mobile Pendaki (`mobile/`):** Modal pop-up E-Tiket di `orders.vue` masih menampilkan ikon ilustrasi statis/placeholder (`<q-icon name="qr_code_2" />`), belum me-render kode QR dinamis asli.
3. **Web Pendaki (`frontend/`):** Halaman `ETicketView.vue` menggunakan layanan pihak ketiga eksternal (`https://api.qrserver.com/...`) yang bergantung pada koneksi internet publik dan berisiko gagal muat jika koneksi lemah atau server pihak ketiga lambat.
4. **Backend API (`backend/`):** Endpoint `POST /api/v1/mitra/orders/check-in` mewajibkan parameter `invoice`, belum fleksibel menerima `kode_tiket` per item atau payload terstruktur hasil scan QR.

### Tujuan Sistem (Target State):
1. **Zero External Hardware:** Petugas basecamp dapat memindai tiket cukup dengan membuka kamera smartphone atau laptop via Web Dashboard Mitra.
2. **Offline-Ready E-Ticket:** Pendaki tetap dapat menampilkan kode QR di aplikasi mobile/web meskipun sedang berada di lereng gunung tanpa sinyal seluler (*flight mode* / *offline cache*).
3. **Dual-Input Flexibility:** Mendukung pemindaian langsung via kamera HP, pemindaian via barcode gun USB/Bluetooth, maupun pencarian manual nama/invoice.
4. **Safety & Manifest Validation:** Sebelum status berubah menjadi `on_going`, petugas pos disajikan daftar checklist identitas anggota rombongan untuk verifikasi fisik KTP/SIM.

---

## 🛠️ Stack & Komponen Terkait

| Komponen | Platform / Modul | File Rujukan Utama | Tanggung Jawab Teknis |
| :--- | :--- | :--- | :--- |
| **QR Generator (Mobile)** | Quasar (Vue 3 + Vite) | `mobile/src/pages/orders.vue` | Render QR Code dinamis berbasis invoice / token tiket, offline cache. |
| **QR Generator (Web)** | Vue 3 + Tailwind | `frontend/src/views/pendaki/ETicketView.vue` | Render SVG/Canvas QR lokal client-side tanpa API eksternal. |
| **Live Camera Scanner** | Vue 3 + shadcn-vue | `frontend/src/components/mitra/orders/CheckInScannerModal.vue` | Pemindai kamera WebRTC (`html5-qrcode`), selector kamera, audio beep. |
| **Check-In API Handler** | Laravel 13 API | `backend/app/Http/Controllers/Mitra/PesananController.php` | Validasi tiket, fleksibilitas input (`invoice` / `kode_tiket`), transisi `on_going`. |
| **Manifest Drawer** | Vue 3 + shadcn-vue | `frontend/src/components/mitra/orders/OrderDetailDrawer.vue` | Konfirmasi pencocokan KTP fisik anggota rombongan sebelum check-in. |

---

## 📈 Roadmap & Tahapan Pengerjaan

- [ ] **Fase 1: Dynamic Client-Side QR Generator (Mobile & Web Pendaki)** (0/3 Sub-task)
- [ ] **Fase 2: Live Camera Video Stream Scanner di Dashboard Mitra** (0/4 Sub-task)
- [ ] **Fase 3: Backend API Harmonization & Flex-Input Check-In** (0/3 Sub-task)
- [ ] **Fase 4: Offline Caching & Low-Signal Resiliency** (0/2 Sub-task)
- [ ] **Fase 5: SOP Safety Gate & Manifest Physical Checklist** (0/2 Sub-task)

**Status Keseluruhan:** `⚪ Planned (Ready for Implementation)`

---

## 📋 WORK BREAKDOWN STRUCTURE (WBS)

### 🔹 FASE 1: DYNAMIC CLIENT-SIDE QR GENERATOR (PENDAKI)
*Fokus: Memastikan tiket digital pendaki menghasilkan QR Code asli secara mandiri, beresolusi tinggi, dan tanpa ketergantungan API pihak ketiga.*

- [ ] **Task 1.1: Integrasi QR Generator di Aplikasi Mobile Pendaki (`mobile/`)**
  - Pasang dependensi generator QR client-side (misal: `qrcode` atau `qrcode.vue` versi Vue 3).
  - Modifikasi modal E-Tiket di `mobile/src/pages/orders.vue`:
    - Gantikan `<div class="qr-placeholder"><q-icon name="qr_code_2" /></div>` dengan komponen QR dinamis.
    - Nilai payload QR: Nomor invoice pesanan (contoh: `INV/20260909/0001`).
    - Atur opsi koreksi kesalahan (*Error Correction Level*) ke level **M (Medium - 15%)** agar tetap terbaca jika layar HP pendaki tergores/redup.
    - Tambahkan tombol pintasan *"Tingkatkan Kecerahan Layar"* / panduan scan saat modal QR aktif.

- [ ] **Task 1.2: Refactor QR Generator di Web E-Tiket Pendaki (`frontend/`)**
  - Modifikasi `frontend/src/views/pendaki/ETicketView.vue`:
    - Hapus pemanggilan URL eksternal `https://api.qrserver.com/v1/create-qr-code/...`.
    - Ganti dengan generator QR client-side lokal berbasis Canvas/SVG.
    - Pastikan QR code tetap tajam dan terbaca saat halaman tiket dicetak (*print view* / PDF).

- [ ] **Task 1.3: Formatisasi Data Payload QR Standar**
  - Standarisasi struktur payload teks di dalam QR Code:
    - Opsi Standar: Plain String Invoice (e.g. `INV/20260803/0001`).
    - Opsi Terstruktur (JSON String):
      ```json
      {
        "app": "SUMMIT",
        "inv": "INV/20260803/0001",
        "bc": 1,
        "date": "2026-08-15"
      }
      ```
    - Memastikan scanner dapat membaca baik teks polos invoice maupun format terstruktur secara cerdas.

---

### 🔹 FASE 2: LIVE CAMERA VIDEO STREAM SCANNER (DASHBOARD MITRA)
*Fokus: Memungkinkan petugas basecamp membuka kamera HP/laptop di browser untuk memindai tiket pendaki secara real-time.*

- [ ] **Task 2.1: Integrasi WebRTC Camera Scanner Library**
  - Pasang pustaka scanner kamera teruji di `frontend/`: `html5-qrcode`.
  - Pustaka ini mendukung deteksi kamera otomatis, decoding multi-format, flashlight/torch (pada smartphone yang mendukung), dan kamera belakang (*facingMode: environment*).

- [ ] **Task 2.2: Refactor `CheckInScannerModal.vue` dengan Tampilan Dual-Mode**
  - Modifikasi antarmuka modal scanner dengan tab navigasi:
    1. **Tab 1: Kamera Live Stream (Mode Utama Petugas):**
       - Area pratinjau video kamera dengan viewfinder persegi (*bounding box* scan visual).
       - Dropdown selector pemilihan perangkat kamera (jika perangkat memiliki >1 kamera: Kamera Utama, Wide, atau Front).
       - Tombol hidupkan/matikan lampu kilat (*Torch/Flashlight*) untuk kondisi pos basecamp yang minim penerangan di malam hari.
    2. **Tab 2: Manual & Barcode Gun (Mode Kasir / PC Meja):**
       - Kolom input nomor invoice dengan auto-focus untuk perangkat barcode scanner fisik.
  - Implementasi *lifecycle hook* pembersihan kamera (`scanner.stop()`) saat modal ditutup agar kamera tidak terus menyala di latar belakang.

- [ ] **Task 2.3: Audio & Haptic Feedback saat Pemindaian Berhasil**
  - Tambahkan efek suara (*beep sound*) menggunakan Web Audio API sintesis lokal (tanpa perlu load file MP3 eksternal).
  - Tambahkan getaran (*haptic feedback*) via `navigator.vibrate([100])` untuk perangkat mobile petugas saat QR terdeteksi.

- [ ] **Task 2.4: State Auto Check-In & Error Recovery**
  - Setelah QR berhasil didecode:
    - Bunyikan beep & getar.
    - Hentikan pemindaian sementara (*pause decoding*) untuk mencegah trigger berulang (*debounce/throttle*).
    - Ambil data pesanan melalui `mitraOrdersApi.getOrders({ search: code })`.
    - Jika opsi *"Otomatis Check-In"* aktif dan pesanan valid `paid`, langsung panggil `mitraOrdersApi.checkInOrder(order.id)`.
    - Tampilkan banner sukses dengan tombol *"Scan Tiket Berikutnya"* untuk meresume kamera seketika.

---

### 🔹 FASE 3: BACKEND API HARMONIZATION & FLEX-INPUT
*Fokus: Menjamin endpoint check-in backend siap menerima identitas tiket secara fleksibel dan aman.*

- [ ] **Task 3.1: Fleksibilitas Identifikasi di `checkInByCode` (`Mitra/PesananController.php`)**
  - Modifikasi method `checkInByCode` pada [backend/app/Http/Controllers/Mitra/PesananController.php]:
    - Terima input payload fleksibel: field `code` atau `invoice` atau `kode_tiket`.
    - Logika pencarian:
      1. Cek kecocokan nomor `invoice` pada tabel `pesanans`.
      2. Jika tidak ditemukan, cek kecocokan `kode_tiket` pada tabel `detail_pesanans`.
  - Pastikan pesan validasi spesifik:
    - 404: *"Tiket atau pesanan tidak ditemukan di pangkalan data."*
    - 403: *"Pesanan ini terdaftar untuk basecamp lain. Anda tidak memiliki otoritas."*
    - 422: *"Tiket belum lunas (status: pending/cancelled). Check-in ditolak."*
    - 422: *"Rombongan pendaki ini sudah melakukan check-in sebelumnya."*

- [ ] **Task 3.2: Pencatatan Audit Check-In Operator**
  - Tambahkan informasi pencatatan pada database/record pesanan:
    - Petugas yang memproses verifikasi (`checked_in_by` / ID user mitra aktif).
    - Metode verifikasi yang digunakan (e.g. `'qr_camera'`, `'barcode_scanner'`, atau `'manual_search'`).

- [ ] **Task 3.3: Pembaruan Pest Feature Tests**
  - Tambahkan unit/feature test di [backend/tests/Feature/Mitra/PesananManagementTest.php]:
    - Test check-in berhasil menggunakan format nomor invoice.
    - Test check-in berhasil menggunakan format kode tiket (`kode_tiket`).
    - Test penolakan check-in jika kode QR tidak dikenali.
    - Test pembatasan akses antar-basecamp.

---

### 🔹 FASE 4: OFFLINE CACHING & LOW-SIGNAL RESILIENCY
*Fokus: Mengantisipasi kendala sinyal seluler di lereng gunung.*

- [ ] **Task 4.1: Offline Local Caching E-Tiket di Mobile Pendaki**
  - Di aplikasi mobile pendaki (`mobile/`), saat pesanan berstatus `paid` pertama kali dimuat saat ada internet:
    - Simpan representasi data tiket & gambar SVG QR code ke dalam penyimpanan lokal peranti (`localStorage` / `@capacitor/preferences`).
    - Jika pendaki membuka tab tiket saat tidak ada koneksi data, aplikasi otomatis menampilkan data tiket dari cache lokal bertanda *"Offline Ready"*.

- [ ] **Task 4.2: Penanganan Timeout Request di Dashboard Mitra**
  - Jika internet di pos basecamp mengalami *intermittent lag* saat tombol konfirmasi check-in ditekan:
    - Berikan feedback loading yang jelas.
    - Timeout batas maksimal 10 detik.
    - Jika gagal, sediakan tombol *"Coba Lagi"* tanpa mereset data rombongan yang sudah terpindai.

---

### 🔹 FASE 5: SOP SAFETY GATE & MANIFEST PHYSICAL CHECKLIST
*Fokus: Menghubungkan proses pemindaian digital dengan SOP pemeriksaan fisik lapangan.*

- [ ] **Task 5.1: Modal Konfirmasi Manifes Anggota Sebelum Check-In**
  - Saat kode QR dipindai dan opsi *"Auto Check-In"* dimatikan (mode default SOP):
    - Tampilkan ringkasan visual kartu rombongan:
      - Nama Ketua & Nomor Kontak Darurat.
      - Daftar Nama seluruh anggota beserta nomor NIK/KTP.
      - Daftar perlengkapan keselamatan / alat rental yang disewa.
    - Terdapat checklist: *"Petugas telah memverifikasi identitas fisik seluruh anggota rombongan"*.
    - Tombol konfirmasi check-in baru dapat diklik setelah petugas meninjau manifes.

- [ ] **Task 5.2: Aktivasi Otomatis Status Perlengkapan Rental**
  - Pastikan setelah rombongan ter-check-in, daftar alat sewa pada pesanan tersebut berpindah status dari `ready` menjadi `active`, memicu penghitungan inventaris sewa di pos secara akurat.

---

## 🛡️ Kriteria Penerimaan & Verifikasi Kualitas (Acceptance Gates)

| Gate ID | Kriteria Penerimaan | Metode Pengujian | Status |
| :--- | :--- | :--- | :---: |
| **G-QR-01** | E-Tiket di Mobile Quasar menampilkan QR Code tajam & dinamis (bukan icon placeholder). | Uji visual di emulator/device mobile. | ⚪ Pending |
| **G-QR-02** | E-Tiket di Mobile dapat ditampilkan dalam keadaan perangkat Mode Pesawat (*Offline*). | Matikan koneksi, buka dialog tiket. | ⚪ Pending |
| **G-QR-03** | E-Tiket Web Pendaki menghasilkan QR SVG client-side tanpa memanggil `api.qrserver.com`. | Inspeksi Network Tab pada browser. | ⚪ Pending |
| **G-QR-04** | Web Dashboard Mitra dapat meminta izin kamera dan menampilkan live stream video. | Uji kamera pada Chrome Desktop & Mobile Safari/Chrome. | ⚪ Pending |
| **G-QR-05** | Kamera mitra sukses membaca QR code dari layar ponsel pendaki dalam waktu < 2 detik. | Uji scan fisik layar-ke-kamera. | ⚪ Pending |
| **G-QR-06** | Pemindaian memicu audio beep dan update status transaksi menjadi `on_going`. | Verifikasi respon API & feedback UI. | ⚪ Pending |
| **G-QR-07** | Endpoint backend menolak check-in jika tiket belum lunas atau milik basecamp lain. | Pest Feature Test (`PesananManagementTest`). | ⚪ Pending |
| **G-QR-08** | Input manual & barcode scanner eksternal tetap bekerja berdampingan dengan kamera. | Uji input form scanner modal. | ⚪ Pending |

---

## 📌 Catatan Pelaksanaan & Etika Pengerjaan
1. Rencana ini telah disiapkan untuk dieksekusi secara bertahap dimulai dari **Fase 1** (E-Tiket Pendaki) lalu **Fase 2** (Scanner Mitra) dan **Fase 3** (Backend).
2. Sesuai instruksi pengguna, **tidak ada perubahan kode yang dieksekusi** pada sesi perencanaan ini. Eksekusi teknis akan dimulai pada sesi berikutnya setelah rencana disetujui.
