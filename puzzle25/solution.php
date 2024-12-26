<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$keyLocks = array_chunk($lines, 8);

$keys = [];
$locks = [];
foreach($keyLocks as $keyLock) {
    if (count($keyLock) > 7) {
        // Remove the last line of each chunk which is empty.
        $keyLock = array_splice($keyLock, 0, -1);
    }
    echo(json_encode($keyLock) . "\n");
    $grid = new Grid($keyLock);
    $grid->show();
    $c = '#';
    $key = false;
    if ($grid->get(0,0)->getKey() == ".") {
        $key = true;
        $c = '.';
    }
    $heights = [];
    for ($x = 0; $x < $grid->getWidth(); $x++) {
        for ($y = 0; $y < $grid->getHeight(); $y++) {
            if ($grid->get($x, $y)->getKey() == $c) {
                $heights[$x] = $y;
            } else {
                break;
            }
        }
        if ($key) {
            // key heights are reversed.
            $heights[$x] = 5 - $heights[$x];
        }
    }
    if ($key) {
        $keys[] = $heights;
    } else {
        $locks[] = $heights;
    }
    echo(join(",", $heights) . "\n");
}

function checkFit(array $key, array $lock): bool {
    for($i = 0; $i < count($key); $i++) {
        if ($key[$i] + $lock[$i] > 5) {
            return false;
        };
    }
    return true;
}
foreach ($keys as $key) {
    echo("Key: " .join(", ", $key) . "\n");
}
foreach ($locks as $lock) {
    echo("Lock: " . join(", ", $lock) . "\n");
}

foreach ($keys as $key) {
    foreach ($locks as $lock) {
        if (checkFit($key, $lock)) {
            $part1++;
        };
    }
}
echo("Part 1: $part1\n");
echo("Part 2: $part2\n");
