<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$grid = new Grid($lines);
function calculateRegion(?GridLocation $loc, string $regionId): int {
    if ($loc->getData()) {
        return 0;
    }
    $data = ['region' => "$regionId", "edges" => []];
    $loc->setData($data);
    $perimeter = 0;
    foreach ($loc->getAdjacent() as $d => $next) {
        if ($next->getData() == $regionId) {
            // Already been visited, skipping.
            continue;
        }
        if ($next->getKey() == $loc->getKey()) {
            $perimeter += calculateRegion($next, $regionId);
        } else {
            $data["edges"][$d] = true;
            $loc->setData($data);
            $perimeter++;
        }
    }
    return $perimeter;
}

$regionId = 1;
for ($y = 0; $y < $grid->getHeight(); $y++) {
    for ($x = 0; $x < $grid->getWidth(); $x++) {
        $loc = $grid->get($x, $y);
        if ($loc->getData()) {
            continue;
        }
        $key = $loc->getKey();
        $perimeter = calculateRegion($loc, "$regionId");
        $area = $grid->countData(function($data) use ($regionId) { return ($data['region'] ?? null) == $regionId; });
        // calculate perimeter?
        echo("$key, $regionId, perimeter: $perimeter, area: $area\n");
        $part1 += $perimeter * $area;
        $regionId++;
    }
}
$totalRegions = $regionId;

echo("Part 1: $part1\n");

function countEdges(Grid $grid, string $regionId): int {
    $edges = 0;
    for ($y = 0; $y < $grid->getHeight(); $y++) {
        for ($x = 0; $x < $grid->getWidth(); $x++) {
            $loc = $grid->get($x, $y);
            $data = $loc->getData();
            $key = $loc->getKey();
            if (($data['region'] ?? null) == $regionId) {
                // This location is in the right region
                // Check its edges
                for ($d = 0; $d < 4; $d++) {
                    if ($data['edges'][$d] ?? false) {
                        // has an edge in this direction.
                        // Find all locations with the same edge.
                        followEdge($loc, $d);
                        $edges++;
                    }
                }
            }
        }
    }
    return $edges;
}

function followEdge(?GridLocation $loc, int $d) {
    // A north facing edge needs to be followed east and west.
    // Determine the follow directions from the edge direction.
    $ds = [
        ($d + 1) % 4,
        ($d + 4 - 1) % 4
    ];
    foreach ($ds as $d1) {
        $next = $loc->getDir($d1);
        while ($next->getKey() == $loc->getKey()) {
            $data = &$next->getData();
            if (isset($data['edges'][$d])) {
                unset($data['edges'][$d]);
            } else {
                // End of this edge
                break;
            }
            $next = $next->getDir($d1);
        }
    }
}

for($regionId = 1; $regionId < $totalRegions; $regionId++) {
    $edges = countEdges($grid, "$regionId");
    $area = $grid->countData(function($data) use ($regionId) { return ($data['region'] ?? null) == $regionId; });
    echo("$regionId, edges: $edges, area: $area\n");
    $part2 += $area * $edges;
}

echo("Part 1: $part1\n");

// 1061126 was too high.
echo("Part 2: $part2\n");