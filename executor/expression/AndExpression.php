<?php

namespace expression;

use RuntimeException;

class AndExpression extends BooleanExpression {

    private BooleanExpression $left;
    private BooleanExpression $right;

    public function __construct(BooleanExpression $lhs, BooleanExpression $rhs) {
        // Using a dud compare to inherit BooleanExpression?
        parent::__construct($lhs, $rhs, "==");
        $this->left = $lhs;
        $this->right = $rhs;
    }

    public function calculate(\ProgramContext $context): bool {
        $leftValue = $this->left->calculate($context);
        if (!$leftValue) {
            return false;
        }
        return $this->right->calculate($context);
    }

    public function __toString(): string {
        return "$this->left && $this->right";
    }
}