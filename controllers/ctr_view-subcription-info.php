<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../assets/phpqrcode/phpqrcode.php';

$subscriptionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$token     = null;
$fullName  = null;
$contactNo = null;
$qrBase64  = null;

if ($subscriptionId > 0) {
    $subsSql = "SELECT s.subscription_token,
                CONCAT(c.first_name, ' ', c.last_name) AS full_name,
                c.contact
                FROM subscriptions AS s
                INNER JOIN clients AS c ON s.client_id = c.client_id
                WHERE s.subscription_id = ?
                LIMIT 1";
    $stmt = $pdo->prepare($subsSql);
    $stmt->execute([$subscriptionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $token     = $row['subscription_token'];
        $fullName  = $row['full_name'];
        $contactNo = $row['contact'];
    }
}

if ($token) {
    $qrDir    = __DIR__ . '/../assets/images/qr/';
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fullName);
    $qrFile   = $qrDir . 'qr_' . $safeName . '_' . md5($token) . '.png';

    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0755, true);
    }

    if (!file_exists($qrFile)) {
        QRcode::png($token, $qrFile, QR_ECLEVEL_M, 8, 2);
    }

    $qrBase64 = base64_encode(file_get_contents($qrFile));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($fullName) ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: #f0f0f0;
            font-family: Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /*
         * CR80 / ISO 7810 ID-1 standard
         * Physical size: 85.60 mm × 53.98 mm
         * At 96 DPI screen: 323.4 px × 204.1 px  → rounded to 323 × 204 px
         * Corner radius (ISO): 3.18 mm → ~12 px at 96 DPI
         */
        .id-card {
            width:  323px;
            height: 204px;

            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #bbb;
            box-shadow: 0 4px 18px rgba(0,0,0,.18);

            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* ── Header strip ── */
        .id-header {
            background: #1a1a1a;
            height: 44px;
            flex-shrink: 0;
            padding: 0 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .id-header img.logo {
            height: 22px;
            width: auto;
            flex-shrink: 0;
        }

        /* ── Address badge ── */
        .id-address {
            display: flex;
            align-items: center;
            gap: 5px;
            text-align: right;
        }

        /* Pin SVG icon inline */
        .id-address .addr-icon {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
            fill: rgba(255,255,255,.45);
        }

        .id-address .addr-lines {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .id-address .addr-top {
            font-size: 7px;
            font-weight: 700;
            color: #fff;
            letter-spacing: .3px;
            white-space: nowrap;
        }

        .id-address .addr-bottom {
            font-size: 6px;
            color: rgba(255,255,255,.48);
            letter-spacing: .2px;
            white-space: nowrap;
        }

        /* ── Body: true 50/50 split ── */
        .id-body {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        /* ── LEFT HALF — avatar + info ── */
        .id-left {
            width: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 10px;
            min-width: 0;
        }

        /* Avatar — centered above the text block */
        .avatar-frame {
            width: 96px;
            height: 96px;
            border: 1px solid #ccc;
            border-radius: 3px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .avatar-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Info fields */
        .id-info {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .info-field {
            text-transform: capitalize;
            width: 100%;
        }

        .info-label {
            font-size: 6px;
            font-weight: bold;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #999;
        }

        .info-value {
            font-size: 10.5px;
            font-weight: bold;
            color: #111;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── RIGHT HALF — QR ── */
        .id-qr {
            width: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 8px 10px;
            border-left: 1px solid #e8e8e8;
            background: #fafafa;
        }

        .qr-frame {
            border: 1px solid #ddd;
            border-radius: 3px;
            line-height: 0;
            background: #fff;
        }

        .qr-frame img {
            width: 124px;
            height: 124px;
            display: block;
        }

        .qr-label {
            font-size: 6px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #bbb;
            text-align: center;
        }

        /* ── Print ── */
        @media print {
            body {
                background: #fff;
                min-height: unset;
            }

            .id-card {
                width:  85.6mm;
                height: 54mm;
                border-radius: 3.18mm;
                box-shadow: none;
                border: 0.5pt solid #aaa;
            }

            .id-header          { height: 12mm; }
            .id-header img.logo { height: 6mm; }
            .avatar-frame       { width: 25.4mm; height: 25.4mm; }
            .qr-frame img       { width: 32.80mm; height: 32.80mm; }
        }
    </style>
</head>
<body>

<?php if ($token && $qrBase64): ?>
    <div class="id-card">

        <div class="id-header">
            <img class="logo" src="../assets/images/logo.png" alt="Logo">

            <div class="id-address">
                <!-- Map-pin SVG icon -->
                <svg class="addr-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/>
                </svg>
                <div class="addr-lines">
                    <span class="addr-top">3/F Jenligtie Bldg · Alfamart Grand Valley Ph6</span>
                    <span class="addr-bottom">Angono, Rizal 1930, Philippines</span>
                </div>
            </div>
        </div>

        <div class="id-body">

            <div class="id-left">
                <div class="avatar-frame">
                    <img src="../assets/images/client-placeholder.png" alt="Photo">
                </div>
                <div class="id-info">
                    <div class="info-field">
                        <span class="info-label">Full Name</span>
                        <span class="info-value"><?= htmlspecialchars($fullName) ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Contact No.</span>
                        <span class="info-value"><?= htmlspecialchars($contactNo ?? '—') ?></span>
                    </div>
                </div>
            </div>

            <div class="id-qr">
                <div class="qr-frame">
                    <img src="data:image/png;base64,<?= $qrBase64 ?>" alt="QR Code">
                </div>
                <span class="qr-label">Scan to Verify</span>
            </div>

        </div>

    </div>
<?php endif; ?>

</body>
</html>