<?php
require_once 'helpers/Graph.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$graph = new Graph();
foreach ($lines as $line) {
    [$l, $r] = explode("-", $line);
    $graph->connection($graph->addNode($l), $graph->addNode($r), 1);
}

$cluster = array_values($graph->getNodes());
echo("found " . count($cluster) . " nodes\n");
// Find triplets.
for ($i = 0; $i < count($cluster); $i++) {
    $reachable = $cluster[$i]->getReachable();
    for ($ii = $i + 1; $ii < count($cluster); $ii++) {
        if (array_key_exists($cluster[$ii]->getId(), $reachable)) {
            // k1 can reach k2.
            for ($iii = $ii + 1; $iii < count($cluster); $iii++) {
                if (array_key_exists($cluster[$iii]->getId(), $reachable)) {
                    // k1 can reach k2
                    if (array_key_exists($cluster[$iii]->getId(), $cluster[$ii]->getReachable())) {
                        // triplet found.
                        $triplets[] = [$cluster[$i]->getId(), $cluster[$ii]->getId(), $cluster[$iii]->getId()];
                    }
                }
            }
        }
    }
}

echo("found " . count($triplets) . " triplets\n");
foreach ($triplets as $triplet) {
//    echo("Triplet: " . join(", ", $triplet) . "\n");
    foreach($triplet as $k) {
        if (str_starts_with($k, "t")) {
            $part1++;
            break;
        }
    }
}

// Find a maximum clique in the graph?
// 520 nodes, 3380 edges.
foreach($cluster as $node) {
    $branchFactor = count($node->getChildren());
    $result[$branchFactor] = ($result[$branchFactor] ?? 0) + 1;
}
echo("branches " . json_encode($result, JSON_PRETTY_PRINT) . "\n");
// Every computer has 13 branches, so it will not be possible to find a clique larger than 14.

$result = [];
foreach($cluster as $node) {
    $names = array_keys($node->getReachable());
    $names[] = $node->getId();
    sort($names);
    $id = join(",", $names);
    $k = str_replace($names[0] . ",", "", $id);
    $result[$k] = ($result[$k] ?? 0) + 1;
    for ($i = 1; $i < count($names); $i++) {
        $k = str_replace("," . $names[$i], "", $id);
        $result[$k] = ($result[$k] ?? 0) + 1;
    }
}

foreach ($result as $id=>$count) {
    if ($count > 1) {
        echo("id $id has count $count\n");
    }
}

// bm,by,dv,ep,ia,ja,jb,ks,lv,ol,oy,uz,yt occurs 13 times.
$part2 = "bm,by,dv,ep,ia,ja,jb,ks,lv,ol,oy,uz,yt";
$check = explode(",", $part2);
foreach($check as $name) {
    $names = array_keys($graph->getNode($name)->getReachable());
    $names[] = $node->getId();
    sort($names);
    $id = join(",", $names);
    echo("Name: $name has id $id\n");
}
echo("All: all has id $part2\n");

echo("Part 1: $part1\n");
echo("Part 2: $part2\n");
