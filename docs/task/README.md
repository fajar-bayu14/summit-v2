# Summit v2 — Task Master Index

Dokumen ini merupakan indeks utama pelacakan tugas (*task tracking & roadmap*) untuk seluruh peran pada platform **Summit v2**. Setiap file di dalam direktori `docs/task/` memuat daftar tugas teknis, spesifikasi API/UI, serta checklist status pengerjaan.

---

## 📂 Struktur Dokumen Task

```text
docs/task/
├── README.md                      <-- Indeks Utama & Status Global
├── 01-admin-endpoints.md          <-- Task Backend & API Role Admin (Aktif)
├── 02-mitra-endpoints.md          <-- Task Backend & API Role Mitra / Basecamp (Rencana)
├── 03-pendaki-endpoints.md        <-- Task Backend & API Role Pendaki / Mobile (Rencana)
├── 04-admin-frontend.md           <-- Task Web Dashboard Frontend Admin (Rencana)
└── 05-mitra-frontend.md           <-- Task Web Dashboard Frontend Mitra (Rencana)
```

---

## 📊 Matriks Status Task per Role & Platform

| ID | Modul / Role | Target Platform | File Rujukan | Status Progres |
| :--- | :--- | :--- | :--- | :---: |
| **TSK-ADM-BE** | **Admin Backend & API** | Laravel 13 API (`backend/`) | [`01-admin-endpoints.md`](01-admin-endpoints.md) | 🟡 **75% Selesai** |
| **TSK-MIT-BE** | **Mitra Backend & API** | Laravel 13 API (`backend/`) | `02-mitra-endpoints.md` | ⚪ *Planned* |
| **TSK-PND-BE** | **Pendaki Backend & API** | Laravel 13 API (`backend/`) | `03-pendaki-endpoints.md` | ⚪ *Planned* |
| **TSK-ADM-FE** | **Admin Web Dashboard** | Vue 3 + Tailwind + shadcn-vue (`frontend/`) | [`04-admin-frontend.md`](04-admin-frontend.md) | 🔵 **WBS Defined (0%)** |
| **TSK-MIT-FE** | **Mitra Web Dashboard** | Vue 3 + Tailwind + shadcn-vue (`frontend/`) | `05-mitra-frontend.md` | ⚪ *Planned* |
| **TSK-PND-MO** | **Pendaki Mobile App** | Quasar Framework + Capacitor (`mobile/`) | *TBD* | ⚪ *Planned* |

---

## 🚀 Panduan Penggunaan Checklist
1. Setiap item tugas ditandai dengan kotak centang markdown (`[x]` untuk selesai, `[ ]` untuk belum/tertunda).
2. Ketika menyelesaikan implementasi sebuah fitur/endpoint, developer atau AI Agent wajib:
   - Menjalankan pengujian Pest (`php artisan test --compact`).
   - Menjalankan linter & formatting (`vendor/bin/pint --dirty`).
   - Memperbarui checklist pada file task terkait.
