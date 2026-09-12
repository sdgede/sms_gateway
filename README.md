# Android SMS Gateway Backend (CodeIgniter 4 + Firebase FCM v1 + MySQL)

Backend SMS Gateway modern, tangguh, dan berkinerja tinggi berbasis **CodeIgniter 4** yang menghubungkan perangkat Android fisik sebagai router SMS transaksional, OTP, dan notifikasi bisnis dengan dukungan **Google Firebase Cloud Messaging (FCM HTTP v1)**, **Server-Sent Events (SSE)**, **WebSocket**, serta **Web Dashboard Monitoring Real-Time**.

---

## Fitur Utama

- **Domain-Driven Modular Architecture:** Controller terpisah rapi menjadi `Web`, `Mobile`, `Internal`, dan `Gateway`.
- **3 Pilihan Metode Dispatch ke Android:** Pilih fleksibel di `.env` antara `USE_FIREBASE`, `USE_SSE`, atau `USE_WEBSOCKET`.
- **Single Target Dispatch:** Otomatis memilih 1 perangkat SIM teraktif dan tidak melakukan broadcast redundan ke semua perangkat.
- **Android QR Code Pairing:** Setup mudah melalui Pairing Code 6-karakter atau scan QR Code langsung dari dashboard (tanpa perlu input API Key manual).
- **Dual Database Support:** Mendukung penuh **MySQL / MariaDB** (untuk skala produksi tinggi) dan **SQLite** (single-file portable).
- **Idempotency & Retry Engine:** Mencegah pengiriman SMS duplikat menggunakan `client_message_id` dan exponential backoff schedule.
- **Web Dashboard Glassmorphic & Clean:** Monitoring live antrean SMS, SIM Lines & FCM, status baterai & sinyal device Android tanpa emoji mentah (menggunakan SVG icon profesional).

---

## Dokumentasi Lengkap, ERD & Flowchart

Panduan lengkap mengenai arsitektur sistem, skema database, diagram alur, dan referensi REST API tersedia di:
- **[DOKUMENTASI_LENGKAP.md](DOKUMENTASI_LENGKAP.md)** *(Berisi Entity Relationship Diagram / ERD, Flowchart Pengiriman, Sequence Diagram, Setup MySQL, dan Referensi Semua Endpoint)*
- **[ANDROID_API_DOCUMENTATION.md](ANDROID_API_DOCUMENTATION.md)** *(Spesifikasi Kontrak Mobile Android)*
- **[ANDROID_WEBSOCKET_GUIDE.md](ANDROID_WEBSOCKET_GUIDE.md)** *(Panduan Real-Time WebSocket)*

---

## Instalasi Cepat

### 1. Clone & Install Dependencies
```bash
git clone https://github.com/sdgede/sms_gateway.git
cd sms_gateway
composer install --no-dev --optimize-autoloader
```

### 2. Salin dan Sesuaikan `.env`
```bash
cp env .env
```

Sesuaikan konfigurasi URL dan Database (MySQL):
```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://your-domain.com/'
app.smsApiKey = 'sms_secret_api_key_2026'

# 3 Metode Dispatch (Pilih yang bernilai true)
USE_FIREBASE  = true
USE_SSE       = false
USE_WEBSOCKET = false

# Database MySQL
database.default.hostname = localhost
database.default.database = sms_gateway
database.default.username = root
database.default.password = password_anda
database.default.DBDriver = MySQLi
```

### 3. Setup Firebase Service Account
Letakkan file kredensial JSON dari Google Firebase Console ke:
```text
writable/firebase/service-account.json
```

### 4. Jalankan Migrasi Database
```bash
php spark migrate
```

### 5. Akses Web Dashboard
Buka browser ke alamat domain Anda (misal: `https://your-domain.com/`).
