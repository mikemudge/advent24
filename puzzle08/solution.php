<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$antenna = [];
$grid = new Grid($lines);
for ($y = 0; $y < $grid->getHeight(); $y++) {
    for ($x = 0; $x < $grid->getWidth(); $x++) {
        $loc = $grid->get($x, $y);
        if ($loc->getKey() != ".") {
            if (!array_key_exists($loc->getKey(), $antenna)) {
                $antenna[$loc->getKey()] = [];
            }
            $antenna[$loc->getKey()][] = $loc;
        }
    }
}
function gcd ($a, $b) {
    return $b ? gcd($b, $a % $b) : $a;
}

foreach ($antenna as $set) {
    foreach ($set as $i => $loc1) {
        for ($ii = $i + 1; $ii < count($set); $ii++) {
            $loc2 = $set[$ii];
            $dx = $loc1->getX() - $loc2->getX();
            $dy = $loc1->getY() - $loc2->getY();
            $antinode = $grid->get($loc1->getX() + $dx, $loc1->getY() + $dy);
            if ($antinode->getKey() != null && $antinode->getKey() != "#") {
                $part1++;
                $antinode->setKey("#");
            }
            $antinode = $grid->get($loc2->getX() - $dx, $loc2->getY() - $dy);
            if ($antinode->getKey() != null && $antinode->getKey() != "#") {
                $part1++;
                $antinode->setKey("#");
            }
        }
    }
}

// Start by including all the antinodes we already found and marked with a #
$part2 = $part1;

foreach ($antenna as $set) {
    // Go through again to find the extra antinodes for part2.
    foreach ($set as $i => $loc1) {
        for ($ii = $i + 1; $ii < count($set); $ii++) {
            $loc2 = $set[$ii];
            $dx = $loc1->getX() - $loc2->getX();
            $dy = $loc1->getY() - $loc2->getY();
            $gcd = gcd($dx, $dy);
            $dx /= $gcd;
            $dy /= $gcd;
            for ($iii = 0; $iii < $grid->getWidth(); $iii++) {
                $antinode = $grid->get($dx * -$iii + $loc1->getX(), $dy * -$iii + $loc1->getY());
                if ($antinode->getKey() != null && $antinode->getKey() != "#") {
                    $part2++;
                    $antinode->setKey("#");
                }
                $antinode = $grid->get($dx * $iii + $loc1->getX(), $dy * $iii + $loc1->getY());
                if ($antinode->getKey() != null && $antinode->getKey() != "#") {
                    $part2++;
                    $antinode->setKey("#");
                }
            }
        }
    }
}

$grid->show();

// 304 is too low?
echo("Part 1: $part1\n");

echo("Part 2: $part2\n");