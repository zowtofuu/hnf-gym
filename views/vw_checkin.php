<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Check-in</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <script src="../assets/js/jsQR.min.js"></script>
</head>

<body>
    <main class="checkin-page">
        <section class="checkin-wrapper">
            <section class="bento-grid">

                <!-- QR SCANNER -->
                <div class="bento-card bento-large">
                    <div class="video-container">
                        <video id="video" autoplay muted playsinline></video>
                        <div id="videoPlaceholder" class="video-placeholder hidden">Camera preview unavailable</div>
                        <canvas id="canvas" style="display:none;"></canvas>

                        <div class="qr-controls">
                            <button class="scanner-toggle is-playing" type="button" id="toggleScanner"
                                title="Pause scanner">
                                <span id="scannerText">Pause scanner</span>
                            </button>
                        </div>

                        <form method="POST" id="qrForm">
                            <input type="hidden" name="qr_token" id="qr_token">
                            <input type="hidden" name="attendance_date" id="qr_attendance_date"
                                value="<?= htmlspecialchars($selectedDate) ?>">
                            <input type="hidden" name="use_session" id="qr_use_session" value="0">
                            <input type="hidden" name="ajax" value="1">
                        </form>
                    </div>
                </div>

                <!-- MANUAL LOG-IN -->
                <div class="bento-card">
                    <section class="checkin-section">
                        <div class="flex justify-center p-md">
                            <form method="POST" class="manual-form" id="manualForm">
                                <h3 class="legend">Manual Check-in</h3>

                                <div class="flex justify-between mb-md">
                                    <label class="session-label" for="global_use_session">
                                        <input class="checkbox" type="checkbox" id="global_use_session" value="1">
                                        Use personal training session
                                    </label>
                                    <a class="icon-button" href="" title="Refresh">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960">
                                            <path
                                                d="M480-160q-134 0-227-93t-93-227q0-134 93-227t227-93q69 0 132 28.5T720-690v-110h80v280H520v-80h168q-32-56-87.5-88T480-720q-100 0-170 70t-70 170q0 100 70 170t170 70q77 0 139-44t87-116h84q-28 106-114 173t-196 67Z" />
                                        </svg>
                                    </a>
                                </div>

                                <div class="form-group">
                                    <label for="client_id">Select client</label>
                                    <select class="capitalize rounded-sm px-md py-sm focus-visible" name="client_id"
                                        id="client_id" required>
                                        <option value="">— choose a client —</option>

                                        <?php foreach ($clients as $client): ?>
                                            <option value="<?= htmlspecialchars($client['client_id']) ?>">
                                                <?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="attendance_date">Attendance date</label>
                                    <input class="capitalize rounded-sm px-md py-sm focus-visible" type="date"
                                        name="attendance_date" id="attendance_date"
                                        value="<?= htmlspecialchars($selectedDate) ?>" required>
                                </div>

                                <input type="hidden" name="use_session" id="manual_use_session" value="0">
                                <input type="hidden" name="ajax" value="1">

                                <div class="form-group">
                                    <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary"
                                        type="submit">
                                        Mark attendance
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>

                <!-- FEEDBACK -->
                <div class="bento-card feedback-card" id="feedbackBox">
                    <div id="feedbackDefault" class="feedback-empty">
                        <h3>Check-in result</h3>
                        <p>Scan a QR code or mark attendance manually.</p>
                    </div>

                    <div id="feedbackResult" class="feedback-result hidden">
                        <p id="feedbackTitle" class="feedback-title"></p>

                        <section id="successDetails" class="flex flex-wrap pb-md gap-sm hidden">
                            <span class="badge badge-lg badge-active">Name: <span id="clientName"
                                    class="muted-text">-</span></span>
                            <span class="badge badge-lg badge-active">Pass ends: <span id="passEnd"
                                    class="muted-text">-</span> &bull; <span id="passRemaining"
                                    class="muted-text"></span></span>
                            <span class="badge badge-lg badge-active">Membership ends: <span id="membershipEnd"
                                    class="muted-text">-</span> &bull; <span id="membershipRemaining"
                                    class="muted-text"></span></span>
                            <span class="badge badge-lg badge-active">Remaining sessions: <span id="remainingSession"
                                    class="muted-text"></span></span>
                        </section>

                        <!-- <div id="successDetails" class="feedback-details hidden">
                            <div class="feedback-row">
                                <span>Name</span>
                                <strong id="clientName">—</strong>
                            </div>

                            <div class="feedback-row">
                                <span>Pass ends</span>
                                <strong>
                                    <span id="passEnd">—</span>
                                    <small id="passRemaining"></small>
                                </strong>
                            </div>

                            <div class="feedback-row">
                                <span>Membership ends</span>
                                <strong>
                                    <span id="membershipEnd">—</span>
                                    <small id="membershipRemaining"></small>
                                </strong>
                            </div>

                            <div class="feedback-row">
                                <span>Remaining sessions</span>
                                <strong id="remainingSession">—</strong>
                            </div>
                        </div> -->
                    </div>
                </div>

            </section>
        </section>
    </main>

    <audio id="successSound" src="../assets/sounds/success.mp3" preload="auto"></audio>
    <audio id="errorSound" src="../assets/sounds/error.mp3" preload="auto"></audio>

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
        const scannerIcon = document.getElementById('scannerIcon');
        const scannerText = document.getElementById('scannerText');

        const successSound = document.getElementById('successSound');
        const errorSound = document.getElementById('errorSound');

        let scanning = false;
        let qrCooldown = false;

        function syncSession() {
            const value = globalToggle.checked ? '1' : '0';

            manualSession.value = value;
            qrSession.value = value;
        }

        function updateScannerButton() {
            toggleScanner.classList.toggle('is-playing', scanning);
            toggleScanner.classList.toggle('is-paused', !scanning);
            toggleScanner.title = scanning ? 'Pause scanner' : 'Resume scanner';
            scannerText.textContent = scanning ? 'Pause scanner' : 'Resume scanner';
        }

        function playSound(type) {
            const sound = type === 'success' ? successSound : errorSound;

            sound.currentTime = 0;
            sound.play().catch(() => { });
        }

        function daysText(value) {
            const days = Number(value);

            if (!Number.isFinite(days)) {
                return '';
            }

            return days === 1 ? '(1 day remaining)' : `(${days} days remaining)`;
        }

        function clearDetails() {
            clientName.textContent = '—';
            passEnd.textContent = '—';
            passRemaining.textContent = '';
            membershipEnd.textContent = '—';
            membershipRemaining.textContent = '';
            remainingSession.textContent = '—';
        }

        function showFeedback(response) {
            const status = response.status || 'error';

            feedbackBox.classList.remove('success', 'error', 'warning');
            feedbackBox.classList.add(status);
            feedbackDefault.classList.add('hidden');
            feedbackResult.classList.remove('hidden');
            feedbackTitle.textContent = response.message || 'Check-in result';

            clearDetails();

            // if (status === 'success' && response.data) {
            //     const data = response.data;

            //     successDetails.classList.remove('hidden');
            //     clientName.textContent = data.client_name || '—';
            //     passEnd.textContent = data.pass_end || '—';
            //     passRemaining.textContent = daysText(data.pass_days_remaining);
            //     membershipEnd.textContent = data.membership_end || '—';
            //     membershipRemaining.textContent = daysText(data.membership_days_remaining);
            //     remainingSession.textContent = data.remaining_sessions ?? '—';
            // } else {
            //     successDetails.classList.add('hidden');
            // }
            if (status === 'success' && response.data) {
                const data = response.data;

                successDetails.classList.remove('hidden');
                clientName.textContent = data.client_name || '—';
                passEnd.textContent = data.pass_end || '—';
                passRemaining.textContent = daysText(data.pass_days_remaining);
                membershipEnd.textContent = data.membership_end || '—';
                membershipRemaining.textContent = daysText(data.membership_days_remaining);
                remainingSession.textContent = data.remaining_sessions ?? '—';

                clearTimeout(successDetails._hideTimer);
                successDetails._hideTimer = setTimeout(() => {
                    successDetails.classList.add('hidden');
                    feedbackResult.classList.add('hidden');
                    feedbackDefault.classList.remove('hidden');
                    feedbackBox.classList.remove('success', 'error', 'warning');
                }, 5000);
            } else {
                successDetails.classList.add('hidden');
            }

            playSound(status === 'success' ? 'success' : 'error');
        }

        async function submitForm(form) {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const text = await response.text();

            try {
                return JSON.parse(text);
            } catch (error) {
                console.error('Invalid JSON response:', text);

                return {
                    status: 'error',
                    message: 'Server returned an invalid response.',
                    data: null
                };
            }
        }

        manualForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            try {
                const response = await submitForm(manualForm);
                showFeedback(response);
            } catch (error) {
                showFeedback({
                    status: 'error',
                    message: 'Request failed.',
                    data: null
                });
            }
        });

        attendanceDate.addEventListener('change', () => {
            qrAttendanceDate.value = attendanceDate.value;
        });

        globalToggle.addEventListener('change', syncSession);
        syncSession();

        async function startCamera() {
            try {
                const cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment'
                    }
                });

                video.srcObject = cameraStream;
                video.classList.remove('hidden');
                videoPlaceholder.classList.add('hidden');

                scanning = true;
                updateScannerButton();
                requestAnimationFrame(scanFrame);
            } catch (error) {
                video.classList.add('hidden');
                videoPlaceholder.classList.remove('hidden');

                showFeedback({
                    status: 'error',
                    message: 'Camera not available.',
                    data: null
                });
            }
        }

        function scanFrame() {
            if (!scanning) {
                return;
            }

            if (qrCooldown) {
                requestAnimationFrame(scanFrame);
                return;
            }

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
                        .catch(() => {
                            showFeedback({
                                status: 'error',
                                message: 'QR check-in failed.',
                                data: null
                            });
                        })
                        .finally(() => {
                            setTimeout(() => {
                                qrInput.value = '';
                                qrCooldown = false;

                                if (scanning) {
                                    requestAnimationFrame(scanFrame);
                                }
                            }, 2000);
                        });

                    return;
                }
            }

            requestAnimationFrame(scanFrame);
        }

        toggleScanner.addEventListener('click', () => {
            scanning = !scanning;
            updateScannerButton();

            if (scanning) {
                requestAnimationFrame(scanFrame);
            }
        });

        updateScannerButton();
        startCamera();
    </script>
</body>

</html>