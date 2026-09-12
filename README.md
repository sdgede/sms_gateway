# 🚀 Android SMS Gateway Backend (CodeIgniter 4 + Firebase FCM)

Backend SMS Gateway modern berbasis CodeIgniter 4 yang menghubungkan aplikasi Android fisik sebagai router SMS transaksional & OTP dengan dukungan notifikasi instan **Firebase Cloud Messaging (FCM HTTP v1)** dan **Web Dashboard Monitoring Real-Time**.

---

## 🌟 Fitur Utama

- 📱 **Android Client Pairing:** Setup super mudah melalui Pairing Code 6-karakter atau scan QR Code langsung dari dashboard (tanpa perlu input API Key manual).
- 🔥 **Firebase Cloud Messaging (FCM HTTP v1):** Push notifikasi instan data-only (`KEY_MESSAGE_ID`) ke Android worker saat ada antrean SMS baru.
- 📥 **Two-Way SMS (Inbox & Outbound):** Mendukung pengiriman SMS keluar dan penerimaan SMS balasan dari customer ke server.
- 🌐 **Web Dashboard Real-Time:** Monitoring live antrean SMS, SIM Lines, status baterai & sinyal device Android, serta generator pairing code interaktif.
- 🚀 **Idempotency & Retry Engine:** Mencegah pengiriman SMS duplikat dan otomatis melakukan retry jika koneksi seluler sempat terputus.
- 🔒 **Dual-Auth Security:** Mendukung Bearer Device Token untuk Android Client dan API Key untuk aplikasi backend eksternal (Laravel, NodeJS, POS, dll.).

---

## 📚 Dokumentasi Lengkap & Panduan Integrasi

Untuk panduan lengkap arsitektur sistem, seluruh endpoint REST API Android & Server, setup Firebase HTTP v1, dan troubleshooting, silakan baca:
👉 **[DOKUMENTASI_LENGKAP.md](DOKUMENTASI_LENGKAP.md)** *(Panduan Lengkap Semua Endpoint & Fitur)*  
👉 **[ANDROID_API_DOCUMENTATION.md](ANDROID_API_DOCUMENTATION.md)** *(Spesifikasi Kontrak Android & FCM)*

---

## 🛠️ Instalasi & Setup Server

### 1. Clone & Install Dependencies
```bash
git clone https://github.com/sdgede/sms_gateway.git
cd sms_gateway
composer install --no-dev
```

### 2. Konfigurasi `.env`
Salin file `env` menjadi `.env` lalu sesuaikan konfigurasi database dan kredensial Firebase:
```ini
CI_ENVIRONMENT = production

app.baseURL = 'https://secureapi.pandemenulis.com/sms/'
app.smsApiKey = 'gw_live_sec_key_2026_ci4_prod_9981'

# Firebase Service Account
fcm.credentialsFile = 'writable/firebase/service-account.json'
```

### 3. Setup Firebase Service Account
Letakkan file kredensial JSON dari Firebase Console ke folder:
```
writable/firebase/service-account.json
```

### 4. Jalankan Migrasi Database
```bash
php spark migrate
```

### 5. Akses Web Dashboard
Buka browser ke URL backend Anda (misal: `https://secureapi.pandemenulis.com/sms/`).
