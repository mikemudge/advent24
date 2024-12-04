<?php
namespace expression;

class VarExpression implements Expression {
    private string $var;

    public function __construct(string $var) {
        if (!$var) {
            throw new \RuntimeException("Invalid var name");
        }
        $this->var = $var;
    }

    public function &calculate(\ProgramContext $context): mixed {
        return $context->getVar($this->var);
    }

    public function __toString(): string {
        return "$this->var";
    }
}