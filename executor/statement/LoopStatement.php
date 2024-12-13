<?php
namespace statement;

use expression\BooleanExpression;
use expression\Expression;
use ProgramContext;

class LoopStatement extends BlockStatement {
    private BooleanExpression $condition;
    private ?Statement $init;
    private ?Statement $loop;

    public function __construct(BooleanExpression $condition, ?Statement $initStatement = null, ?Statement $loopStatement = null) {
        parent::__construct();
        $this->condition = $condition;
        $this->init = $initStatement;
        $this->loop = $loopStatement;
    }

    public function execute(ProgramContext $context) {
        $this->init?->execute($context);
        while($this->condition->calculate($context) === Expression::TRUE) {
            parent::execute($context);
            $control = $context->getAndResetControl();
            if ($control && $control->getType() == "break") {
                // exit the loop now
                break;
            }
            $this->loop?->execute($context);
        }
    }

    public function printDebug(string $prefix): void {
        $cls = get_class($this);
        echo("$prefix$this ($cls)\n");
        foreach($this->getStatements() as $statement) {
            $statement->printDebug("$prefix  ");
        }
        echo"$prefix}\n";
    }

    public function __toString(): string {
        return "while $this->condition {";
    }
}