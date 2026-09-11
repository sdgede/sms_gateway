<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Gateway - Control & Monitoring Center</title>
    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #0b0f19;
            --bg-card: rgba(18, 24, 38, 0.75);
            --bg-card-hover: rgba(24, 32, 50, 0.9);
            --bg-input: #0f1624;
            --border-color: rgba(255, 255, 255, 0.08);
            --border-hover: rgba(99, 102, 241, 0.4);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.25);
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.2);
            --warning: #f59e0b;
            --warning-glow: rgba(245, 158, 11, 0.2);
            --danger: #ef4444;
            --danger-glow: rgba(239, 68, 68, 0.2);
            --info: #0ea5e9;
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-main);
            background-color: var(--bg-base);
            color: var(--text-main);
            min-height: 100vh;
            line-height: 1.5;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(99, 102, 241, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(16, 185, 129, 0.08) 0%, transparent 40%);
            background-attachment: fixed;
            padding: 24px;
        }

        .container {
            max-width: 1440px;
            margin: 0 auto;
        }

        /* Top Header */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding: 16px 24px;
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #6366f1, #3b82f6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .brand-title h1 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .brand-title p {
            font-size: 12px;
            color: var(--text-muted);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .pulse-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            color: var(--success);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--success);
            animation: pulse-animation 1.5s infinite;
        }

        .fcm-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            color: #818cf8;
        }

        @keyframes pulse-animation {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        /* Stat Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 18px 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            border-color: var(--border-hover);
            transform: translateY(-2px);
        }

        .stat-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 800;
            font-family: var(--font-mono);
            letter-spacing: -0.03em;
        }

        .stat-sub {
            font-size: 11px;
            color: var(--text-dim);
            margin-top: 4px;
        }

        /* Main 2-Column Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 24px;
        }

        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        .panel {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 24px;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .panel-title {
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Custom Tabs in Right Panel */
        .tab-nav {
            display: flex;
            gap: 8px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 18px;
            padding-bottom: 10px;
            overflow-x: auto;
        }

        .tab-btn {
            background: transparent;
            border: 1px solid transparent;
            color: var(--text-muted);
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.04);
        }

        .tab-btn.active {
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            border-color: rgba(99, 102, 241, 0.3);
        }

        .tab-badge {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            padding: 2px 6px;
            border-radius: 9999px;
            font-size: 11px;
            font-family: var(--font-mono);
        }

        .tab-btn.active .tab-badge {
            background: rgba(99, 102, 241, 0.3);
            color: #c7d2fe;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        /* Forms */
        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 10px 14px;
            color: var(--text-main);
            font-family: var(--font-main);
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        textarea.form-control {
            min-height: 90px;
            resize: vertical;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            box-shadow: 0 0 15px var(--primary-glow);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 8px;
        }

        .btn-danger-subtle {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-danger-subtle:hover {
            background: rgba(239, 68, 68, 0.25);
        }

        /* Pairing Code Banner */
        .pairing-display {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(59, 130, 246, 0.08));
            border: 1px dashed rgba(99, 102, 241, 0.4);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            margin-top: 14px;
            display: none;
        }

        .pairing-code-text {
            font-family: var(--font-mono);
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 0.2em;
            color: #818cf8;
            margin: 6px 0;
            text-shadow: 0 0 15px rgba(99, 102, 241, 0.5);
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            text-align: left;
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-dim);
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            vertical-align: middle;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            font-family: var(--font-mono);
            letter-spacing: 0.02em;
        }

        .badge-online { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-offline { background: rgba(100, 116, 139, 0.15); color: #94a3b8; border: 1px solid rgba(100, 116, 139, 0.3); }
        .badge-disabled { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        .badge-pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-claimed { background: rgba(14, 165, 233, 0.15); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.3); }
        .badge-sending { background: rgba(99, 102, 241, 0.2); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.4); }
        .badge-sent { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge-delivered { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-retry { background: rgba(217, 70, 239, 0.15); color: #e879f9; border: 1px solid rgba(217, 70, 239, 0.3); }
        .badge-failed { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        /* Toast */
        #toastContainer {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background: #1e293b;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 13px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.1);
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .mono-tag {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-dim);
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Top Header -->
    <header>
        <div class="brand">
            <div class="brand-icon">📱</div>
            <div class="brand-title">
                <h1>Internal SMS Gateway Control Center</h1>
                <p>Android SIM-Based Transactional SMS Router & Dispatcher</p>
            </div>
        </div>
        <div class="header-actions">
            <div class="pulse-badge">
                <span class="pulse-dot"></span>
                <span id="liveStatusText">GATEWAY ENGINE ACTIVE</span>
            </div>
            <div class="fcm-badge" id="fcmStatusBadge">
                🔥 FCM: Checking...
            </div>
            <button class="btn btn-secondary btn-sm" onclick="runWorker()">
                ⚡ Run Worker
            </button>
            <button class="btn btn-secondary btn-sm" onclick="fetchLiveData()">
                🔄 Refresh
            </button>
        </div>
    </header>

    <!-- Top Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Gateways Online</div>
            <div class="stat-value" id="statGateways">0 / 0</div>
            <div class="stat-sub" id="statGatewaysSub">No devices connected</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Outbound</div>
            <div class="stat-value" id="statTotal">0</div>
            <div class="stat-sub">Queued SMS</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Delivered</div>
            <div class="stat-value" style="color: var(--success);" id="statDelivered">0</div>
            <div class="stat-sub" id="statDeliveryRate">0% success rate</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">In Queue / Sending</div>
            <div class="stat-value" style="color: var(--warning);" id="statPending">0</div>
            <div class="stat-sub" id="statPendingSub">0 pending, 0 sending</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">SIM Lines (FCM)</div>
            <div class="stat-value" style="color: #818cf8;" id="statPhoneLines">0</div>
            <div class="stat-sub">Registered SIMs</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">SMS Masuk (Inbox)</div>
            <div class="stat-value" style="color: var(--info);" id="statIncoming">0</div>
            <div class="stat-sub">Total received</div>
        </div>
    </div>

    <!-- Main 2-Column Content -->
    <div class="main-grid">

        <!-- Left Column: Forms -->
        <div>
            <!-- Pairing Code Generator -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        🔑 Generate One-Time Pairing Code
                    </div>
                </div>
                <form id="pairingForm" onsubmit="handleGeneratePairing(event)">
                    <div class="form-group">
                        <label>Device / Gateway Name</label>
                        <input type="text" id="deviceNameInput" class="form-control" placeholder="Contoh: Android Gateway Cabang 01" value="Android Gateway Device 01" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Generate Pairing Code
                    </button>
                </form>

                <div id="pairingDisplay" class="pairing-display">
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Kode Pairing Android Terbaru</div>
                    <div id="pairingCodeResult" class="pairing-code-text">------</div>
                    <div style="font-size: 12px; color: var(--text-dim); margin-bottom: 10px;" id="pairingExpiryNote">Aktif permanen sampai di-pairing</div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="copyActivePairingCode()">📋 Salin Kode</button>
                </div>

                <!-- Active Unused Pairing Codes List -->
                <div id="activeCodesContainer" style="margin-top: 16px; display: none;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
                        📌 Kode Pairing Aktif (Belum Digunakan)
                    </div>
                    <div id="activeCodesList" style="display: flex; flex-direction: column; gap: 8px;"></div>
                </div>
            </div>

            <!-- Test Message Sender -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        ✉️ Kirim Test SMS ke Queue
                    </div>
                </div>

                <div style="display: flex; gap: 6px; margin-bottom: 14px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setPreset('otp')">📱 Preset OTP</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setPreset('trx')">💰 Preset Transaksi</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setPreset('notif')">🔔 Preset Notif</button>
                </div>

                <form id="sendSmsForm" onsubmit="handleSendSms(event)">
                    <div class="form-group">
                        <label>Nomor Penerima (Recipient)</label>
                        <input type="text" id="recipientInput" class="form-control" placeholder="081234567890 atau +6281234567890" required>
                    </div>
                    <div class="form-group">
                        <label>Isi Pesan (Message Body)</label>
                        <textarea id="messageInput" class="form-control" placeholder="Tulis notifikasi transaksi / OTP di sini..." required oninput="updateCharCount()"></textarea>
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-dim); margin-top: 4px;">
                            <span id="charCount">0 karakter</span>
                            <span id="smsCount">1 SMS</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Prioritas</label>
                        <select id="priorityInput" class="form-control">
                            <option value="1">1 - High (OTP / Transaksi Kritis)</option>
                            <option value="2" selected>2 - Normal (Notifikasi Umum)</option>
                            <option value="3">3 - Low (Informasi / Pengingat)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Client Message ID (Opsional / Idempotency)</label>
                        <input type="text" id="clientMsgIdInput" class="form-control" placeholder="Otomatis jika kosong (e.g. TRX-2026...)">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        🚀 Masukkan ke Antrean SMS (Trigger FCM)
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column: Tabs & Monitoring Tables -->
        <div>
            <div class="panel">
                <!-- Navigation Tabs -->
                <div class="tab-nav">
                    <button class="tab-btn active" onclick="switchTab('queue')">
                        📋 Antrean SMS <span class="tab-badge" id="tabBadgeQueue">0</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('inbox')">
                        📥 SMS Masuk (Inbox) <span class="tab-badge" id="tabBadgeInbox">0</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('lines')">
                        📱 SIM Lines & FCM <span class="tab-badge" id="tabBadgeLines">0</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('devices')">
                        📡 Perangkat Android <span class="tab-badge" id="tabBadgeDevices">0</span>
                    </button>
                </div>

                <!-- Tab 1: SMS Outbound Queue -->
                <div id="tabPaneQueue" class="tab-pane active">
                    <div class="panel-header" style="margin-bottom: 12px; border: none; padding: 0;">
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-muted);">
                            Riwayat Antrean & Pengiriman SMS
                        </div>
                        <span class="mono-tag" id="lastUpdatedTag">Updated just now</span>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Job ID / Ref</th>
                                    <th>Penerima</th>
                                    <th>Pesan</th>
                                    <th>Status</th>
                                    <th>Attempt</th>
                                    <th>Device</th>
                                    <th>Waktu</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="jobsTableBody">
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--text-dim); padding: 24px;">
                                        Belum ada antrean SMS.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: Incoming SMS Inbox -->
                <div id="tabPaneInbox" class="tab-pane">
                    <div class="panel-header" style="margin-bottom: 12px; border: none; padding: 0;">
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-muted);">
                            Pesan Masuk yang Diterima oleh HP Gateway
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID / Ref</th>
                                    <th>Pengirim (From)</th>
                                    <th>SIM / Nomor Gateway</th>
                                    <th>Isi Pesan SMS</th>
                                    <th>Waktu Diterima</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="inboxTableBody">
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-dim); padding: 24px;">
                                        Belum ada SMS masuk.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 3: Registered SIM Lines & FCM -->
                <div id="tabPaneLines" class="tab-pane">
                    <div class="panel-header" style="margin-bottom: 12px; border: none; padding: 0;">
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-muted);">
                            Jalur SIM & Token Push Firebase Cloud Messaging
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nomor Telepon</th>
                                    <th>SIM Slot</th>
                                    <th>Token FCM</th>
                                    <th>Status Push</th>
                                    <th>Terakhir Sinkron</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="linesTableBody">
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-dim); padding: 24px;">
                                        Belum ada SIM Line terdaftar. Lakukan login di aplikasi Android.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 4: Android Devices -->
                <div id="tabPaneDevices" class="tab-pane">
                    <div class="panel-header" style="margin-bottom: 12px; border: none; padding: 0;">
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-muted);">
                            Perangkat Android yang Terhubung / Dipasangkan
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Device Name / ID</th>
                                    <th>Status</th>
                                    <th>SIM / Operator</th>
                                    <th>Baterai / Sinyal</th>
                                    <th>Last Seen</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="gatewaysTableBody">
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-dim); padding: 24px;">
                                        Memuat data gateway...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>

<!-- Toast Notification Container -->
<div id="toastContainer"></div>

<script>
    // Robust BASE_URL detection for root, subfolder (/sms), and ports
    const BASE_URL = window.location.origin + window.location.pathname.replace(/\/index\.php\/?$/, '').replace(/\/+$/, '');
    let latestPairingCode = '';

    // Tab Switching
    function switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

        if (tabId === 'queue') {
            document.querySelector('.tab-btn:nth-child(1)').classList.add('active');
            document.getElementById('tabPaneQueue').classList.add('active');
        } else if (tabId === 'inbox') {
            document.querySelector('.tab-btn:nth-child(2)').classList.add('active');
            document.getElementById('tabPaneInbox').classList.add('active');
        } else if (tabId === 'lines') {
            document.querySelector('.tab-btn:nth-child(3)').classList.add('active');
            document.getElementById('tabPaneLines').classList.add('active');
        } else if (tabId === 'devices') {
            document.querySelector('.tab-btn:nth-child(4)').classList.add('active');
            document.getElementById('tabPaneDevices').classList.add('active');
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        fetchLiveData();
        // Poll every 3 seconds for live monitoring
        setInterval(fetchLiveData, 3000);
    });

    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = 'toast';
        
        let icon = 'ℹ️';
        if (type === 'success') icon = '✅';
        if (type === 'error') icon = '⚠️';

        toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // Fetch Live Dashboard Data
    async function fetchLiveData() {
        try {
            const res = await fetch(`${BASE_URL}/web/data`);
            if (!res.ok) throw new Error('Gagal mengambil data dari server');
            const result = await res.json();

            if (result.status === 'success') {
                renderDashboard(result.data);
            }
        } catch (err) {
            console.error('Polling error:', err);
        }
    }

    // Render Data into DOM
    function renderDashboard(data) {
        // FCM Status Badge
        const fcmBadge = document.getElementById('fcmStatusBadge');
        if (data.fcm_status && data.fcm_status.configured) {
            fcmBadge.innerHTML = `🔥 FCM: Active (${data.fcm_status.mode})`;
            fcmBadge.style.color = '#34d399';
            fcmBadge.style.borderColor = 'rgba(16, 185, 129, 0.4)';
            fcmBadge.style.background = 'rgba(16, 185, 129, 0.12)';
        } else {
            fcmBadge.innerHTML = `⚠️ FCM: Not Configured`;
            fcmBadge.style.color = '#fbbf24';
            fcmBadge.style.borderColor = 'rgba(245, 158, 11, 0.4)';
            fcmBadge.style.background = 'rgba(245, 158, 11, 0.12)';
        }

        // Stats
        const stats = data.stats || {};
        document.getElementById('statGateways').innerText = `${stats.gateways_online || 0} / ${stats.gateways_count || 0}`;
        document.getElementById('statGatewaysSub').innerText = stats.gateways_online > 0 ? `${stats.gateways_online} ready to dispatch` : 'No active devices';

        document.getElementById('statTotal').innerText = stats.total_jobs || 0;
        document.getElementById('statDelivered').innerText = (stats.delivered || 0) + (stats.sent || 0);
        
        const total = stats.total_jobs || 0;
        const success = (stats.delivered || 0) + (stats.sent || 0);
        const rate = total > 0 ? Math.round((success / total) * 100) : 0;
        document.getElementById('statDeliveryRate').innerText = `${rate}% success rate`;

        const pending = (stats.pending || 0) + (stats.claimed || 0) + (stats.sending || 0);
        document.getElementById('statPending').innerText = pending;
        document.getElementById('statPendingSub').innerText = `${stats.pending || 0} pending, ${stats.sending || 0} sending`;

        document.getElementById('statPhoneLines').innerText = data.stats.phone_lines_count || (data.phone_lines || []).length;
        document.getElementById('statIncoming').innerText = data.stats.incoming_count || (data.incoming_messages || []).length;

        // Tab Badges
        document.getElementById('tabBadgeQueue').innerText = (data.jobs || []).length;
        document.getElementById('tabBadgeInbox').innerText = (data.incoming_messages || []).length;
        document.getElementById('tabBadgeLines').innerText = (data.phone_lines || []).length;
        document.getElementById('tabBadgeDevices').innerText = (data.gateways || []).length;

        document.getElementById('lastUpdatedTag').innerText = `Updated ${new Date().toLocaleTimeString()}`;

        // Render Active Pairing Codes
        const activeContainer = document.getElementById('activeCodesContainer');
        const activeList = document.getElementById('activeCodesList');
        const unusedCodes = (data.pairing_codes || []).filter(c => !c.is_used);

        if (unusedCodes.length > 0) {
            activeContainer.style.display = 'block';
            activeList.innerHTML = unusedCodes.map(c => `
                <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 8px; padding: 6px 10px;">
                    <div>
                        <span style="font-family: var(--font-mono); font-weight: 700; color: #818cf8; font-size: 14px; letter-spacing: 0.1em;">${c.code}</span>
                        <span style="font-size: 11px; color: var(--text-dim); margin-left: 6px;">(${escapeHtml(c.device_name)})</span>
                    </div>
                    <div style="display: flex; gap: 4px;">
                        <button class="btn btn-secondary btn-sm" style="padding: 2px 8px; font-size: 11px;" onclick="copyCodeText('${c.code}')" title="Salin Kode">📋</button>
                        <button class="btn btn-danger-subtle btn-sm" style="padding: 2px 8px; font-size: 11px;" onclick="handleDeletePairing(${c.id})" title="Hapus Kode">🗑️</button>
                    </div>
                </div>
            `).join('');
        } else {
            activeContainer.style.display = 'none';
        }

        // Render Gateways Table
        const gwTbody = document.getElementById('gatewaysTableBody');
        if (!data.gateways || data.gateways.length === 0) {
            gwTbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-dim); padding: 24px;">
                        Belum ada gateway terdaftar. Silakan buat <strong>Pairing Code</strong> di samping.
                    </td>
                </tr>
            `;
        } else {
            gwTbody.innerHTML = data.gateways.map(g => {
                let badgeClass = 'badge-offline';
                if (g.computed_status === 'ONLINE') badgeClass = 'badge-online';
                if (g.computed_status === 'DISABLED') badgeClass = 'badge-disabled';

                const battery = g.battery_level !== null ? `${g.battery_level}% ${g.is_charging ? '⚡' : ''}` : '-';
                const signal = g.signal_strength !== null ? `${g.signal_strength}%` : '-';

                const isBlocked = g.status === 'DISABLED';
                const toggleAction = isBlocked ? 'enable' : 'disable';
                const toggleLabel = isBlocked ? '🟢 Enable' : '⏸️ Disable';

                return `
                    <tr>
                        <td>
                            <div style="font-weight: 600;">${escapeHtml(g.device_name)}</div>
                            <div class="mono-tag">${escapeHtml(g.device_id)}</div>
                        </td>
                        <td>
                            <span class="badge ${badgeClass}">${g.computed_status}</span>
                        </td>
                        <td>
                            <div>${escapeHtml(g.sim_operator || 'Unknown')}</div>
                            <div class="mono-tag">${escapeHtml(g.phone_number || '-')}</div>
                        </td>
                        <td>
                            <div>🔋 ${battery}</div>
                            <div style="font-size: 11px; color: var(--text-dim);">📶 ${signal}</div>
                        </td>
                        <td>
                            <div style="font-size: 12px;">${g.last_seen_human}</div>
                        </td>
                        <td style="white-space: nowrap;">
                            <button class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px; margin-right: 4px;" title="${toggleAction} device" onclick="handleGatewayAction('${g.device_id}', '${toggleAction}')">
                                ${toggleLabel}
                            </button>
                            <button class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px; margin-right: 4px; color: #fbbf24;" title="Revoke Token" onclick="handleGatewayAction('${g.device_id}', 'revoke')">
                                🔑 Revoke
                            </button>
                            <button class="btn btn-danger-subtle btn-sm" style="padding: 4px 8px; font-size: 11px;" title="Hapus Device" onclick="handleGatewayAction('${g.device_id}', 'delete')">
                                🗑️
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Render Jobs Table (Queue)
        const jobsTbody = document.getElementById('jobsTableBody');
        if (!data.jobs || data.jobs.length === 0) {
            jobsTbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-dim); padding: 24px;">
                        Belum ada pesan SMS di antrean.
                    </td>
                </tr>
            `;
        } else {
            jobsTbody.innerHTML = data.jobs.map(j => {
                let badgeClass = 'badge-pending';
                if (j.status === 'CLAIMED') badgeClass = 'badge-claimed';
                if (j.status === 'SENDING') badgeClass = 'badge-sending';
                if (j.status === 'SENT') badgeClass = 'badge-sent';
                if (j.status === 'DELIVERED') badgeClass = 'badge-delivered';
                if (j.status === 'RETRY') badgeClass = 'badge-retry';
                if (j.status === 'FAILED_PERMANENT' || j.status === 'FAILED') badgeClass = 'badge-failed';

                return `
                    <tr>
                        <td>
                            <div class="mono-tag" style="color: var(--primary); font-weight: 600;">${escapeHtml(j.job_id)}</div>
                            <div class="mono-tag">${escapeHtml(j.client_message_id || '-')}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600; font-family: var(--font-mono);">${escapeHtml(j.recipient)}</div>
                        </td>
                        <td style="max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${escapeHtml(j.message)}">
                            ${escapeHtml(j.message)}
                        </td>
                        <td>
                            <span class="badge ${badgeClass}">${j.status}</span>
                        </td>
                        <td style="text-align: center;">
                            <span class="mono-tag">${j.attempt}/${j.max_attempt}</span>
                        </td>
                        <td>
                            <span class="mono-tag">${escapeHtml(j.assigned_device_id || '-')}</span>
                        </td>
                        <td>
                            <div style="font-size: 11px; color: var(--text-muted);">${j.created_at || '-'}</div>
                        </td>
                        <td style="white-space: nowrap;">
                            <button class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 11px; margin-right: 4px;" title="Reset ke PENDING agar langsung dikirim oleh Android" onclick="handleRequeueSms('${j.job_id}')">
                                🚀 Kirim Ulang
                            </button>
                            <button class="btn btn-danger-subtle btn-sm" style="padding: 4px 8px; font-size: 11px;" title="Hapus SMS" onclick="handleDeleteSms('${j.job_id}')">
                                🗑️
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Render SIM Lines Table
        const linesTbody = document.getElementById('linesTableBody');
        if (!data.phone_lines || data.phone_lines.length === 0) {
            linesTbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-dim); padding: 24px;">
                        Belum ada SIM Line yang terdaftar dengan FCM Token.
                    </td>
                </tr>
            `;
        } else {
            linesTbody.innerHTML = data.phone_lines.map(l => `
                <tr>
                    <td>
                        <div style="font-weight: 600; font-family: var(--font-mono); font-size: 14px; color: #818cf8;">
                            ${escapeHtml(l.phone_number)}
                        </div>
                        <div class="mono-tag">${escapeHtml(l.id)}</div>
                    </td>
                    <td>
                        <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3);">
                            ${escapeHtml(l.sim || 'SIM_1')}
                        </span>
                    </td>
                    <td>
                        <div class="mono-tag" title="${escapeHtml(l.fcm_token || '')}">
                            ${escapeHtml(l.fcm_preview || 'None')}
                        </div>
                    </td>
                    <td>
                        <span class="badge ${l.has_fcm ? 'badge-online' : 'badge-offline'}">
                            ${l.has_fcm ? 'FCM READY' : 'NO TOKEN'}
                        </span>
                    </td>
                    <td>
                        <div style="font-size: 12px;">${l.updated_human || '-'}</div>
                    </td>
                    <td>
                        <button class="btn btn-danger-subtle btn-sm" style="padding: 4px 8px; font-size: 11px;" title="Hapus Line" onclick="handleDeletePhoneLine('${l.id}')">
                            🗑️
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        // Render Incoming SMS (Inbox)
        const inboxTbody = document.getElementById('inboxTableBody');
        if (!data.incoming_messages || data.incoming_messages.length === 0) {
            inboxTbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-dim); padding: 24px;">
                        Belum ada SMS masuk yang diterima.
                    </td>
                </tr>
            `;
        } else {
            inboxTbody.innerHTML = data.incoming_messages.map(m => `
                <tr>
                    <td>
                        <div class="mono-tag" style="color: var(--info); font-weight: 600;">${escapeHtml(m.message_id)}</div>
                    </td>
                    <td>
                        <div style="font-weight: 600; font-family: var(--font-mono); color: #38bdf8;">
                            ${escapeHtml(m.sender_phone)}
                        </div>
                    </td>
                    <td>
                        <div>${escapeHtml(m.recipient_phone || '-')}</div>
                        <div class="mono-tag">${escapeHtml(m.sim || 'SIM_1')}</div>
                    </td>
                    <td style="max-width: 250px; white-space: normal;" title="${escapeHtml(m.message)}">
                        ${escapeHtml(m.message)}
                    </td>
                    <td>
                        <div style="font-size: 12px;">${m.received_human || m.received_at || '-'}</div>
                    </td>
                    <td>
                        <button class="btn btn-danger-subtle btn-sm" style="padding: 4px 8px; font-size: 11px;" title="Hapus SMS Masuk" onclick="handleDeleteIncoming('${m.id}')">
                            🗑️
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    }

    // Set Message Presets
    function setPreset(type) {
        const msgInput = document.getElementById('messageInput');
        const prioInput = document.getElementById('priorityInput');
        const recInput = document.getElementById('recipientInput');

        if (type === 'otp') {
            const randCode = Math.floor(100000 + Math.random() * 900000);
            msgInput.value = `Kode verifikasi OTP Anda adalah ${randCode}. Berlaku selama 5 menit. JANGAN bagikan kode ini kepada siapapun.`;
            prioInput.value = '1';
        } else if (type === 'trx') {
            const nominal = (Math.floor(Math.random() * 10) + 1) * 50000;
            msgInput.value = `Pembayaran berhasil! Saldo Anda berkurang Rp ${nominal.toLocaleString('id-ID')} untuk transaksi di Toko Online. Sisa saldo: Rp 2.500.000.`;
            prioInput.value = '2';
        } else if (type === 'notif') {
            msgInput.value = `Halo, pengingat jadwal layanan Anda besok pukul 10:00 WIB. Mohon hadir 15 menit sebelum waktu yang ditentukan. Terima kasih.`;
            prioInput.value = '3';
        }
        if (!recInput.value) {
            recInput.value = '081234567890';
        }
        updateCharCount();
    }

    // Calculate Character & SMS Parts
    function updateCharCount() {
        const text = document.getElementById('messageInput').value || '';
        const len = text.length;
        document.getElementById('charCount').innerText = `${len} karakter`;

        let smsParts = 1;
        if (len > 160) {
            smsParts = Math.ceil(len / 153);
        }
        document.getElementById('smsCount').innerText = `${smsParts} SMS (${smsParts * 160} maks)`;
    }

    // Generate Pairing Code
    async function handleGeneratePairing(e) {
        e.preventDefault();
        const deviceName = document.getElementById('deviceNameInput').value;

        try {
            const formData = new FormData();
            formData.append('device_name', deviceName);

            const res = await fetch(`${BASE_URL}/web/pairing/generate`, {
                method: 'POST',
                body: formData
            });

            const result = await res.json();
            if (result.status === 'success') {
                latestPairingCode = result.data.code;
                document.getElementById('pairingCodeResult').innerText = result.data.code;
                document.getElementById('pairingDisplay').style.display = 'block';
                showToast(`Kode Pairing ${result.data.code} berhasil dibuat!`, 'success');
                fetchLiveData();
            } else {
                showToast(result.message || 'Gagal membuat pairing code', 'error');
            }
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    // Copy Code Text
    function copyCodeText(code) {
        navigator.clipboard.writeText(code).then(() => {
            showToast(`Kode ${code} disalin ke clipboard!`, 'success');
        });
    }

    function copyActivePairingCode() {
        if (latestPairingCode) copyCodeText(latestPairingCode);
    }

    // Delete Pairing Code
    async function handleDeletePairing(id) {
        if (!confirm('Hapus kode pairing ini?')) return;

        try {
            const formData = new FormData();
            formData.append('id', id);

            const res = await fetch(`${BASE_URL}/web/pairing/delete`, {
                method: 'POST',
                body: formData
            });

            const result = await res.json();
            if (result.status === 'success') {
                showToast(result.message, 'success');
                fetchLiveData();
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    // Send Test SMS
    async function handleSendSms(e) {
        e.preventDefault();

        const recipient = document.getElementById('recipientInput').value;
        const message = document.getElementById('messageInput').value;
        const priority = document.getElementById('priorityInput').value;
        const clientMsgId = document.getElementById('clientMsgIdInput').value;

        try {
            const formData = new FormData();
            formData.append('recipient', recipient);
            formData.append('message', message);
            formData.append('priority', priority);
            if (clientMsgId) formData.append('client_message_id', clientMsgId);

            const res = await fetch(`${BASE_URL}/web/sms/send`, {
                method: 'POST',
                body: formData
            });

            const result = await res.json();
            if (result.status === 'success') {
                showToast(result.message + ' (FCM Push triggered)', 'success');
                document.getElementById('clientMsgIdInput').value = '';
                fetchLiveData();
            } else {
                showToast(result.message || 'Gagal mengirim SMS', 'error');
            }
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    // Gateway Action
    async function handleGatewayAction(deviceId, action) {
        if (action === 'delete' && !confirm(`Hapus device ${deviceId} permanen?`)) return;
        if (action === 'revoke' && !confirm(`Revoke token device ${deviceId}? Device harus pairing ulang.`)) return;

        try {
            const formData = new FormData();
            formData.append('device_id', deviceId);
            formData.append('action', action);

            const res = await fetch(`${BASE_URL}/web/gateway/action`, {
                method: 'POST',
                body: formData
            });

            const result = await res.json();
            if (result.status === 'success') {
                showToast(result.message, 'success');
                fetchLiveData();
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    // Requeue SMS
    async function handleRequeueSms(jobId) {
        try {
            const formData = new FormData();
            formData.append('job_id', jobId);

            const res = await fetch(`${BASE_URL}/web/sms/requeue`, {
                method: 'POST',
                body: formData
            });

            const result = await res.json();
            if (result.status === 'success') {
                showToast(result.message, 'success');
                fetchLiveData();
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    // Delete SMS Job
    async function handleDeleteSms(jobId) {
        if (!confirm(`Hapus pesan ${jobId}?`)) return;

        try {
            const formData = new FormData();
            formData.append('job_id', jobId);

            const res = await fetch(`${BASE_URL}/web/sms/delete`, {
                method: 'POST',
                body: formData
            });

            const result = await res.json();
            if (result.status === 'success') {
                showToast(result.message, 'success');
                fetchLiveData();
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    // Delete Phone Line
    async function handleDeletePhoneLine(id) {
        if (!confirm('Hapus registrasi SIM Line ini?')) return;

        try {
            const formData = new FormData();
            formData.append('id', id);

            const res = await fetch(`${BASE_URL}/web/phone-line/delete`, {
                method: 'POST',
                body: formData
            });

            const result = await res.json();
            if (result.status === 'success') {
                showToast(result.message, 'success');
                fetchLiveData();
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    // Delete Incoming SMS
    async function handleDeleteIncoming(id) {
        if (!confirm('Hapus pesan masuk ini?')) return;

        try {
            const formData = new FormData();
            formData.append('id', id);

            const res = await fetch(`${BASE_URL}/web/incoming/delete`, {
                method: 'POST',
                body: formData
            });

            const result = await res.json();
            if (result.status === 'success') {
                showToast(result.message, 'success');
                fetchLiveData();
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    // Run Background Worker Manually
    async function runWorker() {
        try {
            const res = await fetch(`${BASE_URL}/web/worker/run`, {
                method: 'POST'
            });
            const result = await res.json();
            if (result.status === 'success') {
                showToast(result.message, 'success');
                fetchLiveData();
            }
        } catch (err) {
            showToast('Gagal menjalankan worker: ' + err.message, 'error');
        }
    }

    // Helper: Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>

</body>
</html>
