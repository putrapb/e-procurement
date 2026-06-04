# E-Procurement BNI - Comprehensive System & Design Blueprint

**Project Name:** E-Procurement BNI (Corporate Procurement & Budget Control System)
**Development Methodology:** Rapid Application Development (RAD)
**Context Boundary:** User-Centered Design (UCD) is strictly excluded. Focus is directed solely towards functional prototyping, backend integrity, automated validation, and database architecture.

---

## 1. Software Requirements Specification (SRS) & PRD

### 1.1. Product Objective
Membangun sistem *e-procurement* terotomatisasi untuk memfasilitasi pengajuan barang/jasa internal divisi, mengontrol pagu anggaran secara *real-time* untuk mencegah kebocoran (*budget overrun*), memberikan klasifikasi akuntansi (CAPEX/OPEX) tanpa intervensi manual, dan memastikan kepatuhan dokumen administratif.

### 1.2. System Actors & Roles
1.  **Requester (Staff Divisi):** Melakukan input data pengadaan, mengunggah *file* izin prinsip, dan melacak status tiket. Terikat pada satu `division_id`.
2.  **Validator/Admin (System/Opsional):** Memantau pengajuan masuk, memvalidasi vendor, dan memiliki akses pembacaan laporan (Dashboard) pengeluaran divisi.

### 1.3. State Management (Ticket Lifecycle)
Setiap `Ticket` pengadaan memiliki alur status (*state machine*) yang ketat:
*   `draft`: Tiket dibuat, namun dokumen Izin Prinsip belum lengkap atau belum di- *submit*.
*   `pending_validation`: Tiket di- *submit* dan sedang diproses oleh 4-Gate Validation Engine.
*   `budget_locked`: Tiket lolos validasi anggaran; nominal dana direservasi (*locked*) dari pagu divisi.
*   `approved`: Tiket lolos semua gerbang validasi, vendor valid, dan pengadaan disetujui.
*   `rejected`: Tiket ditolak (karena pagu tidak cukup, dokumen invalid, atau vendor di- *blacklist*).

### 1.4. The 4-Gate Smart Validation Engine (Core Logic)
1.  **Gate 1 (Budget Checking & Smart Locking):**
    *   *Logic:* Membandingkan `budget_estimated` pada *request* dengan `remaining_budget` pada tabel `divisions` milik *user*.
    *   *Action:* Jika *insufficient*, lemparkan HTTP Validation Error. Jika *sufficient*, ubah status menjadi `budget_locked` dan potong `remaining_budget` secara transaksional untuk mencegah *double-spending/race condition*.
2.  **Gate 2 (Auto CAPEX/OPEX Classification):**
    *   *Logic:* Menganalisis *payload* kategori aset dan nominal `budget_estimated`.
    *   *Rule CAPEX:* Hardware fisik, infrastruktur, atau nilai pengadaan > Rp 50.000.000.
    *   *Rule OPEX:* Lisensi software, ATK, pemeliharaan < Rp 50.000.000.
    *   *Action:* *Engine* meng- *inject* nilai `expenditure_type` sebelum *insert* ke database.
3.  **Gate 3 (Vendor & Compliance Verification):**
    *   *Logic:* Memastikan `vendor_name` ada di *whitelist* mitra BNI dan *clearance* *requester* aktif.
4.  **Gate 4 (Document Integrity):**
    *   *Logic:* *Enforce upload* dokumen Izin Prinsip berekstensi `.pdf` (Max 10MB).
    *   *Action:* Menyimpan *stream file* ke S3 Bucket, lalu menuliskan *path URL* ke kolom `document_path`.

### 1.5. Non-Functional Requirements (NFR)
*   **Security:** *Environment variables* dirahasiakan; implementasi *Prepared Statements* (via Session Pooler) mencegah SQL Injection.
*   **Availability & Storage:** *File storage* dipisah dari aplikasi utama menggunakan *cloud bucket* (Supabase S3) untuk skalabilitas tinggi.
*   **Performance:** Menggunakan *thin client* UI (Alpine.js) untuk menghindari *full page reload* pada form interaktif.

---

## 2. Database Schema & Data Dictionary

Struktur relasional PostgreSQL, dibangun dengan Laravel Migrations:

### 2.1. Table: `divisions`
Pusat kontrol pagu anggaran (*budgeting*).
*   `id`: BigInteger, PK, Auto-increment.
*   `name`: String, nama divisi (e.g., "IT Infrastructure").
*   `yearly_budget_limit`: Decimal(15,2), batas pagu awal tahun.
*   `remaining_budget`: Decimal(15,2), sisa dana *real-time* yang dapat digunakan.
*   `timestamps`: created_at, updated_at.

### 2.2. Table: `users`
*   `id`: BigInteger, PK.
*   `division_id`: FK ke `divisions.id`.
*   `name`, `email`, `password`: Bawaan Laravel Breeze.

### 2.3. Table: `tickets`
Data historis dan transaksional pengadaan.
*   `id`: BigInteger, PK.
*   `user_id`: FK ke `users.id` (Cascade).
*   `division_id`: FK ke `divisions.id`.
*   `title`: String (255).
*   `description`: Text.
*   `budget_estimated`: Decimal(15,2).
*   `expenditure_type`: Enum ('CAPEX', 'OPEX').
*   `vendor_name`: String.
*   `document_path`: String (S3 URL).
*   `status`: Enum ('draft', 'pending_validation', 'budget_locked', 'approved', 'rejected') - *Default:* 'draft'.
*   `timestamps`: created_at, updated_at.

---

## 3. System Architecture & Engineering Workflow

### 3.1. Infrastructure & Deployment
*   **Database Cloud:** Supabase PostgreSQL (Tokyo / `ap-northeast-1`).
*   **Connection Protocol:** Wajib menggunakan **Session Pooler (Port 5432)** untuk stabilitas *connection pooling* dan sinkronisasi dengan PDO/PHP *Prepared Statements*.
*   **Object Storage:** Supabase Storage (Public Bucket: `eprocurement-storage`) via AWS S3 Driver (`league/flysystem-aws-s3-v3`).

### 3.2. Request Lifecycle Pattern (Code Architecture)
Arsitektur di- *decouple* untuk kemudahan *maintenance*:
1.  **Route/Middleware:** *User* terautentikasi mengirim *payload* POST ke `/tickets`.
2.  **FormRequest Layer:** `StoreTicketRequest` memvalidasi tipe data awal (wajib ada *title*, *budget* numerik, *file* PDF maks 10MB).
3.  **Controller Layer:** `TicketController` mendelegasikan data ke *Service*. Dijaga tetap tipis (*Thin Controller*).
4.  **Service Layer:** `ProcurementValidationService` menjalankan 4-Gate Smart Validation (cek pagu divisi, potong pagu, tentukan CAPEX/OPEX, simpan ke S3).
5.  **Database/Response:** Menyimpan ke tabel `tickets` dan mengembalikan respons dengan Alpine.js *flash message*.

### 3.3. Quality Assurance (Testing Strategy)
*   **Framework:** Pest Testing.
*   **Rules:** Menggunakan sintaks fungsional, dilarang menggunakan *boilerplate* kelas berbasis PHPUnit.
*   **Test Cases:** Prioritas pada *Unit Testing* kalkulasi pagu divisi (Gate 1) dan *Feature Testing* untuk integrasi *upload* S3 (Gate 4).

---

## 4. Design System & User Interface

Pendekatan *Utility-First* UI, mempercepat RAD tanpa proses iterasi desain UCD.

### 4.1. Layout Structure
*   **App Shell:** Terdiri dari *Sidebar* kiri untuk navigasi (Dashboard, My Tickets, Settings) dan *Top Header* untuk profil/notifikasi.
*   **Content Area:** Area utama menggunakan *Card-based layout* (`bg-white`, `shadow-sm`, `rounded-lg`).

### 4.2. Corporate Branding (BNI Identity Map in Tailwind)
Diimplementasikan pada `tailwind.config.js`:
*   `primary`: Teal/Corporate Blue (Warna dominan *header*, *primary button*, *active links*).
*   `accent`: Corporate Orange (Warna untuk tombol aksi penting, notifikasi *pending*, atau *warning*).
*   `neutral`: Gray-50 untuk *background*, Gray-800 untuk teks *body*.

### 4.3. UI Components & Interactivity
*   **Alpine.js Directives:** Menggunakan `x-data` dan `x-show` untuk menangani transisi modal "Konfirmasi Pengajuan" dan interaksi penutupan *Flash Message*.
*   **State Badges (Pills):**
    *   Draft: `bg-gray-100 text-gray-800`
    *   Pending: `bg-yellow-100 text-yellow-800`
    *   Budget Locked: `bg-blue-100 text-blue-800`
    *   Approved: `bg-green-100 text-green-800`
    *   Rejected: `bg-red-100 text-red-800`
*   **Forms:** *Input field* yang seragam dengan validasi *inline* berwarna merah jika pengguna salah memasukkan format *budget* atau gagal melewati batas ukuran PDF.