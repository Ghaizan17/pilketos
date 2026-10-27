# Pilketos — SMKN 2 Banjarmasin

Aplikasi voting Ketua & Wakil Ketua OSIS berbasis **QR code sekali pakai**.
Setiap murid mendapat kartu QR pribadi; operator bilik memindai QR untuk membuka
halaman pilihan, murid memilih satu paslon lalu mengunci pilihannya.
Satu QR hanya berlaku untuk satu suara.

Stack: Laravel 13 + Filament 5 (panel admin) + SQLite + Tailwind 4 + Alpine.js
+ zxing-wasm (scan QR via kamera) + DomPDF/FPDI (cetak kartu QR).

## Alur pemakaian

### Persiapan (panitia / operator)

1. Buka panel admin di `/admin` dan login sebagai operator.
2. **Import Murid** — unggah file Excel Dapodik
   (`daftar_pd-*.xlsx`, data mulai baris 7; kolom Nama, NIPD, JK, NISN, Rombel
   terdeteksi otomatis). Setiap murid otomatis mendapat UUID untuk QR-nya.
3. **Kandidat** — tambah paslon (nomor urut, nama ketua & wakil, tagline, foto).
4. **Cetak QR** — unduh kartu QR massal (`/students/qr-cards.pdf`, 4 kartu
   per halaman A4) atau per murid langsung dari tabel Murid
   (tombol *Cetak QR* → file PDF terunduh otomatis).
5. **Pemilihan** — buka/tutup pemilihan dari halaman Pengaturan Pemilihan.
   Saat ditutup, `/bilik` menampilkan status "Pemilihan Ditutup".

### Hari-H (bilik suara)

1. Operator login di `/login` (username + password + tombol show/hide password).
2. Buka `/bilik` — arahkan QR murid ke bingkai kamera (otomatis terdeteksi).
3. Modal **Pilih Paslon** muncul (2 paslon tampil kiri-kanan, foto memenuhi
   kartu). Pilih satu paslon → **Pilih Paslon Ini** → popup **Kunci pilihan?**
   → **Ya, Konfirmasi**.
4. Suara tercatat → bunyi sukses + toast hijau → kembali ke mode scan.
   QR salah menampilkan modal *QR Tidak Dikenal*; QR yang sudah dipakai
   menampilkan *Sudah Memilih*.
5. Keluar via tombol *Keluar* (atau POST `/keluar`).

### Peran pengguna

| Role | Login | Akses |
| --- | --- | --- |
| `operator` | `/admin/login` dan `/login` | Penuh: panel admin, cetak QR, bilik scan |
| `scanner` | `/login` saja | Hanya `/bilik` (ditolak di `/admin` dan unduhan QR dengan 403) |

Akun bawaan (ganti password setelah login pertama):

| Username | Password | Role |
| --- | --- | --- |
| `operatorskenda` | `operatorskenda123` | operator |
| `operatorscan` | `operatorscan123` | scanner |

## Menjalankan secara lokal

Syarat: PHP 8.4 + Composer + Node 24. Ekstensi PHP: `sqlite3/pdo_sqlite`,
`gd`, `mbstring`, `xml`, `fileinfo`, `zip` (untuk import Excel).

```bash
# 1. Dependensi
composer install
npm install

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate
# DB default SQLite — pastikan database/database.sqlite ada:
touch database/database.sqlite

# 3. Migrasi + data awal (2 akun + pemilihan terbuka + data demo)
php artisan migrate --seed

# 4. Build aset frontend
npm run build

# 5. Jalankan
php artisan serve
```

Buka `http://127.0.0.1:8000` (halaman utama), `/admin` (panel admin),
`/login` (login bilik), `/bilik` (scan — butuh login + kamera/HTTPS).

Perintah berguna:

```bash
php artisan test                    # seluruh test (19 test)
php artisan test --filter=BoothAuthTest
npm run dev                         # dev server Vite (hot reload)
php artisan pilketos:clear-demo     # hapus data demo (nipd DEMO*)
```

## Struktur penting

- `routes/web.php` — `/`, `/login`, `/bilik`, `/api/voters|votes`, cetak QR.
- `app/Filament/` — panel admin: Resources (Murid, Kandidat, User, Suara),
  Pages (Import Murid, Cetak QR, Pengaturan Pemilihan), Widgets dashboard.
- `resources/views/booth/` — `home.blade.php`, `login.blade.php`, `scan.blade.php`.
- `resources/js/booth.js` — state machine bilik
  (`scanning → resolving → selecting → confirm_popup → confirming`,
  error: `unknown_qr | already_voted | camera_error`) + audio sukses.
- `resources/views/students/qr-*.blade.php` — template kartu QR
  (massal A4 + perorangan, QR sebagai data-URI SVG agar lolos DomPDF).
- `public/sounds/vote-success.mp3` — audio saat suara tercatat.

## Catatan operasional

- Bilik butuh kamera + konteks aman (HTTPS atau `localhost`) agar
  `getUserMedia` diizinkan browser.
- Foto paslon di modal memakai `object-top` — wajah tetap terlihat pada
  crop landscape.
- Import massal menampilkan overlay error generik berbahasa Inggris bila
  server kehabisan memori/waktu — lihat `storage/logs/laravel.log`.
