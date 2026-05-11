<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Check-in</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <script src="../assets/js/jsQR.min.js"></script>

    <style>
        :root {
            --color-primary: oklch(21% 0.006 285.885);
            --color-secondary: oklch(91.499% 0.01683 250.98);
            --color-accent: oklch(84.1% 0.238 128.85);
            --color-accent-hover: oklch(84.1% 0.238 128.85 / 0.8);
            --color-muted: oklch(81.78% 0.04656 257.64);
            --color-muted-bg: oklch(98.4% 0.003 247.858);
            --color-muted-primary: oklch(31.553% 0.01064 285.734);
            --color-muted-border: oklch(85.1% 0.04656 257.64);
        }

        * {
            box-sizing: border-box;
        }

        .checkin-page {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        /* ── Feedback bar ───────────────────────────────── */
        .feedback-box {
            background: var(--color-secondary);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            min-height: 72px;
            display: flex;
            align-items: center;
            border-left: 5px solid transparent;
            transition: border-color 0.2s;
        }

        .feedback-box.success {
            border-left-color: var(--color-accent);
        }

        .feedback-box.error {
            border-left-color: #e55;
        }

        .feedback-box.warning {
            border-left-color: #f0a500;
        }

        .feedback-inner {
            width: 100%;
        }

        .feedback-default {
            color: var(--color-muted-primary);
            font-size: 0.9rem;
            margin: 0;
        }

        .feedback-title {
            font-weight: 600;
            font-size: 1rem;
            margin: 0 0 0.5rem;
        }

        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(3, auto);
            gap: 0.3rem 2.5rem;
            font-size: 0.85rem;
        }

        .feedback-grid .lbl {
            color: var(--color-muted-primary);
        }

        .feedback-grid .val {
            font-weight: 500;
        }

        .feedback-grid .rem {
            color: var(--color-muted-primary);
            font-size: 0.78rem;
        }

        /* ── Session checkbox ───────────────────────────── */
        .session-row {
            display: flex;
            align-items: center;
        }

        .session-row label {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            user-select: none;
        }

        .session-row input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--color-primary);
            flex-shrink: 0;
        }

        /* ── Two-panel grid ─────────────────────────────── */
        .checkin-cont {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            align-items: stretch;
        }

        .checkin-section {
            display: flex;
            flex-direction: column;
        }

        .checkin-section .card {
            flex: 1;
        }

        /* ── Two-panel grid ─────────────────────────────── */
        .checkin-cont {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            align-items: stretch;
        }

        .checkin-section h3 {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--color-muted-primary);
            margin: 0 0 0.55rem;
        }

        .card {
            background: var(--color-secondary);
            border-radius: 14px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            height: 100%;
            /* ← fill the stretched grid cell */
            box-sizing: border-box;
        }

        /* ── Manual form ─────────────────────────────────── */
        .manual-form {
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
            flex: 1;
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .field-group label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--color-muted-primary);
        }

        .manual-form select,
        .manual-form input[type="date"] {
            width: 100%;
            padding: 0.6rem 0.75rem;
            border: 1.5px solid var(--color-muted-border);
            border-radius: 6px;
            background: #fff;
            font-size: 0.9rem;
            color: var(--color-primary);
        }

        .manual-form select:focus,
        .manual-form input[type="date"]:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .btn-submit {
            margin-top: auto;
            padding: 0.75rem;
            border: 0;
            border-radius: 7px;
            background: var(--color-primary);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s;
        }

        .btn-submit:hover {
            opacity: 0.82;
        }

        /* ── QR card ─────────────────────────────────────── */
        .qr-card {
            overflow: hidden;
            justify-content: space-between;
        }

        #video {
            width: 100%;
            flex: 1;
            min-height: 280px;
            object-fit: cover;
            border-radius: 10px;
            background: var(--color-muted);
        }

        .video-placeholder {
            width: 100%;
            flex: 1;
            min-height: 280px;
            border-radius: 10px;
            background: var(--color-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--color-muted-primary);
            letter-spacing: 0.12em;
        }

        .qr-controls {
            display: flex;
            justify-content: center;
            padding-top: 1rem;
        }

        #toggleScanner {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 0;
            background: var(--color-muted-primary);
            color: #fff;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.15s;
        }

        #toggleScanner:hover {
            opacity: 0.8;
        }

        .hidden {
            display: none !important;
        }

        @media (max-width: 768px) {
            .checkin-cont {
                grid-template-columns: 1fr;
            }

            .feedback-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>
    <main class="checkin-page">

        <!-- ① Feedback bar -->
        <div id="feedbackBox" class="feedback-box">
            <div class="feedback-inner">
                <p id="feedbackDefault" class="feedback-default">
                    Welcome to HNF Gym! Please attendance here.
                </p>

                <div id="feedbackResult" class="hidden">
                    <p id="feedbackTitle" class="feedback-title"></p>

                    <div id="successDetails" class="feedback-grid hidden">
                        <span class="lbl">Name</span>
                        <span class="val" id="clientName">—</span>
                        <span></span>

                        <span class="lbl">Pass ends</span>
                        <span class="val" id="passEnd">—</span>
                        <span class="rem" id="passRemaining"></span>

                        <span class="lbl">Membership ends</span>
                        <span class="val" id="membershipEnd">—</span>
                        <span class="rem" id="membershipRemaining"></span>

                        <span class="lbl">Remaining sessions</span>
                        <span class="val" id="remainingSession">—</span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ② Global session checkbox -->
        <div class="session-row">
            <label for="global_use_session">
                <input type="checkbox"  name="use_session" id="global_use_session">
                Use personal training session
            </label>
        </div>

        <!-- ③ Two panels -->
        <div class="checkin-cont">

            <!-- Manual -->
            <section class="checkin-section">
                <h3>Manual</h3>
                <div class="card">
                    <form method="POST" class="manual-form" id="manualForm">
                        <div class="field-group">
                            <label for="client_id">Select client</label>
                            <select name="client_id" id="client_id" required>
                                <option value="">— choose a client —</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= htmlspecialchars($client['client_id']) ?>">
                                        <?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field-group">
                            <label for="attendance_date">Attendance date</label>
                            <input type="date" name="attendance_date" id="attendance_date"
                                value="<?= htmlspecialchars($selectedDate) ?>" required>
                        </div>

                        <input type="hidden" name="use_session" id="manual_use_session" value="0">
                        <input type="hidden" name="ajax" value="1">

                        <button type="submit" class="btn-submit">Mark attendance</button>
                    </form>
                </div>
            </section>

            <!-- QR -->
            <section class="checkin-section">
                <h3>Scan QR</h3>
                <div class="card qr-card">
                    <video id="video" autoplay muted playsinline></video>
                    <div id="videoPlaceholder" class="video-placeholder hidden">VIDEO</div>
                    <canvas id="canvas" style="display:none;"></canvas>

                    <div class="qr-controls">
                        <button type="button" id="toggleScanner" title="Pause / resume">▶</button>
                    </div>

                    <form method="POST" id="qrForm">
                        <input type="hidden" name="qr_token" id="qr_token">
                        <input type="hidden" name="attendance_date" id="qr_attendance_date"
                            value="<?= htmlspecialchars($selectedDate) ?>">
                        <input type="hidden" name="use_session" id="qr_use_session" value="0">
                        <input type="hidden" name="ajax" value="1">
                    </form>
                </div>
            </section>

        </div>
    </main>

    <audio id="successSound" src="../assets/sounds/rizz.mp3" preload="auto"></audio>
    <audio id="errorSound" src="../assets/sounds/fahhh.mp3" preload="auto"></audio>

    <script>
        const feedbackBox = document.getElementById('feedbackBox');
        const feedbackDefault = document.getElementById('feedbackDefault');
        const feedbackResult = document.getElementById('feedbackResult');
        const feedbackTitle = document.getElementById('feedbackTitle');
        const successDetails = document.getElementById('successDetails');
        const clientName = document.getElementById('clientName');
        const passEnd = document.getElementById('passEnd');
        const passRemaining = document.getElementById('passRemaining');
        const membershipEnd = document.getElementById('membershipEnd');
        const membershipRemaining = document.getElementById('membershipRemaining');
        const remainingSession = document.getElementById('remainingSession');

        const manualForm = document.getElementById('manualForm');
        const attendanceDate = document.getElementById('attendance_date');
        const qrAttendanceDate = document.getElementById('qr_attendance_date');

        const globalToggle = document.getElementById('global_use_session');
        const manualSession = document.getElementById('manual_use_session');
        const qrSession = document.getElementById('qr_use_session');

        const video = document.getElementById('video');
        const videoPlaceholder = document.getElementById('videoPlaceholder');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        const qrForm = document.getElementById('qrForm');
        const qrInput = document.getElementById('qr_token');
        const toggleScanner = document.getElementById('toggleScanner');
        const successSound = document.getElementById('successSound');
        const errorSound = document.getElementById('errorSound');

        let scanning = true;
        let qrCooldown = false;
        let feedbackTimer = null;

        /* ── Sync global toggle → both hidden inputs ── */
        function syncSession() {
            const v = globalToggle.checked ? '1' : '0';
            manualSession.value = v;
            qrSession.value = v;
        }
        globalToggle.addEventListener('change', syncSession);
        syncSession();

        /* ── Keep QR date in sync with manual date picker ── */
        attendanceDate.addEventListener('change', () => {
            qrAttendanceDate.value = attendanceDate.value;
        });

        /* ── Sounds ── */
        function playSound(type) {
            const s = type === 'success' ? successSound : errorSound;
            if (!s) return;
            s.currentTime = 0;
            s.play().catch(() => { });
        }

        /* ── Feedback display ── */
        function daysText(val) {
            if (val === null || val === undefined) return '';
            const d = Number(val);
            return d === 1 ? '(1 day remaining)' : `(${d} days remaining)`;
        }

        function showFeedback(response) {
            feedbackBox.classList.remove('success', 'error', 'warning');
            feedbackBox.classList.add(response.status);

            feedbackDefault.classList.add('hidden');
            feedbackResult.classList.remove('hidden');
            feedbackTitle.textContent = response.message || 'Check-in result';

            if (response.status === 'success' && response.data) {
                const d = response.data;
                successDetails.classList.remove('hidden');
                clientName.textContent = d.client_name || '—';
                passEnd.textContent = d.pass_end || '—';
                passRemaining.textContent = daysText(d.pass_days_remaining);
                membershipEnd.textContent = d.membership_end || '—';
                membershipRemaining.textContent = daysText(d.membership_days_remaining);
                remainingSession.textContent = d.remaining_sessions ?? '—';
            } else {
                successDetails.classList.add('hidden');
            }

            playSound(response.status === 'success' ? 'success' : 'error');

            clearTimeout(feedbackTimer);
            feedbackTimer = setTimeout(() => {
                feedbackBox.classList.remove('success', 'error', 'warning');
                feedbackDefault.classList.remove('hidden');
                feedbackResult.classList.add('hidden');
                successDetails.classList.add('hidden');
            }, 10000);
        }

        /* ── AJAX ── */
        async function submitForm(form) {
            const res = await fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form)
            });
            return res.json();
        }

        /* ── Manual submit ── */
        manualForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                showFeedback(await submitForm(manualForm));
            } catch {
                showFeedback({ status: 'error', message: 'Request failed.', data: null });
            }
        });

        /* ── QR scanner ── */
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                video.classList.remove('hidden');
                videoPlaceholder.classList.add('hidden');
                scanning = true;
                toggleScanner.textContent = '⏸';
                requestAnimationFrame(scanFrame);
            } catch {
                video.classList.add('hidden');
                videoPlaceholder.classList.remove('hidden');
                showFeedback({ status: 'error', message: 'Camera not available.', data: null });
            }
        }

        function scanFrame() {
            if (!scanning || qrCooldown) return;

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);

                if (code && code.data) {
                    qrCooldown = true;
                    qrInput.value = code.data.trim();

                    submitForm(qrForm)
                        .then(showFeedback)
                        .catch(() => showFeedback({ status: 'error', message: 'QR check-in failed.', data: null }))
                        .finally(() => {
                            setTimeout(() => {
                                qrInput.value = '';
                                qrCooldown = false;
                                if (scanning) requestAnimationFrame(scanFrame);
                            }, 2000);
                        });

                    return;
                }
            }

            requestAnimationFrame(scanFrame);
        }

        toggleScanner.addEventListener('click', () => {
            if (scanning) {
                scanning = false;
                toggleScanner.textContent = '▶';
            } else {
                scanning = true;
                toggleScanner.textContent = '⏸';
                requestAnimationFrame(scanFrame);
            }
        });

        startCamera();
    </script>
</body>

</html>