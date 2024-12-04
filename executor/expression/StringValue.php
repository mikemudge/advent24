<?php
namespace expression;

use ProgramContext;

class StringValue implements Expression {

    private string $value;

    public function __construct(string $value) {
        $this->value = $value;
    }

    public function calculate(ProgramContext $context): string {
        return $this->value;
    }

    public function __toString(): string {
        return "\"$this->value\"";
    }
}