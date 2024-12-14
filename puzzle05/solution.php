<?php
require_once 'helpers/Grid.php';

$contents = file_get_contents(dirname(__FILE__) . "/input");
$lines = explode("\n", $contents);

//$contents = file_get_contents(dirname(__FILE__) . "/sample");
//$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$ordering = [];
$updates = [];
foreach ($lines as $line) {
    if ($line == "") {
        continue;
    }
    $parts = explode("|", $line);
    if (count($parts) == 2) {
        $ordering[] = [intval($parts[0]), intval($parts[1])];
    } else {
        $updates[] = array_map('intval', explode(",", $parts[0]));
    }
}

function check_order($update, $order): bool {
//    echo("is $order[0] before $order[1] within ". join(",", $update) . "\n");
    $i0 = array_search($order[0], $update);
    $i1 = array_search($order[1], $update);
    if ($i0 === false || $i1 === false) {
        // One of the values is not in the update, so the order is fine.
        return true;
    }
    // Return true if the indexes are in order, and false if not.
    return $i0 < $i1;
}

function reorder(array $update, array $ordering): array {
    $inOrder = false;
    while(!$inOrder) {
        // Assume we are in order until we find something out of order.
        $inOrder = true;
        foreach ($ordering as $order) {
            $i0 = array_search($order[0], $update);
            $i1 = array_search($order[1], $update);
            if ($i0 === false || $i1 === false) {
                // One of the values is not in the update, so the order is fine.
                continue;
            }
            if ($i1 > $i0) {
                $inOrder = false;
                $tmp = $update[$i1];
                $update[$i1] = $update[$i0];
                $update[$i0] = $tmp;
            }
        }
    }
    return $update;
}

foreach ($updates as $update) {
    $mid = $update[(count($update) - 1) / 2];
//    echo("Mid $mid in " . join(",", $update) . "\n");
    $valid = true;
    foreach ($ordering as $order) {
        if (!check_order($update, $order)) {
            $valid = false;
            break;
        };
    }
    if ($valid) {
        $part1 += $mid;
    } else {
        // Determine the correct order
        $correct = reorder($update, $ordering);
        $mid = $correct[(count($correct) - 1) / 2];
        $part2 += $mid;
    }
}
echo("Part 1: $part1\n");

echo("Part 2: $part2\n");