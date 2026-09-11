# 📱 SMS Gateway Backend — Dokumentasi API & Integrasi Android

Dokumentasi lengkap integrasi backend CodeIgniter 4 SMS Gateway dengan aplikasi Android (`com.httpsms`), Firebase Cloud Messaging (FCM), dan sistem backend eksternal (REST API).

---

## 📑 Daftar Isi
1. [Konfigurasi Umum & Base URL](#1-konfigurasi-umum--base-url)
2. [Alur Autentikasi (Pairing vs API Key)](#2-alur-autentikasi-pairing-vs-api-key)
3. [Alur Pairing HP Android (Tanpa API Key)](#3-alur-pairing-hp-android-tanpa-api-key)
4. [Endpoint Kontrak Android & FCM (`/v1/...`)](#4-endpoint-kontrak-android--fcm-v1)
   - [A. Registrasi SIM Line & Simpan Token FCM](#a-registrasi-sim-line--simpan-token-fcm)
   - [B. Ambil Detail SMS untuk Dikirim (Outstanding)](#b-ambil-detail-sms-untuk-dikirim-outstanding)
   - [C. Laporan Status Pengiriman Pesan (Delivery Event)](#c-laporan-status-pengiriman-pesan-delivery-event)
   - [D. Laporan Heartbeat / Baterai / Sinyal](#d-laporan-heartbeat--baterai--sinyal)
   - [E. Teruskan SMS Masuk ke Server (Inbox)](#e-teruskan-sms-masuk-ke-server-inbox)
5. [Firebase Cloud Messaging (FCM Server → Android)](#5-firebase-cloud-messaging-fcm-server--android)
6. [Endpoint REST API untuk Sistem Luar (Kirim SMS via REST API)](#6-endpoint-rest-api-untuk-sistem-luar-kirim-sms-via-rest-api)
7. [Petunjuk Setup Firebase Service Account](#7-petunjuk-setup-firebase-service-account)

---

## 1. Konfigurasi Umum & Base URL

| Item | Nilai |
|---|---|
| **Base URL Production** | `https://secureapi.pandemenulis.com/sms` |
| **Base URL Local Testing** | `http://localhost:8080` |
| **Web Dashboard** | `https://secureapi.pandemenulis.com/sms/` |
| **Format Envelope Response** | `{ "data": ..., "message": "...", "status": "..." }` |
| **Format Timestamp** | ISO-8601 UTC dengan mikrodetik: `yyyy-MM-dd'T'HH:mm:ss.000000Z` (Contoh: `2026-09-11T08:15:00.000000Z`) |

---

## 2. Alur Autentikasi (Pairing vs API Key)

Terdapat 2 jenis pengguna API:

### A. Aplikasi Android Gateway
- **Saat Pairing (Pertama kali):** **TIDAK PERLU API KEY di header**. Cukup kirim `pairing_code` (atau scan QR Code) ke endpoint `POST /api/v1/gateway/pair`.
- **Setelah Pairing Berhasil:** Server mengembalikan `token` (Device Token). Android menyimpan token ini dan menggunakannya di semua request berikutnya via header:
  ```http
  Authorization: Bearer <DEVICE_TOKEN_DARI_PAIRING>
  ```
  *(Atau menggunakan `x-api-key: <DEVICE_TOKEN_DARI_PAIRING>`)*. Backend otomatis mengenali token device hasil pairing.

### B. Backend / Website Lain (Kirim SMS Keluar)
- Website Anda (Laravel, NodeJS, POS, Toko Online) menggunakan Global API Key yang dikonfigurasi di `.env`:
  ```http
  x-api-key: <app.smsApiKey>
  ```

---

## 3. Alur Pairing HP Android (Mode Single SIM: 1 Provider & 1 Nomor HP)

> [!IMPORTANT]
> **Kebijakan Single SIM:** Sistem SMS Gateway ini beroperasi dalam mode **Single SIM**. Setiap perangkat HP Android hanya boleh mendaftarkan **1 nomor HP dan 1 provider operator seluler** (`SIM1`).

### **`POST /api/v1/gateway/pair`**
Dipanggil saat HP Android melakukan scan QR Code atau memasukkan 6 karakter pairing code dari dashboard.

- **Headers:**
  ```http
  Content-Type: application/json
  ```
  *(Catatan: Tidak butuh Authorization / x-api-key).*

- **Request Body:**
  ```json
  {
    "pairing_code": "A8C2E1",
    "fcm_token": "fcm_token_panjang_dari_firebase...",
    "phone_number": "+6281234567890",
    "sim": "SIM1",
    "sim_operator": "Telkomsel",
    "device_name": "Xiaomi Redmi Note 10 Gateway",
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
      "device_name": "Xiaomi Redmi Note 10 Gateway",
      "token": "gw_token_3f9c8d2a1b7e405f6a8b9c0d1e2f3a4b",
      "device_token": "gw_token_3f9c8d2a1b7e405f6a8b9c0d1e2f3a4b",
      "phone_number": "+6281234567890",
      "sim": "SIM1",
      "sim_operator": "Telkomsel",
      "fcm_registered": true,
      "server_time": "2026-09-11 08:58:00"
    }
  }
  ```

---

## 4. Endpoint Kontrak Android & FCM (`/v1/...`)

Semua endpoint di bawah dapat diakses menggunakan header `Authorization: Bearer <DEVICE_TOKEN>` atau `x-api-key: <API_KEY/DEVICE_TOKEN>`.

### A. Registrasi SIM Line & Simpan Token FCM
**`PUT /v1/phones/fcm-token`**  
Dipanggil saat login per SIM atau ketika token FCM diperbarui (`onNewToken`).

- **Headers:**
  ```http
  Authorization: Bearer <DEVICE_TOKEN>
  Content-Type: application/json
  ```
- **Request Body:**
  ```json
  {
    "fcm_token": "eKz9_token_fcm_dari_google...",
    "phone_number": "+6281234567890",
    "sim": "SIM1"
  }
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "data": {
      "id": "phone_a1b2c3d4e5f6",
      "user_id": "usr_default_admin"
    },
    "message": "ok",
    "status": "success"
  }
  ```

---

### B. Ambil Detail SMS untuk Dikirim (Outstanding)
**`GET /v1/messages/outstanding?message_id={message_id}`**  
Dipanggil oleh Android Background Worker segera setelah menerima notifikasi push FCM dengan `KEY_MESSAGE_ID`.

- **Headers:**
  ```http
  Authorization: Bearer <DEVICE_TOKEN>
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "data": {
      "id": "SMS-20260911081500-A1B2C3",
      "contact": "+6281234567890",
      "content": "Kode verifikasi OTP Anda adalah 839201. Berlaku 5 menit.",
      "sim": "SIM1",
      "owner": "+6281234567890",
      "encrypted": false,
      "status": "outstanding",
      "type": "sms",
      "created_at": "2026-09-11T08:15:00.000000Z",
      "order_timestamp": "2026-09-11T08:15:00.000000Z",
      "request_received_at": "2026-09-11T08:15:00.000000Z",
      "updated_at": "2026-09-11T08:15:00.000000Z",
      "failure_reason": null,
      "last_attempted_at": null,
      "received_at": null,
      "sent_at": null,
      "send_time": null,
      "attachments": []
    },
    "message": "ok",
    "status": "success"
  }
  ```

---

### C. Laporan Status Pengiriman Pesan (Delivery Event)
**`POST /v1/messages/{messageId}/events`**  
Dipanggil Android setelah SMS berhasil atau gagal dikirim melalui jaringan seluler pulsa SIM.

- **Request Body (Jika Berhasil Terkirim):**
  ```json
  {
    "event_name": "SENT",
    "timestamp": "2026-09-11T08:15:10.000000Z"
  }
  ```
- **Request Body (Jika Telah Diterima Penerima / Delivered):**
  ```json
  {
    "event_name": "DELIVERED",
    "timestamp": "2026-09-11T08:15:15.000000Z"
  }
  ```
- **Request Body (Jika Gagal Kirim):**
  ```json
  {
    "event_name": "FAILED",
    "reason": "GENERIC_FAILURE: Pulsa habis atau no signal",
    "timestamp": "2026-09-11T08:15:10.000000Z"
  }
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "data": { "recorded": true },
    "message": "ok",
    "status": "success"
  }
  ```

---

### D. Laporan Heartbeat / Baterai / Sinyal
**`POST /v1/heartbeats`**  
Dikirim secara periodik oleh aplikasi Android untuk memantau status daya baterai dan sinyal.

- **Request Body:**
  ```json
  {
    "battery_level": 92,
    "battery_status": "CHARGING",
    "signal_level": 4,
    "sim": "SIM1",
    "timestamp": "2026-09-11T08:15:00.000000Z"
  }
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "data": { "acknowledged": true },
    "message": "ok",
    "status": "success"
  }
  ```

---

### E. Teruskan SMS Masuk ke Server (Inbox)
**`POST /v1/messages/receive`**  
Dipanggil BroadcastReceiver Android ketika nomor HP gateway menerima SMS baru dari luar.

- **Request Body:**
  ```json
  {
    "sender": "+6287712345678",
    "message": "Halo, ini balasan SMS konfirmasi pembayaran dari customer.",
    "sim": "SIM1",
    "received_at": "2026-09-11T08:15:00.000000Z"
  }
  ```
- **Response Sukses (200 OK):**
  ```json
  {
    "data": { "stored": true },
    "message": "ok",
    "status": "success"
  }
  ```

---

## 5. Firebase Cloud Messaging (FCM Server → Android)

Setiap kali ada pesan SMS keluar baru dimasukkan ke queue, backend otomatis mengirimkan **FCM Data-Only Message** ke Android:

```json
{
  "message": {
    "token": "<FCM_TOKEN_ANDROID>",
    "data": {
      "KEY_MESSAGE_ID": "SMS-20260911081500-A1B2C3"
    },
    "android": {
      "priority": "high"
    }
  }
}
```

Aplikasi Android mendengarkan event ini di `FirebaseMessagingService::onMessageReceived`:
1. Mengambil `data["KEY_MESSAGE_ID"]`.
2. Memanggil `GET /v1/messages/outstanding?message_id=...`.
3. Mengirimkan SMS via `SmsManager`.
4. Mengirimkan laporan ke `POST /v1/messages/{id}/events`.

---

## 6. Endpoint REST API untuk Sistem Luar (Kirim SMS via REST API)

Gunakan endpoint ini di backend utama Anda (Laravel, PHP, NodeJS, Python, C#, POS, dll.):

### **A. Masukkan SMS ke Antrean Pengiriman**
- **Method & Path:** `POST /api/v1/sms/send`
- **Headers:**
  ```http
  x-api-key: <app.smsApiKey>
  Content-Type: application/json
  ```
- **Request Body:**
  ```json
  {
    "recipient": "+6281234567890",
    "message": "Terima kasih, pembayaran pesanan #ORD-9981 sebesar Rp 150.000 telah kami terima.",
    "priority": 1,
    "client_message_id": "ORD-9981-NOTIF"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "message": "SMS job queued successfully",
    "data": {
      "job_id": "SMS-20260911083000-B2C3D4",
      "client_message_id": "ORD-9981-NOTIF",
      "recipient": "+6281234567890",
      "status": "PENDING"
    }
  }
  ```

### **B. Cek Status Pesan SMS**
- **Method & Path:** `GET /api/v1/sms/status/{job_id}`
- **Headers:**
  ```http
  x-api-key: <app.smsApiKey>
  ```
- **Response (200 OK):**
  ```json
  {
    "status": "success",
    "data": {
      "job_id": "SMS-20260911083000-B2C3D4",
      "recipient": "+6281234567890",
      "status": "DELIVERED",
      "sent_at": "2026-09-11 08:30:05",
      "delivered_at": "2026-09-11 08:30:10"
    }
  }
  ```

---

## 7. Petunjuk Setup Firebase Service Account

1. Buka [Firebase Console](https://console.firebase.google.com/) → Pilih Project Anda.
2. Buka **Project Settings** (ikon gear ⚙️) → Tab **Service accounts**.
3. Klik tombol **Generate new private key** → Unduh file `.json`.
4. Upload file tersebut ke server backend pada lokasi:
   ```
   writable/firebase/service-account.json
   ```
5. Pastikan baris berikut aktif di file `.env`:
   ```ini
   fcm.credentialsFile = 'writable/firebase/service-account.json'
   ```
6. Backend otomatis menggunakan **Firebase HTTP v1 API** (keamanan tinggi & tanpa limit kuota legacy).
