<?php

$contents = file_get_contents(dirname(__FILE__) . "/input");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$dont = "don't()";
$dontIndex = 0;
$do = "do()";
$doIndex = 0;
$match = "mul(";
$matchIndex = 0;
$number = 0;
$lhs = 0;

$enabled = true;
$results = [];
foreach ($lines as $lineNum => $line) {
    $index = 0;
    $chars = str_split($line);
    for ($i = 0; $i < count($chars); $i++) {
        if ($chars[$i] == $dont[$dontIndex]) {
            $dontIndex++;
            if ($dontIndex == strlen($dont)) {
                $dontIndex = 0;
                $enabled = false;
            }
        } else {
            $dontIndex = 0;
        }
        if ($chars[$i] == $do[$doIndex]) {
            $doIndex++;
            if ($doIndex == strlen($do)) {
                $doIndex = 0;
                $enabled = true;
            }
        } else {
            $doIndex = 0;
        }
        if ($matchIndex <= 3) {
            if ($chars[$i] == $match[$matchIndex]) {
                $matchIndex++;
            }
        } else {
            $matchIndex++;
            // matching digits
            if (ctype_digit($chars[$i])) {
                $number = $number * 10 + $chars[$i];
            } elseif ($chars[$i] == ",") {
                if ($lhs > 0) {
                    // we already have a lhs side, must be a mul(1,2,3)?
                    $matchIndex = 0;
                    $number = 0;
                    $lhs = 0;
                    continue;
                }
                $lhs = $number;
                $number = 0;
            } elseif ($chars[$i] == ")") {
                if (!$lhs) {
                    // appears to be a mul(1)?
                    $matchIndex = 0;
                    $number = 0;
                    $lhs = 0;
                    continue;
                }
                $result = [
                    'lhs' => $lhs,
                    'rhs' => $number,
                    'enabled' => $enabled
                ];
//                echo("Found a mul at line $lineNum char " . $i + 1 . " ". substr($line, $i + 1 - $matchIndex, $matchIndex) . json_encode($result) . "\n");
                $results[] = $result;
                // Reset to find next.
                $matchIndex = 0;
                $number = 0;
                $lhs = 0;
            } else {
                // Reset
                $matchIndex = 0;
                $number = 0;
                $lhs = 0;
            }
        }
    }
}

foreach ($results as $result) {
    $enabled = $result['enabled'];
    $lhs = $result['lhs'];
    $rhs = $result['rhs'];
    $part1 += $lhs * $rhs;
    if ($enabled) {
        $part2 += $lhs * $rhs;
    }
}

echo("Part 1: $part1\n");
echo("Part 2: $part2\n");