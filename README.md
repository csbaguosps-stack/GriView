<!-- License: by cs.baguosps@gmail.com -->
# 📍 GriView - Google Business Review Audit & Analytics Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-by%20cs.baguosps%40gmail.com-blue.svg)](mailto:cs.baguosps@gmail.com)
[![Chrome Extension](https://img.shields.io/badge/Extension-Manifest%20V3-4285F4?style=flat&logo=google-chrome&logoColor=white)](chrome-extension/)
[![Architecture](https://img.shields.io/badge/Architecture-MVC%20%2B%20JSON%20Database-green.svg)](app/)

**GriView** adalah platform audit dan analitik ulasan Google Maps berbasis web yang dilengkapi dengan **Google Chrome Extension (Manifest V3)** resmi. Dirancang untuk membantu bisnis, konsultan, dan pemilik brand dalam mengaudit, menyaring, menganalisis reputasi, serta mengekspor ulasan Google Maps ke format **Microsoft Excel (.XLS) eksekutif yang rapi dan terformat**.

Dibangun dengan arsitektur **MVC (Model-View-Controller)** murni, database portabel **JSON Flat-File (Zero Configuration)**, antarmuka responsif **Bootstrap 5.3**, visualisasi **Chart.js**, dan sistem **Auto-Kompresi Aset Gambar**.

---

## 🌟 Fitur Utama

### 1. 🔍 Audit Ulasan Google Maps via Chrome Extension (Client-Side)
- **Auto-Scroll & Scraping Cepat**: Menggulir otomatis ulasan Google Maps hingga **1.000 ulasan** secara stabil langsung dari browser pengguna.
- **Bypass Captcha & Blokir IP**: Berjalan di sisi klien (browser pengguna) yang sudah terotentikasi oleh Google, sehingga bebas dari pemblokiran IP server dan tanpa butuh API Key berbayar.
- **Deteksi Foto & Panjang Ulasan**: Otomatis mengekstrak foto kontributor, lampiran foto ulasan, menghitung jumlah kata per ulasan, serta menandai ulasan singkat.
- **Auto-Sync & Auto-Save**: Begitu proses audit selesai, ekstensi otomatis mengirim dan menyimpan seluruh data ke database web GriView serta otomatis membuka halaman hasil audit.

### 2. 📅 Filter Cerdas & Pengelolaan Review
- **Filter Berdasarkan Periode / Bulan**: Tampilkan ulasan per bulan tertentu (contoh: *Oktober 2026*, *September 2026*, dst.) lengkap dengan statistik rata-rata rating bulan tersebut.
- **Filter Rating Bintang**: Saring ulasan bintang 1 sampai 5.
- **Filter Lampiran Foto**: Tampilkan semua ulasan, hanya ulasan berfoto, atau ulasan tanpa foto.
- **Filter Sentimen**: Klasifikasi otomatis ulasan Positif (⭐⭐⭐⭐-⭐⭐⭐⭐⭐), Netral (⭐⭐⭐), dan Negatif (⭐-⭐⭐).
- **Status Balasan Owner**: Deteksi ulasan yang sudah dibalas oleh pemilik bisnis vs ulasan yang belum direspon.
- **Hapus Data Terfilter**: Opsi hapus data massal yang aman dan fleksibel berdasarkan filter cabang atau periode yang sedang aktif dipilih.

### 3. 📊 Ekspor Laporan Excel (.XLS) Eksekutif
- Format **XML Spreadsheet (.XLS)** berstandar industri yang langsung rapi saat dibuka di **Microsoft Excel**, **Google Sheets**, maupun **WPS Office**.
- **Kop Laporan Resmi**: Nama brand, cabang toko, periode tanggal, waktu ekspor, dan ringkasan eksekutif.
- **Tabel Ringkasan KPI**: Total ulasan, rating rata-rata, persentase sentimen positif/negatif, dan tingkat respon pemilik.
- **Tabel Data Rapi**: Header berlatar navy gelap, font rapi, teks ulasan *wrap-text* otomatis, badge sentimen berwarna, dan rating bintang simbolis (`★★★★★`).
- Tersedia pula opsi ekspor format **CSV** dan **JSON**.

### 4. 📈 Dashboard Analitik & Tren Reputasi (Chart.js)
- Grafik garis tren volume ulasan dan fluktuasi rata-rata rating dari waktu ke waktu.
- Diagram donat distribusi sentimen pelanggan (Positif, Netral, Kritis/Negatif).
- Diagram batang sebaran rating bintang 1 hingga 5.
- Metrik tingkat respon owner (*Response Rate*).

### 5. 🎨 Pusat Pengaturan Branding & Auto-Kompresi
- **Kustomisasi Brand Web**: Upload Logo Utama Website, Favicon browser, dan Thumbnail Banner (OpenGraph / Social Share) dengan *Live Preview*.
- **Kustomisasi Ekstensi Chrome**: Upload ikon ekstensi, ubah nama, versi, dan deskripsi ekstensi yang langsung tersinkronisasi ke `manifest.json`.
- **Auto-Kompresi Otomatis (Level 9 GD)**: Setiap gambar yang diunggah otomatis di-resize dan dikompresi ke tingkat kompresi maksimal (PNG Level 9 / Web-ready) agar web sangat cepat diakses tanpa boros bandwidth.
- **Auto-Generate & Pack ZIP**: Setiap perubahan pengaturan otomatis menyusun ulang arsip paket ekstensi `assets/griview-chrome-extension.zip`.
- **Fitur Sinkronisasi 1-Klik**: Tombol *"Samakan dari Favicon"* untuk langsung menyelaraskan ikon ekstensi dari favicon web, serta tombol *"Auto-Kompres Aset"* untuk mengompres ulang seluruh aset kapan saja.

---

## 🏛️ Struktur Direktori Proyek

```
griview/
├── index.php                          # Front Controller & Router (MVC)
├── .htaccess                          # Proteksi keamanan berkas data & MIME config
├── README.md                          # Dokumentasi resmi aplikasi
├── app/
│   ├── config/
│   │   └── config.php                 # Konfigurasi aplikasi, ROOT_DIR & auto-detect BASE_URL
│   ├── controllers/
│   │   ├── ReviewController.php       # Manajemen ulasan, filter, & ekspor XLS
│   │   ├── StoreController.php        # Manajemen cabang toko & profil bisnis
│   │   ├── AnalyticsController.php    # Visualisasi data, tren, & grafik Chart.js
│   │   ├── ExtensionController.php    # API bridge & download ZIP ekstensi
│   │   └── SettingsController.php     # Pusat branding, auto-kompresi, & upload aset
│   ├── models/
│   │   ├── Review.php                 # Logika data ulasan & word counter
│   │   ├── Store.php                  # Logika data cabang toko
│   │   └── PlaceConfig.php            # Logika konfigurasi branding & preferensi fungsi
│   ├── helpers/
│   │   ├── JsonDatabase.php           # Flat-file JSON database handler (ACID-safe)
│   │   └── ExcelHelper.php            # Generator file XLS eksekutif terformat rapi
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── header.php             # Meta tag, dynamic favicon, CSS library
│   │   │   ├── navbar.php             # Navbar responsif dengan logo kustom
│   │   │   └── footer.php             # Modal dialog, script bootstrap, & JS bridge
│   │   ├── reviews/                   # Tampilan audit ulasan (Tabel & Filter)
│   │   ├── store/                     # Tampilan manajemen cabang toko
│   │   ├── analytics/                 # Tampilan grafik & dashboard analitik
│   │   ├── extension/                 # Tampilan panduan & download ekstensi
│   │   └── settings/                  # Tampilan pusat branding & pengaturan fungsi
│   └── data/
│       ├── reviews.json               # Database penyimpanan data ulasan
│       ├── stores.json                # Database data cabang toko
│       ├── settings.json              # Database konfigurasi & branding aktif
│       └── jobs.json                  # Riwayat status job scraping
├── assets/
│   ├── css/style.css                  # Custom styling responsif & tema modern
│   ├── uploads/                       # Direktori penyimpanan logo, favicon, thumbnail
│   └── griview-chrome-extension.zip   # Paket ZIP installer ekstensi terkompresi otomatis
└── chrome-extension/
    ├── manifest.json                  # Konfigurasi Google Chrome Extension Manifest V3
    ├── icons/                         # Ikon resmi ekstensi (16px, 48px, 128px)
    ├── background.js                  # Service worker pengelola antrean audit
    ├── maps-audit.js                  # Script auto-scroll & ekstraksi ulasan Google Maps
    ├── griview-bridge.js              # Jembatan komunikasi web GriView & ekstensi
    ├── audit.html                     # Halaman ringkasan hasil audit ekstensi
    ├── audit.js                       # Logika penyimpanan & ekspor lokal ekstensi
    ├── audit.css                      # Styling antarmuka ekstensi
    ├── search.js                      # Injector tombol audit pada Google Search
    └── search.css                     # Styling tombol audit di Google Search
```

---

## 🚀 Panduan Menjalankan di Komputer Lokal (XAMPP / Windows)

1. **Letakkan Proyek di Folder Web Server**:
   Salin folder proyek ini ke `C:\xampp\htdocs\griview`.

2. **Nyalakan Apache di XAMPP**:
   - Buka **XAMPP Control Panel**.
   - Klik tombol **Start** pada modul **Apache** *(MySQL tidak wajib karena database menggunakan JSON flat-file)*.

3. **Buka Aplikasi di Browser**:
   Buka peramban browser Anda dan akses:
   ```
   http://localhost/griview/
   ```

4. **Atau Menggunakan PHP Built-in Server**:
   ```powershell
   cd C:\xampp\htdocs\griview
   C:\xampp\php\php.exe -S localhost:8080
   ```
   Lalu buka `http://localhost:8080` di browser.

---

## 🧭 Cara Memasang Ekstensi di Google Chrome

1. Buka menu **Extension Chrome** pada navbar GriView (atau buka tab **Ikon & Identitas Ekstensi** di menu Pengaturan).
2. Unduh berkas **`griview-chrome-extension.zip`** lalu ekstrak ke komputer Anda (atau langsung gunakan folder `chrome-extension/`).
3. Buka browser **Google Chrome** dan masuk ke alamat:
   ```
   chrome://extensions/
   ```
4. Aktifkan sakelar **"Developer mode" (Mode Pengembang)** di pojok kanan atas.
5. Klik tombol **"Load unpacked" (Muat yang belum dibongkar)** di pojok kiri atas, lalu pilih folder `chrome-extension`.
6. Ekstensi **GriView Review Audit** siap digunakan!

> 💡 **Tips Reload Ekstensi:** Jika Anda baru saja memperbarui ikon atau nama ekstensi di web GriView, cukup buka `chrome://extensions` lalu klik ikon **Reload (🔄)** pada kartu ekstensi untuk langsung menerapkan perubahan.

---

## 🌐 Panduan Upload ke Web Hosting (cPanel / DirectAdmin / VPS)

Aplikasi GriView sudah dirancang dengan konsep **Zero Configuration / Plug-and-Play**:

1. **Tanpa Perlu Setup MySQL**: Seluruh data tersimpan otomatis di berkas JSON (`app/data/`). Anda tidak perlu membuat database MySQL, username, atau password database.
2. **Auto-Detect Base URL**: Sistem otomatis mendeteksi protokol (`http` / `https`), domain (`domainanda.com`), subdomain (`griview.domainanda.com`), maupun subfolder tanpa perlu mengubah baris kode apa pun.
3. **Auto-Sync Ekstensi**: Saat web diakses di hosting baru, ekstensi Chrome secara otomatis menyesuaikan target koneksi ke domain hosting tersebut.

### Langkah-Langkah Deploy ke Hosting:
1. Kompres seluruh isi folder proyek (`app`, `assets`, `chrome-extension`, `index.php`, `.htaccess`) menjadi satu file `.zip`.
2. Upload berkas `.zip` ke File Manager hosting Anda (misal ke `public_html` atau subdomain).
3. Ekstrak berkas `.zip` di direktori tujuan hosting.
4. Pastikan izin akses (*permission*) folder berikut dapat ditulis (*writable*):
   - `app/data/` (Permission `755` atau `775`)
   - `assets/uploads/` (Permission `755` atau `775`)
   - `assets/` (Permission `755` atau `775`)
   - `chrome-extension/` (Permission `755` atau `775`)
5. Pastikan hosting menggunakan **PHP 8.0 ke atas** dengan ekstensi **`gd`** dan **`zip`** aktif.
6. Akses domain Anda di browser, dan GriView siap digunakan secara penuh!

---

## 📝 Catatan Rilis & Pembaruan (Changelog)

### 🚀 Versi 1.0.5
- **🎯 Dynamic Scrape Limit & Tombol Stop Fleksibel:**
  - Penambahan tombol melayang **⏹ Stop & Ambil Data ({count})** pada halaman scraping Google Maps & Search, memungkinkan pengguna menghentikan scraping kapan saja dan langsung memproses ulasan yang telah terkumpul.
  - Perbaikan bug batasan ulasan sehingga opsi filter (50, 100, 200, 500, 1.000) bekerja secara presisi tanpa tembus ke 1.000 review.
- **🔄 Pembaruan Tampilan Real-Time di Tab Awal (GriView Web):**
  - Tab awal GriView kini menampilkan status langsung (*live progress bar*, hitungan ulasan, dan pesan progres).
  - Tampilan modal di tab awal **otomatis berubah** menjadi notifikasi sukses (`Auto-Audit Selesai! 🎉`) dengan ringkasan jumlah ulasan dan status database saat proses selesai.
  - Tersedia 3 tombol aksi instan: **[Buka Halaman Audit]**, **[Download File XLS]**, dan **[Selesai & Muat Ulang]**.
  - Endpoint `checkSyncStatus` dan komunikasi *cross-tab* via `griview-bridge.js` memastikan status selalu tersinkronisasi 100%.
- **🧹 Penyederhanaan Kolom Aksi & Pembersihan Fitur Balas Ulasan:**
  - Menghapus tombol dan modal tanggapi/balas review manual yang sebelumnya hanya tersimpan lokal dan tidak dapat otomatis terbit ke Google Maps.
  - Kolom aksi tabel audit difokuskan secara bersih untuk manajemen hapus ulasan.
- **📦 Pembaruan Paket Chrome Extension:**
  - Sinkronisasi versi ekstensi ke **v1.0.5** pada `manifest.json`, `griview-bridge.js`, dan arsip `assets/griview-chrome-extension.zip`.

---

## 📜 Lisensi & Hak Cipta

```text
License: by cs.baguosps@gmail.com
Hak Cipta (c) 2026 GriView Project. All rights reserved.
Kontak & Dukungan: cs.baguosps@gmail.com
```

---
*Dibuat dengan dedikasi untuk efisiensi audit reputasi bisnis dan kecerdasan ulasan digital.*
