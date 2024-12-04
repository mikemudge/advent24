<?php
namespace statement;

use ProgramContext;
use Throwable;

class BlockStatement extends Statement {

    /** @var Statement[] */
    private array $statements;

    public function __construct() {
        $this->statements = [];
    }

    public function execute(ProgramContext $context) {
        foreach($this->statements as $statement) {
            // TODO need to support break/continue type statements?
            try {
                $statement->execute($context);
            } catch (Throwable $e) {
                echo "Error: $statement\n";
                throw $e;
            }
        }
    }

    public function append(Statement $statement) {
        $this->statements[] = $statement;
    }

    public function getStatements() {
        return $this->statements;
    }
}