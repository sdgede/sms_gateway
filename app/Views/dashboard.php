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
    <!-- QRCode JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
            <div class="brand-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
            </div>
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
                Checking dispatcher...
            </div>
            <button class="btn btn-primary btn-sm" onclick="openBulkResendModal(30)" title="Kirim Ulang Semua SMS ke Semua Nomor dengan Jeda 30s / 1 Menit (Uji Limit P2P)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Kirim Ulang Semua (Delay 30s / 1m)
            </button>
            <button class="btn btn-secondary btn-sm" onclick="runWorker()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Run Worker
            </button>
            <button class="btn btn-secondary btn-sm" onclick="fetchLiveData()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg> Refresh
            </button>
        </div>
    </header>

    <!-- Top Stats -->
    <!-- Top Stats (5 Cards) -->
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
            <div class="stat-label">SIM Lines & FCM</div>
            <div class="stat-value" style="color: #818cf8;" id="statPhoneLines">0</div>
            <div class="stat-sub">Registered SIMs</div>
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
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -3px; margin-right: 6px;"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg> Generate One-Time Pairing Code
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

                    <!-- QR Code Preview Card -->
                    <div style="display: flex; flex-direction: column; align-items: center; margin: 12px 0;">
                        <div style="background: white; padding: 10px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); display: inline-block;">
                            <div id="pairingQrcode"></div>
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 6px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/></svg> Scan QR Code dari Aplikasi Android
                        </div>
                    </div>

                    <div style="font-size: 12px; color: var(--text-dim); margin-bottom: 10px;" id="pairingExpiryNote">Aktif permanen sampai di-pairing</div>
                    <div style="display: flex; justify-content: center; gap: 8px;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="copyActivePairingCode()">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg> Salin Kode
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="showQrModal(latestPairingCode, document.getElementById('deviceNameInput').value)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;"><circle cx="11" cy="11" r="8"/><line x1="21" x2="16.65" y1="21" y2="16.65"/><line x1="11" x2="11" y1="8" y2="14"/><line x1="8" x2="14" y1="11" y2="11"/></svg> Perbesar QR
                        </button>
                    </div>
                </div>

                <!-- Active Unused Pairing Codes List -->
                <div id="activeCodesContainer" style="margin-top: 16px; display: none;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
                        Kode Pairing Aktif (Belum Digunakan)
                    </div>
                    <div id="activeCodesList" style="display: flex; flex-direction: column; gap: 8px;"></div>
                </div>
            </div>

            <!-- Test Message Sender -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -3px; margin-right: 6px;"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg> Kirim Test SMS ke Queue
                    </div>
                </div>

                <div style="display: flex; gap: 6px; margin-bottom: 14px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setPreset('otp')">Preset OTP</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setPreset('trx')">Preset Transaksi</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="setPreset('notif')">Preset Notif</button>
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
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 6px;"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg> Masukkan ke Antrean SMS
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column: Tabs & Monitoring Tables -->
        <div>
            <div class="panel">
                <!-- Navigation Tabs (3 Tabs) -->
                <div class="tab-nav">
                    <button class="tab-btn active" onclick="switchTab('queue')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 6px;"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg> Antrean SMS <span class="tab-badge" id="tabBadgeQueue">0</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('lines')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 6px;"><path d="M6 2h8l6 6v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z"/><path d="M9 10v4"/><path d="M15 10v4"/><path d="M9 14h6"/></svg> SIM Lines & FCM <span class="tab-badge" id="tabBadgeLines">0</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('devices')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 6px;"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"/><path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"/><circle cx="12" cy="12" r="2"/><path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"/><path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"/></svg> Perangkat Android <span class="tab-badge" id="tabBadgeDevices">0</span>
                    </button>
                </div>

                <!-- Tab 1: SMS Outbound Queue -->
                <div id="tabPaneQueue" class="tab-pane active">
                    <div class="panel-header" style="margin-bottom: 12px; border: none; padding: 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-muted);">
                            Riwayat Antrean & Pengiriman SMS
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <button class="btn btn-primary btn-sm" onclick="openBulkResendModal(30)" style="padding: 4px 10px; font-size: 11px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Kirim Ulang Semua (Delay 30s / 1m)
                            </button>
                            <span class="mono-tag" id="lastUpdatedTag">Updated just now</span>
                        </div>
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

                <!-- Tab 2: Registered SIM Lines & FCM -->
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
    // Robust BASE_URL detection: uses PHP base_url() with browser pathname fallback
    const BASE_URL = '<?= rtrim(base_url(), '/') ?>' || (window.location.origin + window.location.pathname.replace(/\/index\.php\/?$/, '').replace(/\/+$/, ''));
    let latestPairingCode = '';

    function setElText(id, val) {
        const el = document.getElementById(id);
        if (el) el.innerText = val;
    }

    function setElHtml(id, val) {
        const el = document.getElementById(id);
        if (el) el.innerHTML = val;
    }

    // Tab Switching (3 Tabs)
    function switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

        if (tabId === 'queue') {
            document.querySelector('.tab-btn:nth-child(1)')?.classList.add('active');
            document.getElementById('tabPaneQueue')?.classList.add('active');
        } else if (tabId === 'lines') {
            document.querySelector('.tab-btn:nth-child(2)')?.classList.add('active');
            document.getElementById('tabPaneLines')?.classList.add('active');
        } else if (tabId === 'devices') {
            document.querySelector('.tab-btn:nth-child(3)')?.classList.add('active');
            document.getElementById('tabPaneDevices')?.classList.add('active');
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
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'toast';
        
        let iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>';
        if (type === 'success') {
            iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2" style="flex-shrink:0;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
        }
        if (type === 'error') {
            iconSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
        }

        toast.innerHTML = `<span style="display:inline-flex; align-items:center;">${iconSvg}</span> <span>${message}</span>`;
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
            if (!res.ok) throw new Error(`HTTP ${res.status}: Gagal mengambil data`);
            const result = await res.json();

            if (result && result.status === 'success') {
                renderDashboard(result.data);
            }
        } catch (err) {
            console.error('Polling error:', err);
        }
    }

    // Render Data into DOM
    function renderDashboard(data) {
        if (!data) return;
        currentAvailableJobs = data.jobs || [];

        // Dispatcher & FCM Status Badge
        const fcmBadge = document.getElementById('fcmStatusBadge');
        if (fcmBadge) {
            if (data.dispatcher_labels && data.dispatcher_labels.length > 0) {
                const badgeText = data.dispatcher_labels.map(l => l.name).join(' | ');
                fcmBadge.innerHTML = badgeText;
                fcmBadge.style.color = '#818cf8';
                fcmBadge.style.borderColor = 'rgba(99, 102, 241, 0.4)';
                fcmBadge.style.background = 'rgba(99, 102, 241, 0.12)';
            } else if (data.fcm_status && data.fcm_status.configured) {
                fcmBadge.innerHTML = `FCM: Active (${data.fcm_status.mode})`;
                fcmBadge.style.color = '#34d399';
                fcmBadge.style.borderColor = 'rgba(16, 185, 129, 0.4)';
                fcmBadge.style.background = 'rgba(16, 185, 129, 0.12)';
            } else {
                fcmBadge.innerHTML = `Mode: Direct Polling`;
                fcmBadge.style.color = '#fbbf24';
                fcmBadge.style.borderColor = 'rgba(245, 158, 11, 0.4)';
                fcmBadge.style.background = 'rgba(245, 158, 11, 0.12)';
            }
        }

        // Stats
        const stats = data.stats || {};
        setElText('statGateways', `${stats.gateways_online || 0} / ${stats.gateways_count || 0}`);
        setElText('statGatewaysSub', stats.gateways_online > 0 ? `${stats.gateways_online} ready to dispatch` : 'No active devices');

        setElText('statTotal', stats.total_jobs || 0);
        setElText('statDelivered', (stats.delivered || 0) + (stats.sent || 0));
        
        const total = stats.total_jobs || 0;
        const success = (stats.delivered || 0) + (stats.sent || 0);
        const rate = total > 0 ? Math.round((success / total) * 100) : 100;
        setElText('statDeliveryRate', `${rate}% success rate`);

        const pending = (stats.pending || 0) + (stats.claimed || 0) + (stats.sending || 0);
        setElText('statPending', pending);
        setElText('statPendingSub', `${stats.pending || 0} pending, ${stats.sending || 0} sending`);

        setElText('statPhoneLines', (data.stats && data.stats.phone_lines_count) || (data.phone_lines || []).length);

        // Tab Badges
        setElText('tabBadgeQueue', (data.jobs || []).length);
        setElText('tabBadgeLines', (data.phone_lines || []).length);
        setElText('tabBadgeDevices', (data.gateways || []).length);

        setElText('lastUpdatedTag', `Updated ${new Date().toLocaleTimeString()}`);

        // Render Active Pairing Codes List
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
                        <button class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px; display: inline-flex; align-items: center;" onclick="showQrModal('${c.code}', '${escapeHtml(c.device_name)}')" title="Lihat QR Code">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </button>
                        <button class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px; display: inline-flex; align-items: center;" onclick="copyCodeText('${c.code}')" title="Salin Kode">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                        <button class="btn btn-danger-subtle btn-sm" style="padding: 4px 8px; font-size: 11px; display: inline-flex; align-items: center;" onclick="handleDeletePairing(${c.id})" title="Hapus Kode">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
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

                const battery = g.battery_level !== null ? `${g.battery_level}%${g.is_charging ? ' (Chg)' : ''}` : '-';
                const signal = g.signal_strength !== null ? `${g.signal_strength}%` : '-';

                const isBlocked = g.status === 'DISABLED';
                const toggleAction = isBlocked ? 'enable' : 'disable';
                const toggleLabel = isBlocked ? 
                    '<svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" stroke="none" style="margin-right: 3px;"><polygon points="5 3 19 12 5 21 5 3"/></svg> Enable' : 
                    '<svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" stroke="none" style="margin-right: 3px;"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg> Disable';

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
                            <div>BAT: ${battery}</div>
                            <div style="font-size: 11px; color: var(--text-dim);">SIG: ${signal}</div>
                        </td>
                        <td>
                            <div style="font-size: 12px;">${g.last_seen_human}</div>
                        </td>
                        <td style="white-space: nowrap;">
                            <button class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px; margin-right: 4px; display: inline-flex; align-items: center;" title="${toggleAction} device" onclick="handleGatewayAction('${g.device_id}', '${toggleAction}')">
                                ${toggleLabel}
                            </button>
                            <button class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px; margin-right: 4px; color: #fbbf24; display: inline-flex; align-items: center; gap: 3px;" title="Revoke Token" onclick="handleGatewayAction('${g.device_id}', 'revoke')">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m21 3-9.5 9.5"/><path d="m15.5 7.5 2.5 2.5"/></svg> Revoke
                            </button>
                            <button class="btn btn-danger-subtle btn-sm" style="padding: 4px 8px; font-size: 11px; display: inline-flex; align-items: center;" title="Hapus Device" onclick="handleGatewayAction('${g.device_id}', 'delete')">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
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
                            <span class="mono-tag" style="${j.assigned_device_id ? 'color: #818cf8; font-weight: 600;' : 'color: var(--text-dim);'}">${escapeHtml(j.assigned_device_id || 'Auto-Dispatch')}</span>
                        </td>
                        <td>
                            <div style="font-size: 11px; color: var(--text-muted);">${j.created_at || '-'}</div>
                        </td>
                        <td style="white-space: nowrap;">
                            <button class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 11px; margin-right: 4px; display: inline-flex; align-items: center; gap: 4px;" title="Reset ke PENDING agar langsung dikirim oleh Android" onclick="handleRequeueSms('${j.job_id}')">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Kirim Ulang
                            </button>
                            <button class="btn btn-danger-subtle btn-sm" style="padding: 4px 8px; font-size: 11px; display: inline-flex; align-items: center;" title="Hapus SMS" onclick="handleDeleteSms('${j.job_id}')">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
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
                        <button class="btn btn-danger-subtle btn-sm" style="padding: 4px 8px; font-size: 11px; display: inline-flex; align-items: center;" title="Hapus Line" onclick="handleDeletePhoneLine('${l.id}')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
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
            msgInput.value = `Halo, pesanan Anda #ORD-98214 sedang dalam perjalanan oleh kurir. Terima kasih telah berbelanja!`;
            prioInput.value = '2';
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
                renderQrCode('pairingQrcode', result.data.code, 150);
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

    // QR Code Generator Function
    function renderQrCode(elementId, text, size = 150) {
        const el = document.getElementById(elementId);
        if (!el) return;
        el.innerHTML = '';
        if (typeof QRCode !== 'undefined') {
            try {
                new QRCode(el, {
                    text: text,
                    width: size,
                    height: size,
                    colorDark: '#0b0f19',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
                return;
            } catch (e) {
                console.warn('QRCode lib error, using image fallback:', e);
            }
        }
        // Image Fallback
        el.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(text)}" width="${size}" height="${size}" alt="QR Code" style="display:block; border-radius: 8px;" />`;
    }

    // Show QR Code Modal
    function showQrModal(code, deviceName = 'Android Gateway Device') {
        if (!code) return;
        latestPairingCode = code;
        document.getElementById('modalCodeText').innerText = code;
        document.getElementById('modalDeviceName').innerText = deviceName || 'Android Gateway Device';
        renderQrCode('modalQrcode', code, 200);
        document.getElementById('qrModal').style.display = 'flex';
    }

    // Close QR Code Modal
    function closeQrModal() {
        document.getElementById('qrModal').style.display = 'none';
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

    // -------------------------------------------------------------
    // Bulk Resend / P2P Load Testing Functions
    // -------------------------------------------------------------
    let currentAvailableJobs = [];

    function openBulkResendModal(defaultInterval = 30) {
        // Collect distinct recipients from current jobs
        const select = document.getElementById('bulkRecipientSelect');
        if (select) {
            const currentSelected = select.value;
            select.innerHTML = '<option value="">-- Semua Nomor yang Pernah Dikirim --</option>';
            const distinctRecipients = [...new Set(currentAvailableJobs.map(j => j.recipient).filter(Boolean))];
            distinctRecipients.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r;
                opt.innerText = `${r} (${currentAvailableJobs.filter(j => j.recipient === r).length} riwayat pesan)`;
                select.appendChild(opt);
            });
            select.value = currentSelected || '';
        }
        
        if (defaultInterval) {
            const intInput = document.getElementById('bulkIntervalInput');
            if (intInput) intInput.value = defaultInterval;
        }

        updateBulkSummary();
        document.getElementById('bulkResendModal').style.display = 'flex';
    }

    function closeBulkResendModal() {
        document.getElementById('bulkResendModal').style.display = 'none';
    }

    function setBulkInterval(sec) {
        document.getElementById('bulkIntervalInput').value = sec;
        updateBulkSummary();
    }

    function updateBulkSummary() {
        const interval = parseInt(document.getElementById('bulkIntervalInput')?.value || '30', 10);
        const limit = parseInt(document.getElementById('bulkLimitInput')?.value || '100', 10);
        const recipient = document.getElementById('bulkRecipientSelect')?.value || '';
        const scope = document.querySelector('input[name="bulkScope"]:checked')?.value || 'unique_recipients';
        
        let count = 0;
        if (recipient) {
            count = currentAvailableJobs.filter(j => j.recipient === recipient).length;
        } else if (scope === 'unique_recipients') {
            const uniqueRecipients = new Set(currentAvailableJobs.map(j => j.recipient).filter(Boolean));
            count = uniqueRecipients.size || (currentAvailableJobs.length > 0 ? 1 : 0);
        } else {
            count = currentAvailableJobs.length || 0;
        }

        const actualCount = Math.min(count || 1, limit);
        const totalSecs = Math.max(0, (actualCount - 1) * (interval || 0));
        
        let durStr = `${totalSecs} detik`;
        if (totalSecs >= 60) {
            const m = Math.floor(totalSecs / 60);
            const s = totalSecs % 60;
            durStr = `${m} menit ${s > 0 ? s + ' detik' : ''}`;
        }

        const summaryEl = document.getElementById('bulkSummaryText');
        if (summaryEl) {
            const scopeLabel = scope === 'unique_recipients' ? 'nomor tujuan unik' : 'pesan antrean';
            summaryEl.innerHTML = `Akan mengirim ulang ke <strong>${actualCount} ${scopeLabel}</strong> dengan jeda <strong>${interval} detik</strong> (${interval >= 60 ? (interval/60) + ' menit' : interval + 's'}) per SMS.<br><span style="color: #818cf8; font-size: 13px;">Estimasi total durasi: <strong>${durStr}</strong></span>`;
        }
    }

    async function handleBulkResendSubmit(e, customInterval = null) {
        if (e && e.preventDefault) e.preventDefault();

        let interval = parseInt(document.getElementById('bulkIntervalInput').value || '30', 10);
        if (customInterval !== null) {
            interval = customInterval;
            document.getElementById('bulkIntervalInput').value = customInterval;
        }

        const mode = document.querySelector('input[name="bulkMode"]:checked')?.value || 'clone_new';
        const scope = document.querySelector('input[name="bulkScope"]:checked')?.value || 'unique_recipients';
        const recipient = document.getElementById('bulkRecipientSelect').value || '';
        const limit = parseInt(document.getElementById('bulkLimitInput').value || '100', 10);

        const btn = document.getElementById('btnSubmitBulk');
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Menjadwalkan Antrean...';
        }

        try {
            const res = await fetch(`${BASE_URL}/web/sms/bulk-resend`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    interval: interval,
                    mode: mode,
                    scope: scope,
                    recipient: recipient,
                    limit: limit
                })
            });

            const result = await res.json();
            if (result.status === 'success') {
                showToast(result.message, 'success');
                closeBulkResendModal();
                fetchLiveData();
            } else {
                showToast(result.message || 'Gagal menjadwalkan pengiriman massal', 'error');
            }
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerText = 'Mulai Kirim Ulang Massal';
            }
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

<!-- Bulk Resend / P2P Limit Testing Modal -->
<div id="bulkResendModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;" onclick="if(event.target === this) closeBulkResendModal()">
    <div style="background: #121826; border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 20px; padding: 28px; max-width: 520px; width: 100%; box-shadow: 0 25px 60px rgba(0,0,0,0.8); position: relative; animation: slideIn 0.25s ease;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
            <div style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 700; color: #fff;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                Kirim Ulang Semua SMS (Uji Limit P2P)
            </div>
            <button onclick="closeBulkResendModal()" style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 20px; padding: 4px;">&times;</button>
        </div>

        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 18px; line-height: 1.5;">
            Kirim ulang SMS ke semua nomor yang pernah dihubungi dengan <strong>jeda waktu 30 detik atau 1 menit per pesan</strong>. Setiap SMS akan otomatis dijadwalkan dan tetap terhitung dalam statistik.
        </p>

        <form id="bulkResendForm" onsubmit="handleBulkResendSubmit(event)">
            <!-- Target Scope Selection -->
            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-weight: 600; color: var(--text-main); font-size: 12px; margin-bottom: 6px; display: block;">Target Penerima</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <label style="display: flex; align-items: flex-start; gap: 8px; background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 10px; padding: 8px 10px; cursor: pointer;">
                        <input type="radio" name="bulkScope" value="unique_recipients" checked onchange="updateBulkSummary()" style="margin-top: 2px;">
                        <div>
                            <div style="font-size: 11px; font-weight: 600; color: #fff;">Semua Nomor Unik</div>
                            <div style="font-size: 10px; color: var(--text-dim);">Pesan terakhir ke tiap nomor</div>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 8px; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 10px; padding: 8px 10px; cursor: pointer;">
                        <input type="radio" name="bulkScope" value="all_jobs" onchange="updateBulkSummary()" style="margin-top: 2px;">
                        <div>
                            <div style="font-size: 11px; font-weight: 600; color: #fff;">Semua Riwayat Pesan</div>
                            <div style="font-size: 10px; color: var(--text-dim);">Seluruh isi antrean</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Jeda / Interval Input -->
            <div class="form-group" style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label style="margin: 0; font-weight: 600; font-size: 12px;">Jeda Waktu Antar Pesan (Detik)</label>
                    <span style="font-size: 11px; color: #818cf8; font-weight: 600;">Standar P2P: 30s - 60s</span>
                </div>
                <input type="number" id="bulkIntervalInput" class="form-control" value="30" min="0" max="3600" oninput="updateBulkSummary()" required style="font-size: 16px; font-weight: 700; color: #818cf8; text-align: center;">
                <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-primary btn-sm" style="padding: 4px 10px; font-size: 11px; font-weight: 600;" onclick="setBulkInterval(30)">⚡ 30 Detik (Standar)</button>
                    <button type="button" class="btn btn-primary btn-sm" style="padding: 4px 10px; font-size: 11px; font-weight: 600; background: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.5); color: #34d399;" onclick="setBulkInterval(60)">⏱️ 1 Menit (60s)</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px;" onclick="setBulkInterval(90)">90 Detik</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px;" onclick="setBulkInterval(120)">2 Menit</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px;" onclick="setBulkInterval(15)">15s</button>
                    <button type="button" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px;" onclick="setBulkInterval(0)">0s (Instan)</button>
                </div>
            </div>

            <!-- Mode Selection -->
            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-weight: 600; color: var(--text-main); font-size: 12px; margin-bottom: 6px; display: block;">Mode Antrean</label>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="display: flex; align-items: flex-start; gap: 8px; background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 8px; padding: 8px 10px; cursor: pointer;">
                        <input type="radio" name="bulkMode" value="clone_new" checked onchange="updateBulkSummary()" style="margin-top: 2px;">
                        <div>
                            <div style="font-size: 11px; font-weight: 600; color: #fff;">Buat Antrean Baru (Tetap Terhitung di Statistik Total)</div>
                        </div>
                    </label>
                    <label style="display: flex; align-items: flex-start; gap: 8px; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 10px; cursor: pointer;">
                        <input type="radio" name="bulkMode" value="reset_existing" onchange="updateBulkSummary()" style="margin-top: 2px;">
                        <div>
                            <div style="font-size: 11px; font-weight: 600; color: #fff;">Reset Status Pesan Lama (Kembali ke PENDING)</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Target Recipient Filter & Limit -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom: 14px;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 11px;">Filter Nomor (Opsional)</label>
                    <select id="bulkRecipientSelect" class="form-control" onchange="updateBulkSummary()" style="font-size: 12px;">
                        <option value="">-- Semua Nomor yang Pernah Dikirim --</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 11px;">Maksimal SMS</label>
                    <input type="number" id="bulkLimitInput" class="form-control" value="100" min="1" max="500" oninput="updateBulkSummary()" style="font-size: 12px;">
                </div>
            </div>

            <!-- Summary Box -->
            <div style="background: rgba(99, 102, 241, 0.1); border: 1px dashed rgba(99, 102, 241, 0.4); border-radius: 12px; padding: 12px 14px; margin-bottom: 18px; font-size: 12px; color: var(--text-main); line-height: 1.5;">
                <div id="bulkSummaryText">
                    Memuat ringkasan estimasi...
                </div>
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary" onclick="closeBulkResendModal()">Batal</button>
                <button type="button" class="btn btn-secondary" style="border-color: rgba(16, 185, 129, 0.5); color: #34d399;" onclick="handleBulkResendSubmit(event, 60)">
                    ⏱️ Kirim (Jeda 1 Menit)
                </button>
                <button type="submit" id="btnSubmitBulk" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    ⚡ Kirim (Jeda 30 Detik)
                </button>
            </div>
        </form>
    </div>
</div>

<!-- QR Code Modal -->
<div id="qrModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;" onclick="if(event.target === this) closeQrModal()">
    <div style="background: #121826; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 28px; max-width: 380px; width: 100%; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.7); position: relative; animation: slideIn 0.25s ease;">
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 16px; font-weight: 700; margin-bottom: 4px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            Scan Pairing QR Code
        </div>
        <div id="modalDeviceName" style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">Android Gateway Device</div>
        
        <div style="background: white; padding: 16px; border-radius: 16px; display: inline-block; margin-bottom: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div id="modalQrcode"></div>
        </div>

        <div style="font-family: var(--font-mono); font-size: 28px; font-weight: 800; letter-spacing: 0.2em; color: #818cf8; margin-bottom: 8px;" id="modalCodeText">------</div>
        <p style="font-size: 12px; color: var(--text-dim); margin-bottom: 20px;">
            Arahkan scanner kamera atau aplikasi Android Gateway ke QR Code ini untuk verifikasi pairing instan.
        </p>

        <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="btn btn-secondary btn-sm" onclick="copyActivePairingCode()" style="display: inline-flex; align-items: center; gap: 4px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                Salin Kode
            </button>
            <button class="btn btn-primary btn-sm" onclick="closeQrModal()">Tutup</button>
        </div>
    </div>
</div>

</body>
</html>
