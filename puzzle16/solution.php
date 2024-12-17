<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$grid = new Grid($lines);

$grid->show();

$start = $grid->find("S");
$end = $grid->find("E");

// find path from start to end.

$direction = 1;
$possible = [[$start, $direction, 0]];
$visited = [];
$bestScore = PHP_INT_MAX;
while($possible) {
    $next = [];
    foreach ($possible as $state) {
        [$loc, $d, $score] = $state;
        $key = $loc . "-" . $d;
        if ($loc == $end) {
            if ($score < $bestScore) {
                $bestScore = $score;
                echo("Found new best $bestScore\n");
            }
            $visited[$key] = $score;
            continue;
        }
        if (!in_array($loc->getKey(), ['.', 'S', 'E'])) {
            // Non traversable location.
            continue;
        }
        if (array_key_exists($key, $visited) && $visited[$key] <= $score) {
            continue;
        }
        // Update the score with the better score and proceed.
        $visited[$key] = $score;
        $next[] = [$loc, ($d + 1) % 4, $score + 1000];
        $next[] = [$loc, ($d + 3) % 4, $score + 1000];
        $next[] = [$loc->getDir($d), $d, $score + 1];
    }
    $possible = $next;
}

$part1 = $bestScore;
echo("Part 1: $part1\n");

$grid->show();

echo("solveInCost $bestScore\n");

// Solve in reverse to find all optimal paths.
// Start at the end with any direction.
$possible = [[$end, 0, $bestScore], [$end, 1, $bestScore], [$end, 2, $bestScore], [$end, 3, $bestScore]];
$visited2 = [];
while($possible) {
    $next = [];
    foreach ($possible as $state) {
        [$loc, $d, $score] = $state;
        $key = $loc . "-" . $d;
        if ($loc === $start && $d == 1) {
            // Reached the start and facing East.
            if ($score != 0) {
                throw new RuntimeException("Score was $score at the start");
            }
            $loc->setData('O');
            continue;
        }
        if (($visited[$key] ?? null) != $score) {
            // This path is not an optimal one.
            continue;
        }
        if (array_key_exists($key, $visited2)) {
            // Already explored this node, skip.
            continue;
        }
        $visited2[$key] = true;
        // This is still an optimal path, consider all the previous steps which can get to this state.
        $next[] = [$loc->getDir(($d + 2) % 4), $d, $score - 1];
        $next[] = [$loc, ($d + 1) % 4, $score - 1000];
        $next[] = [$loc, ($d + 3) % 4, $score - 1000];
        $loc->setData('O');
    }
    $possible = $next;
}


$grid->forEach(function($loc) {
    if ($loc->getData() == 'O') {
        $loc->setKey('O');
    }
});
$part2 = $grid->count('O');
$grid->show();

echo("Part 2: $part2\n");