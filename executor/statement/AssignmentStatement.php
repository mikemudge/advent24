<?php
namespace statement;

use expression\Expression;
use ProgramContext;

class AssignmentStatement extends Statement {
    private Expression $valueExpression;
    private string $var;

    public function __construct(mixed $variable, Expression $expr) {
        $this->var = $variable;
        $this->valueExpression = $expr;
    }

    public function execute(ProgramContext $context): void {
        $context->setVar($this->var, $this->valueExpression->calculate($context));
    }

    public function __toString(): string {
        return "$this->var = $this->valueExpression";
    }
}