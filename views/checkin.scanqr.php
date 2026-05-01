<?php
require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';
$selectedDate = date('Y-m-d');
$scannedToken = '';
$checkedInClient = null;

/*
|--------------------------------------------------------------------------
| HANDLE QR CHECK-IN SUBMISSION
|--------------------------------------------------------------------------
| QR contains: subscription_token
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scannedToken = isset($_POST['qr_token']) ? trim($_POST['qr_token']) : '';
    $selectedDate = isset($_POST['attendance_date']) && $_POST['attendance_date'] !== ''
        ? trim($_POST['attendance_date'])
        : date('Y-m-d');

    $dateObject = DateTime::createFromFormat('Y-m-d', $selectedDate);

    if ($scannedToken === '') {
        $message = 'No QR token received.';
        $messageType = 'error';
    } elseif (!$dateObject || $dateObject->format('Y-m-d') !== $selectedDate) {
        $message = 'Invalid attendance date.';
        $messageType = 'error';
    } else {
        /*
        |--------------------------------------------------------------------------
        | STEP 1: FIND SUBSCRIPTION BY TOKEN
        |--------------------------------------------------------------------------
        | Also verify:
        | - subscription exists
        | - subscription is active
        | - selected date is within start/end
        */
        $stmt = $conn->prepare("
            SELECT
                s.subscription_id,
                s.client_id,
                s.subscription_token,
                s.subscription_start,
                s.subscription_end,
                s.status,
                c.first_name,
                c.last_name
            FROM subscriptions s
            INNER JOIN clients c ON c.client_id = s.client_id
            WHERE s.subscription_token = ?
            LIMIT 1
        ");

        if (!$stmt) {
            die('Prepare failed: ' . $conn->error);
        }

        $stmt->bind_param("s", $scannedToken);
        $stmt->execute();
        $subscriptionResult = $stmt->get_result();
        $subscription = $subscriptionResult->fetch_assoc();
        $stmt->close();

        if (!$subscription) {
            $message = 'Invalid QR code. Subscription not found.';
            $messageType = 'error';
        } else {
            /*
            |--------------------------------------------------------------------------
            | STEP 2: VALIDATE SUBSCRIPTION STATUS AND DATE RANGE
            |--------------------------------------------------------------------------
            */
            if ($subscription['status'] !== 'active') {
                $message = 'This subscription is not active.';
                $messageType = 'error';
            } elseif ($selectedDate < $subscription['subscription_start'] || $selectedDate > $subscription['subscription_end']) {
                $message = 'Subscription is not valid for the selected date.';
                $messageType = 'error';
            } else {
                $clientId = (int) $subscription['client_id'];

                /*
                |--------------------------------------------------------------------------
                | STEP 3: CHECK IF ATTENDANCE ALREADY EXISTS
                |--------------------------------------------------------------------------
                */
                $stmt = $conn->prepare("
                    SELECT attendance_id
                    FROM attendance
                    WHERE client_id = ? AND attendance_date = ?
                    LIMIT 1
                ");

                if (!$stmt) {
                    die('Prepare failed: ' . $conn->error);
                }

                $stmt->bind_param("is", $clientId, $selectedDate);
                $stmt->execute();
                $attendanceResult = $stmt->get_result();
                $existingAttendance = $attendanceResult->fetch_assoc();
                $stmt->close();

                if ($existingAttendance) {
                    $fullName = trim($subscription['first_name'] . ' ' . $subscription['last_name']);
                    $message = $fullName . ' already checked in on this date.';
                    $messageType = 'error';
                } else {
                    /*
                    |--------------------------------------------------------------------------
                    | STEP 4: INSERT ATTENDANCE
                    |--------------------------------------------------------------------------
                    */
                    $checkInTime = date('H:i:s');

                    $stmt = $conn->prepare("
                        INSERT INTO attendance (client_id, attendance_date, check_in_time)
                        VALUES (?, ?, ?)
                    ");

                    if (!$stmt) {
                        die('Prepare failed: ' . $conn->error);
                    }

                    $stmt->bind_param("iss", $clientId, $selectedDate, $checkInTime);

                    if ($stmt->execute()) {
                        $checkedInClient = [
                            'full_name' => trim($subscription['first_name'] . ' ' . $subscription['last_name']),
                            'subscription_token' => $subscription['subscription_token'],
                            'attendance_date' => $selectedDate,
                            'check_in_time' => $checkInTime,
                        ];

                        $message = 'Check-in successful for ' . $checkedInClient['full_name'] . '.';
                        $messageType = 'success';
                    } else {
                        if ($conn->errno === 1062) {
                            $message = 'Client already checked in on this date.';
                        } else {
                            $message = 'Failed to save attendance.';
                        }
                        $messageType = 'error';
                    }

                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Check-in</title>
    <script src="../assets/js/jsQR.min.js"></script>
</head>

<body>
    <?php include '../components/navbar.php'; ?>
    <div class="wrapper">

        <h2 class="legend">QR Check-in</h2>

        <div class="rounded-container p1">
            <?php if ($message !== ''): ?>
                <p>
                    <strong><?php echo $messageType === 'success' ? 'Success:' : 'Notice:'; ?></strong>
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <?php if ($checkedInClient): ?>
                <div>
                    <p><strong>Client:</strong> <?php echo htmlspecialchars($checkedInClient['full_name']); ?></p>
                    <p><strong>Token:</strong> <?php echo htmlspecialchars($checkedInClient['subscription_token']); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($checkedInClient['attendance_date']); ?></p>
                    <p><strong>Time:</strong> <?php echo htmlspecialchars($checkedInClient['check_in_time']); ?></p>
                </div>
                <hr>
            <?php endif; ?>

            <div class="pb">
                <label for="attendance_date">Check-in Date</label><br>
                <input class="date-input" type="date" id="attendance_date"
                    value="<?php echo htmlspecialchars($selectedDate); ?>" required>
            </div>

            <div>
                <button class="btn btn-text" type="button" id="startScannerBtn">Start Scanner</button>
                <button class="btn btn-text" type="button" id="stopScannerBtn">Stop Scanner</button>
            </div>

            <div class="px">
                <video id="qr-video" width="400" height="300" autoplay playsinline></video>
                <canvas id="qr-canvas" width="400" height="300" style="display:none;"></canvas>
            </div>

            <p><strong>Scanner Status:</strong> <span id="scanner-status">Idle</span></p>
            <p><strong>Scanned Token:</strong> <span id="scanned-token">None</span></p>

            <form method="POST" action="" id="qr-checkin-form">
                <input type="hidden" name="qr_token" id="qr_token" value="">
                <input type="hidden" name="attendance_date" id="form_attendance_date"
                    value="<?php echo htmlspecialchars($selectedDate); ?>">
            </form>
        </div>
    </div>


    <script>
        const video = document.getElementById('qr-video');
        const canvas = document.getElementById('qr-canvas');
        const canvasContext = canvas.getContext('2d');
        const scannerStatus = document.getElementById('scanner-status');
        const scannedTokenText = document.getElementById('scanned-token');
        const qrTokenInput = document.getElementById('qr_token');
        const attendanceDateInput = document.getElementById('attendance_date');
        const formAttendanceDateInput = document.getElementById('form_attendance_date');
        const qrCheckinForm = document.getElementById('qr-checkin-form');
        const startScannerBtn = document.getElementById('startScannerBtn');
        const stopScannerBtn = document.getElementById('stopScannerBtn');

        let stream = null;
        let animationFrameId = null;
        let isScanning = false;
        let hasSubmitted = false;

        /**
         * Keep hidden form date synced with visible date input
         */
        attendanceDateInput.addEventListener('change', function () {
            formAttendanceDateInput.value = this.value;
        });

        /**
         * Start camera and scanner
         */
        async function startScanner() {
            if (isScanning) {
                return;
            }

            hasSubmitted = false;

            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment'
                    },
                    audio: false
                });

                video.srcObject = stream;
                scannerStatus.textContent = 'Camera started. Point it at a QR code.';
                isScanning = true;

                video.onloadedmetadata = function () {
                    video.play();
                    scanFrame();
                };
            } catch (error) {
                console.error(error);
                scannerStatus.textContent = 'Unable to access camera.';
            }
        }

        /**
         * Stop camera and scanner
         */
        function stopScanner() {
            isScanning = false;

            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
                animationFrameId = null;
            }

            if (stream) {
                stream.getTracks().forEach(function (track) {
                    track.stop();
                });
                stream = null;
            }

            video.srcObject = null;
            scannerStatus.textContent = 'Scanner stopped.';
        }

        /**
         * Scan every frame for QR code
         */
        function scanFrame() {
            if (!isScanning || hasSubmitted) {
                return;
            }

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvasContext.drawImage(video, 0, 0, canvas.width, canvas.height);

                const imageData = canvasContext.getImageData(0, 0, canvas.width, canvas.height);
                const qrCode = jsQR(imageData.data, imageData.width, imageData.height);

                if (qrCode && qrCode.data) {
                    const token = qrCode.data.trim();

                    if (token !== '') {
                        scannedTokenText.textContent = token;
                        scannerStatus.textContent = 'QR detected. Submitting check-in...';

                        qrTokenInput.value = token;
                        formAttendanceDateInput.value = attendanceDateInput.value;

                        hasSubmitted = true;
                        stopScanner();
                        qrCheckinForm.submit();
                        return;
                    }
                }
            }

            animationFrameId = requestAnimationFrame(scanFrame);
        }

        startScannerBtn.addEventListener('click', startScanner);
        stopScannerBtn.addEventListener('click', stopScanner);

        /**
         * Optional auto-start
         */
        startScanner();
    </script>
</body>

</html>