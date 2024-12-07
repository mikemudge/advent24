<?php

namespace parser;

use RuntimeException;

class ParserException extends RuntimeException {

    private int $lineNumber;
    private int $charIndex;

    public function __construct(string $string, Line $line) {
        $this->lineNumber = $line->getLineNumber();
        $this->charIndex = $line->getIndex();
        parent::__construct("$string at Line $this->lineNumber, character $this->charIndex");
    }
}