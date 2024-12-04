<?php
namespace statement;

use expression\Expression;
use ProgramContext;

class ArrayAssignmentStatement extends Statement {
    private Expression $valueExpression;
    private string $var;
    private Expression $index;

    public function __construct(string $variable, Expression $index, Expression $expr) {
        $this->var = $variable;
        $this->index = $index;
        $this->valueExpression = $expr;
    }

    public function execute(ProgramContext $context): void {
        $arr = &$context->getVar($this->var);
        $arr[$this->index->calculate($context)] = $this->valueExpression->calculate($context);
    }

    public function __toString(): string {
        return "$this->var = $this->valueExpression";
    }
}