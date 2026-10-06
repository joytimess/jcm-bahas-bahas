# Bahas-Bahas

Platform seperti media sosial: pengguna bisa membuat **thread** (postingan), menambah **gambar**, saling **berkomentar**, dan memberi **like**.

## Fitur Utama

- **Autentikasi**: register, login, logout, reset password, verifikasi email (Laravel Breeze).
- **Thread**: buat, lihat, ubah, hapus postingan (teks + gambar).
- **Komentar**: komentar di setiap thread, bisa diubah dan dihapus.
- **Like**: like pada thread maupun komentar.
- **Gambar**: lampiran gambar untuk thread dan komentar.
- **Profil**: ubah data profil, ganti password, hapus akun.
- **Soft delete**: data yang dihapus hanya ditandai (`is_deleted`), tidak benar-benar dihapus. Menghapus thread ikut menandai komentar dan gambarnya.

## Teknologi

- PHP 8.3+, Laravel 13
- Laravel Sanctum (autentikasi API berbasis token)
- Laravel Breeze (halaman login/register, Blade)
- Pest (testing), Pint (code style)
- Dijalankan lewat Laragon

## Cara Kerja

- Halaman web (Blade) memuat data lewat **REST API** (`/api/...`) menggunakan fetch/XHR.
- Endpoint API dilindungi `auth:sanctum`.
- Middleware `BlockBrowserNavigation` membuat endpoint API tidak bisa dibuka langsung dari address bar browser (hasilnya 404).

## Halaman Web

| URL | Fungsi |
|---|---|
| `/` | Halaman awal |
| `/dashboard` | Daftar thread (butuh login) |
| `/threads/{id}` | Detail thread dan komentar |
| `/profile` | Pengaturan profil |

## Endpoint API

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/register`, `/api/login` | Daftar dan login |
| POST | `/api/logout` | Logout |
| GET | `/api/user` | Data user yang sedang login |
| CRUD | `/api/threads` | Kelola thread |
| POST | `/api/threads/{id}/like` | Like thread |
| GET/POST | `/api/threads/{id}/comments` | Lihat dan tambah komentar |
| PUT/DELETE | `/api/comments/{id}` | Ubah dan hapus komentar |
| POST | `/api/comments/{id}/like` | Like komentar |

## Struktur Data

- `users`
- `threads`
- `comments`
- `images` (polymorphic, untuk thread dan komentar)
- `likes` (polymorphic, untuk thread dan komentar)
- `personal_access_tokens` (Sanctum)

## Menjalankan Project

```sh
composer setup   # install, buat .env, generate key, migrate, build asset
composer dev     # jalankan server, queue, dan vite
composer test    # jalankan test
```
