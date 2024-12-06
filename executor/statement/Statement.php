<?php
namespace statement;

use ProgramContext;

abstract class Statement {

    public abstract function execute(ProgramContext $context);

    public function printDebug(string $prefix) {
        $cls = get_class($this);
        echo("$prefix$this ($cls)\n");
    }

}