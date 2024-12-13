<?php

use statement\LoopControlStatement;

class ProgramContext {

    private array $vars;
    private ?LoopControlStatement $control;

    public function __construct() {
        $this->vars = [];
        $this->control = null;
    }

    public function setVar(string $key, mixed $value): void {
        $this->vars[$key] = $value;
    }

    public function &getVar(string $key) {
        if (!array_key_exists($key, $this->vars)) {
            throw new RuntimeException("Undeclared variable $key");
        }
        return $this->vars[$key];
    }

    public function __toString(): string {
        return json_encode($this->vars, JSON_PRETTY_PRINT);
    }

    public function getAndResetControl(): ?LoopControlStatement {
        $control = $this->control;
        $this->control = null;
        return $control;
    }

    public function setControl(LoopControlStatement $control) {
        $this->control = $control;
    }

    public function checkControl(): ?LoopControlStatement {
        return $this->control;
    }
}