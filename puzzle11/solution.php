<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$values = array_map('intval', explode(" ", $lines[0]));
echo(join(",", $values) . "\n");

function iterate(array $values) {
    $result = [];
    foreach ($values as $value) {
        // replace with a 1.
        if ($value == 0) {
            $result[] = 1;
        } else if (strlen("$value") % 2 == 0) {
            $half = strlen("$value") / 2;
            [$a, $b] = str_split("$value", $half);
            $result[] = intval($a);
            $result[] = intval($b);
        } else {
            $result[] = 2024 * $value;
        }
    }
    return $result;
}
function getCount(int $blinks, int $value, array &$visited) {
    if ($blinks == 0) {
        return 1;
    }
    $key = "$blinks-$value";
    if (array_key_exists($key, $visited)) {
        return $visited[$key];
    }
    if ($value == 0) {
        $count = getCount($blinks - 1, 1, $visited);
    } else if (strlen("$value") % 2 == 0) {
        $half = strlen("$value") / 2;
        [$a, $b] = str_split("$value", $half);
        $count = getCount($blinks - 1, intval($a), $visited) + getCount($blinks - 1, intval($b), $visited);
    } else {
        $count = getCount($blinks - 1, 2024 * $value, $visited);
    }
    $visited[$key] = $count;
    return $count;
}

$visited = [];
foreach ($values as $value) {
    $part1 += getCount(25, $value, $visited);
}

foreach ($values as $value) {
    $part2 += getCount(75, $value, $visited);
}

echo("Part 1: $part1\n");

echo("Part 2: $part2\n");