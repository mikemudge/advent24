<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$machines = [];
for ($i = 0; $i < count($lines); $i += 4) {
    [$name, $values] = explode(": ", $lines[$i]);
    [$x, $y] = explode(", ", $values);
    $x = intval(substr($x, 2));
    $y = intval(substr($y, 2));
    $buttonA = [$x, $y];
    [, $values] = explode(": ", $lines[$i + 1]);
    [$x, $y] = explode(", ", $values);
    $x = intval(substr($x, 2));
    $y = intval(substr($y, 2));
    $buttonB = [$x, $y];
    [, $values] = explode(": ", $lines[$i + 2]);
    [$x, $y] = explode(", ", $values);
    $x = intval(substr($x, 2));
    $y = intval(substr($y, 2));
    $prize = [$x, $y];

    $machines[] = [$buttonA, $buttonB, $prize];
}

foreach ($machines as $machineId => $machine) {
    [$buttonA, $buttonB, $prize] = $machine;
    echo(json_encode($machine) . "\n");

    // a series of simultaneous equations
    // buttonA[0] * a + buttonB[0] * b = $machine[0];
    // buttonA[1] * a + buttonB[1] * b = $machine[1];
    // 3 * a + b = cost;
    // minimize cost.

    // a = ($m0 - bB0 * b) / bA0
    // a = ($m1 - bB1 * b) / bA1
    // ($m0 - bB0 * b) / bA0 = ($m1 - bB1 * b) / bA1
    // ($m0 - bB0 * b) * bA1 = ($m1 - bB1 * b) * bA0
    // $m0 * bA1 - bB0 * b * bA1 = $m1 * bA0 - bB1 * b * bA0
    // $m0 * bA1 - $m1 * bA0 = bB0 * b * bA1 - bB1 * b * bA0
    // $m0 * bA1 - $m1 * bA0 = b * (bB0 * bA1 - bB1 * bA0)
    // b = ($m0 * bA1 - $m1 * bA0) / (bB0 * bA1 - bB1 * bA0)
    $denominator = ($buttonB[0] * $buttonA[1] - $buttonB[1] * $buttonA[0]);
    if ($denominator == 0) {
        // Can't divide by 0
        echo("Machine isn't solvable? $machineId\n");
        continue;
    }
    $b = ($prize[0] * $buttonA[1] - $prize[1] * $buttonA[0]) / $denominator;
    $a = ($prize[0] - $buttonB[0] * $b) / $buttonA[0];
    if (!is_int($b) || !is_int($a)) {
        // Its possible that we can't solve it with an integer number of button presses.
        // Not a real solution.
        continue;
    }
    $cost = $b + 3 * $a;
    $part1 += $cost;
}

foreach ($machines as $machineId => $machine) {
    [$buttonA, $buttonB, $prize] = $machine;
    $prize[0] += 10000000000000;
    $prize[1] += 10000000000000;
    $denominator = ($buttonB[0] * $buttonA[1] - $buttonB[1] * $buttonA[0]);
    if ($denominator == 0) {
        // Can't divide by 0
        echo("Machine isn't solvable? $machineId\n");
        continue;
    }
    $b = ($prize[0] * $buttonA[1] - $prize[1] * $buttonA[0]) / $denominator;
    $a = ($prize[0] - $buttonB[0] * $b) / $buttonA[0];
    if (!is_int($b) || !is_int($a)) {
        // Its possible that we can't solve it with an integer number of button presses.
        // Not a real solution.
        continue;
    }
    $cost = $b + 3 * $a;
    echo("$machineId is solvable with $a, $b = $cost\n");
    $part2 += $cost;
}

echo("Part 1: $part1\n");

echo("Part 2: $part2\n");