# Dokumentasi API & Payload - Android SMS Gateway

Dokumen ini berisi spesifikasi teknis REST API, endpoint, payload request/response, dan panduan implementasi untuk aplikasi **Android Gateway (Flutter / Native Kotlin)**.

---

## 1. Arsitektur & Alur Kerja Android Gateway

```
[UI/Settings] -> Input Pairing Code -> POST /gateway/pair -> Simpan device_token
                                                                    │
┌─────────────────────────── Loop Background Service ───────────────┘
│
├─► [Setiap 30–60s] -> POST /gateway/heartbeat (Baterai, Sinyal, SIM, Status)
│
└─► [Setiap 3–5s]   -> GET  /gateway/jobs/next (Polling antrean PENDING)
                             │
                             ├─ Jika ada job:
                             │  1. POST /gateway/jobs/{job_id}/claim
                             │  2. POST /gateway/jobs/{job_id}/start
                             │  3. Panggil Native Android SmsManager
                             │
                             ├─ Saat Callback Sent Intent (BroadcastReceiver):
                             │  -> POST /gateway/jobs/{job_id}/report {"status": "SENT"}
                             │
                             └─ Saat Callback Delivery Intent (BroadcastReceiver):
                                -> POST /gateway/jobs/{job_id}/report {"status": "DELIVERED"}
```

---

## 2. Autentikasi & Konfigurasi

- **Base URL**: `http://<IP_SERVER>:8080` (Local) atau `https://<DOMAIN_SERVER>` (Production).
- **Format Data**: JSON (`Content-Type: application/json` dan `Accept: application/json`).
- **Autentikasi**:
  - Endpoint `POST /gateway/pair` bersifat publik (menggunakan kode pairing sekali pakai).
  - Semua endpoint `/gateway/*` lainnya **wajib** menyertakan header:
    ```http
    Authorization: Bearer <device_token>
    ```

---

## 3. Daftar Endpoint Lengkap

### Endpoint 1: Device Pairing (Sekali Pakai)
Dipanggil saat pertama kali admin memasukkan Kode Pairing di aplikasi Android.

- **Method & URL**: `POST /gateway/pair`
- **Headers**:
  ```http
  Content-Type: application/json
  Accept: application/json
  ```
- **Request Body**:
  ```json
  {
    "pairing_code": "8CBE09",
    "device_id": "android_hardware_uuid_atau_android_id",
    "device_name": "Xiaomi Redmi 9A - Gateway 01",
    "sim_operator": "Telkomsel",
    "sim_slot": 1,
    "phone_number": "+6281234567890",
    "app_version": "1.0.0"
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "status": "success",
    "message": "Device successfully paired to SMS Gateway.",
    "data": {
      "device_id": "android_hardware_uuid_atau_android_id",
      "device_name": "Xiaomi Redmi 9A - Gateway 01",
      "device_token": "gw_tok_4f89a7b1c3e5...",
      "server_time": "2026-09-11 11:30:00"
    }
  }
  ```
- **Response `400 Bad Request`** (Kode salah / kadaluarsa / sudah terpakai):
  ```json
  {
    "status": "error",
    "code": "INVALID_OR_EXPIRED_CODE",
    "message": "Pairing code is invalid, already used, or expired."
  }
  ```

> ⚠️ **PENTING**: Simpan `device_token` ke penyimpanan aman Android (`EncryptedSharedPreferences` / Android Keystore / `FlutterSecureStorage`).

---

### Endpoint 2: Device Heartbeat
Dipanggil secara berkala (interval 30–60 detik) untuk menjaga status device `ONLINE`.

- **Method & URL**: `POST /gateway/heartbeat`
- **Headers**:
  ```http
  Authorization: Bearer <device_token>
  Content-Type: application/json
  Accept: application/json
  ```
- **Request Body**:
  ```json
  {
    "battery_level": 85,
    "signal_strength": 90,
    "is_charging": true,
    "sim_operator": "Telkomsel",
    "phone_number": "+6281234567890",
    "app_version": "1.0.0"
  }
  ```
- **Response `200 OK`**:
  ```json
  {
    "status": "success",
    "message": "Heartbeat acknowledged",
    "data": {
      "device_id": "android_hardware_uuid_atau_android_id",
      "status": "ONLINE",
      "server_time": "2026-09-11 11:30:30"
    }
  }
  ```

---

### Endpoint 3: Profil Device
Mendapatkan profil dan konfigurasi batas pengiriman (*rate limit*).

- **Method & URL**: `GET /gateway/profile`
- **Headers**:
  ```http
  Authorization: Bearer <device_token>
  Accept: application/json
  ```
- **Response `200 OK`**:
  ```json
  {
    "status": "success",
    "data": {
      "id": 1,
      "device_id": "android_hardware_uuid_atau_android_id",
      "device_name": "Xiaomi Redmi 9A - Gateway 01",
      "status": "ONLINE",
      "sim_operator": "Telkomsel",
      "rate_limit_per_minute": 30,
      "rate_limit_per_hour": 500,
      "rate_limit_per_day": 5000
    }
  }
  ```

---

### Endpoint 4: Ambil Job Siap Kirim (Polling Queue)
Dipanggil setiap beberapa detik (polling interval 3–5 detik) untuk mengambil pesan siap kirim.

- **Method & URL**: `GET /gateway/jobs/next?limit=1`
- **Headers**:
  ```http
  Authorization: Bearer <device_token>
  Accept: application/json
  ```
- **Response `200 OK` (Ada Job)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "id": 1,
        "job_id": "SMS-20260911031921-BC375D",
        "client_message_id": "TRX-10023",
        "recipient": "+6281234567890",
        "message": "Kode OTP Anda adalah 991823",
        "status": "PENDING",
        "priority": 1,
        "attempt": 0,
        "max_attempt": 3
      }
    ]
  }
  ```
- **Response `200 OK` (Tidak Ada Job)**:
  ```json
  {
    "status": "success",
    "data": []
  }
  ```
- **Response `429 Too Many Requests` (Rate Limit Terlampaui)**:
  ```json
  {
    "status": "error",
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Gateway sending rate limit reached. Please wait before polling.",
    "data": []
  }
  ```

---

### Endpoint 5: Claim / Kunci Job (Atomic Locking)
Mengunci job agar hanya dieksekusi oleh device ini.

- **Method & URL**: `POST /gateway/jobs/{job_id}/claim`
- **Headers**:
  ```http
  Authorization: Bearer <device_token>
  Accept: application/json
  ```
- **Response `200 OK`**:
  ```json
  {
    "status": "success",
    "message": "Job claimed successfully.",
    "data": {
      "job_id": "SMS-20260911031921-BC375D",
      "status": "CLAIMED",
      "assigned_device_id": "android_hardware_uuid_atau_android_id",
      "claim_expires_at": "2026-09-11 11:31:00"
    }
  }
  ```
- **Response `409 Conflict` (Job sudah diklaim device lain)**:
  ```json
  {
    "status": "error",
    "code": "JOB_CLAIM_FAILED",
    "message": "Job could not be claimed. It may have already been claimed by another gateway or is not pending."
  }
  ```

---

### Endpoint 6: Tandai Mulai Mengirim (Start Sending)
Dipanggil tepat sebelum memanggil API `SmsManager`.

- **Method & URL**: `POST /gateway/jobs/{job_id}/start`
- **Headers**:
  ```http
  Authorization: Bearer <device_token>
  Accept: application/json
  ```
- **Response `200 OK`**:
  ```json
  {
    "status": "success",
    "message": "Job marked as SENDING."
  }
  ```

---

### Endpoint 7: Laporan Pengiriman (Status / Delivery Report)
Dipanggil setelah Android menerima hasil dari operator seluler.

- **Method & URL**: `POST /gateway/jobs/{job_id}/report`
- **Headers**:
  ```http
  Authorization: Bearer <device_token>
  Content-Type: application/json
  Accept: application/json
  ```

#### Skenario 1: Konfirmasi SMS Berhasil Diserahkan ke Jaringan (SENT)
```json
{
  "status": "SENT",
  "operator_status_code": "Activity.RESULT_OK",
  "operator_status_message": "SMS diserahkan ke jaringan operator"
}
```

#### Skenario 2: Konfirmasi SMS Sampai di HP Penerima (DELIVERED)
```json
{
  "status": "DELIVERED",
  "operator_status_code": "Activity.RESULT_OK",
  "operator_status_message": "SMS diterima di handset tujuan"
}
```

#### Skenario 3: Pengiriman Gagal Sementara / Recoverable (FAILED)
```json
{
  "status": "FAILED",
  "operator_status_code": "SmsManager.RESULT_ERROR_NO_SERVICE",
  "operator_status_message": "Sinyal hilang saat mengirim SMS",
  "is_recoverable": true
}
```
*Backend akan otomatis menjadwalkan ulang ke `RETRY` (Jeda: Attempt 1 = 30s, Attempt 2 = 5m, Attempt 3 = 15m).*

---

### Endpoint 8: Revoke Token (Logout Gateway)
Dipanggil jika pengguna memilih "Disconnect" atau "Unpair" di aplikasi Android.

- **Method & URL**: `POST /gateway/revoke`
- **Headers**:
  ```http
  Authorization: Bearer <device_token>
  Accept: application/json
  ```
- **Response `200 OK`**:
  ```json
  {
    "status": "success",
    "message": "Device token has been revoked successfully."
  }
  ```

---

## 4. Contoh Kode Integrasi Android (Native Kotlin)

### Mengirim SMS dan Menangani Callback Delivery Report

```kotlin
import android.app.Activity
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.os.Build
import android.telephony.SmsManager

class SmsSenderHelper(private val context: Context, private val apiService: ApiService) {

    fun sendSms(jobId: String, recipient: String, message: String) {
        val sentAction = "SMS_SENT_ACTION_$jobId"
        val deliveredAction = "SMS_DELIVERED_ACTION_$jobId"

        val flags = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            PendingIntent.FLAG_ONE_SHOT or PendingIntent.FLAG_IMMUTABLE
        } else {
            PendingIntent.FLAG_ONE_SHOT
        }

        val sentPI = PendingIntent.getBroadcast(context, 0, Intent(sentAction), flags)
        val deliveredPI = PendingIntent.getBroadcast(context, 0, Intent(deliveredAction), flags)

        // 1. Register Receiver untuk status SENT (Diserahkan ke operator)
        context.registerReceiver(object : BroadcastReceiver() {
            override fun onReceive(c: Context?, intent: Intent?) {
                context.unregisterReceiver(this)
                if (resultCode == Activity.RESULT_OK) {
                    apiService.reportStatus(jobId, status = "SENT", code = "RESULT_OK", msg = "SMS handed over")
                } else {
                    apiService.reportStatus(
                        jobId,
                        status = "FAILED",
                        code = "RESULT_ERROR_$resultCode",
                        msg = "Failed to send SMS",
                        isRecoverable = true
                    )
                }
            }
        }, IntentFilter(sentAction))

        // 2. Register Receiver untuk status DELIVERED (Diterima handset tujuan)
        context.registerReceiver(object : BroadcastReceiver() {
            override fun onReceive(c: Context?, intent: Intent?) {
                context.unregisterReceiver(this)
                if (resultCode == Activity.RESULT_OK) {
                    apiService.reportStatus(jobId, status = "DELIVERED", code = "DELIVERY_OK", msg = "Handset received SMS")
                }
            }
        }, IntentFilter(deliveredAction))

        // 3. Panggil SmsManager bawaan Android
        val smsManager = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            context.getSystemService(SmsManager::class.java)
        } else {
            @Suppress("DEPRECATION")
            SmsManager.getDefault()
        }

        val parts = smsManager.divideMessage(message)
        if (parts.size > 1) {
            val sentIntents = arrayListOf(sentPI)
            val deliveredIntents = arrayListOf(deliveredPI)
            smsManager.sendMultipartTextMessage(recipient, null, parts, sentIntents, deliveredIntents)
        } else {
            smsManager.sendTextMessage(recipient, null, message, sentPI, deliveredPI)
        }
    }
}
```

---

## 5. Ringkasan Status Lifecycle SMS

| Status | Deskripsi |
| :--- | :--- |
| `PENDING` | SMS ada di antrean server, menunggu diambil oleh Android Gateway. |
| `CLAIMED` | SMS telah dikunci (*locked*) oleh satu device Android tertentu. |
| `SENDING` | SMS sedang diproses oleh native `SmsManager`. |
| `SENT` | Konfirmasi bahwa SMS telah sukses diserahkan ke jaringan operator. |
| `DELIVERED` | Konfirmasi bahwa SMS telah sampai dan diterima di HP penerima. |
| `FAILED` | Pengiriman gagal sementara (akan dievaluasi untuk retry). |
| `RETRY` | SMS dijadwalkan ulang ke `PENDING` sesuai interval backoff (30s / 5m / 15m). |
| `FAILED_PERMANENT` | Batas maksimum retry tercapai atau error non-recoverable. |
