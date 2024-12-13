<?php
namespace expression;

class ArrayValue implements Expression {

    /** @var Expression[] */
    private $values;

    public function __construct(array $values) {
        $this->values = $values;
    }

    public function calculate(\ProgramContext $context): array {
        $results = [];
        foreach ($this->values as $value) {
            $results[] = $value->calculate($context);
        }
        return $results;
    }

    public function __toString() {
        return "[" . join($this->values) .  "]";
    }
}