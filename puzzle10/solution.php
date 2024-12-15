<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$grid = new Grid($lines);

function findtrails(GridLocation $loc, array &$results): int {
    $val = intval($loc->getKey());
    if ($val == 9) {
        $results[$loc->getLocationString()] = true;
        return 1;
    }
    $result = 0;
    foreach ($loc->getAdjacent() as $next) {
        if (intval($next->getKey()) == $val + 1) {
            $result += findtrails($next, $results);
        }
    }
    return $result;
}

for ($y = 0; $y < $grid->getHeight(); $y++) {
    for ($x = 0; $x < $grid->getWidth(); $x++) {
        $loc = $grid->get($x, $y);
        if ($loc->getKey() == "0") {
            // trailhead
            $results = [];
            $part2 += findtrails($loc, $results);
            echo("Score: " . count($results) . "\n");
            $part1 += count($results);
        }
    }
}

echo("Part 1: $part1\n");

echo("Part 2: $part2\n");