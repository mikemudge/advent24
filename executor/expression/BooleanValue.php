<?php
namespace expression;

use ProgramContext;

class BooleanValue implements Expression {

    private bool $value;

    public function __construct(bool $value) {
        $this->value = $value;
    }

    public function calculate(ProgramContext $context): bool {
        return $this->value;
    }

    public function __toString() {
        return "$this->value";
    }
}