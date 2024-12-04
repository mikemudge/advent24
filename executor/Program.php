<?php

use statement\Statement;

class Program {

    /** @var Statement[] */
    private $instructions;
    /** @var string */
    private $file;
    /** @var string */
    private $folder;
    private $parser;

    public function __construct($folder, $file) {
        $this->folder = $folder;
        $this->file = $file;
        $this->instructions = [];
        $this->parser = new Parser();
    }

    public function parseAll(): void {
        $contents = file_get_contents($this->file);
        $lines = explode("\n", $contents);

        $this->instructions = $this->parser->parseLines($lines);
    }

    public function execute(): void {
        // Print out the parsed program to aid with debugging.
        foreach ($this->instructions as $instruction) {
            $instruction->printDebug("DEBUG:");
        }

        // execute the program.
        $context = new ProgramContext();
        foreach ($this->instructions as $instruction) {
            try {
                $instruction->execute($context);
            } catch (Throwable $e) {
                echo "Error: $instruction\n";
                throw $e;
            }
        }
    }
}