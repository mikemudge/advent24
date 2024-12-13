<?php
namespace statement;

use ProgramContext;

class LoopControlStatement extends Statement {

    private string $type;

    public function __construct(string $type) {
        $this->type = $type;
    }

    public function execute(ProgramContext $context) {
        $context->setControl($this);
    }

    public function getType() {
        return $this->type;
    }
}