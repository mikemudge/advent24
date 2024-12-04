<?php
namespace statement;

use expression\BooleanExpression;
use expression\Expression;
use ProgramContext;

class LoopStatement extends BlockStatement {
    private BooleanExpression $condition;

    public function __construct(BooleanExpression $condition) {
        parent::__construct();
        $this->condition = $condition;
    }

    public function execute(ProgramContext $context) {
        while($this->condition->calculate($context) === Expression::TRUE) {
            parent::execute($context);
        }
    }

    public function printDebug(string $prefix): void {
        echo("$prefix$this\n");
        foreach($this->getStatements() as $statement) {
            $statement->printDebug("$prefix  ");
        }
        echo"$prefix}";
    }

    public function __toString(): string {
        return "while $this->condition {";
    }
}