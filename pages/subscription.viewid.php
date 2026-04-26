<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/utility.php';
require_once __DIR__ . '/../assets/phpqrcode/qrlib.php';

/*
|--------------------------------------------------------------------------
| Get subscription ID
|--------------------------------------------------------------------------
*/
$subscriptionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($subscriptionId <= 0) {
    http_response_code(400);
    exit('Invalid subscription ID.');
}

/*
|--------------------------------------------------------------------------
| Fetch subscription + client info
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        s.subscription_id,
        s.subscription_start,
        s.subscription_end,
        s.subscription_token,
        c.first_name,
        c.last_name
    FROM subscriptions s
    INNER JOIN clients c ON c.client_id = s.client_id
    WHERE s.subscription_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    exit('Failed to prepare query.');
}

$stmt->bind_param('i', $subscriptionId);
$stmt->execute();
$result = $stmt->get_result();
$subscription = $result->fetch_assoc();
$stmt->close();

if (!$subscription) {
    http_response_code(404);
    exit('Subscription not found.');
}

/*
|--------------------------------------------------------------------------
| Prepare display values
|--------------------------------------------------------------------------
*/
$fullName = trim(
    (string) ($subscription['first_name'] ?? '') . ' ' .
    (string) ($subscription['last_name'] ?? '')
);

$startDate = !empty($subscription['subscription_start'])
    ? formatReadableDate($subscription['subscription_start'])
    : '';

$endDate = !empty($subscription['subscription_end'])
    ? formatReadableDate($subscription['subscription_end'])
    : '';

$token = (string) ($subscription['subscription_token'] ?? '');

if ($token === '') {
    http_response_code(500);
    exit('Subscription token is missing.');
}

/*
|--------------------------------------------------------------------------
| Generate QR as base64 image
|--------------------------------------------------------------------------
*/
ob_start();
QRcode::png($token, null, QR_ECLEVEL_M, 8, 2);
$qrImage = ob_get_clean();

if ($qrImage === false || $qrImage === '') {
    http_response_code(500);
    exit('Failed to generate QR code.');
}

$qrBase64 = base64_encode($qrImage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View ID</title>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    <div class="wrapper">
        <h1 class="legend">Subscription ID</h1>

        <div class="p1">
            <div>
                <span class="label">Name:</span>
                <?php echo htmlspecialchars($fullName); ?>
            </div>
            <div>
                <span class="label">Start:</span>
                <?php echo htmlspecialchars($startDate); ?>
            </div>
            <div>
                <span class="label">End:</span>
                <?php echo htmlspecialchars($endDate); ?>
            </div>
        </div>

        <div class="qr-wrap">
            <img
                src="data:image/png;base64,<?php echo $qrBase64; ?>"
                alt="Subscription QR Code"
            >
        </div>
    </div>
</body>
</html>