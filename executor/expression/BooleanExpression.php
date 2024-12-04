<?php

namespace expression;

use RuntimeException;

class BooleanExpression implements Expression {

    private Expression $left;
    private Expression $right;
    private string $compare;

    public function __construct(Expression $lhs, Expression $rhs, string $compare) {
        $this->left = $lhs;
        $this->right = $rhs;

        if (in_array($compare, ["==", "!=", "<=", ">=", "<", ">", "in"])) {
            $this->compare = $compare;
        } else {
            throw new RuntimeException("Unknown comparator $compare");
        }
    }

    public function calculate(\ProgramContext $context): bool {
        $leftValue = $this->left->calculate($context);
        $rightValue = $this->right->calculate($context);
        return match ($this->compare) {
            "==" => $leftValue == $rightValue,
            "!=" => $leftValue != $rightValue,
            "<=" => $leftValue <= $rightValue,
            ">=" => $leftValue >= $rightValue,
            "<" => $leftValue < $rightValue,
            ">" => $leftValue > $rightValue,
            "in" => array_key_exists($leftValue, $rightValue),
            default => throw new \RuntimeException("Should be unreachable, check the constructor constraints"),
        };
    }

    public function __toString(): string {
        return "$this->left $this->compare $this->right";
    }
}