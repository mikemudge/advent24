<?php
namespace statement;

use ProgramContext;

class BlockStatement extends Statement {

    /** @var Statement[] */
    private array $statements;

    public function __construct() {
        $this->statements = [];
    }

    public function execute(ProgramContext $context) {
        foreach($this->statements as $statement) {
            // TODO need to support break/continue type statements?
            $statement->execute($context);
        }
    }

    public function append(Statement $statement) {
        $this->statements[] = $statement;
    }

    public function getStatements() {
        return $this->statements;
    }
}