<?php
require_once 'helpers/Graph.php';

$file = $argv[1];
$contents = file_get_contents(dirname(__FILE__) . "/$file");
$lines = explode("\n", $contents);

$part1 = 0;
$part2 = 0;

interface Expr {
    public function eval(): int;
    public function getDebugString(int $depth): string;
}
class Value implements Expr {
    private int $val;
    public string $key;

    public function __construct(string $key, int $val) {
        $this->val = $val;
        $this->key = $key;
    }

    public function eval(): int{
        return $this->val;
    }
    public function getDebugString(int $depth): string {
        return "$this->key";
    }

}
class Op implements Expr {
    public Expr $lhs;
    public string $op;
    public Expr $rhs;
    public string $key;

    public function __construct(Expr $l, string $op, Expr $r, string $key) {
        $this->lhs = $l;
        $this->op = $op;
        $this->rhs = $r;
        $this->key = $key;
    }

    public function eval(): int {
        return match ($this->op) {
            "AND" => $this->lhs->eval() & $this->rhs->eval(),
            "XOR" => $this->lhs->eval() ^ $this->rhs->eval(),
            "OR" => $this->lhs->eval() | $this->rhs->eval(),
        };
    }

    public function getDebugString(int $depth): string {
        if ($depth == 0) {
            return $this->key;
        }
        return "$this->key = (" . $this->lhs->getDebugString($depth - 1) . " $this->op " . $this->rhs->getDebugString($depth - 1) . ")";
    }
}

$exprs = [];
for($i = 0; $i < count($lines); $i++) {
    if (!$lines[$i]) {
        break;
    }
    [$key, $value] = explode(": ", $lines[$i]);
    $exprs[$key] = new Value($key, intval($value));
}
$i++;
for(; $i < count($lines); $i++) {
    [$expr, $key] = explode(" -> ", $lines[$i]);
    if (str_contains($expr, "AND")) {
        $op = "AND";
    } else if (str_contains($expr, "XOR")) {
        $op = "XOR";
    } else {
        $op = "OR";
    }
    [$lhs, $rhs] = explode(" $op ", $expr);
    $tmp[$key] = [$lhs, $op, $rhs];
}

function getOp(string $key, &$tmp, &$exprs): Expr {
    if (array_key_exists($key, $exprs)) {
        return $exprs[$key];
    }
    [$lhs, $op, $rhs] = $tmp[$key];
    $exprs[$key] = new Op(getOp($lhs, $tmp, $exprs), $op, getOp($rhs, $tmp, $exprs), $key);
    return $exprs[$key];
}

$numZs = 45;

for ($i = 0; $i <= $numZs; $i++) {
    $v = "z" . str_pad("$i", 2, "0", STR_PAD_LEFT);
    $exprs[$v] = getOp($v, $tmp, $exprs);
}

$bitString = "";
for ($i = 0; $i <= $numZs; $i++) {
    $v = "z" . str_pad("$i", 2, "0", STR_PAD_LEFT);
    $value = $exprs[$v]->eval();
    $bitString = "" . $value . $bitString;
}
echo("BitString $bitString\n");
$part1 = bindec($bitString);

function recurseOutputBit(Op $v): void {
    // This num indicates which output we are looking at.
    $num = substr($v->key, 1);
    // check lhs and rhs to find some input bits?
    if ($num == "00") {
        // Lowest bit doesn't have a carry.
//        echo("Low bit " . $v->getDebugString(2) . "\n");
        return;
    } else if ($num == "45") {
        // There are no input bits for 45, so its just the carry bit immediately.
        recurseCarryBit($v, $num);
        return;
    }
    // An output bit should be determined as the XOR of a carry bit and the inputs.
    if ($v->op != "XOR") {
        echo("Expected XOR op for output bit " . $v->getDebugString(2) . "\n");
        return;
    }

    if ($v->lhs->op == "OR") {
        $carrySide = "lhs";
        $carry = $v->lhs;
        $inputs = $v->rhs;
    } else {
        // Assume rhs is the carry.
        $carrySide = "rhs";
        $carry = $v->rhs;
        $inputs = $v->lhs;
    }
    if ($carry->op != "OR") {
        // The first carry bit is special and can directly check the inputs.
        if ($num == "01") {
            // lhs/rhs are just x00 and y00
            return;
        }

        echo("Expected OR op for carry($carrySide?) $num " . $v->getDebugString(2) . "\n");
        return;
    }
    // Both left and right of carry should be AND's
    if ($carry->lhs->op != "AND") {
        echo("Expected AND op for lhs carry bit " . $v->getDebugString(3) . "\n");
        return;
    }
    if ($carry->rhs->op != "AND") {
        echo("Expected AND op for rhs of carry bit " . $v->getDebugString(3) . "\n");
        return;
    }
    // this should be the carry bit.
    recurseCarryBit($carry, $num);
    recurseInputs($inputs, $num);
}

function recurseCarryBit(Expr $v, $num) {
//    echo("Carry bit for $num = " . $v->getDebugString(2) . "\n");
    // One side should be the previous inputs.

    // The inputs for carry should be 1 less than the current output.
    $num = "" . (intval($num) - 1);
    if (str_ends_with($v->lhs->lhs->key, $num)) {
        if (!str_ends_with($v->lhs->rhs->key, $num)) {
            echo("Expected $num in lhs for carry bit " . $v->getDebugString(2) . "\n");
        }
        return;
    }
    if (str_ends_with($v->rhs->lhs->key, $num)) {
        if (!str_ends_with($v->rhs->rhs->key, $num)) {
            echo("Expected $num in rhs for carry bit " . $v->getDebugString(2) . "\n");
        }
        return;
    }
    echo("Unexpected carry bit " . $v->getDebugString(2) . "\n");
}

function recurseInputs(Expr $v, $num): void {
    if ($v->op != "XOR") {
        echo("Expected XOR op for inputs bit " . $v->getDebugString(1) . "\n");
    }
    // We expect that the xA and yA inputs are used for the zA output.
    if (str_starts_with($v->lhs->key, $num)) {
        echo("Expected $num inputs $v->key = {$v->lhs->key} for $num\n");
    }
    if (str_starts_with($v->rhs->key, $num)) {
        echo("Expected $num input $v->key = {$v->rhs->key} for $num\n");
    }
}

for ($i = 0; $i <= $numZs; $i++) {
    $v = "z" . str_pad("$i", 2, "0", STR_PAD_LEFT);
//    echo("$v debug = " . $exprs[$v]->getDebugString(2) . "\n");
}

$wrong = [];
// Swap kfp = (x09 AND y09) and hbs = (y09 XOR x09)
$exprs["kfp"]->op = "XOR";
$exprs["hbs"]->op = "AND";

$wrong[] = "kfp";
$wrong[] = "hbs";

// Swap z27 with jcp
// Swap z27 = (ckj = (y27 XOR x27) AND bch = (ntq OR cnr))
// With jcp = (ckj XOR bch)
$a = 'z27';
$b = 'jcp';
$lhs = $exprs[$a]->lhs;
$rhs = $exprs[$a]->rhs;
$op = $exprs[$a]->op;
$exprs[$a]->lhs = $exprs[$b]->lhs;
$exprs[$a]->rhs = $exprs[$b]->rhs;
$exprs[$a]->op = $exprs[$b]->op;
$exprs[$b]->lhs = $lhs;
$exprs[$b]->rhs = $rhs;
$exprs[$b]->op = $op;
$wrong[] = $a;
$wrong[] = $b;


// z44 = (kgp = (vdn OR twg) XOR tpf = (x44 XOR y44))
// z19 = (vmg = (x19 XOR y19) XOR rfk = (dhq = (pvk XOR fwt) OR qdb = (pvk AND fwt)))
//z18 = (x18 AND y18)
// dhq looks wrong, expecting an AND, but got XOR?
// z18 look wrong, expecting an XOR
$a = 'z18';
$b = 'dhq';
$lhs = $exprs[$a]->lhs;
$rhs = $exprs[$a]->rhs;
$op = $exprs[$a]->op;
$exprs[$a]->lhs = $exprs[$b]->lhs;
$exprs[$a]->rhs = $exprs[$b]->rhs;
$exprs[$a]->op = $exprs[$b]->op;
$exprs[$b]->lhs = $lhs;
$exprs[$b]->rhs = $rhs;
$exprs[$b]->op = $op;
$wrong[] = $a;
$wrong[] = $b;

// A normal output looks like
// z44 = (kgp = (vdn OR twg) XOR tpf = (x44 XOR y44))
// z22 = (bqp = (x22 AND y22) OR gkg = (dcm AND dbp))
// z23 = (pdg = (dcm XOR dbp) XOR tfm = (x23 XOR y23))
// pdg looks wrong, expecting an OR, but got XOR.
// z22 looks wrong, expecting XOR but got OR

$a = 'pdg';
$b = 'z22';
$lhs = $exprs[$a]->lhs;
$rhs = $exprs[$a]->rhs;
$op = $exprs[$a]->op;
$exprs[$a]->lhs = $exprs[$b]->lhs;
$exprs[$a]->rhs = $exprs[$b]->rhs;
$exprs[$a]->op = $exprs[$b]->op;
$exprs[$b]->lhs = $lhs;
$exprs[$b]->rhs = $rhs;
$exprs[$b]->op = $op;
$wrong[] = $a;
$wrong[] = $b;

echo("Debug: \n");
for ($i = 0; $i <= $numZs; $i++) {
    $v = "z" . str_pad("$i", 2, "0", STR_PAD_LEFT);
    recurseOutputBit($exprs[$v]);
}

sort($wrong);
$part2 = join(",", $wrong);
echo("Part 1: $part1\n");
echo("Part 2: $part2\n");
