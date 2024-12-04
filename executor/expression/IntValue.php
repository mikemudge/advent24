<?php
namespace expression;

use ProgramContext;

class IntValue implements Expression {

    private int $value;

    public function __construct(int $value) {
        $this->value = $value;
    }

    public function calculate(ProgramContext $context): int {
        return $this->value;
    }

    public function __toString() {
        return "$this->value";
    }
}