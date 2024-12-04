<?php

class ProgramContext {

    private array $vars;

    public function __construct() {
        $this->vars = [];
    }

    public function setVar(string $key, mixed $value): void {
        $this->vars[$key] = $value;
    }

    public function getVar(string $key) {
        if (!array_key_exists($key, $this->vars)) {
            throw new RuntimeException("Undeclared variable $key");
        }
        return $this->vars[$key];
    }

    public function __toString(): string {
        return json_encode($this->vars, JSON_PRETTY_PRINT);
    }
}