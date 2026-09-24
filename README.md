# Hotel Management System

**Lokanata HMS** Sistem Manajemen Hotel berbasis web yang dirancang untuk mengelola seluruh operasional hotel secara digital, mulai dari pemesanan kamar online oleh tamu, pengelolaan reservasi oleh resepsionis, hingga manajemen tata graha (housekeeping). Dibangun dengan stack **Laravel 12 (Backend REST API)** dan **Vue.js 3 + Vite**.

---

## Fitur & Aktor Terlibat

Sistem ini melibatkan 4 aktor utama:

1.  **Tamu (Guest)**: Mengakses website hotel, melihat daftar kamar & harga, dan melakukan pemesanan kamar.
2.  **Resepsionis**: Mengelola booking online (terima/tolak), membuat reservasi manual, proses check-in & check-out, pencatatan pembayaran & faktur, serta mengelola data tamu.
3.  **Tata Graha (Housekeeping)**: Melihat daftar kamar yang perlu dibersihkan dan memperbarui status kebersihan kamar.
4.  **Admin / Manajer**: Memiliki akses penuh ke seluruh fitur sistem, termasuk dashboard statistik, manajemen staf, tipe kamar, daftar kamar, fasilitas, pengaturan landing page, dan laporan pendapatan/reservasi.

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
Sistem database ini memiliki 9 tabel dengan integritas relasi antar-tabel.
![ERD Diagram](frontend/diagrams/erd.svg)

### 2. Use Case Diagram
Diagram ini memperlihatkan interaksi setiap aktor terhadap 17 fungsionalitas utama sistem.
![Use Case Diagram](frontend/diagrams/usecasediagram.svg)

### 3. User Flow Diagram
Alur jalannya interaksi user dari 4 perspektif: Tamu, Resepsionis, Tata Graha, dan Admin.
![User Flow Diagram](frontend/diagrams/User%20Flow%20Diagram.svg)

---

## Menjalankan Aplikasi

berjalan pada ekosistem menggunakan stack berikut:
- **PHP** >= 8.2
- **Composer** v2+
- **Node.js** v20+
- **Database** MySQL / MariaDB (Dianjurkan via Container/Docker)

### Menjalankan via Docker (Recommended)
Pada project ini **sudah disediakan Native Container Mapping** untuk proses development cepat.

```bash
docker compose up -d --build
```

Setelah dijalankan, Anda bisa mengakses url lokal berikut:
- **Frontend App:** `http://localhost:3000`
- **Backend/API:** `http://localhost:8000`
- **phpMyAdmin:** `http://localhost:8080`

### Menjalankan Tanpa Docker (Manual)

**1. Siapkan Database**

Buat database MySQL dengan nama `lokanata_hotel`, pastikan service MySQL/MariaDB sudah berjalan di komputer Anda (misalnya via XAMPP atau Laragon).

**2. Setup Backend**

```bash
cd backend
composer install
copy .env.example .env
```

Sesuaikan file `.env` pada bagian database:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lokanata_hotel
DB_USERNAME=root
DB_PASSWORD=
```

Lanjutkan:
```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan serve --port=8000
```

**3. Setup Frontend**

Buka terminal baru:
```bash
cd frontend
npm install
npm run dev
```

**4. Akses Aplikasi**
- **Backend/API:** `http://localhost:8000`
- **Frontend App:** `http://localhost:5173`

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
|-- diagram.drawio                  # Source file diagram (editable)
|-- docker-compose.yml
|-- dev.bat
|-- .gitignore
|-- README.md
```

---

## Lisensi

Project ini dilisensikan di bawah [MIT License](LICENSE).

```
MIT License

Copyright (c) 2026 Cybha

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
