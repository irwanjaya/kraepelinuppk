# Aplikasi Tes Kraepelin - PHP & MySQL

Aplikasi tes Kraepelin yang dibangun menggunakan PHP dan MySQL untuk melakukan tes psikologi Kraepelin secara digital.

## Fitur Utama

- **Interface Modern**: Menggunakan Tailwind CSS untuk tampilan yang responsif dan modern
- **Manajemen Peserta**: Input nama dan NIP peserta dengan validasi
- **Tes Interaktif**: Grid 25x50 dengan navigasi keyboard yang intuitif
- **Kontrol Tes**: Tombol mulai, hentikan, dan reset tes
- **Penyimpanan Database**: Semua data tersimpan di MySQL
- **Export Data**: Export hasil tes ke format CSV/Excel
- **Riwayat Tes**: Melihat dan mengelola hasil tes sebelumnya
- **Auto-save**: Jawaban tersimpan otomatis
- **Validasi Real-time**: Pengecekan jawaban benar/salah

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx)
- Browser modern dengan JavaScript enabled

## Instalasi

1. **Clone atau download aplikasi**
   ```bash
   git clone [repository-url]
   cd kraepelin-test-php
   ```

2. **Setup Database**
   - Buat database MySQL baru
   - Update konfigurasi database di `config/database.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'kraepelin_test');
     ```

3. **Setup Web Server**
   - Pastikan web server mengarah ke direktori aplikasi
   - Pastikan PHP memiliki akses write ke direktori session

4. **Akses Aplikasi**
   - Buka browser dan akses URL aplikasi
   - Database dan tabel akan dibuat otomatis pada akses pertama

## Struktur Database

### Tabel `test_sessions`
- `id`: Primary key
- `participant_name`: Nama peserta
- `participant_nip`: NIP peserta (18 digit)
- `start_time`: Waktu mulai tes
- `end_time`: Waktu selesai tes
- `duration_seconds`: Durasi tes dalam detik
- `total_answers`: Total soal (1250)
- `filled_answers`: Jumlah jawaban yang diisi
- `completion_percentage`: Persentase penyelesaian

### Tabel `test_questions`
- `id`: Primary key
- `session_id`: Foreign key ke test_sessions
- `row_index`: Indeks baris (0-24)
- `col_index`: Indeks kolom (0-49)
- `question_number`: Angka soal (0-9)

### Tabel `test_answers`
- `id`: Primary key
- `session_id`: Foreign key ke test_sessions
- `row_index`: Indeks baris (0-24)
- `col_index`: Indeks kolom (0-49)
- `answer_value`: Nilai jawaban (maksimal 2 digit)
- `is_correct`: Status benar/salah
- `answered_at`: Waktu menjawab

## Cara Penggunaan

1. **Memulai Tes**
   - Isi nama peserta dan NIP (18 digit)
   - Klik tombol "Mulai Tes"
   - Fokus akan otomatis pindah ke input pertama

2. **Mengerjakan Tes**
   - Jumlahkan dua angka berurutan dalam kolom
   - Masukkan hasil (maksimal 2 digit)
   - Gunakan Tab/Enter untuk pindah ke input berikutnya
   - Gunakan arrow keys untuk navigasi manual

3. **Menghentikan Tes**
   - Klik tombol "Hentikan Tes" untuk mengakhiri
   - Data akan tersimpan otomatis ke database

4. **Melihat Hasil**
   - Akses `results.php` untuk melihat semua hasil tes
   - Klik "Lihat" untuk detail hasil
   - Klik "Export" untuk download data

## Fitur Navigasi Keyboard

- **Tab/Enter**: Pindah ke input berikutnya
- **Arrow Up/Down**: Navigasi vertikal
- **Arrow Left/Right**: Navigasi horizontal
- **Escape**: Keluar dari input

## File Struktur

```
kraepelin-test-php/
├── index.php              # Halaman utama tes
├── results.php             # Halaman hasil tes
├── view_result.php         # Detail hasil tes
├── export.php              # Export data
├── config/
│   └── database.php        # Konfigurasi database
├── includes/
│   └── functions.php       # Fungsi-fungsi utama
├── assets/
│   ├── css/
│   │   └── style.css       # Styling tambahan
│   └── js/
│       └── script.js       # JavaScript interaktif
└── README.md               # Dokumentasi
```

## Kustomisasi

### Mengubah Jumlah Soal
Edit di `includes/functions.php` fungsi `generateTestData()`:
```php
// Ubah 25 dan 50 sesuai kebutuhan
for ($row = 0; $row < 25; $row++) {
    for ($col = 0; $col < 50; $col++) {
        // ...
    }
}
```

### Mengubah Tampilan
- Edit `assets/css/style.css` untuk styling
- Edit template HTML di file PHP untuk layout

### Menambah Validasi
Edit di `assets/js/script.js` fungsi `setupFormValidation()`.

## Troubleshooting

### Database Connection Error
- Periksa konfigurasi di `config/database.php`
- Pastikan MySQL service berjalan
- Periksa username/password database

### Session Issues
- Pastikan PHP session enabled
- Periksa permission direktori session
- Restart web server jika perlu

### JavaScript Errors
- Periksa console browser untuk error
- Pastikan file `assets/js/script.js` dapat diakses
- Periksa kompatibilitas browser

## Keamanan

- Input validation untuk semua form
- Prepared statements untuk query database
- Session management yang aman
- XSS protection dengan htmlspecialchars()

## Lisensi

Aplikasi ini dibuat untuk keperluan edukasi dan dapat dimodifikasi sesuai kebutuhan.

## Kontribusi

Silakan buat issue atau pull request untuk perbaikan dan penambahan fitur.