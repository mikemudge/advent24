<?php
namespace parser;

class Line {

    private int $index;
    private string $line;
    private int $lineNumber;
    private int $length;

    public function __construct(string $line, int $lineNumber) {
        $this->index = 0;
        $this->lineNumber = $lineNumber;
        $this->line = trim($line);
        $this->length = strlen($this->line);
    }

    public function isComment() {
        return str_starts_with($this->line, "#");
    }
    public function isEmpty() {
        return empty($this->line);
    }

    public function consumeWhitespace(): void {
        while($this->index < $this->length) {
            $cur = $this->line[$this->index];
            if (in_array($cur, [" ", "\t"])) {
                $this->index++;
            } else {
                return;
            }
        }
    }

    public function consumeChar() {
        $this->index++;
    }

    public function isComplete(): bool {
        return $this->index >= $this->length;
    }

    public function getCurrentChar() {
        $this->consumeWhitespace();
        if ($this->isComplete()) {
            throw new ParserException("EOL reached", $this);
        }
        return $this->line[$this->index];
    }

    public function expectChar(string $char) {
        if ($this->getCurrentChar() != $char) {
            throw new ParserException("Expecting $char", $this);
        }
        $this->consumeChar();
    }

    public function readUntil(string $string): string {
        $start = $this->index;
        while($this->index < $this->length) {
            $cur = $this->line[$this->index];
            if ($cur == $string) {
                return substr($this->line, $start, $this->index - $start);
            }
            $this->index++;
        }
        throw new ParserException("No $string was found", $this);
    }

    public function consumeSymbol(): string {
        $this->consumeWhitespace();

        $start = $this->index;

        while($this->index < $this->length) {
            $cur = $this->line[$this->index];
            // alphanumeric chars are not symbols.
            if (ctype_alnum($cur)) {
                break;
            }
            if ($cur == ";") {
                break;
            }
            // whitespace is not part of symbols.
            if (in_array($cur, [" ", "\t"])) {
                break;
            }
            $this->index++;
        }
        $length = $this->index - $start;
        if ($length == 0) {
            throw new ParserException("No symbol available $start:$length $cur", $this);
        }
        return substr($this->line, $start, $this->index - $start);
    }

    public function getNextToken() {
        // Consume all whitespace before reading the next token.
        $this->consumeWhitespace();

        $start = $this->index;

        // TODO "}" should be handled differently?
        $symbols = ["-", "_"];
        while($this->index < $this->length) {
            $cur = $this->line[$this->index];
            // alphanumeric chars are included in a token.
            if (ctype_alnum($cur)) {
                $this->index++;
                continue;
            }
            if (in_array($cur, $symbols)) {
                $this->index++;
                continue;
            }
            // Everything else ends the token.
            break;
        }
        $length = $this->index - $start;
        if ($length == 0) {
            throw new ParserException("No token available for getNextToken() $start:$length $cur", $this);
        }
        return substr($this->line, $start, $this->index - $start);

        // EOL?
    }

    public function __toString(): string {
        return $this->line . " at line $this->lineNumber, character $this->index";
    }

    public function getIndex(): int {
        return $this->index;
    }

    public function getLineNumber(): int {
        return $this->lineNumber;
    }
}