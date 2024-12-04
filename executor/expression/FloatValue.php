<?php
namespace expression;

use ProgramContext;

class FloatValue implements Expression {

    private float $value;

    public function __construct(float $value) {
        $this->value = $value;
    }

    public function calculate(ProgramContext $context): float {
        return $this->value;
    }

    public function __toString() {
        return "$this->value";
    }
}