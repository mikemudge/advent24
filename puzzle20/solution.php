<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$grid = new Grid($lines);

$start = $grid->find("S");
$end = $grid->find("E");
$grid->solveMaze($start, $end);

$cheats = [];

$grid->forEach(function(GridLocation $loc) {
    global $part1;
    if ($loc->getKey() == '#') {
        // We could cheat here.
        foreach ($loc->getAdjacent() as $a1) {
            if (!$a1->getKey() || $a1->getKey() == "#") {
                // can't start cheat on a wall.
                continue;
            }
            foreach ($loc->getAdjacent() as $a2) {
                if (!$a2->getKey() || $a2->getKey() == "#") {
                    // can't start cheat on a wall.
                    continue;
                }
                // subtract the 2 steps we take during the cheat.
                $cheatLength = $a2->getData() - $a1->getData() - 2;
                if ($cheatLength >= 10) {
                    $part1++;
                }
            }
        }
    }
});

function countCheats(?GridLocation $loc1, Grid $grid, int $cheatTime, array &$counts): void {
    if (!$loc1->getKey() || $loc1->getKey() == "#") {
        // Must start on a walkable location
        return;
    }
    for ($y = max(0, $loc1->getY() - 21); $y < min($grid->getHeight(), $loc1->getY() + 21); $y++) {
        for ($x = max(0, $loc1->getX() - 21); $x < min($grid->getWidth(), $loc1->getX() + 21); $x++) {
            $loc2 = $grid->get($x, $y);
            if (!$loc2->getKey() || $loc2->getKey() == "#") {
                // Must end on a walkable location
                continue;
            }
            $distance = $loc1->getManhattenDistance($loc2);
            if ($distance > $cheatTime) {
                // We are only allowed to cheat for 20 steps.
                continue;
            }
            $cheatLength = $loc2->getData() - $loc1->getData() - $distance;
            if ($cheatLength > 0) {
                $counts[$cheatLength] = ($counts[$cheatLength] ?? 0) + 1;
            }
        }
    }
}

$counts = [];
$counts2 = [];
$total = $grid->getHeight() * $grid->getWidth();
for ($y = 0; $y < $grid->getHeight(); $y++) {
    for ($x = 0; $x < $grid->getWidth(); $x++) {
        $i = $x + $y * $grid->getWidth();
        echo("Progress: $i/$total\n");
        $loc1 = $grid->get($x, $y);
        countCheats($loc1, $grid, 2, $counts);
        countCheats($loc1, $grid, 20, $counts2);
    }
}
$test1 = 0;
$test2 = 0;
ksort($counts);
foreach ($counts as $length => $count) {
    echo("There are $count cheats that save $length picoseconds.\n");
    if ($length >= 100) {
        $part1 += $count;
    }
}
echo("20 cheats:\n");
ksort($counts2);
foreach ($counts2 as $length => $count) {
    if ($length >= 50) {
        echo("There are $count cheats that save $length picoseconds.\n");
    }
    if ($length >= 100) {
        $part2 += $count;
    }
}

echo("Part 1: $part1\n");

// 977380 is too low.
echo("Part 2: $part2\n");
