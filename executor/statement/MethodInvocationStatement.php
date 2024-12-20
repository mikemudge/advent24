<?php

namespace statement;
use expression\Expression;
use expression\VarExpression;
use ProgramContext;

class MethodInvocationStatement extends Statement implements Expression {
    private ?Expression $targetObject;
    private string $methodName;
    /** @var Expression[] */
    private array $args;

    public function __construct(?Expression $targetObject, string $methodName, array $args) {
        $this->targetObject = $targetObject;
        $this->methodName = $methodName;
        $this->args = $args;
    }

    public function execute(ProgramContext $context) {
        $this->calculate($context);
    }

    public function calculate(\ProgramContext $context): mixed {

        $evaluatedArgs = [];
        foreach($this->args as $arg) {
            $evaluatedArgs[] = $arg->calculate($context);
        }
        if ($this->methodName == "print") {
            echo("Output: " . join(" ", $evaluatedArgs) . PHP_EOL);
            return null;
        }
        if ($this->methodName == "readfile") {
            $contents = file_get_contents($evaluatedArgs[0]);
            if (!$contents) {
                // TODO this should use an internal exception?
                $dir = getcwd();
                throw new \RuntimeException("No contents for $evaluatedArgs[0] in $dir");
            }
            return explode("\n", $contents);
        }
        if ($this->methodName == "count") {
            return count($evaluatedArgs[0]);
        }

        if ($this->methodName == "toString") {
            return strval($evaluatedArgs[0]);
        }

        if ($this->methodName == "abs") {
            return abs($evaluatedArgs[0]);
        }

        if (!$this->targetObject) {
            throw new \RuntimeException("Target was not provided for method call $this->methodName");
        }
        if ($this->methodName == "sort") {
            $obj = &$this->targetObject->calculate($context);
            sort($obj);
            return $obj;
        }
        if ($this->methodName == "split") {
            $separator = $evaluatedArgs[0] ?? " ";
            if (!$evaluatedArgs[0]) {
                throw new \RuntimeException("Method split had a null arg");
            }
            $obj = &$this->targetObject->calculate($context);
            return explode($separator, $obj);
        }
        if (count($evaluatedArgs) != 1) {
            throw new \RuntimeException("Unexpected number of arguments for $this->methodName call");
        }
        if ($this->methodName == "push") {
            // How to push to array which is referenced as a var?
            $array = &$this->targetObject->calculate($context);
            $array[] = $evaluatedArgs[0];
            return $array;
        }
        if ($this->methodName == "join") {
            // A catch all which will call any php function
            $obj = &$this->targetObject->calculate($context);
            return join($evaluatedArgs[0], $obj);
        }

        throw new \RuntimeException("Unknown method $this->methodName");
    }

    public function __toString(): string {
        $argsString = join(", ", $this->args);
        return "$this->methodName ($argsString)";
    }
}