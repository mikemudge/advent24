<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

$diskMap = $lines[0];

$files = [];
$gaps = [];
$block = 0;
for ($i = 0; $i < strlen($diskMap); $i++) {
    $size = intval($diskMap[$i]);
    $thing = [
        'block' => $block,
        'size' => $size,
    ];
    $block += $size;
    if ($i % 2 == 0) {
        $thing['fileId'] = $i / 2;
        $files[] = $thing;
    } else {
        $gaps[] = $thing;
    }
}

// Iterate backwards through $files and insert them into $gaps?
for ($fileIdx = count($files) - 1; $fileIdx >= 0; $fileIdx--) {
    $file = $files[$fileIdx];
    // Find somewhere to put this?
    $size = $file['size'];
    foreach ($gaps as &$gap) {
        if ($gap['block'] < $file['block'] && $gap['size'] >= $size) {
            // This gap can fit the file, relocate the file, then update the gap.
            $files[$fileIdx]['block'] = $gap['block'];
            $gap['size'] -= $size;
            // shift the start of the gap along as well.
            $gap['block'] += $size;
            break;
        }
    }
}

foreach($files as $file) {
    $addedValue = (($file['block'] + $file['size'] - 1) * ($file['block'] + $file['size']) - $file['block'] * ($file['block'] - 1)) * $file['fileId'] / 2;
    echo($file['fileId'] . ' at '. $file['block'] . "-" . $file['block'] + $file['size'] . " adds $addedValue\n");
    $part2 += $addedValue;
}

$left = 0;
$right = strlen($diskMap) - 1;
if ($right % 2 == 1) {
    // Started on a gap, so skip it.
    $right--;
}
$availableBlocks = $diskMap[$right];
$fileId = 0;
$result = "";
$i = 0;
while ($left < $right) {
    // Calculate the disk usage?
    $numBlocks = intval($diskMap[$left]);
    if ($left % 2 == 0) {
        // A file
        $fileId = $left / 2;
        $addedValue = (($i + $numBlocks - 1) * ($i + $numBlocks) - $i * ($i - 1)) * $fileId / 2;
//        echo("File $fileId at $i with size $numBlocks added $addedValue\n");
        $part1 += $addedValue;
        $i += $numBlocks;
    } else {
        // Need to pull $numBlocks from the end file.
        while($numBlocks > 0) {
            // Determine what fileId we are filling in from.
            $fillFileId = $right / 2;
            if ($numBlocks <= $availableBlocks) {
                // We have enough available blocks at the current end file.
                $addedValue = (($i + $numBlocks - 1) * ($i + $numBlocks) - $i * ($i - 1)) * $fillFileId / 2;
                $part1 += $addedValue;
//                echo("Fill Gap at $i of size $numBlocks, with $fillFileId adding $addedValue\n");
                $availableBlocks -= $numBlocks;
                $i += $numBlocks;
                break;
            } else {
                // We don't have enough available block at the current end file.
                // Use what we have and move to the next end file.
                $addedValue = (($i + $availableBlocks - 1) * ($i + $availableBlocks) - $i * ($i - 1)) * $fillFileId / 2;
//                echo("Partially fill Gap at $i of size $numBlocks, with $availableBlocks remaining blocks of $fillFileId adding $addedValue\n");
                $part1 += $addedValue;
                $numBlocks -= $availableBlocks;
                $i += $availableBlocks;

                // We used all blocks in the fill file, move to the next one.
                $right -= 2;
                if ($right <= $left) {
                    // something like .....99 at the end of the filesystem.
                    // we moved 99 to the start of the gap 99... and have nothing left to consider.
                    echo("We have a gap at $i, but can't use $right to fill it\n");
                    break;
                }
                $availableBlocks = $diskMap[$right];
            }
        }
    }
    $left++;
}
echo("$left, $right, $availableBlocks\n");
// Add the value for the remaining blocks of the last file.
$fileId = $right / 2;
$addedValue = (($i + $availableBlocks - 1) * ($i + $availableBlocks) - $i * ($i - 1)) * $fileId / 2;
echo("File $fileId at $i with $availableBlocks remaining blocks added $addedValue\n");
$part1 += $addedValue;


echo("Part 1: $part1\n");

// 8666607636406 was too high.
echo("Part 2: $part2\n");