<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$width = 7;
$height = 7;
$time = 12;
if ($file == "input") {
    $width = 71;
    $height = 71;
    $time = 1024;
}

$bytes = [];
foreach($lines as $i => $line) {
    [$x, $y] = array_map('intval', explode(",", $line));
    $bytes[] = [$x, $y];
}

function findPath(?GridLocation $start, GridLocation $end) {
    $current = [[$start, 0]];
    while($current) {
        $next = [];
        foreach ($current as $state) {
            /** @var GridLocation $loc */
            [$loc, $cost] = $state;

            if ($loc->getKey() != '.') {
                // Can't walk on this location.
                continue;
            }
            if ($loc === $end) {
                return $cost;
            }
            if ($loc->getData()) {
                $prev = $loc->getData();
                if ($cost >= $prev) {
                    // We have already been here for cheaper or the same cost.
                    continue;
                }
            }
            $loc->setData($cost);
            $next[] = [$loc->north(), $cost + 1];
            $next[] = [$loc->east(), $cost + 1];
            $next[] = [$loc->south(), $cost + 1];
            $next[] = [$loc->west(), $cost + 1];
        }
        $current = $next;
    }
    return false;
}

function recreateGridAtTime($time) {
    global $width, $height, $bytes;
    $grid = Grid::create($width, $height);
    for ($i =0; $i < $time; $i++) {
        [$x, $y] = $bytes[$i];
        $grid->get($x, $y)->setKey("#");
    }

    return $grid;
}

$grid = recreateGridAtTime($time);
$start = $grid->get(0, 0);
$end = $grid->bottomRight();
$part1 = findPath($start, $end);

$grid->show();

echo("Part 1: $part1\n");

$low = 0; $high = count($bytes) - 1;
while($low + 1 < $high) {
    $mid = floor(($low + $high) / 2);
    $grid = recreateGridAtTime($mid);
    $start = $grid->get(0, 0);
    $end = $grid->bottomRight();
    $result = findPath($start, $end);
    if ($result === false) {
        // Unsolvable
        $high = $mid;
        echo("Unsolvable at $mid ($low, $high)\n");
    } else {
        // Can solve
        $low = $mid;
        echo("Solvable at $mid ($low, $high)\n");
    }
}
echo("$low, $high\n");
$part2 = join(",", $bytes[$low]);
echo("Part 2: $part2\n");
