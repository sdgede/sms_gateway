# 📱 SMS Gateway Backend — Dokumentasi Lengkap & Panduan Integrasi

Dokumentasi komprehensif untuk sistem **SMS Gateway Backend** berbasis **CodeIgniter 4**, **Firebase Cloud Messaging (FCM HTTP v1)**, dan aplikasi **Android Gateway Client** (`com.sevanam.androidsmsgateway` / `com.httpsms`) serta integrasi REST API untuk aplikasi pihak ketiga (Laravel, NodeJS, POS, E-commerce, Python, dll.).

---

## 📑 Daftar Isi

1. [Arsitektur Sistem & Alur Kerja](#1-arsitektur-sistem--alur-kerja)
2. [Spesifikasi Teknis & Persyaratan Server](#2-spesifikasi-teknis--persyaratan-server)
3. [Panduan Instalasi & Setup Server](#3-panduan-instalasi--setup-server)
4. [Konfigurasi Firebase Cloud Messaging (FCM HTTP v1)](#4-konfigurasi-firebase-cloud-messaging-fcm-http-v1)
5. [Alur Pairing Android (Mode Single SIM: 1 Provider & 1 Nomor)](#5-alur-pairing-android-mode-single-sim-1-provider--1-nomor)
6. [Dokumentasi Lengkap REST API Mobile Android](#6-dokumentasi-lengkap-rest-api-mobile-android)
   - [A. Pairing Device (`POST /api/v1/gateway/pair`)](#a-pairing-device-post-apiv1gatewaypair)
   - [B. Registrasi Token FCM (`PUT /api/v1/phones/fcm-token`)](#b-registrasi-token-fcm-put-apiv1phonesfcm-token)
   - [C. Ambil Pesan untuk Dikirim (`GET /api/v1/messages/outstanding`)](#c-ambil-pesan-untuk-dikirim-get-apiv1messagesoutstanding)
   - [D. Laporan Status Pengiriman (`POST /api/v1/messages/{id}/events`)](#d-laporan-status-pengiriman-post-apiv1messagesidevents)
   - [E. Laporan Heartbeat / Baterai / Sinyal (`POST /api/v1/heartbeats`)](#e-laporan-heartbeat--baterai--sinyal-post-apiv1heartbeats)
   - [F. Penerimaan SMS Masuk / Inbox (`POST /api/v1/messages/receive`)](#f-penerimaan-sms-masuk--inbox-post-apiv1messagesreceive)
   - [G. Real-Time SSE Stream Job (`GET /api/v1/gateway/jobs/stream`)](#g-real-time-sse-stream-job-get-apiv1gatewayjobsstream)
7. [Dokumentasi REST API untuk Server / Aplikasi Luar](#7-dokumentasi-rest-api-untuk-server--aplikasi-luar)
   - [A. Kirim SMS Keluar (`POST /api/v1/sms/send`)](#a-kirim-sms-keluar-post-apiv1smssend)
   - [B. Cek Status Pengiriman (`GET /api/v1/sms/status/{id}`)](#b-cek-status-pengiriman-get-apiv1smsstatusid)
   - [C. Statistik SMS (`GET /api/v1/sms/statistics`)](#c-statistik-sms-get-apiv1smsstatistics)
   - [D. Generate Pairing Code via API (`POST /api/v1/admin/pairing/generate`)](#d-generate-pairing-code-via-api-post-apiv1adminpairinggenerate)
8. [Web Dashboard & Monitoring Interaktif](#8-web-dashboard--monitoring-interaktif)
9. [Panduan Logging & Troubleshooting](#9-panduan-logging--troubleshooting)

---

## 1. Arsitektur Sistem & Alur Kerja

Sistem ini bekerja menghubungkan aplikasi backend eksternal, web dashboard, dan HP Android yang bertindak sebagai router pengirim SMS pulsa seluler.

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Web Dashboard / Server Eksternal
    participant Server as Backend SMS Gateway (CI4)
    participant FCM as Google Firebase (HTTP v1)
    participant Android as HP Android Gateway

    %% Tahap 1: Pairing
    Note over Admin,Android: 1. Tahap Pairing (Sekali saat registrasi HP)
    Admin->>Server: Generate Pairing Code / Scan QR Code
    Android->>Server: POST /api/v1/gateway/pair (Pairing Code + FCM Token + No HP)
    Server-->>Android: 200 OK (Device Paired, Single SIM1 Activated)

    %% Tahap 2: Kirim SMS
    Note over Admin,Android: 2. Tahap Pengiriman SMS Transaksional / OTP
    Admin->>Server: POST /api/v1/sms/send (Nomor HP + Pesan)
    Server->>Server: Simpan SMS ke Queue (Status: PENDING)
    Server->>FCM: POST /v1/projects/{project}/messages:send (Data: KEY_MESSAGE_ID)
    FCM-->>Android: Push Data Notification (Silent Wakeup)
    Android->>Server: GET /api/v1/messages/outstanding?message_id=SMS-XXXX
    Server-->>Android: 200 OK (Nomor Penerima & Isi Pesan)
    Android->>Android: Kirim SMS via SmsManager (Pulsa SIM1)
    Android->>Server: POST /api/v1/messages/SMS-XXXX/events (SENT / DELIVERED)
    Server->>Server: Update status SMS menjadi SENT / DELIVERED

    %% Tahap 3: Heartbeat
    Note over Android,Server: 3. Pemantauan Kondisi HP
    Android->>Server: POST /api/v1/heartbeats (Baterai, Charging, Sinyal)
    Server-->>Android: 200 OK
```

---

## 2. Spesifikasi Teknis & Persyaratan Server

| Komponen | Spesifikasi / Kebutuhan |
|---|---|
| **Framework** | CodeIgniter 4.4+ (PHP 8.1 / 8.2 / 8.3 / 8.4) |
| **Database** | SQLite 3 (Default, single-file di `writable/sms_gateway.db`) atau MySQL / MariaDB |
| **Protokol Push** | Google Firebase Cloud Messaging **HTTP v1 API** (Otentikasi OAuth2 RS256) |
| **Web Server** | Apache / LiteSpeed / Nginx dengan mod_rewrite aktif |
| **Keamanan Auth** | Token Pairing Device (Bearer Token), Global API Key (`x-api-key`), dan Device ID Filtering |
| **Format Data** | JSON Payload dengan Standar Envelope: `{ "status": "...", "data": ..., "message": "..." }` |
| **Kebijakan SIM** | **Strict Single SIM Mode** (1 provider & 1 nomor HP aktif per perangkat HP) |

---

## 3. Panduan Instalasi & Setup Server

### 1. Clone Repository & Install Dependencies
```bash
git clone https://github.com/sdgede/sms_gateway.git
cd sms_gateway
composer install --no-dev --optimize-autoloader
```

### 2. Salin dan Sesuaikan Konfigurasi `.env`
Salin file `env` menjadi `.env`:
```bash
cp env .env
```

Buka dan sesuaikan isi file `.env`:
```ini
# Environment
CI_ENVIRONMENT = production

# URL Aplikasi (Sesuaikan domain & subfolder jika ada)
app.baseURL = 'https://secureapi.pandemenulis.com/sms/'

# Global API Key untuk Server Eksternal (Laravel, NodeJS, dll)
app.smsApiKey = 'sms_secret_api_key_2026'

#--------------------------------------------------------------------
# 3 METODE DISPATCH KE ANDROID (PILIH METODE DENGAN TRUE / FALSE)
#--------------------------------------------------------------------
USE_FIREBASE  = true    # Metode 1: Google Firebase Cloud Messaging (FCM HTTP v1)
USE_SSE       = false   # Metode 2: Server-Sent Events (SSE Stream via HTTP)
USE_WEBSOCKET = false   # Metode 3: WebSocket Real-Time Daemon

# Lokasi File Kredensial Firebase Service Account (Jika USE_FIREBASE = true)
fcm.credentialsFile = 'writable/firebase/service-account.json'

# Konfigurasi Database SQLite (Otomatis absolut ke folder writable/)
database.default.DBDriver = SQLite3
database.default.database = WRITEPATH . 'sms_gateway.db'
```

### 3. Izin Folder (Permissions)
Pastikan folder `writable` dapat dibaca dan ditulis oleh web server:
```bash
chmod -R 775 writable
```

### 4. Jalankan Migrasi Database
```bash
php spark migrate
```

---

## 4. Tiga (3) Metode Dispatch ke Android (`.env`)

Sistem menyediakan **3 pilihan metode komunikasi** untuk memicu pengiriman SMS ke HP Android secara instan:

| Metode | Pengaturan di `.env` | Cara Kerja | Keunggulan |
|---|---|---|---|
| **1. Firebase FCM (HTTP v1)** *(Default)* | `USE_FIREBASE = true` | Server mengirim push silent data ke Android (`KEY_MESSAGE_ID`), Android bangun dan mengambil SMS via REST API. | Paling hemat baterai di Android, HP bisa standby/layar mati, tidak butuh port khusus. |
| **2. Server-Sent Events (SSE)** | `USE_SSE = true` | Android terhubung ke stream HTTP persistent (`GET /api/v1/gateway/jobs/stream`). Setiap ada SMS baru langsung di-push. | Tanpa dependensi Firebase / Google Play Service, berjalan di port HTTP(S) standar web server. |
| **3. WebSocket Daemon** | `USE_WEBSOCKET = true` | Android terhubung ke server WebSocket real-time (`ws://...`). | Latensi instan milidetik, koneksi full-duplex dua arah. |

> [!TIP]
> Anda cukup mengeset `true` pada metode yang ingin digunakan di `.env`. Jika ingin mengaktifkan lebih dari 1 metode sekaligus (misal: Firebase + SSE), Anda cukup mengeset keduanya bernilai `true`.


---

## 4. Konfigurasi Firebase Cloud Messaging (FCM HTTP v1)

> [!IMPORTANT]
> Google telah **menonaktifkan FCM Legacy API** (`fcm.googleapis.com/fcm/send`). Sistem SMS Gateway ini menggunakan standar resmi **Firebase HTTP v1 API** menggunakan Private Key Service Account.

### Langkah Setup Firebase:
1. Masuk ke [Firebase Console](https://console.firebase.google.com/) dan buka project Anda.
2. Klik ikon ⚙️ **Project Settings** (di kiri atas) → pilih tab **Service accounts**.
3. Klik tombol **Generate new private key** → Simpan file `.json` yang terunduh.
4. Terapkan kredensial tersebut ke server dengan salah satu cara berikut:

#### Opsi A: Upload File (Direkomendasikan)
Upload file `.json` tersebut ke server pada path:
```
writable/firebase/service-account.json
```

#### Opsi B: Inject String JSON ke `.env`
Buka file `.env` di server dan paste string JSON (atau base64-nya) secara langsung:
```ini
fcm.credentialsJson = '{"type":"service_account","project_id":"project-id-anda","private_key":"-----BEGIN PRIVATE KEY-----\nMIIE...","client_email":"firebase-adminsdk@project-id-anda.iam.gserviceaccount.com"}'
```

*Verifikasi:* Buka dashboard web `https://domain-anda.com/sms/`. Widget status **FCM Service** akan berubah menjadi **Active (HTTP_V1)**.

---

## 5. Alur Pairing Android (Mode Single SIM: 1 Provider & 1 Nomor)

### Kebijakan Single SIM:
Sistem beroperasi dalam mode **Single SIM** (`SIM1`). Setiap device gateway hanya boleh memiliki **1 provider aktif dan 1 nomor telepon**.

### Cara Melakukan Pairing:
1. Buka Web Dashboard di browser: `https://domain-anda.com/sms/`.
2. Masukkan nama perangkat pada form **Device Pairing** (contoh: `Redmi Note 10 Gateway`) lalu klik **Generate Pairing Code & QR**.
3. Muncul modal QR Code dan 6 karakter kode pairing (contoh: `A8C2E1`).
4. Buka aplikasi Android:
   - **Opsi 1 (Scan QR):** Scan QR Code di layar dashboard.
   - **Opsi 2 (Ketik Manual):** Masukkan kode 6 karakter ke aplikasi.
5. HP Android akan otomatis mengirim data SIM, nomor HP, dan token FCM ke server tanpa memerlukan API Key di header.

---

## 6. Dokumentasi Lengkap REST API Mobile Android

Semua endpoint mobile dapat diakses pada prefix `/api/v1/...` maupun alias `/v1/...`.  
Otentikasi mobile diverifikasi secara otomatis melalui `device_id`, token pairing, atau `X-Client-Version`.

---

### A. Pairing Device (`POST /api/v1/gateway/pair`)
Dipanggil saat HP Android melakukan scan QR Code atau submit kode pairing.

- **Method & URL:** `POST /api/v1/gateway/pair` (atau `POST /gateway/pair`)
- **Headers:** `Content-Type: application/json`
- **Request Body:**
  ```json
  {
    "pairing_code": "A8C2E1",
    "fcm_token": "fcm_token_panjang_dari_firebase...",
    "phone_number": "+6282147836034",
    "sim": "SIM1",
    "sim_operator": "TELKOMSEL",
    "device_name": "Xiaomi Gateway",
    "device_model": "Redmi Note 10",
    "app_version": "1.0.0"
  }
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "status": "success",
    "message": "Device successfully paired to SMS Gateway (Single SIM Mode).",
    "data": {
      "device_id": "redmi_note_10-a1b2c3",
      "device_name": "Xiaomi Gateway",
      "token": "gw_tok_3f9c8d2a1b7e405f6a8b9c0d1e2f3a4b",
      "device_token": "gw_tok_3f9c8d2a1b7e405f6a8b9c0d1e2f3a4b",
      "phone_number": "+6282147836034",
      "sim": "SIM1",
      "sim_operator": "TELKOMSEL",
      "fcm_registered": true,
      "server_time": "2026-09-12 09:30:00"
    }
  }
  ```

---

### B. Registrasi Token FCM (`PUT /api/v1/phones/fcm-token`)
Dipanggil saat login per SIM atau ketika token FCM disegarkan (`onNewToken`).

- **Method & URL:** `PUT /api/v1/phones/fcm-token` (atau `PUT /v1/phones/fcm-token`)
- **Headers:** `Content-Type: application/json`
- **Request Body:**
  ```json
  {
    "fcm_token": "eKz9_token_fcm_terbaru_dari_google...",
    "phone_number": "+6282147836034",
    "sim": "SIM1"
  }
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "status": "success",
    "message": "ok",
    "data": {
      "id": "phone_a1b2c3d4e5f6",
      "user_id": "usr_default_admin"
    }
  }
  ```

---

### C. Ambil Pesan untuk Dikirim (`GET /api/v1/messages/outstanding`)
Dipanggil oleh background worker Android setelah menerima notifikasi push FCM data `KEY_MESSAGE_ID`.

- **Method & URL:** `GET /api/v1/messages/outstanding?message_id=SMS-20260912-A1B2C3`
- **Response Sukses (200 OK):**
  ```json
  {
    "status": "success",
    "message": "ok",
    "data": {
      "id": "SMS-20260912-A1B2C3",
      "contact": "+6281234567890",
      "content": "Kode OTP verifikasi akun Anda adalah 948201. Berlaku 5 menit.",
      "sim": "SIM1",
      "owner": "+6282147836034",
      "encrypted": false,
      "status": "outstanding",
      "type": "sms",
      "created_at": "2026-09-12T09:30:00.000000Z",
      "order_timestamp": "2026-09-12T09:30:00.000000Z",
      "request_received_at": "2026-09-12T09:30:00.000000Z",
      "updated_at": "2026-09-12T09:30:00.000000Z",
      "failure_reason": null,
      "last_attempted_at": null,
      "received_at": null,
      "sent_at": null,
      "send_time": null,
      "attachments": []
    }
  }
  ```

---

### D. Laporan Status Pengiriman (`POST /api/v1/messages/{id}/events`)
Dipanggil oleh Android setelah SMS sukses diserahkan ke jaringan seluler atau gagal kirim.

- **Method & URL:** `POST /api/v1/messages/{messageId}/events`
- **Request Body (Jika Sukses Terkirim / Handed to network):**
  ```json
  {
    "event_name": "SENT",
    "timestamp": "2026-09-12T09:30:05.000000Z"
  }
  ```
- **Request Body (Jika Laporan Delivery / Handset received):**
  ```json
  {
    "event_name": "DELIVERED",
    "timestamp": "2026-09-12T09:30:10.000000Z"
  }
  ```
- **Request Body (Jika Gagal / Pulsa Habis / No Signal):**
  ```json
  {
    "event_name": "FAILED",
    "reason": "RESULT_ERROR_GENERIC_FAILURE: Pulsa tidak mencukupi",
    "timestamp": "2026-09-12T09:30:05.000000Z"
  }
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "status": "success",
    "message": "ok",
    "data": null
  }
  ```

---

### E. Laporan Heartbeat / Baterai / Sinyal (`POST /api/v1/heartbeats`)
Dikirim secara periodik oleh aplikasi Android untuk memantau konektivitas, daya baterai, dan operator.

- **Method & URL:** `POST /api/v1/heartbeats`
- **Headers:** `X-Client-Version: 1.0.0`
- **Request Body:**
  ```json
  {
    "device_id": "700d6d4f-7725-4f2c-b0a9-bbe41ec4b4b1",
    "app_version": "1.0.0",
    "timestamp": 1789117867,
    "sms_permission": true,
    "battery_optimization_disabled": true,
    "battery_level": 98,
    "is_charging": true,
    "active_subscription_id": 1,
    "sim_carrier": "TELKOMSEL",
    "network_type": "CELLULAR",
    "phone_numbers": ["+6282147836034"]
  }
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "status": "success",
    "message": "ok",
    "data": null
  }
  ```

---

### F. Penerimaan SMS Masuk / Inbox (`POST /api/v1/messages/receive`)
Dipanggil saat nomor HP gateway menerima SMS baru dari customer.

- **Method & URL:** `POST /api/v1/messages/receive`
- **Request Body:**
  ```json
  {
    "from": "+6281299887766",
    "to": "+6282147836034",
    "content": "Halo, saya sudah transfer pembayaran pesanan #ORD-9981.",
    "sim": "SIM1",
    "timestamp": "2026-09-12 09:35:00",
    "encrypted": false
  }
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "status": "success",
    "message": "ok",
    "data": null
  }
  ```

---

### G. Real-Time SSE Stream Job (`GET /api/v1/gateway/jobs/stream`)
Koneksi persistent Server-Sent Events (SSE) jika Android ingin menerima stream job instan secara langsung tanpa menunggu polling.

- **Method & URL:** `GET /api/v1/gateway/jobs/stream`
- **Headers:** `Authorization: Bearer <DEVICE_TOKEN>`
- **Event Stream Output:**
  ```text
  event: connected
  data: {"status":"ONLINE","device_id":"redmi-10"}

  event: new_sms_job
  data: {"job_id":"SMS-20260912-A1B2C3","recipient":"+6281234567890","message":"OTP: 123456"}
  ```

---

## 7. Dokumentasi REST API untuk Server / Aplikasi Luar

Gunakan API Key (`x-api-key`) yang tertera di file `.env` server Anda untuk mengirimkan request dari Laravel, Express.js, Golang, Python, atau script cron.

---

### A. Kirim SMS Keluar (`POST /api/v1/sms/send`)
Memasukkan pesan SMS ke dalam antrean pengiriman dan langsung mentrigger notifikasi FCM ke HP Android.

- **Method & URL:** `POST /api/v1/sms/send`
- **Headers:**
  ```http
  Content-Type: application/json
  x-api-key: <app.smsApiKey>
  ```
- **Request Body:**
  ```json
  {
    "recipient": "+6281234567890",
    "message": "Halo Bpk/Ibu, pesanan Anda #INV-1029 telah dikirimkan via JNE (Resi: 01293819283).",
    "priority": 1,
    "client_message_id": "INV-1029-NOTIF",
    "max_attempt": 3
  }
  ```
- **Parameter Penjelasan:**
  - `recipient` *(Wajib)*: Nomor HP tujuan (format `08...`, `628...`, atau `+628...`).
  - `message` *(Wajib)*: Teks isi SMS.
  - `priority` *(Opsional)*: `1` (High / OTP), `2` (Medium / Transaksional), `3` (Low / Promosi). Default: `2`.
  - `client_message_id` *(Opsional)*: Unique ID dari sistem Anda untuk idempotency (mencegah double kirim).
  - `max_attempt` *(Opsional)*: Maksimal percobaan ulang jika gagal (default: `3`).

- **Response Sukses (202 Accepted / 200 OK jika replay):**
  ```json
  {
    "status": "success",
    "message": "SMS job queued successfully",
    "data": {
      "job_id": "SMS-20260912093000-8A9B0C",
      "client_message_id": "INV-1029-NOTIF",
      "recipient": "+6281234567890",
      "status": "PENDING",
      "priority": 1,
      "attempt": 0,
      "is_replay": false,
      "available_at": "2026-09-12 09:30:00",
      "created_at": "2026-09-12 09:30:00"
    }
  }
  ```

#### Contoh Integrasi PHP / cURL:
```php
<?php
$curl = curl_init();

$payload = [
    'recipient'         => '+6281234567890',
    'message'           => 'Kode OTP Anda adalah 839201. Rahasiakan kode ini.',
    'priority'          => 1,
    'client_message_id' => 'OTP-' . time(),
];

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://secureapi.pandemenulis.com/sms/api/v1/sms/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: sms_secret_api_key_2026',
    ],
]);

$response = curl_exec($curl);
curl_close($curl);

echo $response;
```

---

### B. Cek Status Pengiriman (`GET /api/v1/sms/status/{id}`)
Mengecek status live pengiriman pesan berdasarkan `job_id` atau `client_message_id`.

- **Method & URL:** `GET /api/v1/sms/status/{job_id_atau_client_message_id}`
- **Headers:** `x-api-key: <app.smsApiKey>`
- **Response Sukses (200 OK):**
  ```json
  {
    "status": "success",
    "data": {
      "job_id": "SMS-20260912093000-8A9B0C",
      "client_message_id": "INV-1029-NOTIF",
      "recipient": "+6281234567890",
      "message": "Halo Bpk/Ibu, pesanan Anda #INV-1029 telah dikirimkan...",
      "status": "DELIVERED",
      "priority": 1,
      "attempt": 1,
      "max_attempt": 3,
      "assigned_device_id": "dev_6282147836034",
      "sent_at": "2026-09-12 09:30:04",
      "delivered_at": "2026-09-12 09:30:08",
      "failed_reason": null,
      "created_at": "2026-09-12 09:30:00",
      "updated_at": "2026-09-12 09:30:08",
      "delivery_reports": [
        {
          "status": "DELIVERED",
          "reported_at": "2026-09-12 09:30:08"
        },
        {
          "status": "SENT",
          "reported_at": "2026-09-12 09:30:04"
        }
      ]
    }
  }
  ```

---

### C. Statistik SMS (`GET /api/v1/sms/statistics`)
Mengambil rekap total SMS (Total, Pending, Sending, Sent, Delivered, Failed).

- **Method & URL:** `GET /api/v1/sms/statistics`
- **Headers:** `x-api-key: <app.smsApiKey>`
- **Response Sukses (200 OK):**
  ```json
  {
    "status": "success",
    "data": {
      "total": 1250,
      "pending": 2,
      "claimed": 0,
      "sending": 1,
      "sent": 210,
      "delivered": 1020,
      "failed": 12,
      "retry": 5,
      "failed_permanent": 0
    }
  }
  ```

---

### D. Generate Pairing Code via API (`POST /api/v1/admin/pairing/generate`)
Membuat kode pairing secara programmatik dari sistem admin lain.

- **Method & URL:** `POST /api/v1/admin/pairing/generate`
- **Headers:**
  ```http
  Content-Type: application/json
  x-api-key: <app.smsApiKey>
  ```
- **Request Body:**
  ```json
  {
    "device_name": "Gateway Gudang Jakarta",
    "expiry_minutes": 15
  }
  ```
- **Response Sukses (201 Created):**
  ```json
  {
    "status": "success",
    "message": "Pairing code generated successfully",
    "data": {
      "code": "C7E9A2",
      "device_name": "Gateway Gudang Jakarta",
      "expires_at": "2026-09-12 09:45:00"
    }
  }
  ```

---

## 8. Web Dashboard & Monitoring Interaktif

Dashboard interaktif dapat diakses langsung melalui browser di:
👉 `https://domain-anda.com/sms/`

### Fitur Dashboard:
1. **Live Analytics Cards:** Total Dispatched, Success Delivered, Pending Queue, Gateway Active, dan status Firebase FCM.
2. **Device Pairing Modal (QR Code & 6-Digit Code):** Memudahkan onboarding HP Android baru dengan scan instan.
3. **Send Test SMS Panel:** Menguji kirim SMS langsung dari browser dengan pengaturan nomor HP, pesan, dan prioritas.
4. **SMS Dispatch Queue Table:** Memantau status real-time setiap SMS. Dilengkapi tombol **Requeue (Reset)** untuk kirim ulang pesan yang tertunda/gagal dan tombol **Hapus**.
5. **Gateway Devices & Phone Lines:** Melihat status online/offline HP, level baterai, status charging, nomor SIM, operator, dan token FCM.
6. **SMS Inbox (Two-Way):** Membaca seluruh SMS balasan yang masuk ke nomor HP gateway.

---

## 9. Panduan Logging & Troubleshooting

### Lokasi File Log:
Seluruh aktivitas sistem dicatat pada folder:
```bash
writable/logs/log-YYYY-MM-DD.log
```

### 1. Memantau Log Secara Real-Time (Live Tail):
Jalankan perintah berikut di terminal server:
```bash
tail -f writable/logs/log-$(date +%Y-%m-%d).log
```

### 2. Membaca 100 Baris Log Terakhir:
```bash
tail -n 100 writable/logs/log-$(date +%Y-%m-%d).log
```

### 3. Masalah Umum & Solusi:

| Gejala Error | Penyebab | Solusi |
|---|---|---|
| `401 UNAUTHORIZED_API_CLIENT` saat Android kirim heartbeat | Rute terhalang filter API key server | Sudah teratasi di versi terbaru; jalankan `git pull origin main`. |
| `[FCM Legacy] FAILED HTTP 404` | Google telah mematikan endpoint FCM legacy | Upload file `service-account.json` (FCM HTTP v1) ke `writable/firebase/service-account.json`. |
| `[DEPRECATED] Passing lowercase HTTP method` | Method `match()` CI4 menggunakan huruf kecil | Sudah teratasi menggunakan uppercase `['GET', 'POST']`; jalankan `git pull origin main`. |
| Pesan status `PENDING` tidak terkirim ke HP | Token FCM HP belum masuk atau Firebase belum diset | Cek tabel *Phone Lines* di dashboard, pastikan token FCM HP terdaftar dan status FCM `Active (HTTP_V1)`. |
| `Unable to prepare statement: no such table` | Database SQLite baru belum dimigrasi | Jalankan `php spark migrate` di terminal server. |
