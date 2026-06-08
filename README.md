# E-KSB (Koperasi Syariah Berkah Elektronik)

## Tentang E-KSB
E-KSB (Koperasi Syariah Berkah Elektronik) adalah aplikasi web yang dibangun menggunakan framework Laravel dan Inertia. Aplikasi ini berfungsi sebagai platform untuk mengelola operasional koperasi, memberikan pengalaman yang lancar bagi pengguna dalam melakukan berbagai tugas terkait koperasi.

Fitur-fitur E-KSB meliputi:
- Autentikasi dan registrasi pengguna
- Manajemen anggota dan pengurus koperasi
- Manajemen simpanan dan tabungan
- Manajemen pembiayaan murabahah
- Pelacakan angsuran murabahah
- Notifikasi Jatuh Tempo

## Instalasi
Untuk menjalankan aplikasi E-KSB secara lokal, ikuti langkah-langkah berikut:

1. Klon repositori:
```bash
   git clone [https://github.com/your-username/e-ksb.git](https://github.com/your-username/e-ksb.git)

```

2. Masuk ke direktori proyek:

```bash
   cd e-ksb

```

3. Instal dependensi menggunakan Composer:

```bash
   composer install

```

4. Salin file contoh environment dan konfigurasikan:

```bash
   cp .env.example .env

```

Perbarui file `.env` dengan kredensial database MySQL Anda beserta pengaturan konfigurasi lainnya.

5. Hasilkan kunci aplikasi (*application key*):

```bash
   php artisan key:generate

```

6. Jalankan migrasi database:

```bash
   php artisan migrate

```

7. Jalankan *seeder* database untuk mengisi data awal:

```bash
   php artisan db:seed

```

8. Mulai server pengembangan:

```bash
   php artisan serve

```

Di terminal terpisah, kompilasi aset *frontend*:

```bash
   npm run dev

```

## Hak Cipta

Proyek ini dikembangkan dan dikelola oleh Tim Kelompok Tugas Akhir-203 2026.

[Anggota Tim]

* Alanna Tanisya Anwar (231511034)
* Dhira Ramadini (231511041)
* Erina Dwi Yanti (231511043)
