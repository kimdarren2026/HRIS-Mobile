# Google SSO Setup

## Tujuan

Google SSO di HRIS Mobile dipakai hanya untuk autentikasi identitas pengguna. Google bukan sumber role, status pegawai, unit kerja, jabatan, atau hak akses.

Prinsip utama:

- Google mengonfirmasi identitas pengguna.
- HRIS menentukan apakah pengguna boleh masuk.
- User harus sudah ada di database HRIS sebelum login Google berhasil.
- Role dan authorization selalu diambil dari database internal.

## OAuth Client di Google Cloud Console

1. Buka Google Cloud Console.
2. Pilih project yang akan digunakan untuk login HRIS.
3. Aktifkan Google Identity atau OAuth consent flow sesuai kebijakan organisasi.
4. Buat OAuth Client baru dengan tipe `Web application`.
5. Masukkan Authorized redirect URI yang sesuai.

Contoh redirect URI:

- Local: `http://127.0.0.1:8000/auth/google/callback`
- Production: `https://hrismobile.my.id/auth/google/callback`

Jika domain production berbeda, sesuaikan dengan `APP_URL` dan `GOOGLE_REDIRECT_URI`.

## Variabel Environment

Tambahkan konfigurasi berikut pada `.env`:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
GOOGLE_ALLOWED_DOMAINS=example.ac.id,example.co.id
```

Catatan:

- `GOOGLE_ALLOWED_DOMAINS` mendukung lebih dari satu domain dan dipisahkan koma.
- Domain akan dinormalisasi ke lowercase dan dicocokkan secara exact.
- Jika daftar domain kosong, login Google akan ditolak secara aman.

## Allowed Domains

Contoh konfigurasi:

```env
GOOGLE_ALLOWED_DOMAINS=kampus.ac.id,anakperusahaan.co.id
```

Contoh email yang diizinkan:

- `user@kampus.ac.id`
- `user@anakperusahaan.co.id`

Contoh email yang ditolak:

- `user@notkampus.ac.id`
- `user@kampus.ac.id.attacker.com`

## Alur Account Linking

1. User login dengan Google.
2. Sistem mengambil `google_id` dan email dari Google.
3. Email diverifikasi domainnya.
4. Sistem mencoba mencari user internal berdasarkan `google_id`.
5. Jika belum ada, sistem mencari user berdasarkan email internal.
6. Jika user ditemukan dan belum punya `google_id`, sistem melakukan initial account linking.
7. Login berikutnya memakai `google_id` sebagai identitas utama.

HRIS tidak membuat user aktif baru dari akun Google.

## Jika User Belum Terdaftar

Jika email hasil login Google belum ada di tabel `users`, login akan gagal dan user harus menghubungi Admin HR.

## Testing

Gunakan mock Socialite dan jalankan:

```bash
php artisan test --filter=GoogleSsoLoginTest
```

Jika ingin menjalankan formatter:

```bash
./vendor/bin/pint
```

## Checklist Deployment Production

- Isi `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, dan `GOOGLE_ALLOWED_DOMAINS`.
- Pastikan `APP_URL` production benar.
- Pastikan redirect URI di Google Cloud Console sama persis dengan callback production.
- Pastikan HTTPS aktif di production.
- Pastikan `SESSION_SECURE_COOKIE=true` di production.
- Jalankan `php artisan config:cache` setelah env final siap.
- Verifikasi user internal yang akan memakai Google sudah ada di tabel `users`.

## Troubleshooting

### `redirect_uri_mismatch`

- Pastikan `GOOGLE_REDIRECT_URI` sama persis dengan Authorized redirect URI di Google Cloud Console.
- Pastikan protocol (`http` vs `https`) dan domain tidak berbeda.

### Callback gagal atau kembali ke login

- Cek `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, dan `GOOGLE_ALLOWED_DOMAINS`.
- Pastikan domain email Google benar-benar ada di daftar allowed domains.
- Pastikan user sudah terdaftar di HRIS dan aktif.
- Lihat log aplikasi untuk kategori `google_sso_login_failed`.

### Ganti atau lepas akun Google

Jika akun Google yang terhubung perlu diganti, lakukan secara aman lewat admin atau update data user di database internal sesuai prosedur operasional. Jangan mengaktifkan auto-linking lintas akun tanpa verifikasi admin.
