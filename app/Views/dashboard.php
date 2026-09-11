<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Gateway - Control & Monitoring Center</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
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
            gap: 16px;
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

        @keyframes pulse-animation {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        /* Stat Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            min-height: 80px;
            resize: vertical;
        }

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
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            box-shadow: 0 4px 12px var(--primary-glow);
        }

        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-muted);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-main);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 11px;
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
            <div class="stat-label">Total Messages</div>
            <div class="stat-value" id="statTotal">0</div>
            <div class="stat-sub">Lifetime queued</div>
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
            <div class="stat-label">Failed / Retry</div>
            <div class="stat-value" style="color: var(--danger);" id="statFailed">0</div>
            <div class="stat-sub" id="statRetrySub">0 scheduled retries</div>
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
                    <div class="form-group">
                        <label>Masa Berlaku (Menit)</label>
                        <select id="expiryMinutesInput" class="form-control">
                            <option value="15" selected>15 Menit</option>
                            <option value="30">30 Menit</option>
                            <option value="60">1 Jam</option>
                            <option value="1440">24 Jam</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Generate Pairing Code
                    </button>
                </form>

                <div id="pairingDisplay" class="pairing-display">
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Kode Pairing Android</div>
                    <div id="pairingCodeResult" class="pairing-code-text">------</div>
                    <div style="font-size: 12px; color: var(--text-dim); margin-bottom: 10px;" id="pairingExpiryNote">Berlaku selama 15 menit</div>
                    <button class="btn btn-secondary btn-sm" onclick="copyPairingCode()">📋 Salin Kode</button>
                </div>
            </div>

            <!-- Test Message Sender -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        ✉️ Kirim Test SMS ke Queue
                    </div>
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
                        🚀 Masukkan ke Antrean SMS
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column: Monitoring Tables -->
        <div>
            <!-- Devices Table -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        📡 Perangkat Android Gateway Terhubung
                    </div>
                    <span class="mono-tag" id="lastUpdatedTag">Updated just now</span>
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

            <!-- SMS Queue Table -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        📋 Antrean & Aktivitas SMS (Live Feed)
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
                            </tr>
                        </thead>
                        <tbody id="jobsTableBody">
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-dim); padding: 24px;">
                                    Belum ada antrean SMS.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Toast Notification Container -->
<div id="toastContainer"></div>

<script>
    let activePairingCode = '';

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
        const icon = type === 'success' ? '✅' : (type === 'error' ? '❌' : 'ℹ️');
        toast.innerHTML = `<span>${icon}</span><span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    function updateCharCount() {
        const text = document.getElementById('messageInput').value;
        const chars = text.length;
        document.getElementById('charCount').innerText = `${chars} karakter`;
        const sms = Math.ceil(chars / 160) || 1;
        document.getElementById('smsCount').innerText = `${sms} SMS`;
    }

    // Fetch Live Data
    async function fetchLiveData() {
        try {
            const res = await fetch('/web/data');
            const result = await res.json();
            if (result.status === 'success') {
                renderDashboard(result.data);
            }
        } catch (e) {
            console.error('Polling error:', e);
        }
    }

    function renderDashboard(data) {
        const stats = data.stats;
        document.getElementById('statGateways').innerText = `${stats.gateways_online} / ${stats.gateways_count}`;
        document.getElementById('statGatewaysSub').innerText = `${stats.gateways_online} online & ready`;

        document.getElementById('statTotal').innerText = stats.total;
        document.getElementById('statDelivered').innerText = stats.delivered;
        const rate = stats.total > 0 ? Math.round((stats.delivered / stats.total) * 100) : 0;
        document.getElementById('statDeliveryRate').innerText = `${rate}% delivery rate`;

        document.getElementById('statPending').innerText = stats.pending + stats.sending;
        document.getElementById('statPendingSub').innerText = `${stats.pending} pending, ${stats.sending} sending`;

        document.getElementById('statFailed').innerText = stats.failed_permanent + stats.retry;
        document.getElementById('statRetrySub').innerText = `${stats.retry} scheduled retry`;

        document.getElementById('lastUpdatedTag').innerText = `Updated ${new Date().toLocaleTimeString()}`;

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
                        <td>
                            <button class="btn btn-danger-subtle btn-sm" onclick="handleGatewayAction('${g.device_id}', 'revoke')">
                                Revoke
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Render Jobs Table
        const jobsTbody = document.getElementById('jobsTableBody');
        if (!data.jobs || data.jobs.length === 0) {
            jobsTbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-dim); padding: 24px;">
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
                            <div class="mono-tag" style="color: var(--primary);">${escapeHtml(j.job_id)}</div>
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
                    </tr>
                `;
            }).join('');
        }
    }

    // Generate Pairing Code Form Handler
    async function handleGeneratePairing(e) {
        e.preventDefault();
        const deviceName = document.getElementById('deviceNameInput').value;
        const expiryMinutes = document.getElementById('expiryMinutesInput').value;

        const formData = new FormData();
        formData.append('device_name', deviceName);
        formData.append('expiry_minutes', expiryMinutes);

        try {
            const res = await fetch('/web/pairing/generate', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            if (result.status === 'success') {
                activePairingCode = result.data.code;
                document.getElementById('pairingCodeResult').innerText = activePairingCode;
                document.getElementById('pairingExpiryNote').innerText = `Berlaku s/d ${result.data.expires_at}`;
                document.getElementById('pairingDisplay').style.display = 'block';
                showToast(`Pairing code ${activePairingCode} generated!`, 'success');
            } else {
                showToast(result.message || 'Failed to generate pairing code', 'error');
            }
        } catch (err) {
            showToast('Connection error: ' + err.message, 'error');
        }
    }

    function copyPairingCode() {
        if (!activePairingCode) return;
        navigator.clipboard.writeText(activePairingCode);
        showToast(`Kode ${activePairingCode} disalin ke clipboard!`, 'success');
    }

    // Send SMS Form Handler
    async function handleSendSms(e) {
        e.preventDefault();
        const recipient = document.getElementById('recipientInput').value;
        const message = document.getElementById('messageInput').value;
        const priority = document.getElementById('priorityInput').value;
        const clientMsgId = document.getElementById('clientMsgIdInput').value;

        const formData = new FormData();
        formData.append('recipient', recipient);
        formData.append('message', message);
        formData.append('priority', priority);
        formData.append('client_message_id', clientMsgId);

        try {
            const res = await fetch('/web/sms/send', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            if (result.status === 'success') {
                showToast(`SMS queued successfully! Job ID: ${result.data.job_id}`, 'success');
                document.getElementById('messageInput').value = '';
                document.getElementById('clientMsgIdInput').value = '';
                updateCharCount();
                fetchLiveData();
            } else {
                showToast(result.message || 'Failed to queue SMS', 'error');
            }
        } catch (err) {
            showToast('Connection error: ' + err.message, 'error');
        }
    }

    // Gateway Action Handler (Revoke)
    async function handleGatewayAction(deviceId, action) {
        if (!confirm(`Apakah Anda yakin ingin melakukan '${action}' pada device ini?`)) return;

        const formData = new FormData();
        formData.append('device_id', deviceId);
        formData.append('action', action);

        try {
            const res = await fetch('/web/gateway/action', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            if (result.status === 'success') {
                showToast(result.message, 'success');
                fetchLiveData();
            } else {
                showToast(result.message || 'Action failed', 'error');
            }
        } catch (err) {
            showToast('Connection error: ' + err.message, 'error');
        }
    }

    // Run Background Worker
    async function runWorker() {
        try {
            const res = await fetch('/web/worker/run', { method: 'POST' });
            const result = await res.json();
            showToast(result.message, 'success');
            fetchLiveData();
        } catch (err) {
            showToast('Failed to run worker: ' + err.message, 'error');
        }
    }

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
