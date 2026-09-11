# API & FCM Contract — Android SMS Gateway

Kontrak backend ↔ aplikasi Android `com.httpsms` (diambil langsung dari kode app saat ini).

---

## 1. Konfigurasi Umum

| Item | Nilai |
|---|---|
| **Base URL** | `https://secureapi.pandemenulis.com/sms` (atau `http://localhost:8080` saat local testing). |
| **Header semua request** | `x-api-key: <API key>` dan `X-Client-Version: <versi app>` (contoh `1.0.0`). |
| **Envelope response standar** | `{ "data": ..., "message": "...", "status": "..." }` |
| **Format timestamp** | ISO-8601 UTC: `yyyy-MM-dd'T'HH:mm:ss.SSS000000Z` (mis. `2026-09-09T03:00:00.000000Z`). |

---

## 2. Registrasi Line & Validasi API Key

**`PUT /v1/phones/fcm-token`** — dipakai saat login (per SIM), FCM `onNewToken`, dan refresh token 24 jam.

- **Headers**:
  ```http
  x-api-key: <API_KEY>
  Content-Type: application/json
  ```
- **Request Body**:
  ```json
  {
    "fcm_token": "<firebase_cloud_messaging_token>",
    "phone_number": "+60123456789",
    "sim": "SIM1"
  }
  ```
- **Response Sukses (200 OK)**:
  ```json
  {
    "data": {
      "id": "phone-id",
      "user_id": "account-user-id"
    },
    "message": "ok",
    "status": "success"
  }
  ```
- **Response Gagal (401 Unauthorized)**:
  ```json
  {
    "data": null,
    "message": "Cannot validate the API key. Please check your credentials.",
    "status": "error"
  }
  ```

---

## 3. Ambil Pesan untuk Dikirim

**`GET /v1/messages/outstanding?message_id=<messageId>`** — dipanggil worker setelah menerima FCM `KEY_MESSAGE_ID`.

- **Headers**:
  ```http
  x-api-key: <API_KEY>
  ```
- **Response Envelope (200 OK)**:
  ```json
  {
    "data": {
      "id": "SMS-20260911081500-A1B2C3",
      "contact": "+628123456789",
      "content": "Kode verifikasi Anda adalah 839201.",
      "sim": "SIM1",
      "owner": "+60123456789",
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

## 4. Laporan Status Pengiriman (Device → Server)

**`POST /v1/messages/{messageId}/events`**

- **Event Sukses (SENT / DELIVERED)**:
  ```json
  {
    "event_name": "SENT",
    "timestamp": "2026-09-11T08:15:10.000000Z"
  }
  ```
- **Event Gagal (FAILED)**:
  ```json
  {
    "event_name": "FAILED",
    "reason": "NO_SERVICE",
    "timestamp": "2026-09-11T08:15:10.000000Z"
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "data": null,
    "message": "ok",
    "status": "success"
  }
  ```

---

## 5. Heartbeat (Device → Server)

**`POST /v1/heartbeats`** — dikirim periodik tiap ~15 menit (WorkManager), saat FCM ping `KEY_HEARTBEAT_ID`, dan tombol manual "Send Heartbeat" di Settings.

- **Request Body**:
  ```json
  {
    "device_id": "3f9c-uuid-acak",
    "app_version": "1.0.0",
    "timestamp": 1773124514,
    "sms_permission": true,
    "battery_optimization_disabled": true,
    "battery_level": 85,
    "is_charging": true,
    "active_subscription_id": 1,
    "sim_carrier": "Telkomsel",
    "network_type": "WIFI",
    "phone_numbers": ["+60123456789"]
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "data": null,
    "message": "ok",
    "status": "success"
  }
  ```

---

## 6. Teruskan SMS/MMS Masuk (Device → Server)

**`POST /v1/messages/receive`** — dipanggil receiver SMS/MMS masuk saat ada SMS diterima di SIM fisik.

- **Request Body**:
  ```json
  {
    "sim": "SIM1",
    "from": "+62812345678",
    "to": "+60123456789",
    "content": "Pesan balasan dari customer",
    "encrypted": false,
    "timestamp": "2026-09-11T08:15:00.000000Z",
    "attachments": null
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "data": null,
    "message": "ok",
    "status": "success"
  }
  ```

---

## 7. Firebase Cloud Messaging (FCM Server → Device)

Backend mengirim **FCM Data-Only Message** (tanpa object notification):
1. **Push Kirim SMS Baru**:
   ```json
   {
     "to": "<FCM_TOKEN>",
     "data": {
       "KEY_MESSAGE_ID": "SMS-20260911081500-A1B2C3"
     },
     "priority": "high"
   }
   ```
2. **Push Heartbeat Ping**:
   ```json
   {
     "to": "<FCM_TOKEN>",
     "data": {
       "KEY_HEARTBEAT_ID": "hb_1773124514"
     },
     "priority": "high"
   }
   ```
