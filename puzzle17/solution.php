<?php
require_once 'helpers/Grid.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

[,$a] = explode(": ", $lines[0]);
[,$b] = explode(": ", $lines[1]);
[,$c] = explode(": ", $lines[2]);
$a = intval($a);
$b = intval($b);
$c = intval($c);

// line 3 is blank

[,$values] = explode(": ", $lines[4]);
$instructions = array_map('intval', explode(",", $values));

$ops = [
    "adv", //
    "bxl",
    "bst",
    "jnz",
    "bxc",
    "out",
    "bdv",
    "cdv"
];

function runProgram($a, $b, $c): array {
    global $ops, $instructions;
    // Instruction pointer is $i
    $i = 0;
    $outputs = [];
    while($i + 1 < count($instructions)) {
        $op = $ops[$instructions[$i]];
        $operand = $instructions[$i + 1];
        $combo = $operand;
//        echo("A=$a, B=$b, C=$c $op $operand ($combo)\n");
        if ($operand == 4) {
            $combo = $a;
        }
        if ($operand == 5) {
            $combo = $b;
        }
        if ($operand == 6) {
            $combo = $c;
        }
        if ($op == "adv") {
            $a = $a >> $combo;
        }
        if ($op == "bxl") {
            $b = $b ^ $operand;
        }
        if ($op == "bst") {
            $b = $combo % 8;
        }
        if ($op == "bxc") {
            $b = ($b ^ $c);
        }
        if ($op == "out") {
            $outputs[] = $combo % 8;
        }
        if ($op == "jnz") {
            if ($a != 0) {
                $i = $operand;
                // No regular instruction pointer increase.
                continue;
            }
        }
        if ($op == "bdv") {
            $b = $a >> $combo;
        }
        if ($op == "cdv") {
            $c = $a >> $combo;
        }
        $i += 2;
    }
//    echo("A=$a, B=$b, C=$c\n");
    return $outputs;
}

$outputs = runProgram($a, $b, $c);
$part1 = join(",", $outputs);

echo("Part 1: $part1\n");

if (isset($argv[2])) {
    // Support testing part2 with any a value.
    $test = intval($argv[2]);
    $outputs = runProgram($test, $b, $c);
    echo("Test: " . join(",", $outputs). "\n");
}

$val = 0;
function findResult(int $val, int $cur, array $instructions, int $b, int $c): int {
    // Find an $a which can return the output we expect.
    echo("Cur $cur Value: $val Need a $instructions[$cur] \n");
    // We have to try the lowest first to meet the criteria lowest positive initial value
    for ($i = 0; $i < 8; $i++) {
        $outs = runProgram($val + $i, $b, $c);
        if ($outs[0] == $instructions[$cur]) {
            echo("Value: " . $val + $i . " gets a result " . join(",", $outs) . "\n");
            if ($cur == 0) {
                return $val + $i;
            }
            $r = findResult(($val + $i) * 8, $cur - 1, $instructions, $b, $c);
            if ($r > 0) {
                return $r;
            } else {
                echo("No result possible, backtracking\n");
            }
        }
    }
    return 0;
}

$part2 = findResult($val, count($instructions) - 1, $instructions, $b, $c);


// Verify the output for this result.
$outs = runProgram($part2, $b, $c);
echo("Result: " .join(",", $outs) . "\n");
echo("Expect: " . join(",", $instructions) . "\n");

echo("Part 2: $part2\n");

//
// 2,4,1,2,7,5,4,3,0,3,1,7,5,5,3,0
// 0 (bst) b = a % 8
// 2 (bxl) b = b ^ 2
// 4 (cdv) c = a >> b
// 6 (bxc) b = b ^ c
// 8 (adv) a = a >> 3
// 10 (bxl) b = b ^ 7
// 12 (out) print b
// 14 (jnz) if (a != 0) goto 0

// while (a != 0) {
//  b is the low 3 bits of a with the middle bit flipped?
//  b = (a % 8) ^ 2
//  b xors a shifted by b? We do only care about the low 3 bits here.
//  b = b ^ (a >> b)
//  shift a by 3 bits (look at the next 3 bits)
//  a = a >> 3
//  switch the low 3 bits of b and print b.
//  print b ^ 7
// }

// 0 -> 2, 4 -> 6
// 1 -> 3, 5 -> 7
// 2 -> 0, 6 -> 4
// 3 -> 1, 7 -> 5


// We want to print the program as output, so 2 first.

// Then we consider a's higher bits with this XOR
// AAA -> B B B ^ (A >> B)
// 000 -> 0 1 0 ^ A4 A3 0
// 001 -> 0 1 1 ^ A5 A4 A3
// 010 -> 0 0 0 ^ 0 1 0
// 011 -> 0 0 1 ^ A3 0 1
// 100 -> 1 1 0 ^ A8 A7 A6
// 101 -> 1 1 1 ^ A9 A8 A7
// 110 -> 1 0 0 ^ A6 A5 A4
// 111 -> 1 0 1 ^ A7 A6 A5

// For the high number we can assume all higher A values are 0.
// So our final number output is 0
// To get this we need 101 (5) as the input?
// Now consider 110XXX with an output of 3, 0
// Each time multiply by 8 and check the next 8 numbers.
// 2,4,1,2,7,5,4,3,0,3,1,7,5,5,3,0
// 47 * 8 + X
// 376