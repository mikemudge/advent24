<?php
namespace expression;

class MathExpression implements Expression {

    private Expression $lhs;
    private Expression $rhs;
    private string $op;

    public function __construct(Expression $lhs, Expression $rhs, string $op) {
        $this->lhs = $lhs;
        $this->rhs = $rhs;
        $this->op = $op;
    }

    public function calculate(\ProgramContext $context): mixed {
        // TODO we may need to handle types better here.
        $l = (float)$this->lhs->calculate($context);
        $r = (float)$this->rhs->calculate($context);
        return match ($this->op) {
            "+" => $l + $r,
            "-" => $l - $r,
            "*" => $l * $r,
            "/" => $l / $r,
            default => throw new \RuntimeException("Unknown math operator $this->op")
        };
    }

    public function __toString(): string {
        return "$this->lhs $this->op $this->rhs";
    }
}