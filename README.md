# Hotel Management System

**Lokanata HMS** Sistem Manajemen Hotel berbasis web yang dirancang untuk mengelola seluruh operasional hotel secara digital, mulai dari pemesanan kamar online oleh tamu, pengelolaan reservasi oleh resepsionis, hingga manajemen tata graha (housekeeping). Dibangun dengan stack **Laravel 12 (Backend REST API)** dan **Vue.js 3 + Vite**.

**Pembuat:** Muhamad Farhan Muizaddin

---

## Fitur & Aktor Terlibat

Sistem ini melibatkan 4 aktor utama:

1.  **Tamu (Guest)**: Mengakses website hotel, melihat daftar kamar & harga, dan melakukan pemesanan kamar.
2.  **Resepsionis**: Mengelola booking online (terima/tolak), membuat reservasi manual, proses check-in & check-out, pencatatan pembayaran & faktur, serta mengelola data tamu.
3.  **Tata Graha (Housekeeping)**: Melihat daftar kamar yang perlu dibersihkan dan memperbarui status kebersihan kamar.
4.  **Admin / Manajer**: Memiliki akses penuh ke seluruh fitur sistem, termasuk dashboard statistik, manajemen staf, tipe kamar, daftar kamar, fasilitas, pengaturan landing page, dan laporan pendapatan/reservasi.

Autentikasi portal staf menggunakan session cookie Laravel Sanctum untuk SPA (CSRF cookie dan proteksi cookie), bukan bearer token. Endpoint publik tetap hanya tersedia untuk landing page, booking, dan pelacakan reservasi.

---

## Demo Aplikasi

### Pengunjung (Guest Booking)

https://github.com/user-attachments/assets/6746f5f3-f456-476c-bdca-a88a9162b07e

### Admin (Manajemen Sistem)

https://github.com/user-attachments/assets/6c0db86c-0a54-4648-912d-7f9ff099f3cf

### Resepsionis (Operasional Harian)

https://github.com/user-attachments/assets/24cb0659-71ca-4765-80a2-bede2903dc41

### Housekeeping (Tata Graha)

https://github.com/user-attachments/assets/3a730b09-f9d5-447e-83f9-87296d7a1880

---

## Struktur & Perancangan Sistem

### 1. Database Entity Relationship Diagram (ERD)
Sistem database ini memiliki 10 tabel domain dengan integritas relasi antar-tabel.
![ERD Diagram](frontend/diagrams/erd.svg)

### 2. Use Case Diagram
Diagram ini memperlihatkan interaksi setiap aktor terhadap 17 fungsionalitas utama sistem.
![Use Case Diagram](frontend/diagrams/usecasediagram.svg)

### 3. User Flow Diagram
Alur jalannya interaksi user dari 4 perspektif: Tamu, Resepsionis, Tata Graha, dan Admin.
![User Flow Diagram](frontend/diagrams/User%20Flow%20Diagram.svg)

---

## Menjalankan Aplikasi

Aplikasi menggunakan stack berikut:
- **PHP** >= 8.2
- **Composer** v2+
- **Node.js** v20.19+ (`.nvmrc` tersedia di `frontend/`)
- **Database** MySQL / MariaDB (Dianjurkan via Container/Docker)

### Menjalankan via Docker (Development)

Docker Compose menyediakan workflow development dengan pemetaan port lokal dan volume bernama. Compose ini hanya untuk development, bukan konfigurasi production.

**1. Siapkan environment Compose**

Salin template dari root project:
```bash
cp .env.example .env
```

Di PowerShell, gunakan `Copy-Item .env.example .env`. Jangan commit file `.env`.

**2. Isi secret dan APP_KEY**

Edit `.env`, lalu isi `DB_PASSWORD` dan `DB_ROOT_PASSWORD` dengan password kuat dan unik. Jangan memakai credential default. Generate APP_KEY dengan PHP:
```bash
php -r "echo 'APP_KEY=base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
```

Salin baris hasil perintah ke `APP_KEY` pada `.env`. Nilai `RUN_MIGRATIONS=true` menjalankan migration saat container backend mulai; ubah menjadi `false` bila migration dikelola terpisah.

**3. Jalankan container**
```bash
docker compose up -d --build
```

URL development:
- **Frontend App (Docker):** `http://localhost:3000`
- **Backend/API:** `http://localhost:8000`
- **phpMyAdmin:** `http://localhost:8080`

Port Docker dipublish hanya ke `127.0.0.1`. Login phpMyAdmin menggunakan user root dan password yang disimpan di `DB_ROOT_PASSWORD`.

### Menjalankan Tanpa Docker (Manual)

**1. Siapkan Database**

Pastikan MySQL/MariaDB berjalan, buat database, dan siapkan credential database Anda sendiri.

**2. Setup Backend**

```bash
cd backend
composer install
cp .env.example .env
```

Sesuaikan `backend/.env` untuk database lokal. Template manual menggunakan `DB_HOST=127.0.0.1`, `FRONTEND_URL=http://localhost:5173`, `SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000`, dan `SESSION_SECURE_COOKIE=false`. Isi credential database secara manual, lalu jalankan:
```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

`php artisan db:seed` bersifat idempotent untuk data master. Akun demo hanya dibuat pada environment `local`/`testing` bila `DEMO_USER_PASSWORD` diisi secara lokal; production harus membuat akun staf melalui prosedur provisioning yang dikontrol.

**3. Setup Frontend**

Buka terminal baru:
```bash
cd frontend
npm ci
npm run dev
```

**4. Akses Aplikasi**
- **Frontend App (manual):** `http://localhost:5173`
- **Backend/API:** `http://localhost:8000`

Untuk manual development, pastikan `FRONTEND_URL` dan `SANCTUM_STATEFUL_DOMAINS` menggunakan port `5173`/`8000` serta `DB_HOST=127.0.0.1`. Untuk Docker, root `.env` memakai `DB_HOST=db` dan port frontend `3000`; proxy internal Vite mengarah ke `http://backend:8000`.

### Catatan Deployment

Docker Compose di repository ini development-only. Production memerlukan TLS dan reverse proxy, secret manager untuk `APP_KEY` serta password, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, dan migration yang dikelola secara terkontrol. Repository ini tidak menyediakan konfigurasi production atau jaminan keamanan deployment. Jika credential lama pernah dipakai di environment publik, rotasi password database/root, akun staf, dan `APP_KEY`, invalidate seluruh session, serta amankan atau hapus log lama.

### Verifikasi

```bash
cd backend
composer validate --strict
composer audit --locked
vendor/bin/pint --test
php artisan test
```

```bash
cd frontend
npm ci
npm audit
npm run build
```

Feature test backend mencakup role isolation, authentication JSON response, kapasitas dan konflik booking, transisi check-in/out, payment balance, housekeeping, pelacakan publik, dan penghapusan data historis. Frontend belum memiliki automated unit/E2E test; verifikasi frontend saat ini mencakup production build dan smoke test manual.

---

## Struktur Project

```
lokanata-hotel-management/
|-- backend/                         # Laravel 12 (REST API)
|   |-- app/
|   |   |-- Http/
|   |   |   |-- Controllers/
|   |   |   |   |-- Admin/          # Controller (Dashboard, CRUD Master Data, Laporan)
|   |   |   |   |-- Auth/           # Login Controller
|   |   |   |   |-- Guest/          # Controller (Landing Page, Booking Online)
|   |   |   |   |-- Housekeeper/    # Controller (Status Kebersihan Kamar)
|   |   |   |   |-- Receptionist/   # Controller (Reservasi, Check-In/Out, Pembayaran)
|   |   |   |-- Middleware/
|   |   |-- Models/                  # Model Eloquent
|   |-- database/
|   |   |-- migrations/             # File migrasi tabel
|   |   |-- seeders/
|   |-- routes/
|   |   |-- api.php                 # Seluruh definisi route REST API
|   |-- Dockerfile
|   |-- docker-entrypoint.sh
|   |-- .env.example
|   |-- composer.json
|
|-- frontend/                        # Vue.js 3 + Vite + Tailwind CSS v4
|   |-- src/
|   |   |-- components/
|   |   |   |-- layout/             # AppLayout, Sidebar, Navbar
|   |   |-- router/                 # Vue Router (role-based routing)
|   |   |-- services/               # Axios instance & interceptor
|   |   |-- stores/                 # Pinia state management
|   |   |-- views/
|   |   |   |-- admin/              # Halaman Admin (Dashboard, Kamar, Staf, dll)
|   |   |   |-- auth/               # Halaman Login
|   |   |   |-- landing/            # Halaman Publik (Home, Daftar Kamar, Booking)
|   |   |-- App.vue
|   |   |-- main.js
|   |-- diagrams/                   # SVG Diagram (ERD, Use Case, User Flow)
|   |-- Dockerfile
|   |-- package.json
|   |-- vite.config.js
|
|-- docker-compose.yml
|-- .env.example
|-- .gitignore
|-- README.md
```

---

## Lisensi

Project ini dilisensikan di bawah MIT License.

```
MIT License

Copyright (c) 2026 Muhamad Farhan Muizaddin

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```
