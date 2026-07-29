# Sistem Peminjaman Mess & Bungalow

> Modul standalone untuk pengelolaan peminjaman Mess dan Bungalow BEM Polmed, terpisah dari Sistem Peminjaman Kendaraan, dengan alur approval dan hierarki jabatan tersendiri.

## Deskripsi

Sistem ini mengatur proses pengajuan, approval berjenjang, penjadwalan, hingga rating untuk peminjaman **Mess** (beserta kamar-kamarnya) dan **Bungalow**. Seluruh jabatan dapat mengajukan permintaan, namun kelayakan pemesanan unit tertentu dan prioritas saat terjadi bentrok jadwal ditentukan berdasarkan hierarki jabatan.

## Fitur Utama

- Pengajuan peminjaman oleh seluruh jabatan (Staff, Kasubag, Kabag)
- Approval berjenjang: Staff → Kasubag → Kabag → Admin
- Admin dapat mengedit waktu peminjaman & memvalidasi jadwal akhir
- Deteksi bentrok jadwal otomatis + aturan prioritas berdasarkan jabatan
- CRUD Mess & Kamar (dengan pembatasan hierarki jabatan per kamar)
- CRUD Bungalow (pemesanan per unit, hierarki serupa Mess)
- Rating pasca-penggunaan
- Log peminjaman & log aktivitas
- Export data peminjaman (Excel/PDF)

## Daftar Isi

1. Aktor & Hierarki Jabatan
2. Alur Peminjaman
3. Modul CRUD Mess & Kamar
4. Modul CRUD Bungalow
5. Aturan Hierarki pada Pemesanan
6. Log Peminjaman & Log Aktivitas
7. Export Data
8. Rating
9. Pengembangan Selanjutnya
10. Catatan & Asumsi

---

## 1. Aktor & Hierarki Jabatan

| Level | Jabatan | Peran |
|---|---|---|
| 1 (terendah) | Staff | Pemohon |
| 2 | Kasubag | Pemohon + Approver |
| 3 (tertinggi) | Kabag | Pemohon + Approver |
| — | Admin | Approver final, validasi jadwal, edit waktu, kelola master data |

Hierarki ini dipakai untuk dua hal berbeda:
- **Urutan approval** (lihat bagian 2)
- **Kelayakan pemesanan unit & prioritas saat bentrok jadwal** (lihat bagian 5)

---

## 2. Alur Peminjaman

### Langkah 1 — Pengajuan Permintaan

Semua jabatan (Staff, Kasubag, Kabag) dapat mengajukan surat permintaan peminjaman Mess/Kamar atau Bungalow. Data minimal yang diisi: pemohon & jabatan, unit yang dituju, tanggal & jam mulai-selesai, keperluan.

### Langkah 2 — Approval Berjenjang

```
Pemohon → Staff → Kasubag → Kabag → Admin
```

Permintaan berjalan berurutan melalui tiap tahap. Setiap approver dapat **Menyetujui** (lanjut ke tahap berikutnya) atau **Menolak** (proses berhenti, notifikasi terkirim ke pemohon).

### Langkah 3 — Diterima Admin

Setelah lolos approval berjenjang (Staff → Kasubag → Kabag), permintaan diteruskan ke Admin sebagai tahap validasi akhir.

> **Wewenang khusus Admin:** Admin dapat mengedit tanggal/jam peminjaman yang diajukan sebelum status akhir ditetapkan — baik untuk resolusi bentrok jadwal maupun penyesuaian operasional lainnya.

### Langkah 4 — Pengecekan Bentrok Jadwal & Prioritas

- Admin memantau seluruh jadwal aktif. Jika ditemukan permintaan dengan waktu yang bentrok pada unit yang sama, Admin dapat melakukan **penolakan**.
- Penolakan ini bersifat **soft-reject**: bukan pembatalan permanen, melainkan permintaan agar pemohon mengajukan ulang di waktu lain. Notifikasi otomatis dikirim ke pemohon terkait.
- **Aturan Prioritas:** jika dua permintaan bentrok, pemohon dengan **jabatan lebih tinggi diprioritaskan**, meskipun pengajuannya masuk lebih belakangan dibanding pemohon lain.

### Langkah 5 — Rating Pasca Penggunaan

Setelah masa peminjaman selesai, pemohon dapat memberi rating & ulasan terhadap Mess/Kamar/Bungalow yang telah digunakan.

### Langkah 6 — Penyimpanan Data

Seluruh data peminjaman (termasuk histori approval & rating) disimpan sebagai basis untuk pengembangan fitur lanjutan (lihat bagian 9).

---

## 3. Modul CRUD Mess & Kamar

**Mess**
- Field: nama, **alamat**, deskripsi, foto (opsional), status aktif/nonaktif
- Relasi: 1 Mess → banyak Kamar

**Kamar**
- Field: nama/nomor kamar, kapasitas, status ketersediaan, `minimum_jabatan` (lihat bagian 5)
- Kamar dalam 1 mess yang sama dapat dipinjam **secara independen** oleh permintaan berbeda pada waktu bersamaan, selama kamarnya tidak sama

Operasi CRUD (Create, Read, Update, Delete) tersedia untuk Admin pada data Mess maupun Kamar.

---

## 4. Modul CRUD Bungalow

- Field: nama, alamat, deskripsi, foto (opsional), kapasitas, status aktif/nonaktif, `minimum_jabatan`
- Berbeda dari Mess, Bungalow dipesan sebagai **satu unit utuh** (bukan per kamar), sesuai sifatnya sebagai unit rumah
- Pola hierarki jabatan & operasi CRUD mengikuti pola yang sama seperti Mess (bagian 3)

---

## 5. Aturan Hierarki pada Pemesanan

- Setiap Kamar dan Bungalow memiliki atribut `minimum_jabatan` (nilai: Staff / Kasubag / Kabag)
- Contoh: sebuah Kamar diberi `minimum_jabatan = Kasubag` → hanya Kasubag dan Kabag yang bisa mengajukan peminjaman untuk kamar tersebut; Staff tidak bisa memilihnya saat mengajukan
- Aturan ini terpisah dari **aturan prioritas saat bentrok jadwal** (bagian 2, Langkah 4):
  - `minimum_jabatan` → syarat **kelayakan** mengajukan
  - Prioritas jabatan → penentu **siapa yang menang** saat dua pengajuan yang sama-sama layak, bentrok jadwal

---

## 6. Log Peminjaman & Log Aktivitas

**Log Peminjaman** — histori tiap transaksi:
- ID peminjaman, pemohon, jabatan, unit (mess/kamar/bungalow), waktu mulai-selesai, status & waktu di tiap tahap approval, hasil akhir (disetujui/ditolak/soft-reject), rating (jika ada)

**Log Aktivitas** — audit trail seluruh aksi pengguna:
- CRUD Mess/Kamar/Bungalow oleh Admin
- Approval/penolakan oleh Staff, Kasubag, Kabag
- Perubahan waktu oleh Admin
- (Opsional) login/logout, mengikuti pola log aktivitas yang sudah berjalan di sistem peminjaman kendaraan

---

## 7. Export Data

- Export data peminjaman ke **Excel (.xlsx)** dan/atau **PDF**, mengikuti pola export yang sudah dipakai pada sistem sebelumnya (Laravel Excel & dompdf)
- Filter yang tersedia: rentang tanggal, jabatan pemohon, unit (mess/bungalow) tertentu, status peminjaman

---

## 8. Rating

- Skala 1-5 + ulasan teks singkat, diisi setelah masa peminjaman selesai
- Ditampilkan sebagai rata-rata pada halaman detail tiap Mess/Kamar/Bungalow

---

## 9. Pengembangan Selanjutnya

Data historis (peminjaman, approval, rating) disimpan agar bisa jadi landasan fitur berikutnya, misalnya:
- Dashboard statistik okupansi per Mess/Bungalow
- Laporan rating rata-rata per unit
- Notifikasi pengingat H-1 sebelum jadwal peminjaman
- Potensi integrasi dengan Sistem Peminjaman Kendaraan untuk acara yang butuh mess/bungalow + kendaraan sekaligus

---

## 10. Catatan & Asumsi

Poin berikut sebaiknya dikonfirmasi lebih lanjut sebelum/selama implementasi:

1. **Approval untuk pemohon Kasubag/Kabag** — alur "Staff → Kasubag → Kabag" di atas mengasumsikan tahap yang setara/di bawah level pemohon dilewati (misal: pemohon Kasubag → approval dimulai dari Kabag saja) untuk menghindari self-approval. Perlu dipastikan apakah ini sesuai kebutuhan sebenarnya.
2. **Definisi Admin** — apakah Admin adalah role sistem yang terpisah dari jabatan struktural (seperti pola di Sistem Peminjaman Kendaraan), atau melekat pada jabatan tertentu (misal Kasubag Kestari)?
3. **Reschedule setelah soft-reject** — apakah pemohon perlu mengajukan ulang dari tahap approval awal, atau cukup mengganti waktu dengan approval sebelumnya yang tetap berlaku?
4. **Media notifikasi** — in-app, email, atau kombinasi keduanya.

---

*README ini merupakan spesifikasi alur & fitur untuk Modul Peminjaman Mess & Bungalow.*