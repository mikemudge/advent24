<?php
require_once 'helpers/Grid.php';
ini_set('memory_limit', '2048M');

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;
$prune = pow(2, 24) - 1;

function rotateSecret(int $secret): int {
    global $prune;
    // Mix the result of multiplying the secret by 64 with the secret, and prune
    $result = $secret << 6;
    $secret ^= $result;
    $secret &= $prune;
    // Mix the result of dividing the secret by 32 (floor) with the secret, and prune.
    $result = $secret >> 5;
    $secret ^= $result;
    $secret &= $prune;
    // Mix the result of multiplying the secret by 2048 with the secret, and prune.
    $result = $secret << 11;
    $secret ^= $result;
    $secret &= $prune;
    return $secret;
}

$secrets = [];
foreach($lines as $line) {
    $secrets[] = intval($line);
}

$allPatterns = [];
$monkeys = [];
foreach($secrets as $secret) {
    $prices = [];
    $test = $secret;
    for ($i = 0; $i < 2000; $i++) {
        $prices[] = $test % 10;
        $test = rotateSecret($test);
    }
    $part1 += $test;

    $seenBefore = [];
    for ($i = 4; $i < count($prices); $i++) {
        $pattern = "";
        $diffs = [];
        for ($ii = 0; $ii < 4; $ii++) {
            $diffs[] = $prices[$i - 3 + $ii] - $prices[$i - 4 + $ii];
        }

        $pattern = join(",", $diffs);
        if (!array_key_exists($pattern, $seenBefore)) {
            // Assign the current price to this pattern.
            $seenBefore[$pattern] = $prices[$i];
        }
        $allPatterns[$pattern] = true;
    }
    $monkeys[] = $seenBefore;
}

$best = 0;
$bestPattern = "";
foreach(array_keys($allPatterns) as $pattern) {
    $value = 0;
    foreach($monkeys as $monkey) {
        $value += $monkey[$pattern] ?? 0;
    }
    if ($value > $best) {
        $best = $value;
        $bestPattern = $pattern;
    }
}

$part2 = $best;
echo("Best $bestPattern with a value of $best\n");

echo("Part 1: $part1\n");
echo("Part 2: $part2\n");
