<?php
namespace expression;

class ArrayExpression implements Expression {
    private string $var;
    private Expression $index;

    public function __construct(string $var, $index) {
        if (!$var) {
            throw new \RuntimeException("Invalid var name");
        }
        $this->var = $var;
        $this->index = $index;
    }

    public function calculate(\ProgramContext $context): mixed {
        $arr = $context->getVar($this->var);
        $index = $this->index->calculate($context);
        $count = count($arr);
        if ($index >= $count) {
            throw new \OutOfBoundsException("$index is not in range of array $this->var with $count items");
        }
        return $arr[$index];
    }

    public function __toString(): string {
        return "$this->var";
    }
}