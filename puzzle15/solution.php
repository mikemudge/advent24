<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

foreach ($lines as $i => $line) {
    if ($line == "") {
        $splitIdx = $i;
        break;
    }
}
$grid = new Grid(array_splice($lines, 0, $splitIdx));

// Remove the blank line so the remaining lines are instructions.
array_splice($lines, 0, 1);

$grid->show();
$instructions = str_split(join("", $lines));
echo("Instructions: " . count($instructions) . join($instructions). "\n");

$grid2 = Grid::create($grid->getWidth() * 2, $grid->getHeight());
for ($y = 0; $y < $grid->getHeight(); $y++) {
    for ($x = 0; $x < $grid->getWidth(); $x++) {
        $key = $grid->get($x, $y)->getKey();
        if ($key == ".") {
            $grid2->get($x * 2, $y)->setKey(".");
            $grid2->get($x * 2 + 1, $y)->setKey(".");
        }
        if ($key == "@") {
            $grid2->get($x * 2, $y)->setKey("@");
            $grid2->get($x * 2 + 1, $y)->setKey(".");
        }
        if ($key == "#") {
            $grid2->get($x * 2, $y)->setKey("#");
            $grid2->get($x * 2 + 1, $y)->setKey("#");
        }
        if ($key == "O") {
            $grid2->get($x * 2, $y)->setKey("[");
            $grid2->get($x * 2 + 1, $y)->setKey("]");
        }
    }
}

$robot = $grid->find("@");

foreach ($instructions as $i) {
    $direction = match($i) {
        "^" => 0,
        ">" => 1,
        "v" => 2,
        "<" => 3
    };

    $next = $robot->getDir($direction);
    // Move boxes?
    $gap = $next;
    while($gap->getKey() == 'O') {
        $gap = $gap->getDir($direction);
    }

    // If the first non box is a wall, we can't move.
    if ($gap->getKey() == "#") {
        continue;
    }
    // If its a gap then we can move.
    if ($gap->getKey() == '.') {
        // If there were no boxes then gap and next are the same loc, meaning we only move the bot.
        $gap->setKey('O');
        $next->setKey('@');
        $robot->setKey('.');
        $robot = $next;
    } else {
        $grid->show();
        throw new RuntimeException("$robot moving $direction hit thing " . $gap->getKey() . "\n");
    }
}

$grid->show();

$part1 = $grid->sum(function(GridLocation $loc) {
    if ($loc->getKey() == "O") {
        return 100 * $loc->getY() + $loc->getX();
    }
    return 0;
});

$grid2->show();


$robot = $grid2->find("@");

function canMove(?GridLocation $loc, int $direction): bool {
    $next = $loc->getDir($direction);
    $key = $next->getKey();
    if ($next->getKey() == ".") {
        return true;
    }
    if ($next->getKey() == "#") {
        return false;
    }
    if ($direction == 0 || $direction == 2) {
        // North and south movements need to move both.
        if ($next->getKey() == "[") {
            $otherBox = $next->east();
        } else if ($next->getKey() == "]") {
            $otherBox = $next->west();
        } else {
            throw new RuntimeException("Unknown key $key");
        }

        return canMove($next, $direction) && canMove($otherBox, $direction);
    }
    // east/west only needs to move 1 at a time.
    return canMove($next, $direction);
}

function makeMove(?GridLocation $loc, int $direction) {
    $next = $loc->getDir($direction);
    $key = $next->getKey();

    if ($next->getKey() == ".") {
        $next->setKey($loc->getKey());
        $loc->setKey(".");
        return;
    }
    if ($next->getKey() == "#") {
        throw new RuntimeException("canMove should have failed");
    }

    // Move the box out of the way first.
    if ($direction == 0 || $direction == 2) {
        // North and south movements need to move the other part.
        if ($next->getKey() == "[") {
            $otherBox = $next->east();
        } else if ($next->getKey() == "]") {
            $otherBox = $next->west();
        } else {
            throw new RuntimeException("Unknown key $next");
        }
        makeMove($otherBox, $direction);
    }
    makeMove($next, $direction);

    // Then update this thing.
    if ($next->getKey() == ".") {
        $next->setKey($loc->getKey());
        $loc->setKey(".");
    } else {
        throw new RuntimeException("makeMove didn't result in a . at $next");
    }
}

foreach ($instructions as $i => $instruction) {
    $direction = match($instruction) {
        "^" => 0,
        ">" => 1,
        "v" => 2,
        "<" => 3
    };

    if (canMove($robot, $direction)) {
        // Move the robot and everything its pushing.
        makeMove($robot, $direction);
        // Update the robot's location
        $robot = $robot->getDir($direction);
        echo("$i, $instruction, $direction moved\n");
    } else {
        echo("$i, $instruction, $direction not possible\n");
    }
}

$grid2->show();

$part2 = $grid2->sum(function(GridLocation $loc) {
    if ($loc->getKey() == "[") {
        return 100 * $loc->getY() + $loc->getX();
    }
    return 0;
});

echo("Part 1: $part1\n");

echo("Part 2: $part2\n");