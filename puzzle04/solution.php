<?php
require_once 'helpers/Grid.php';

$contents = file_get_contents(dirname(__FILE__) . "/input");
$lines = explode("\n", $contents);

//$contents = file_get_contents(dirname(__FILE__) . "/sample");
//$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$grid = new Grid($lines);

$grid->forEach(function (GridLocation $gridLoc) {
    global $part1;

    if ($gridLoc->getKey() == "X") {
        // Possible start letter.
        for ($d = 0; $d < 8; $d++) {
            $next = $gridLoc->getCardinalPlusOrdinal()[$d];
            if ($next->getKey() == "M") {
                $next = $next->getCardinalPlusOrdinal()[$d];
                if ($next->getKey() == "A") {
                    $next = $next->getCardinalPlusOrdinal()[$d];
                    if ($next->getKey() == "S") {
                        $part1++;
                    }
                }
            }
        }
    }
});

// Part 2
for ($d = 1; $d < 8; $d+=2) {
    // X means diagonal only, so skip the cardinal directions.
    $count = 0;

    $grid->forEach(function (GridLocation $gridLoc) {
        global $d, $count;

        if ($gridLoc->getKey() === "A") {
            // Possible start letter.
            // Calculate the 3 other directions to make an X.
            $d2 = ($d + 2) % 8;
            $d3 = ($d + 4) % 8;
            $d4 = ($d + 6) % 8;

            $gridLocs = $gridLoc->getCardinalPlusOrdinal();
            if ($gridLocs[$d]->getKey() === "M" && $gridLocs[$d2]->getKey() === "M") {
                if ($gridLocs[$d3]->getKey() === "S" && $gridLocs[$d4]->getKey() === "S") {
//                    echo("Found X-MAS at $gridLoc\n");
//                    echo("Adjacent " . join(",", $gridLocs) . "\n");
//                    $gridLoc->setKey("#");
                    $count++;
                }
            }
        }
    });
    echo("Orientation $d had a count of $count\n");
    $part2 += $count;
}

//$grid->show();

echo("Part 1: $part1\n");

// 2023 was too high.
// 2021 was too high.
echo("Part 2: $part2\n");