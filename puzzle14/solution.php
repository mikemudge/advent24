<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

if ($file == "input") {
    $width = 101;
    $height = 103;
} else {
    $width = 11;
    $height = 7;
}

$part1 = 0;
$part2 = 0;

$robots = [];
foreach($lines as $line) {
    [$p, $v] = explode(" ", $line);
    [$x, $y] = array_map('intval', explode(",", substr($p, 2)));
    [$vx, $vy] = array_map('intval', explode(",", substr($v, 2)));
    echo("p=$x,$y v=$vx,$vy\n");
    $robots[] = [
        'p' => [$x, $y],
        'v' => [$vx, $vy]
    ];
}

$halfHeight = ($height - 1) / 2;
$halfWidth = ($width - 1) / 2;
$time = 100;
$quad = [
    [0, 0],
    [0, 0]
];
$grid = Grid::create($width, $height);

foreach($robots as $botId => $bot) {
    $fx = ($bot['p'][0] + $bot['v'][0] * $time) % $width;
    $fy = ($bot['p'][1] + $bot['v'][1] * $time) % $height;
    if ($fx < 0) {
        $fx += $width;
    }
    if ($fy < 0) {
        $fy += $height;
    }

    echo("Bot $botId: $fx, $fy\n");
    if ($fx == $halfWidth || $fy == $halfHeight) {
        // Exactly on the split line.
        continue;
    }
    $qx = $fx < $halfWidth ? 0 : 1;
    $qy = $fy < $halfHeight ? 0 : 1;
    $quad[$qx][$qy]++;
}

echo(json_encode($quad) . "\n");

$part1 = $quad[0][0] * $quad[0][1] * $quad[1][0] * $quad[1][1];
echo("Part 1: $part1\n");

$time = 1;
$bestTime = 0;
$bestGrid = null;
$bestCount = 0;
// theoretical max is 101 * 103 = 10403.
// after this the pattern should repeat.
while($time <= 10403) {
    $grid = Grid::create($width, $height);
    foreach($robots as $botId => $bot) {
        $fx = ($bot['p'][0] + $bot['v'][0] * $time) % $width;
        $fy = ($bot['p'][1] + $bot['v'][1] * $time) % $height;
        if ($fx < 0) {
            $fx += $width;
        }
        if ($fy < 0) {
            $fy += $height;
        }
        $loc = $grid->get($fx, $fy);
        $key = $loc->getKey();
        if ($key == ".") {
            $key = 0;
        } else {
            $key = intval($key);
        }
        $key++;
        $loc->setKey($key);
    }
    // Check "treeness"
    $close = 0;
    foreach($robots as $botId => $bot) {
        $loc = $grid->get($fx, $fy);
        foreach ($loc->getCardinalPlusOrdinal() as $next) {
            if ($next->getKey() && $next->getKey() != '.') {
                $close++;
            }
        }
    }
    if ($close > $bestCount) {
        $bestCount = $close;
        $bestTime = $time;
        $bestGrid = $grid;
        echo("Time: $time best $bestCount\n");
    }
    $time++;
}
echo("Time: $bestTime line length $bestCount\n");
$bestGrid->show();

echo("Part 1: $part1\n");

echo("Part 2: $part2\n");