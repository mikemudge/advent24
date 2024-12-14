<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

function solve1(int $solution, array $values): bool {
    if (count($values) == 1) {
        return $values[0] == $solution;
    }
    $lastVal = array_pop($values);
    // Sol = rest + X
    // Sol = rest * X
    if ($solution % $lastVal == 0) {
        if (solve1($solution / $lastVal, $values)) {
            return true;
        }
    }
    return solve1($solution - $lastVal, $values);
}

function solve2(int $solution, array $values): bool {
    if (count($values) == 1) {
        return $values[0] == $solution;
    }
    $lastVal = array_pop($values);
    // Sol = rest + X
    // Sol = rest * X
    if ($solution % $lastVal == 0) {
        if (solve2($solution / $lastVal, $values)) {
            return true;
        }
    }
    if (str_ends_with("$solution", "$lastVal")) {
        $subSol = intval(substr("$solution", 0, - strlen("$lastVal")));
        if (solve2($subSol, $values)) {
            return true;
        }
    }
    // Sol = (XY + Z) || B
    return solve2($solution - $lastVal, $values);
}

foreach($lines as $line) {
    [$solution, $values] = explode(": ", $line);
    $values = array_map('intval', explode(" ", $values));
    $solution = intval($solution);
    if (solve1($solution, $values)) {
        $part1 += $solution;
    }
    if (solve2($solution, $values)) {
        $part2 += $solution;
    }
}

echo("Part 1: $part1\n");

echo("Part 2: $part2\n");