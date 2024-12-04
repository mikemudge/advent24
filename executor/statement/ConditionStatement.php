<?php
namespace statement;

use expression\BooleanExpression;
use expression\Expression;
use ProgramContext;

class ConditionStatement extends BlockStatement {
    private BooleanExpression $condition;

    public function __construct(BooleanExpression $condition) {
        parent::__construct();
        $this->condition = $condition;
    }

    public function execute(ProgramContext $context) {
        if($this->condition->calculate($context) === Expression::TRUE) {
            parent::execute($context);
        }
        // TODO support else case?
    }

    public function printDebug(string $prefix) {
        $cls = get_class($this);
        echo("$prefix$cls\n");
        echo("$prefix$this\n");
        foreach($this->getStatements() as $statement) {
            $statement->printDebug("$prefix  ");
        }
        echo"$prefix}";
    }
    public function __toString(): string {
        return "if $this->condition {";
    }
}