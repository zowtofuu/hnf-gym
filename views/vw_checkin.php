<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Check-in</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <script src="../assets/js/jsQR.min.js"></script>

    <style>
        .checkin-page {
            padding: 1.5rem;
        }

        .feedback-box {
            background: #d9d9d9;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .feedback-box h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .feedback-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 520px;
            margin: 0.9rem auto;
            text-align: left;
            gap: 2rem;
        }

        .feedback-row span:last-child {
            text-align: left;
        }

        .feedback-box.error,
        .feedback-box.warning {
            padding: 2rem;
        }

        .checkin-cont {
            display: flex;
            gap: 1.2rem;
            align-items: stretch;
        }

        .checkin-section {
            flex: 1;
        }

        .checkin-section h3 {
            font-size: 1.3rem;
            font-weight: 500;
            margin-bottom: 0.6rem;
            text-transform: uppercase;
        }

        .manual-card,
        .qr-card {
            background: #d9d9d9;
            border-radius: 14px;
            min-height: 280px;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .manual-form {
            width: 80%;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .manual-form label {
            font-size: 0.85rem;
        }

        .manual-form select,
        .manual-form input[type="date"] {
            width: 100%;
            padding: 0.6rem;
            border: 0;
            border-radius: 5px;
        }

        .manual-form button {
            padding: 0.7rem;
            border: 0;
            border-radius: 5px;
            background: #333;
            color: white;
            cursor: pointer;
        }

        .session-check {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 1.1rem;
        }

        .session-check input {
            width: 20px;
            height: 20px;
        }

        .qr-card {
            position: relative;
            overflow: hidden;
            flex-direction: column;
        }

        #video {
            width: 100%;
            height: 260px;
            object-fit: cover;
            border-radius: 12px;
            background: #cfcfcf;
        }

        .video-placeholder {
            width: 100%;
            height: 260px;
            border-radius: 12px;
            background: #d9d9d9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            transform: rotate(-30deg);
        }

        .qr-controls {
            margin-top: 1rem;
            display: flex;
            justify-content: center;
        }

        #toggleScanner {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: 0;
            background: #777;
            color: white;
            cursor: pointer;
            font-size: 1.4rem;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 768px) {
            .checkin-cont {
                flex-direction: column;
            }

            .feedback-row {
                grid-template-columns: 1fr;
                gap: 0.2rem;
                text-align: center;
            }

            .feedback-row span:last-child {
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <main class="checkin-page">

        <section id="feedbackBox" class="feedback-box hidden">
            <h2 id="feedbackTitle"></h2>

            <div id="successDetails" class="hidden">
                <div class="feedback-row">
                    <span>Pass End: <span id="passEnd">N/A</span></span>
                    <span id="passRemaining">N/A</span>
                </div>

                <div class="feedback-row">
                    <span>Membership End: <span id="membershipEnd">N/A</span></span>
                    <span id="membershipRemaining">N/A</span>
                </div>

                <div class="feedback-row">
                    <span>Remaining Session: <span id="remainingSession">N/A</span></span>
                    <span></span>
                </div>
            </div>
        </section>

        <div class="checkin-cont">

            <section class="checkin-section">
                <h3>Manual Attendance</h3>

                <div class="manual-card">
                    <form method="POST" class="manual-form" id="manualForm">
                        <div>
                            <label for="client_id">Select Client</label>
                            <select name="client_id" id="client_id" required>
                                <option value="">Select client</option>

                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= htmlspecialchars($client['client_id']) ?>">
                                        <?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="attendance_date">Attendance Date</label>
                            <input 
                                type="date" 
                                name="attendance_date" 
                                id="attendance_date" 
                                value="<?= htmlspecialchars($selectedDate) ?>" 
                                required
                            >
                        </div>

                        <label class="session-check" for="use_session">
                            <input type="checkbox" name="use_session" id="use_session" value="1">
                            Use session
                        </label>

                        <input type="hidden" name="ajax" value="1">

                        <button type="submit">Mark Attendance</button>
                    </form>
                </div>
            </section>

            <section class="checkin-section">
                <h3>QR Attendance</h3>

                <div class="qr-card">
                    <video id="video" autoplay muted playsinline></video>
                    <div id="videoPlaceholder" class="video-placeholder hidden">VIDEO</div>
                    <canvas id="canvas" style="display:none;"></canvas>

                    <div class="qr-controls">
                        <button type="button" id="toggleScanner">⏸</button>
                    </div>

                    <form method="POST" id="qrForm">
                        <input type="hidden" name="qr_token" id="qr_token">
                        <input type="hidden" name="attendance_date" id="qr_attendance_date" value="<?= htmlspecialchars($selectedDate) ?>">
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
        const feedbackTitle = document.getElementById('feedbackTitle');
        const successDetails = document.getElementById('successDetails');

        const passEnd = document.getElementById('passEnd');
        const passRemaining = document.getElementById('passRemaining');
        const membershipEnd = document.getElementById('membershipEnd');
        const membershipRemaining = document.getElementById('membershipRemaining');
        const remainingSession = document.getElementById('remainingSession');

        const manualForm = document.getElementById('manualForm');
        const attendanceDate = document.getElementById('attendance_date');
        const qrAttendanceDate = document.getElementById('qr_attendance_date');

        const video = document.getElementById('video');
        const videoPlaceholder = document.getElementById('videoPlaceholder');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });

        const qrForm = document.getElementById('qrForm');
        const qrInput = document.getElementById('qr_token');
        const toggleScanner = document.getElementById('toggleScanner');

        const successSound = document.getElementById('successSound');
        const errorSound = document.getElementById('errorSound');

        let stream = null;
        let scanning = true;
        let qrCooldown = false;

        function playSound(type) {
            const sound = type === 'success' ? successSound : errorSound;

            if (!sound) {
                return;
            }

            sound.currentTime = 0;
            sound.play().catch(() => {});
        }

        function remainingText(value, fallback = 'N/A') {
            if (value === null || value === undefined) {
                return fallback;
            }

            const days = Number(value);
            return days === 1 ? '1 day remaining' : `${days} days remaining`;
        }

        function showFeedback(response) {
            feedbackBox.classList.remove('hidden', 'success', 'error', 'warning');
            feedbackBox.classList.add(response.status);

            feedbackTitle.textContent = response.message || 'Check-in result';

            if (response.status === 'success' && response.data) {
                successDetails.classList.remove('hidden');

                passEnd.textContent = response.data.pass_end || 'N/A';
                passRemaining.textContent = remainingText(response.data.pass_days_remaining);

                membershipEnd.textContent = response.data.membership_end || 'N/A';
                membershipRemaining.textContent = remainingText(response.data.membership_days_remaining);

                remainingSession.textContent = response.data.remaining_sessions ?? 'N/A';
            } else {
                successDetails.classList.add('hidden');
            }

            playSound(response.status === 'success' ? 'success' : 'error');

            setTimeout(() => {
                feedbackBox.classList.add('hidden');
            }, 10000);
        }

        async function submitForm(form) {
            const formData = new FormData(form);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            return await response.json();
        }

        manualForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const response = await submitForm(manualForm);
            showFeedback(response);
        });

        attendanceDate.addEventListener('change', function () {
            qrAttendanceDate.value = attendanceDate.value;
        });

        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment'
                    }
                });

                video.srcObject = stream;
                video.classList.remove('hidden');
                videoPlaceholder.classList.add('hidden');

                scanning = true;
                toggleScanner.textContent = '⏸';

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

        function pauseScanner() {
            scanning = false;
            toggleScanner.textContent = '▶';
        }

        function resumeScanner() {
            scanning = true;
            toggleScanner.textContent = '⏸';
            requestAnimationFrame(scanFrame);
        }

        function scanFrame() {
            if (!scanning || qrCooldown) {
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

        toggleScanner.addEventListener('click', function () {
            if (scanning) {
                pauseScanner();
            } else {
                resumeScanner();
            }
        });

        startCamera();
    </script>
</body>

</html>