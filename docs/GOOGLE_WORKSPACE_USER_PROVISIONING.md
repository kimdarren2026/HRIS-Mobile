# Google Workspace User Provisioning

## Tujuan

Mendaftarkan email Google Workspace ke HRIS secara aman sebelum karyawan login menggunakan Google SSO, tanpa membuat user satu per satu secara manual.

Prinsip utama tetap mengikuti [GOOGLE_SSO_SETUP.md](./GOOGLE_SSO_SETUP.md):

- Google hanya mengonfirmasi identitas. HRIS tetap sumber kebenaran untuk role, status, dan akses.
- Command ini **tidak** membuat user langsung bisa login. User baru dibuat dalam status `is_active = false` sampai Admin HR melengkapi profil pegawai (Employee) dan mengaktifkannya.
- Akun bersama/unit (mis. `webmaster@`, `arsip@`, `baak@`) **tidak pernah** dibuat otomatis. Command hanya melaporkannya untuk ditinjau manual.
- Tidak ada data pegawai (NIK, jabatan, departemen, tanggal masuk) yang dikarang. Jika data itu belum ada, user dibiarkan tanpa profil pegawai dan dilaporkan sebagai "needs employee profile completion".

## Command

```bash
# Preview saja, tidak menulis ke database (default jika tidak ada flag)
php artisan hris:provision-google-users --dry-run

# Terapkan perubahan ke database
php artisan hris:provision-google-users --apply
```

Opsi tambahan:

| Opsi | Keterangan |
|---|---|
| `--emails=a@x.ac.id,b@x.ac.id` | Daftar email langsung (comma-separated), mengesampingkan `--file`. |
| `--file=/path/to/list.txt` | File daftar email (satu email per baris, baris `#` diabaikan). |

Jika `--emails` dan `--file` tidak diisi, command memakai daftar bawaan di `resources/data/google-workspace-emails.txt`.

## Cara Kerja

Untuk setiap email:

1. **Validasi format** — email tidak valid masuk ke laporan "Invalid / rejected emails" dengan alasan `invalid_format`.
2. **Validasi domain** — hanya domain yang terdaftar di `GOOGLE_ALLOWED_DOMAINS` (mis. `stikesadvaitamedika.ac.id`) yang diizinkan; selain itu ditolak dengan alasan `domain_not_allowed`. Validasi ini memakai method yang sama dengan `GoogleSsoService`, jadi tidak ada logika ganda yang bisa berbeda hasil.
3. **User sudah ada** — dilaporkan sebagai "Existing users", tidak diubah sama sekali (tidak ada duplikasi, role/status lama tidak disentuh).
4. **Terdeteksi akun bersama/unit** — jika local-part email mengandung kata kunci di `config/google_workspace_provisioning.php` (`shared_unit_keywords`), akun dilewati (tidak dibuat) dan masuk laporan "Skipped shared/unit accounts" untuk ditinjau manual oleh Admin HR.
5. **Akun personal baru** — dengan `--apply`, dibuat `User` baru:
   - `role = employee`
   - `is_active = false` (aman, karena belum ada profil Employee)
   - `password` = random 48 karakter yang di-hash (`Hash::make`), tidak pernah dicetak ke output/log.
   - `name` awal diturunkan dari local-part email (placeholder, bukan data karyawan resmi) dan bisa diperbarui nanti.
   - Dicatat ke `audit_logs` dengan action `google_workspace_user_provisioned`.

## Setelah User Dibuat

User baru **belum bisa login via Google SSO** sampai Admin HR:

1. Membuat/menghubungkan `Employee` record dengan data pegawai yang valid (NIK, departemen, posisi, dsb).
2. Mengaktifkan user (`is_active = true`).

Ini konsisten dengan `UserAccessValidator`: role `employee` wajib punya `Employee` record aktif sebelum bisa mengakses HRIS, termasuk lewat Google SSO.

## Laporan Output

Setiap run menampilkan:

- Created / would-create personal users
- Existing users beserta status "ready for SSO"
- Skipped shared/unit accounts (butuh review manual)
- Invalid/rejected emails
- Users yang masih butuh kelengkapan profil pegawai
- Users yang sudah siap login Google SSO sekarang

## Keamanan

- Password acak tidak pernah dicetak atau di-log dalam bentuk plaintext.
- Command tidak mengubah `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` atau konfigurasi OAuth.
- Command tidak melemahkan middleware `has_employee`, role checks, atau `UserAccessValidator`.
- Jalankan `--dry-run` terlebih dahulu sebelum `--apply` di lingkungan produksi.
