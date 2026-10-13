<?php

/**
 * Builds a realistic fake Jamf list for scale tests (docs/TEST-LOG.md, "Scale").
 *
 *   php tests/Scale/make-jamf-list.php 5000 storage/app/private/scale/jamf-5000.json
 *
 * The same size always gives the same list (fixed random seed). About 1.2 laptops
 * per person; about 2% without an owner; about 5% without a phone; desktops
 * without a battery value. The list is built in memory, so above about 20,000
 * laptops add -d memory_limit=2G after php.
 */
[$script, $count, $output] = $argv + [null, null, null];

if (! ctype_digit((string) $count) || $output === null) {
    fwrite(STDERR, "Usage: php tests/Scale/make-jamf-list.php <number of laptops> <output file>\n");
    exit(1);
}

mt_srand(42);
$count = (int) $count;
$people = max(1, (int) ceil($count / 1.2));
$firstNames = ['Alex', 'Emma', 'Lucas', 'Mia', 'Noah', 'Olivia', 'Sam', 'Lena', 'Omar', 'Sofia', 'Daan', 'Julia', 'Finn', 'Yara', 'Bram', 'Noor'];
$lastNames = ['Smith', 'Johnson', 'Brown', 'Wilson', 'Miller', 'Davis', 'de Vries', 'Jansen', 'Bakker', 'Visser', 'Smit', 'Meijer', 'Mulder', 'Bos'];
$positions = ['Backend Engineer', 'Product Designer', 'UX Designer', 'QA Engineer', 'Customer Support', 'Finance Manager', 'Sales Lead', 'Recruiter'];
$models = [
    ['MacBook Pro (14-inch, M1 Pro, 2021)', 'MacBookPro18,3', 'Apple M1 Pro', 10, 16384, true],
    ['MacBook Air (M2, 2022)', 'MacBookAir14,2', 'Apple M2', 8, 8192, true],
    ['MacBook Pro (16-inch, M3 Max, 2023)', 'Mac15,9', 'Apple M3 Max', 16, 36864, true],
    ['Mac mini (M1, 2020)', 'Macmini9,1', 'Apple M1', 8, 16384, false],
];

$results = [];
for ($i = 1; $i <= $count; $i++) {
    $person = mt_rand(1, $people);
    $first = $firstNames[$person % count($firstNames)];
    $last = $lastNames[intdiv($person, count($firstNames)) % count($lastNames)];
    $email = strtolower(str_replace(' ', '', "{$first}.{$last}.{$person}")).'@company.test';
    [$model, $identifier, $processor, $cores, $ram, $hasBattery] = $models[$i % count($models)];
    $unassigned = mt_rand(1, 100) <= 2;

    $results[] = [
        'id' => (string) (100000 + $i),
        'udid' => sprintf('%08X-0000-4000-8000-%012X', $i, $person),
        'general' => [
            'name' => "{$first}'s ".explode(' (', $model)[0]." #{$i}",
            'platform' => 'Mac',
            'reportDate' => sprintf('2025-%02d-%02dT09:00:00Z', mt_rand(1, 12), mt_rand(1, 28)),
            'lastContactTime' => sprintf('2026-01-%02dT%02d:00:00Z', mt_rand(1, 5), mt_rand(0, 23)),
            'site' => ['id' => '-1', 'name' => 'None'],
        ],
        'purchasing' => ['purchased' => true, 'poDate' => sprintf('20%02d-%02d-%02d', mt_rand(19, 25), mt_rand(1, 12), mt_rand(1, 28))],
        'userAndLocation' => $unassigned
            ? ['username' => null, 'realname' => null, 'email' => null, 'position' => null, 'phone' => null]
            : [
                'username' => strstr($email, '@', true),
                'realname' => "{$first} {$last}",
                'email' => $email,
                'position' => $positions[$person % count($positions)],
                'phone' => mt_rand(1, 100) <= 5 ? null : sprintf('+3161%07d', $person),
            ],
        'hardware' => [
            'make' => 'Apple',
            'model' => $model,
            'modelIdentifier' => $identifier,
            'serialNumber' => sprintf('SCALE%07d', $i),
            'processorType' => $processor,
            'coreCount' => $cores,
            'totalRamMegabytes' => $ram,
            'batteryCapacityPercent' => $hasBattery ? mt_rand(60, 100) : null,
        ],
    ];
}

file_put_contents($output, json_encode(['totalCount' => $count, 'results' => $results], JSON_PRETTY_PRINT));
echo "Wrote {$count} laptops for {$people} possible people to {$output}\n";
