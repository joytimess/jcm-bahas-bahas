# Bahas-Bahas

Platform diskusi bergaya media sosial. Pengguna bisa menulis **thread** berisi teks dan gambar, saling **berkomentar** (termasuk balasan bersarang), memberi **like**, dan **mengikuti** pengguna lain. Akun juga bisa dibuat **privat**, sehingga thread-nya hanya terlihat oleh pengikut yang disetujui.

## Tech Stack

**Backend**
- PHP 8.3+ dan Laravel 13
- Laravel Sanctum untuk autentikasi API (session cookie stateful untuk halaman web)
- Laravel Breeze (Blade) untuk login, register, reset password, dan verifikasi email
- Eloquent ORM dengan soft delete berbasis kolom `is_deleted`
- Penyimpanan file di disk `public` untuk foto profil dan lampiran gambar

**Frontend**
- Blade dan Alpine.js
- Tailwind CSS 3 dengan palet kustom (primary `#287A74`, secondary `#FFF8B0`) dan font Roboto
- Vite sebagai bundler
- Cropper.js untuk memotong gambar sebelum diunggah (dimuat lazy)
- GLightbox untuk melihat gambar ukuran penuh
- Google Material Symbols untuk ikon

**Tooling**
- Pest untuk testing
- Laravel Pint untuk code style
- Laragon sebagai lingkungan lokal

## Fitur

**Akun dan profil**
- Register, login, logout, lupa dan reset password, verifikasi email. Field password punya tombol lihat atau sembunyikan.
- Edit nama dan email, ganti password, hapus akun.
- Foto profil dengan crop 1:1. Bila belum ada foto, tampil avatar inisial.
- Opsi **akun privat**.

**Thread dan komentar**
- Buat, edit, dan hapus thread (maksimal 280 karakter dan 5 gambar).
- Komentar dengan balasan bersarang tanpa batas, bisa diedit dan dihapus, dan bisa memuat gambar.
- Like pada thread dan komentar.
- Setiap gambar yang dipilih bisa diatur dulu: crop, pilihan rasio, dan putar, atau dipakai apa adanya.
- Menghapus thread ikut menandai komentar dan gambarnya sebagai terhapus.

**Sosial**
- Follow dan unfollow. Jumlah pengikut dan mengikuti tampil di profil.
- Halaman profil pengguna lain di `/users/{id}`, berisi thread-nya, jumlah pengikut dan mengikuti, serta tombol follow.
- Kolom saran **Pengguna baru** berisi 5 pengguna terbaru.
- **Akun privat:** menekan follow membuat permintaan yang harus disetujui pemilik lewat halaman `/profile`. Thread akun privat disembunyikan di feed, profil, detail, komentar, dan like bagi yang belum disetujui. Aturan ini ditegakkan di API.

**Navigasi**
- **Dashboard:** feed semua thread yang boleh kamu lihat.
- **My Threads:** thread milikmu sendiri.
- **My Likes:** riwayat thread dan komentar yang kamu like. Like pada komentar menampilkan thread induknya.
- **Profile:** pengaturan akun dan permintaan follow.

**Tampilan**
- Landing page di `/`.
- Layout 3 kolom setelah login: sidebar di kiri, konten di tengah, saran di kanan. Di ponsel, sidebar diganti bottom bar.

## API

Semua endpoint di bawah `/api` memakai middleware `auth:sanctum`, kecuali `register` dan `login`. Endpoint ini tidak bisa dibuka langsung dari address bar browser.

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/register`, `/login`, `/logout` | Autentikasi token |
| GET | `/user` | Data diri, termasuk jumlah pengikut dan mengikuti |
| CRUD | `/threads` | Thread |
| POST | `/threads/{id}/like`, `/comments/{id}/like` | Toggle like |
| GET, POST | `/threads/{id}/comments` | Daftar dan tambah komentar |
| PUT, DELETE | `/comments/{id}` | Ubah dan hapus komentar |
| GET | `/users/suggestions` | Saran pengguna baru |
| GET | `/users/{id}`, `/users/{id}/threads` | Profil publik dan thread-nya |
| POST | `/users/{id}/follow` | Toggle follow atau permintaan follow |
| GET | `/me/likes` | Riwayat like |
| GET, POST, DELETE | `/follow-requests`, `/follow-requests/{id}/accept`, `/follow-requests/{id}` | Kelola permintaan follow |

## Menjalankan Project

```sh
composer setup            # install, .env, key, migrate, build asset
php artisan storage:link  # agar foto profil dan gambar bisa diakses
composer dev              # server, queue, dan vite
composer test             # jalankan test
```

## Lisensi

MIT
