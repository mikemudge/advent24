<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$patterns = explode(", ", $lines[0]);
function makePattern(string $requiredPattern, array $patterns, array &$visited): int {
    if (empty($requiredPattern)) {
        // We matched everything
        return true;
    }
    if (array_key_exists($requiredPattern, $visited)) {
        // We have already tried this pattern, used the cached value.
        return $visited[$requiredPattern];
    }
    $count = 0;
    foreach($patterns as $pattern) {
        // If our required pattern starts with a pattern we can remove it and match the rest.
        if (str_starts_with($requiredPattern, $pattern)) {
            $rest = substr($requiredPattern, strlen($pattern));
            $count += makePattern($rest, $patterns, $visited);
        }
    }
    // None of the patterns were able to be used to create a full match.
    $visited[$requiredPattern] = $count;
    return $count;
}

echo("Patterns " . join(", ", $patterns) . "\n");

$visited = [];
for ($i = 2; $i < count($lines); $i++) {
    $requiredPattern = $lines[$i];
    $numberOfWays = makePattern($requiredPattern, $patterns, $visited);
    echo("Match $requiredPattern $numberOfWays ways\n");
    if ($numberOfWays > 0) {
        $part1++;
    }
    $part2 += $numberOfWays;
}

echo("Part 1: $part1\n");
echo("Part 2: $part2\n");
