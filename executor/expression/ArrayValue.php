<?php
namespace expression;

class ArrayValue implements Expression {

    public function __construct() {
    }

    public function calculate(\ProgramContext $context): mixed {
        return [];
    }

    public function __toString() {
        return "[]";
    }
}