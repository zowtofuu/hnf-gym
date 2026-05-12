<?php

function hnfEscape(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function hnfSelected(string $currentValue, string $optionValue): string
{
    return $currentValue === $optionValue ? 'selected' : '';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription</title>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>

<body>
    <?php include __DIR__ . '/../components/navbar.php'; ?>

    <div class="wrapper">
        <h3 class="legend">Subscription</h3>

        <!-- CONTROLS -->
        <section class="flex justify-between flex-wrap pb-lg">
            <form action="" method="get">
                <input class="capitalize rounded-sm px-md py-sm focus-visible" type="text" id="search" name="search"
                    placeholder="Search by client name" value="<?= hnfEscape($filters['search'] ?? '') ?>">

                <select class="capitalize rounded-sm px-md py-sm focus-visible" id="membership_type"
                    name="membership_type">
                    <option value="">All Membership Types</option>

                    <?php foreach (($filterOptions['membership_types'] ?? []) as $membershipType): ?>
                        <option value="<?= hnfEscape($membershipType) ?>" <?= hnfSelected($filters['membership_type'] ?? '', $membershipType) ?>>
                            <?= hnfEscape(hnfMembershipTypeLabel($membershipType)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select class="capitalize rounded-sm px-md py-sm focus-visible" id="pass_type" name="pass_type">
                    <option value="">All Pass Types</option>
                    <?php foreach (($filterOptions['pass_types'] ?? []) as $passType): ?>
                        <option value="<?= hnfEscape($passType) ?>" <?= hnfSelected($filters['pass_type'] ?? '', $passType) ?>>
                            <?= hnfEscape(hnfPassTypeLabel($passType)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select class="capitalize rounded-sm px-md py-sm focus-visible" id="status_filter" name="status_filter">
                    <option value="">All Status</option>

                    <option value="active" <?= hnfSelected($filters['status_filter'] ?? '', 'active') ?>>
                        Active
                    </option>

                    <option value="suspended" <?= hnfSelected($filters['status_filter'] ?? '', 'suspended') ?>>
                        Suspended
                    </option>

                    <option value="expired_membership" <?= hnfSelected($filters['status_filter'] ?? '', 'expired_membership') ?>>
                        Expired Membership
                    </option>

                    <option value="expired_pass" <?= hnfSelected($filters['status_filter'] ?? '', 'expired_pass') ?>>
                        Expired Pass
                    </option>
                </select>

                <select class="capitalize rounded-sm px-md py-sm focus-visible" id="expiring_filter"
                    name="expiring_filter">
                    <option value="">Select Expiring</option>

                    <option value="membership" <?= hnfSelected($filters['expiring_filter'] ?? '', 'membership') ?>>
                        Expiring Membership
                    </option>

                    <option value="pass" <?= hnfSelected($filters['expiring_filter'] ?? '', 'pass') ?>>
                        Expiring Pass
                    </option>
                </select>

                <button class="capitalize rounded-sm px-md py-sm cursor-pointer btn-primary"
                    type="submit">Filter</button>
                <a class="capitalize rounded-sm px-md py-sm btn-anchor btn-secondary"
                    href="ctr_subscriptions.php">Reset</a>
            </form>
        </section>

        <!-- TABLE -->
        <section>
            <?php if (empty($subscriptions)): ?>
                <?php include __DIR__ . '/../components/alert.php'; ?>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($columns as $label): ?>
                                <th><?= hnfEscape($label) ?></th>
                            <?php endforeach; ?>

                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <tr>
                                <?php foreach ($columns as $key => $label): ?>
                                    <td>
                                        <?php
                                        $value = $subscription[$key] ?? null;

                                        if ($key === 'membership_type') {
                                            echo hnfEscape(hnfMembershipTypeLabel($value));
                                        } elseif ($key === 'pass_type') {
                                            echo hnfEscape(hnfPassTypeLabel($value));
                                        } elseif ($key === 'display_status') {
                                            echo hnfEscape(hnfStatusLabel($value));
                                        } elseif (
                                            $key === 'membership_start' ||
                                            $key === 'membership_end' ||
                                            $key === 'subscription_start' ||
                                            $key === 'subscription_end'
                                        ) {
                                            echo hnfEscape(hnfFormatDate($value));
                                        } elseif (
                                            $key === 'membership_days_remaining' ||
                                            $key === 'pass_days_remaining'
                                        ) {
                                            echo $value === null ? 'N/A' : hnfEscape($value);
                                        } else {
                                            echo hnfEscape($value ?? 'N/A');
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <a class="icon-button-plain" title="Edit Subscription"
                                        href="ctr_subscription-edit.php?id=<?= hnfEscape($subscription['subscription_id']) ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960">
                                            <path
                                                d="M200-200h57l391-391-57-57-391 391v57Zm-40 80q-17 0-28.5-11.5T120-160v-97q0-16 6-30.5t17-25.5l505-504q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L313-143q-11 11-25.5 17t-30.5 6h-97Zm600-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z" />
                                        </svg>
                                    </a>

                                    <a class="icon-button-plain" title="View Subscription" target="_blank"
                                        href="ctr_view-subcription-info.php?id=<?= hnfEscape($subscription['subscription_id']) ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960">
                                            <path
                                                d="M480-200q-135 0-245-67.5T65-446q-7-13-10-26.5T52-500q0-14 3-27.5T65-554q60-111 170-178.5T480-800q135 0 245 67.5T895-554q7 13 10 26.5t3 27.5q0 14-3 27.5T895-446q-60 111-170 178.5T480-200Zm-320.5 50.5Q144-146 127-160q-17-15-33-32t-29-36q-10-14-5.5-28.5T77-279q13-8 29-6.5t30 19.5q10 14 21.5 25t24.5 22q14 12 13.5 28T185-164q-10 11-25.5 14.5ZM480-280q115 0 209-59t144-161q-50-102-144-161t-209-59q-115 0-209 59T127-500q50 102 144 161t209 59ZM439-81q-2 15-14.5 26T390-47q-27-4-53.5-10T284-72q-20-7-26-23t-1-30q5-14 18.5-23t31.5-2q25 9 50.5 14.5T409-126q18 2 25 16t5 29Zm41-239q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm45 282q8-14 27-16 26-4 51-10t50-15q17-6 30.5 3t18.5 23q5 14-1.5 30T673-71q-26 9-52 14.5T568-47q-22 3-34.5-8T519-81q-2-15 6-29Zm237.5-80.5Q762-207 777-219t28-25.5q13-13.5 24-29.5 10-14 25.5-14t27.5 9q12 9 16 25t-10 35q-12 17-25.5 31T833-160q-17 14-33.5 11T773-163q-10-11-10.5-27.5ZM480-500Z" />
                                        </svg>
                                    </a>

                                    <?php if (
                                        ($subscription['display_status'] ?? '') === 'expired_membership' ||
                                        ($subscription['display_status'] ?? '') === 'expired_pass'
                                    ): ?>
                                        <a class="icon-button-plain" title="Renew Subscription"
                                            href="ctr_subscription-renew.php?id=<?= hnfEscape($subscription['subscription_id']) ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960">
                                                <path
                                                    d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-40q0-17 11.5-28.5T280-880q17 0 28.5 11.5T320-840v40h320v-40q0-17 11.5-28.5T680-880q17 0 28.5 11.5T720-840v40h40q33 0 56.5 23.5T840-720v200q0 17-11.5 28.5T800-480q-17 0-28.5-11.5T760-520v-40H200v400h240q17 0 28.5 11.5T480-120q0 17-11.5 28.5T440-80H200ZM760 0q-64 0-114.5-35.5T573-128q-5-11 2.5-21.5T595-160q14 0 25.5 8.5T639-130q18 32 50 51t71 19q58 0 99-41t41-99q0-58-41-99t-99-41q-29 0-54 10.5T662-300h28q13 0 21.5 8.5T720-270q0 13-8.5 21.5T690-240h-90q-17 0-28.5-11.5T560-280v-90q0-13 8.5-21.5T590-400q13 0 21.5 8.5T620-370v27q27-26 63-41.5t77-15.5q83 0 141.5 58.5T960-200q0 83-58.5 141.5T760 0ZM200-640h560v-80H200v80Zm0 0v-80 80Z" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</body>

</html>