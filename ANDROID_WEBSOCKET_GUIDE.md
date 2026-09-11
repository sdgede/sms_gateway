# 🚀 Panduan Real-Time WebSocket & Event Stream Android SMS Gateway

Dokumen ini adalah panduan lengkap untuk tim Android mengintegrasikan **Instant Push Dispatch** sehingga HP Android menerima perintah pengiriman SMS secara **real-time** tanpa jeda polling.

---

## 📡 2 Pilihan Koneksi Real-Time

| Metode | URL Endpoint | Port | Keterangan |
| :--- | :--- | :--- | :--- |
| **1. SSE Stream (Disarankan)** | `https://secureapi.pandemenulis.com/sms/api/v1/gateway/jobs/stream` | `443 (HTTPS)` | ✅ Berjalan di port HTTPS normal, sangat stabil di cPanel/Hostinger. |
| **2. Native WebSocket** | `ws://secureapi.pandemenulis.com:8085` | `8085 (TCP)` | ✅ Menggunakan RFC 6455 daemon (`php spark ws:serve`). |

---

## 📱 Implementasi 1: Real-Time SSE Stream (Sangat Direkomendasikan)

Gunakan library resmi `okhttp-sse` dari Square.

### 1. Tambahkan Dependency di `build.gradle (app)`:
```groovy
dependencies {
    implementation("com.squareup.okhttp3:okhttp:4.12.0")
    implementation("com.squareup.okhttp3:okhttp-sse:4.12.0")
}
```

### 2. Kode Kotlin Android (Foreground Service / Worker):
```kotlin
package com.example.smsgateway

import android.app.Service
import android.content.Intent
import android.os.IBinder
import android.telephony.SmsManager
import android.util.Log
import okhttp3.*
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.toRequestBody
import okhttp3.sse.EventSource
import okhttp3.sse.EventSourceListener
import okhttp3.sse.EventSources
import org.json.JSONObject
import java.util.concurrent.TimeUnit

class SmsGatewaySseService : Service() {

    private val BASE_URL = "https://secureapi.pandemenulis.com/sms/api/v1/gateway"
    private val DEVICE_TOKEN = "gw_tok_PASTE_TOKEN_ANDA_DI_SINI"

    private lateinit var okHttpClient: OkHttpClient
    private var eventSource: EventSource? = null

    override fun onCreate() {
        super.onCreate()
        okHttpClient = OkHttpClient.Builder()
            .readTimeout(0, TimeUnit.MILLISECONDS) // 0 untuk persistent stream
            .connectTimeout(15, TimeUnit.SECONDS)
            .build()

        startEventStream()
    }

    private fun startEventStream() {
        val request = Request.Builder()
            .url("$BASE_URL/jobs/stream")
            .header("Authorization", "Bearer $DEVICE_TOKEN")
            .build()

        val factory = EventSources.createFactory(okHttpClient)
        eventSource = factory.newEventSource(request, object : EventSourceListener() {
            override fun onOpen(eventSource: EventSource, response: Response) {
                Log.d("SMS_STREAM", "🟢 Terhubung ke Real-Time Stream Server")
            }

            override fun onEvent(eventSource: EventSource, id: String?, type: String?, data: String) {
                Log.d("SMS_STREAM", "📩 Event masuk: type=$type | data=$data")

                if (type == "new_sms_job") {
                    val json = JSONObject(data)
                    val jobId = json.getString("job_id")
                    val recipient = json.getString("recipient")
                    val message = json.getString("message")

                    // 1. Klaim Job & Kirim SMS
                    processAndSendSms(jobId, recipient, message)
                }
            }

            override fun onClosed(eventSource: EventSource) {
                Log.w("SMS_STREAM", "🟡 Stream ditutup, mencoba reconnect...")
                reconnect()
            }

            override fun onFailure(eventSource: EventSource, t: Throwable?, response: Response?) {
                Log.e("SMS_STREAM", "🔴 Stream Error: ${t?.message}. Reconnecting in 3s...")
                reconnect()
            }
        })
    }

    private fun reconnect() {
        Thread.sleep(3000)
        startEventStream()
    }

    private fun processAndSendSms(jobId: String, recipient: String, message: String) {
        // Step A: Claim Job
        val claimReq = Request.Builder()
            .url("$BASE_URL/jobs/$jobId/claim")
            .header("Authorization", "Bearer $DEVICE_TOKEN")
            .post("{}".toRequestBody("application/json".toMediaType()))
            .build()

        okHttpClient.newCall(claimReq).execute().use { response ->
            if (!response.isSuccessful) {
                Log.e("SMS_GATEWAY", "Gagal claim job $jobId")
                return
            }
        }

        // Step B: Mark as SENDING & Dispatch via SIM Card
        val smsManager = SmsManager.getDefault()
        try {
            // Dispatch SMS Native Android
            smsManager.sendTextMessage(recipient, null, message, null, null)
            Log.d("SMS_GATEWAY", "✅ SMS terkirim ke $recipient")

            // Step C: Lapor Sukses (SENT)
            val reportBody = JSONObject().apply {
                put("status", "SENT")
                put("operator_status_code", "RESULT_OK")
                put("operator_status_message", "Sent via SIM")
            }.toString()

            val reportReq = Request.Builder()
                .url("$BASE_URL/jobs/$jobId/report")
                .header("Authorization", "Bearer $DEVICE_TOKEN")
                .post(reportBody.toRequestBody("application/json".toMediaType()))
                .build()

            okHttpClient.newCall(reportReq).execute()
        } catch (e: Exception) {
            Log.e("SMS_GATEWAY", "❌ Gagal kirim SMS: ${e.message}")
            // Lapor Gagal (FAILED)
            val failBody = JSONObject().apply {
                put("status", "FAILED")
                put("operator_status_message", e.message)
                put("is_recoverable", true)
            }.toString()

            val reportReq = Request.Builder()
                .url("$BASE_URL/jobs/$jobId/report")
                .header("Authorization", "Bearer $DEVICE_TOKEN")
                .post(failBody.toRequestBody("application/json".toMediaType()))
                .build()

            okHttpClient.newCall(reportReq).execute()
        }
    }

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onDestroy() {
        eventSource?.cancel()
        super.onDestroy()
    }
}
```

---

## 🌐 Implementasi 2: Native WebSocket (`ws://` / `wss://`)

Jika Anda menjalankan WebSocket Daemon di server:
```bash
php spark ws:serve --port 8085
```

### Kode Kotlin Android (OkHttp WebSocketListener):
```kotlin
val wsUrl = "ws://secureapi.pandemenulis.com:8085?token=$DEVICE_TOKEN"
val request = Request.Builder().url(wsUrl).build()

val wsClient = okHttpClient.newWebSocket(request, object : WebSocketListener() {
    override fun onOpen(webSocket: WebSocket, response: Response) {
        Log.d("WS", "🟢 Terhubung ke WebSocket SMS Server!")
    }

    override fun onMessage(webSocket: WebSocket, text: String) {
        Log.d("WS", "📩 Pesan masuk: $text")
        val json = JSONObject(text)
        val event = json.optString("event")

        if (event == "new_sms_job") {
            val job = json.getJSONObject("data")
            val jobId = job.getString("job_id")
            val recipient = job.getString("recipient")
            val message = job.getString("message")

            // Eksekusi kirim SMS
            processAndSendSms(jobId, recipient, message)
        }
    }

    override fun onFailure(webSocket: WebSocket, t: Throwable, response: Response?) {
        Log.e("WS", "🔴 WebSocket Error: ${t.message}. Reconnecting in 3s...")
        Thread.sleep(3000)
        // trigger reconnect
    }
})
```
