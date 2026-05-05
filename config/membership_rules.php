<?php
declare(strict_types=1);

function membershipPriceRules(): array
{
    return [
        'member' => [
            'daily' => 80.00,
            'monthly' => 800.00,
        ],
        'non_member' => [
            'daily' => 100.00,
            'monthly' => 1000.00,
        ],
        'student_senior' => [
            'daily' => 50.00,
            'monthly' => 500.00,
        ],
    ];
}

function membershipTypeLabels(): array
{
    return [
        'member' => 'Member',
        'non_member' => 'Non Member',
        'student_senior' => 'Student/Senior',
    ];
}

function passTypeLabels(): array
{
    return [
        'daily' => 'Daily',
        'monthly' => 'Monthly',
    ];
}

function isValidMembershipType(string $membershipType): bool
{
    return array_key_exists($membershipType, membershipPriceRules());
}

function isValidPassType(string $passType): bool
{
    return array_key_exists($passType, passTypeLabels());
}

function membershipPrice(string $membershipType, string $passType): ?float
{
    $rules = membershipPriceRules();

    if (!isset($rules[$membershipType][$passType])) {
        return null;
    }

    return (float) $rules[$membershipType][$passType];
}

function membershipTypeLabel(string $membershipType): string
{
    $labels = membershipTypeLabels();

    return $labels[$membershipType] ?? ucwords(str_replace('_', ' ', $membershipType));
}

function passTypeLabel(string $passType): string
{
    $labels = passTypeLabels();

    return $labels[$passType] ?? ucwords(str_replace('_', ' ', $passType));
}

function membershipPlanName(array $plan): string
{
    return membershipTypeLabel((string) $plan['membership_type'])
        . ' - '
        . passTypeLabel((string) $plan['pass_type']);
}

function membershipPlanDefinitions(): array
{
    $plans = [];

    foreach (membershipPriceRules() as $membershipType => $passPrices) {
        foreach ($passPrices as $passType => $price) {
            $plans[] = [
                'membership_type' => $membershipType,
                'pass_type' => $passType,
                'price' => (float) $price,
            ];
        }
    }

    return $plans;
}

function membershipPlanDurationDays(string $passType): int
{
    return $passType === 'monthly' ? 30 : 1;
}

function computeMembershipEndDate(string $startDate, string $passType): string
{
    $date = new DateTimeImmutable($startDate);

    if ($passType === 'monthly') {
        $date = $date->modify('+1 month')->modify('-1 day');
    }

    return $date->format('Y-m-d');
}

function isValidMembershipDate(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

    return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
}

function membershipPlanColumnExists(PDO $pdo, string $column): bool
{
    $sql = "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'membership_plans'
              AND COLUMN_NAME = :column";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function ensureMembershipPlans(PDO $pdo): void
{
    $hasDurationDays = membershipPlanColumnExists($pdo, 'duration_days');

    if ($hasDurationDays) {
        $sql = "INSERT INTO membership_plans (
                    membership_type,
                    pass_type,
                    price,
                    duration_days
                ) VALUES (
                    :membership_type,
                    :pass_type,
                    :price,
                    :duration_days
                )
                ON DUPLICATE KEY UPDATE
                    price = VALUES(price),
                    duration_days = VALUES(duration_days)";
    } else {
        $sql = "INSERT INTO membership_plans (
                    membership_type,
                    pass_type,
                    price
                ) VALUES (
                    :membership_type,
                    :pass_type,
                    :price
                )
                ON DUPLICATE KEY UPDATE
                    price = VALUES(price)";
    }

    $stmt = $pdo->prepare($sql);

    foreach (membershipPlanDefinitions() as $plan) {
        $params = [
            ':membership_type' => $plan['membership_type'],
            ':pass_type' => $plan['pass_type'],
            ':price' => $plan['price'],
        ];

        if ($hasDurationDays) {
            $params[':duration_days'] = membershipPlanDurationDays((string) $plan['pass_type']);
        }

        $stmt->execute($params);
    }
}

function normalizeMembershipPlan(array $plan): ?array
{
    $price = membershipPrice((string) $plan['membership_type'], (string) $plan['pass_type']);

    if ($price === null) {
        return null;
    }

    $plan['price'] = $price;

    return $plan;
}

function getEnforcedMembershipPlans(PDO $pdo): array
{
    ensureMembershipPlans($pdo);

    $sql = "SELECT
                id,
                membership_type,
                pass_type,
                price
            FROM membership_plans
            WHERE membership_type IN ('member', 'non_member', 'student_senior')
              AND pass_type IN ('daily', 'monthly')
            ORDER BY
                FIELD(membership_type, 'member', 'non_member', 'student_senior'),
                FIELD(pass_type, 'daily', 'monthly')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $plans = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $plan) {
        $normalized = normalizeMembershipPlan($plan);

        if ($normalized !== null) {
            $plans[] = $normalized;
        }
    }

    return $plans;
}

function getEnforcedMembershipPlan(PDO $pdo, string $membershipType, string $passType): ?array
{
    if (!isValidMembershipType($membershipType) || !isValidPassType($passType)) {
        return null;
    }

    ensureMembershipPlans($pdo);

    $sql = "SELECT
                id,
                membership_type,
                pass_type,
                price
            FROM membership_plans
            WHERE membership_type = :membership_type
              AND pass_type = :pass_type
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':membership_type' => $membershipType,
        ':pass_type' => $passType,
    ]);

    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    return $plan ? normalizeMembershipPlan($plan) : null;
}

function getEnforcedMembershipPlanById(PDO $pdo, int $planId): ?array
{
    ensureMembershipPlans($pdo);

    $sql = "SELECT
                id,
                membership_type,
                pass_type,
                price
            FROM membership_plans
            WHERE id = :id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $planId]);

    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    return $plan ? normalizeMembershipPlan($plan) : null;
}
