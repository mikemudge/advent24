<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$grid = new Grid($lines);

$guard = $grid->find("^");

$direction = 0;
$locations = [];
while($guard->getKey() != null) {
    $guard->setKey('X');
    $next = $guard->getDir($direction);
    if ($next->getKey() == "#") {
        // turn right and continue;
        $direction = ($direction + 1) % 4;
    } else {
        // take a step forward.
        $guard = $next;
        if ($guard->getKey() == ".") {
            $locations[] = [$guard->getX(), $guard->getY()];
        }
    }
}

$grid->show();
$part1 = $grid->count('X');

function check_loop(Grid $grid2) {
    $visited = [];
    $guard = $grid2->find("^");
    $direction = 0;
    while($guard->getKey() != null) {
        $guard->setKey('X');
        $next = $guard->getDir($direction);
        if ($next->getKey() == "#") {
            // turn right and continue;
            $direction = ($direction + 1) % 4;
        } else {
            // take a step forward.
            $guard = $next;
        }
        $key = $guard->getLocationString() . "$direction";
        if (array_key_exists($key, $visited)) {
            // We have been here facing this direction before.
            // Must now be stuck in a loop.
            return true;
        }
        $visited[$key] = true;
    }
    // Exiting here means the loop finished with a null key (out of bounds)
    return false;
}

$total = count($locations);
// Consider every location which the guard goes to.
foreach ($locations as $i => $location) {
    if ($i % 50 == 49) {
        // Show some progress.
        echo("$i / $total\n");
    }
    // Attempt with an obstacle here.
    $grid2 = new Grid($lines);
    $grid2->get($location[0], $location[1])->setKey("#");
    if (check_loop($grid2)) {
        $part2++;
    };
}

echo("Part 1: $part1\n");

echo("Part 2: $part2\n");