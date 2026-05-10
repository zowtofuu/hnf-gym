<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/membership_rules.php';


function getAddClientMembershipPlans(PDO $pdo): array
{
    return getEnforcedMembershipPlans($pdo);
}

function addClient(PDO $pdo, array $data): int
{
    $firstName = trim((string) ($data['first_name'] ?? ''));
    $lastName = trim((string) ($data['last_name'] ?? ''));
    $contact = trim((string) ($data['contact'] ?? ''));
    $membershipType = trim((string) ($data['membership_type'] ?? ''));
    $passType = trim((string) ($data['pass_type'] ?? ''));

    validateAddClientData($firstName, $lastName, $contact, $membershipType, $passType);

    $pdo->beginTransaction();

    try {
        $plan = getEnforcedMembershipPlan($pdo, $membershipType, $passType);

        if ($plan === null) {
            throw new RuntimeException('Selected membership plan was not found.');
        }

        $clientId = insertClient($pdo, $firstName, $lastName, $contact);
        $subscriptionId = insertClientSubscription($pdo, $clientId, $plan);

        insertSubscriptionSale($pdo, $subscriptionId, $clientId, $plan);

        if ($membershipType === 'member') {
            insertAnnualMembershipSale($pdo, $clientId, $subscriptionId);
        }

        $pdo->commit();

        return $clientId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

function validateAddClientData(
    string $firstName,
    string $lastName,
    string $contact,
    string $membershipType,
    string $passType
): void {
    if ($firstName === '') {
        throw new InvalidArgumentException('First name is required.');
    }

    if ($lastName === '') {
        throw new InvalidArgumentException('Last name is required.');
    }

    if (!preg_match('/^09\d{9}$/', $contact)) {
        throw new InvalidArgumentException('Contact number must be 11 digits and start with 09.');
    }

    if (!isValidMembershipType($membershipType)) {
        throw new InvalidArgumentException('Invalid membership type.');
    }

    if (!isValidPassType($passType)) {
        throw new InvalidArgumentException('Invalid pass type.');
    }
}

function insertClient(PDO $pdo, string $firstName, string $lastName, string $contact): int
{
    $sql = "INSERT INTO clients (first_name, last_name, contact)
            VALUES (:first_name, :last_name, :contact)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':contact' => $contact,
    ]);

    return (int) $pdo->lastInsertId();
}

function insertClientSubscription(PDO $pdo, int $clientId, array $plan): int
{
    $today = date('Y-m-d');
    $membershipType = (string) $plan['membership_type'];
    $passType = (string) $plan['pass_type'];

    $subscriptionStart = $today;
    $subscriptionEnd = computeMembershipEndDate($subscriptionStart, $passType);

    $membershipStart = null;
    $membershipEnd = null;

    if ($membershipType === 'member') {
        $membershipStart = $today;
        $membershipEnd = (new DateTimeImmutable($today))->modify('+1 year')->modify('-1 day')->format('Y-m-d');
    }

    $sql = "INSERT INTO subscriptions (
                client_id,
                plan_id,
                membership_start,
                membership_end,
                subscription_start,
                subscription_end,
                subscription_token,
                status
            ) VALUES (
                :client_id,
                :plan_id,
                :membership_start,
                :membership_end,
                :subscription_start,
                :subscription_end,
                :subscription_token,
                'active'
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':client_id' => $clientId,
        ':plan_id' => (int) $plan['id'],
        ':membership_start' => $membershipStart,
        ':membership_end' => $membershipEnd,
        ':subscription_start' => $subscriptionStart,
        ':subscription_end' => $subscriptionEnd,
        ':subscription_token' => generateSubscriptionToken(),
    ]);

    return (int) $pdo->lastInsertId();
}

function generateSubscriptionToken(): string
{
    return bin2hex(random_bytes(24));
}

function insertSubscriptionSale(PDO $pdo, int $subscriptionId, int $clientId, array $plan): void
{
    $sql = "INSERT INTO sales (
                transaction_type,
                reference_id,
                client_id,
                item_name,
                quantity,
                amount
            ) VALUES (
                'subscription',
                :reference_id,
                :client_id,
                :item_name,
                1,
                :amount
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':reference_id' => $subscriptionId,
        ':client_id' => $clientId,
        ':item_name' => membershipPlanName($plan),
        ':amount' => (float) $plan['price'],
    ]);
}

function insertAnnualMembershipSale(PDO $pdo, int $clientId, int $subscriptionId): void
{
    $annualFee = getAnnualMembershipFee($pdo);

    if ($annualFee <= 0) {
        return;
    }

    $sql = "INSERT INTO sales (
                transaction_type,
                reference_id,
                client_id,
                item_name,
                quantity,
                amount
            ) VALUES (
                'annual_membership',
                :reference_id,
                :client_id,
                :item_name,
                1,
                :amount
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':reference_id' => $subscriptionId,
        ':client_id' => $clientId,
        ':item_name' => 'Annual Membership Fee',
        ':amount' => $annualFee,
    ]);
}

function getAnnualMembershipFee(PDO $pdo): float
{
    $sql = "SELECT price
            FROM other_pricings
            WHERE item = :item
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':item' => 'annual_mem_fee']);

    $price = $stmt->fetchColumn();

    return $price === false ? 0.00 : (float) $price;
}
