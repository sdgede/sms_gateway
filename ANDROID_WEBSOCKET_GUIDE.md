# Panduan Native WebSocket Android SMS Gateway

Dokumen ini adalah panduan lengkap untuk tim pengembang aplikasi Android untuk mengintegrasikan **Native WebSocket (RFC 6455)** secara *real-time*, dua arah (*bi-directional*), dan instan tanpa jeda polling.

---

## Koneksi Native WebSocket

- **WebSocket URL**: `ws://secureapi.pandemenulis.com:8085?token=DEVICE_TOKEN`
- **Alternatif Header**: `Authorization: Bearer DEVICE_TOKEN`
- **Protokol**: RFC 6455 Standard Text Frame (JSON)

---

## 1. Setup Dependency di Android

Tambahkan library `OkHttp` di `app/build.gradle`:
```groovy
dependencies {
    implementation("com.squareup.okhttp3:okhttp:4.12.0")
}
```
Dan pastikan permission di `AndroidManifest.xml`:
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.SEND_SMS" />
<uses-permission android:name="android.permission.READ_PHONE_STATE" />
<uses-permission android:name="android.permission.FOREGROUND_SERVICE" />
```

---

## 2. Kode Lengkap Android (Kotlin Foreground Service)

Berikut adalah implementasi lengkap Foreground Service dengan auto-reconnect, pengiriman SMS native, dan pelaporan status instan:

```kotlin
package com.example.smsgateway

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Intent
import android.os.Build
import android.os.IBinder
import android.telephony.SmsManager
import android.util.Log
import androidx.core.app.NotificationCompat
import okhttp3.*
import org.json.JSONObject
import java.util.concurrent.TimeUnit

class SmsGatewayWebSocketService : Service() {

    private val TAG = "SMS_GATEWAY_WS"
    
    // Ganti dengan domain server dan token perangkat hasil pairing
    private val WS_HOST = "ws://secureapi.pandemenulis.com:8085"
    private val DEVICE_TOKEN = "gw_tok_PASTE_TOKEN_PERANGKAT_ANDA"

    private lateinit var okHttpClient: OkHttpClient
    private var webSocket: WebSocket? = null
    private var isServiceRunning = true

    override fun onCreate() {
        super.onCreate()
        startForegroundNotification()

        okHttpClient = OkHttpClient.Builder()
            .readTimeout(0, TimeUnit.MILLISECONDS) // 0 = persistent socket connection
            .pingInterval(25, TimeUnit.SECONDS)   // Ping otomatis setiap 25 detik
            .connectTimeout(10, TimeUnit.SECONDS)
            .build()

        connectWebSocket()
    }

    private fun connectWebSocket() {
        if (!isServiceRunning) return

        val wsUrl = "$WS_HOST?token=$DEVICE_TOKEN"
        val request = Request.Builder()
            .url(wsUrl)
            .header("Authorization", "Bearer $DEVICE_TOKEN")
            .build()

        Log.d(TAG, "Menghubungkan ke WebSocket: $wsUrl")

        webSocket = okHttpClient.newWebSocket(request, object : WebSocketListener() {
            override fun onOpen(ws: WebSocket, response: Response) {
                Log.d(TAG, "TERHUBUNG KE WEBSOCKET SERVER!")
                // Kirim info baterai awal
                sendHeartbeat(85, 90, false)
            }

            override fun onMessage(ws: WebSocket, text: String) {
                Log.d(TAG, "Pesan masuk dari Server: $text")
                try {
                    val json = JSONObject(text)
                    val event = json.optString("event")

                    when (event) {
                        "new_sms_job" -> {
                            // Event Job SMS Baru diterima dari server
                            val jobData = json.getJSONObject("data")
                            handleNewSmsJob(jobData)
                        }
                        "claim_result" -> {
                            val jobId = json.optString("job_id")
                            val success = json.optBoolean("success")
                            Log.d(TAG, "Claim Result untuk $jobId: $success")
                        }
                        "heartbeat_ack" -> {
                            Log.d(TAG, "Heartbeat berhasil diterima server")
                        }
                        "connected" -> {
                            Log.d(TAG, "Status: ${json.optString("message")}")
                        }
                    }
                } catch (e: Exception) {
                    Log.e(TAG, "Gagal memproses pesan JSON: ${e.message}")
                }
            }

            override fun onClosing(ws: WebSocket, code: Int, reason: String) {
                Log.w(TAG, "WebSocket sedang ditutup: $reason")
            }

            override fun onClosed(ws: WebSocket, code: Int, reason: String) {
                Log.w(TAG, "WebSocket terputus. Mencoba reconnect dalam 3 detik...")
                scheduleReconnect()
            }

            override fun onFailure(ws: WebSocket, t: Throwable, response: Response?) {
                Log.e(TAG, "WebSocket Error: ${t.message}. Reconnecting...")
                scheduleReconnect()
            }
        })
    }

    private fun scheduleReconnect() {
        if (!isServiceRunning) return
        Thread {
            Thread.sleep(3000)
            connectWebSocket()
        }.start()
    }

    /**
     * Memproses dan mengeksekusi Job SMS dari Server
     */
    private fun handleNewSmsJob(job: JSONObject) {
        val jobId = job.getString("job_id")
        val recipient = job.getString("recipient")
        val message = job.getString("message")

        Log.d(TAG, "Memproses Job SMS: $jobId ke $recipient")

        // 1. Kunci (Claim) Job via WebSocket
        val claimMessage = JSONObject().apply {
            put("action", "claim")
            put("job_id", jobId)
        }.toString()
        webSocket?.send(claimMessage)

        // 2. Beri sinyal mulai kirim
        val startMessage = JSONObject().apply {
            put("action", "start")
            put("job_id", jobId)
        }.toString()
        webSocket?.send(startMessage)

        // 3. Eksekusi Kirim SMS lewat SIM Card fisik Android
        try {
            val smsManager = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                applicationContext.getSystemService(SmsManager::class.java)
            } else {
                SmsManager.getDefault()
            }

            smsManager.sendTextMessage(recipient, null, message, null, null)
            Log.d(TAG, "SMS berhasil dikirim ke tower seluler untuk $recipient")

            // 4. Lapor Status SUKSES (SENT) via WebSocket
            val reportSuccess = JSONObject().apply {
                put("action", "report")
                put("job_id", jobId)
                put("status", "SENT")
                put("operator_status_code", "RESULT_OK")
                put("operator_status_message", "Sent successfully by Android Modem")
            }.toString()
            webSocket?.send(reportSuccess)

        } catch (e: Exception) {
            Log.e(TAG, "Gagal kirim SMS via SIM: ${e.message}")

            // Lapor Status GAGAL (FAILED) via WebSocket
            val reportFailed = JSONObject().apply {
                put("action", "report")
                put("job_id", jobId)
                put("status", "FAILED")
                put("operator_status_message", e.message ?: "Send error")
                put("is_recoverable", true) // Server akan auto-retry
            }.toString()
            webSocket?.send(reportFailed)
        }
    }

    /**
     * Kirim Heartbeat status baterai / sinyal via WebSocket
     */
    fun sendHeartbeat(battery: Int, signal: Int, isCharging: Boolean) {
        val hb = JSONObject().apply {
            put("action", "heartbeat")
            put("battery_level", battery)
            put("signal_strength", signal)
            put("is_charging", isCharging)
        }.toString()
        webSocket?.send(hb)
    }

    private fun startForegroundNotification() {
        val channelId = "sms_gateway_channel"
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                channelId,
                "SMS Gateway Service",
                NotificationManager.IMPORTANCE_LOW
            )
            val manager = getSystemService(NotificationManager::class.java)
            manager.createNotificationChannel(channel)
        }

        val notification: Notification = NotificationCompat.Builder(this, channelId)
            .setContentTitle("SMS Gateway Aktif")
            .setContentText("Koneksi WebSocket terhubung untuk memproses antrean SMS")
            .setSmallIcon(android.R.drawable.ic_dialog_info)
            .build()

        startForeground(1001, notification)
    }

    override fun onDestroy() {
        isServiceRunning = false
        webSocket?.close(1000, "Service stopped")
        super.onDestroy()
    }

    override fun onBind(intent: Intent?): IBinder? = null
}
```

---

## 3. Format Protokol JSON WebSocket

### A. Push dari Server ke Android saat ada SMS baru:
```json
{
  "event": "new_sms_job",
  "timestamp": "2026-09-11 15:10:00",
  "data": {
    "job_id": "SMS-20260911-151000-A1B2C3",
    "recipient": "081234567890",
    "message": "Kode verifikasi Anda adalah 849201.",
    "priority": 1
  }
}
```

### B. Kirim dari Android ke Server untuk Claim Job:
```json
{
  "action": "claim",
  "job_id": "SMS-20260911-151000-A1B2C3"
}
```

### C. Kirim dari Android ke Server untuk Lapor Sukses (SENT):
```json
{
  "action": "report",
  "job_id": "SMS-20260911-151000-A1B2C3",
  "status": "SENT",
  "operator_status_code": "RESULT_OK",
  "operator_status_message": "Delivered to cellular network"
}
```

### D. Kirim dari Android ke Server untuk Lapor Gagal (FAILED):
```json
{
  "action": "report",
  "job_id": "SMS-20260911-151000-A1B2C3",
  "status": "FAILED",
  "operator_status_message": "NO_SERVICE / Pulsa habis",
  "is_recoverable": true
}
```

---

## 4. Menjalankan WebSocket Server di VPS / Server

Jalankan perintah ini di server:
```bash
cd /home/u948840458/domains/secureapi.pandemenulis.com/public_html/sms
php spark ws:serve --port 8085
```

Atau jalankan di background (Daemon):
```bash
nohup php spark ws:serve --port 8085 > writable/logs/websocket.log 2>&1 &
```
