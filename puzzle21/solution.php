<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$numeric_pad = new Grid([
    '789',
    '456',
    '123',
    ' 0A'
]);

$directional_pad = new Grid([
    ' ^A',
    '<v>',
]);

function createInput(string $line, Grid $grid): array {
    $start = $grid->find('A');
    $instructions = [];
    foreach (str_split($line) as $c) {
        $target = $grid->find($c);
        $xInstruction = "";
        $yInstruction = "";
        $x = $start->getX() - $target->getX();
        $y = $start->getY() - $target->getY();
        if ($x < 0) {
            $xInstruction .= str_pad("", - $x, ">");
        } else {
            $xInstruction .= str_pad("", $x, "<");
        }
        if ($y < 0) {
            $yInstruction .= str_pad("", - $y, "v");
        } else {
            $yInstruction .= str_pad("", $y, "^");
        }
        $options = permute("", $xInstruction, $yInstruction);
        // TODO check that no options uses the blank space?
        $options2 = [];
        foreach($options as $option) {
            $test = $start;
            $fail = false;
            foreach(str_split($option) as $cd) {
                $d = match ($cd) {
                    "^" => 0,
                    ">" => 1,
                    "v" => 2,
                    "<" => 3,
                };
                $test = $test->getDir($d);
                if ($test->getKey() == " ") {
                    // Unusable option;
                    $fail = true;
                    break;
                }
            }
            if (!$fail) {
                $options2[] = $option . "A";
            }
        }
        $instructions[] = [
            'start' => $start->getKey(),
            'target' => $target->getKey(),
            'options' => $options2,
        ];
        // Update our start to the target for the next set of instructions.
        $start = $target;
    }
    return $instructions;
}

function permute(string $prefix, string $xInstruction, string $yInstruction) {
    if (strlen($xInstruction) == 0) {
        // there is only one option which is the entire y instruction.
        return [$prefix . $yInstruction];
    }
    if (strlen($yInstruction) == 0) {
        // there is only one option which is the entire x instruction.
        return [$prefix . $xInstruction];
    }
    // Choose to use an x or a y next.
    $xC = $xInstruction[0];
    $yC = $yInstruction[0];
    return array_merge(
        permute($prefix . $xC, substr($xInstruction, 1), $yInstruction),
        permute($prefix . $yC, $xInstruction, substr($yInstruction, 1))
    );
}

function findBest2(array $instructions, Grid $grid, $iterations, array &$cache): int {
    $total = 0;
    foreach ($instructions as $step) {
        $options = $step['options'];
        $key = $step['start'] . $step['target'] . $iterations;
        if (array_key_exists($key, $cache)) {
            $total += $cache[$key];
            continue;
        }
        $bestVal = PHP_INT_MAX;
        $best = null;
        foreach ($options as $instruction) {
            if ($iterations === 0) {
                // Base case, can just use this instruction.
                $length = strlen($instruction);
            } else {
                $instructions2 = createInput($instruction, $grid);
                $length = findBest2($instructions2, $grid, $iterations - 1, $cache);
            }
            if ($length < $bestVal) {
                $bestVal = $length;
                $best = $instruction;
            }
        }
        if ($iterations > 0 && strlen($best) > 1) {
            echo("It $iterations, best option to get from " . $step['start'] . " to " . $step['target'] . " is " . json_encode($best) . "\n");
        }
        $cache[$key] = $bestVal;
        $total += $bestVal;
    }

    return $total;
}

$cache = [];
foreach ($lines as $line) {
    $instructions = createInput($line, $numeric_pad);
    $total = findBest2($instructions, $directional_pad, 2, $cache);

    echo("You: $total\n");

    $b = intval(substr($line, 0, -1));
    echo("$line = $total * $b\n");
    $part1 += $total * $b;
}

$cache = [];
foreach ($lines as $line) {
    $instructions = createInput($line, $numeric_pad);
    // This will require some caching.
    $total = findBest2($instructions, $directional_pad, 25, $cache);

    echo("You: $total\n");
    $b = intval(substr($line, 0, -1));
    echo("$line = $total * $b\n");
    $part2 += $total * $b;
}

// 196304 is too high.
echo("Part 1: $part1\n");

echo("Part 2: $part2\n");
