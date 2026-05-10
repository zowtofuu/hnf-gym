<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../assets/phpqrcode/phpqrcode.php';

// FETCH TOKEN
function fetchToken(PDO $pdo, int $subscriptionId): ?string
{
    $sql = "SELECT subscription_token 
            FROM subscriptions 
            WHERE subscription_id = :subscription_id 
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':subscription_id' => $subscriptionId
    ]);

    $token = $stmt->fetchColumn();

    return $token ?: null;
}

// GENERATE QR BASE64 FROM TOKEN
function generateQrBase64(string $token): ?string
{
    ob_start();

    QRcode::png($token, null, QR_ECLEVEL_M, 8, 2);

    $qrImage = ob_get_clean();

    if ($qrImage === false || $qrImage === '') {
        return null;
    }

    return base64_encode($qrImage);
}

// Get subscription id from URL
$subscriptionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$token = null;
$qrBase64 = null;

if ($subscriptionId > 0) {
    $token = fetchToken($pdo, $subscriptionId);

    if ($token) {
        $qrBase64 = generateQrBase64($token);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Info</title>
</head>
<body>

<?php if ($token && $qrBase64): ?>

    <p>Token here: <?= htmlspecialchars($token) ?></p>

    <div class="qr-container">
        <img 
            src="data:image/png;base64,<?= htmlspecialchars($qrBase64) ?>" 
            alt="Subscription QR Code"
        >
    </div>

<?php elseif ($token && !$qrBase64): ?>

    <p>Token found, but QR code failed to generate.</p>

<?php else: ?>

    <p>No token found.</p>

<?php endif; ?>

</body>
</html>