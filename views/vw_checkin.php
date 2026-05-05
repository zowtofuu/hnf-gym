<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Check-in</title>
    <script src="../assets/js/jsQR.min.js"></script>
</head>

<body>

    <?php include '../components/navbar.php'; ?>

    <div class="wrapper">

        <?php if ($message): ?>
            <p id="alert-message">
                <?= htmlspecialchars($message) ?>
            </p>

            <script>
                setTimeout(() => {
                    const msg = document.getElementById('alert-message');
                    if (msg) {
                        msg.style.opacity = '0';
                        setTimeout(() => msg.remove(), 500); // smooth fade out
                    }
                }, 5000); // 5 seconds
            </script>
        <?php endif; ?>

        <div style="display:flex; gap:40px;">

            <!-- MANUAL -->
            <div>
                <h3>Manual Attendance</h3>

                <form method="POST">
                    <select name="client_id" required>
                        <option value="">Select client</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['client_id'] ?>">
                                <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="date" name="attendance_date" value="<?= $selectedDate ?>" required>

                    <button type="submit">Mark Attendance</button>
                </form>
            </div>

            <!-- QR -->
            <div>
                <h3>QR Attendance</h3>

                <video id="video" width="300" autoplay></video>
                <canvas id="canvas" style="display:none;"></canvas>

                <button id="toggle">Pause</button>

                <form method="POST" id="qrForm">
                    <input type="hidden" name="qr_token" id="qr_token">
                    <input type="hidden" name="attendance_date" value="<?= $selectedDate ?>">
                </form>
            </div>

        </div>

        <script>
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const ctx = canvas.getContext('2d');
            const qrInput = document.getElementById('qr_token');
            const form = document.getElementById('qrForm');
            const toggle = document.getElementById('toggle');

            let stream, scanning = true;

            async function start() {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                scan();
            }

            function stop() {
                stream.getTracks().forEach(t => t.stop());
            }

            function scan() {
                if (!scanning) return;

                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;

                    ctx.drawImage(video, 0, 0);
                    const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(img.data, img.width, img.height);

                    if (code) {
                        qrInput.value = code.data.trim();
                        scanning = false;
                        stop();
                        form.submit();
                        return;
                    }
                }
                requestAnimationFrame(scan);
            }

            toggle.onclick = () => {
                scanning = !scanning;
                toggle.textContent = scanning ? 'Pause' : 'Play';
                if (scanning) start();
            };

            start();
        </script>
        
</body>

</html>