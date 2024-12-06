<?php

use expression\ArrayExpression;
use expression\ArrayValue;
use expression\BooleanExpression;
use expression\FloatValue;
use expression\InExpression;
use expression\IntValue;
use expression\MathExpression;
use expression\StringValue;
use expression\VarExpression;
use statement\ArrayAssignmentStatement;
use statement\AssignmentStatement;
use statement\BlockStatement;
use statement\ConditionStatement;
use statement\LoopStatement;
use statement\MethodInvocationStatement;

class Parser {

    private array $stack;
    /**
     * @var ConditionStatement|mixed|null
     */
    private BlockStatement $currentBlock;

    public function __construct() {
        $this->stack = [];
    }

    public function parseLines($lines) {
        $root = new BlockStatement();
        $this->currentBlock = $root;
        $this->stack[] = $root;
        foreach($lines as $line) {
            $line = trim($line);
            if ($line && !str_starts_with($line, "#")) {
                // Only parse lines which aren't empty, and aren't comments.
                $this->parseStatement($line);
            }
        }
        // Check that the stack was successfully emptied?
        return $root->getStatements();
    }

    public function parseStatement($line) {
        // Remove any semicolon from the end of the line.
        $line = trim($line,";");

        $tokens = explode(" ", $line);

        switch ($tokens[0]) {
            case '}':
                // End block
                $this->currentBlock = array_pop($this->stack);
                return;
            case 'while':
                // loop stuff;
                $loop = $this->handleLoop($tokens);
                $this->currentBlock->append($loop);
                // Put the current block aside as we want to build the loop now.
                $this->stack[] = $this->currentBlock;
                $this->currentBlock = $loop;
                return;
            case 'if':
                // Conditional stuff.
                $conditional = $this->handleConditional($tokens);
                $this->currentBlock->append($conditional);
                // Put the current block aside as we want to build the loop now.
                $this->stack[] = $this->currentBlock;
                $this->currentBlock = $conditional;
                return;
            default:
                // Handle other statements.
                $statement = $this->handleStatement($line);
                $this->currentBlock->append($statement);
        }
    }

    private function handleStatement(string $line) {

        // TODO check if tokens[0] is a type definition?
        $tokens = explode(" ", $line);
        if (str_contains($tokens[0], "(")) {
            // Probably a method call?
            return $this->handleMethodInvocation($line);
        }
        $operators = ["="];
        if (in_array($tokens[1], $operators)) {
            $variable = $tokens[0];
            $operator = $tokens[1];
            $remaining = join(" ", array_splice($tokens, 2));
        } else if (in_array($tokens[2], $operators)) {
            $variable = $tokens[1];
            $operator = $tokens[2];
            $remaining = join(" ", array_splice($tokens, 3));
        } else {
            throw new RuntimeException("Unknown operator $line");
        }
        if ($operator == "=") {
            $expression = $this->parseExpression($remaining);

            // Check for assigning to an array variable.
            $parts = explode("[", $variable);
            if (count($parts) == 1) {
                return new AssignmentStatement($variable, $expression);
            }
            // Remove trailing ]
            $index = $this->parseExpression(substr($parts[1], 0, -1));
            return new ArrayAssignmentStatement($parts[0], $index, $expression);
        } else {
            throw new RuntimeException("Unknown statement $line");
        }
    }

    private function handleConditional(array $tokens): ConditionStatement {
        if (count($tokens) < 3) {
            throw new RuntimeException("Not enough tokens for a conditional");
        }
        $lhs = $this->parseExpression($tokens[1]);
        $rhs = $this->parseExpression($tokens[3]);
        $compare = $tokens[2];
        $condition = new BooleanExpression($lhs, $rhs, $compare);
        return new ConditionStatement($condition);
    }

    private function handleLoop(array $tokens): BlockStatement {
        // while i < count(data) {
        if (count($tokens) < 3) {
            throw new RuntimeException("Not enough tokens for a loop" . join($tokens));
        }
        // parse the current line to determine the entry criteria etc.
        $lhs = $this->parseExpression($tokens[1]);
        $rhs = $this->parseExpression($tokens[3]);
        $compare = $tokens[2];
        $condition = new BooleanExpression($lhs, $rhs, $compare);
        return new LoopStatement($condition);
    }

    private function parseExpression(string $line) {
        $tokens = explode(" ", $line);

        // TODO need a better way to detect methods.
        // ( can be used in math expressions as well.
        if (str_contains($tokens[0], "(")) {
            // Probably a method call?
            return $this->handleMethodInvocation($line);
        }

        if (str_starts_with($line, "[")) {
            // Assume its an array value.
            // TODO support a non empty array.
            return new ArrayValue();
        }
        if (str_starts_with($line, "\"")) {
            // Assume its a string value.
            return new StringValue(substr($line, 1, -1));
        }
        if (is_numeric($line)) {
            if (is_int($line)) {
                return new IntValue($line);
            } else {
                return new FloatValue((float)$line);
            }
        }

        // It could be mathematical (E.g x + 1)
        // TODO here we should find an expression greedily.
        // E.g x + 1 would find "x"
        // Then we check if there is more after that expression and create more complex expressions.

        // If we are here it must be a var or array access.
        $varName = [];
        $chars = str_split($line);
        foreach ($chars as $c) {
            if (ctype_alnum($c)) {
                $varName[] = $c;
            } else {
                break;
            }
        }
        if ($c == "]") {
            // We reached the end of an array index, return what we have.
            return $this->parseExpression(join("", $varName));
        }
        if ($c == "[") {
            // We hit an array lookup, get the index so we can return an array expression.
            [, $rest] = explode("[", $line, 2);
            $index = $this->parseExpression($rest);
            // Expect an ] after this?
            $firstPart = new ArrayExpression(join("", $varName), $index);
            // we need to consume everything which made up index now.
            // E.g v[ii - 1] is multiple tokens, but our call to parseExpression consumed them.
            // TODO This will not support nested array lookups.
            [, $rest] = explode("]", $line, 2);
            $rest = trim($rest);
            if (empty($rest)) {
                return $firstPart;
            }
            $remainingTokens = explode(" ", $rest);
        } else {
            // Otherwise we just found a var.
            $firstPart = new VarExpression(join("", $varName));
            if (count($tokens) == 1) {
                return $firstPart;
            }
            $remainingTokens = array_splice($tokens, 1);
        }

        // Check for mathematical expression like "x - 1" rather than just "x"
        // Check number of tokens to see.

        $op = $remainingTokens[0];
        $remaining = join(" ", array_splice($remainingTokens, 1));

        // If there are more tokens, see what operator we have to deal with.
        if (in_array($op, ["+", "-", "*", "/"])) {
            $lhs = $firstPart;
            // Parse the rest.
            $rhs = $this->parseExpression($remaining);
            return new MathExpression($lhs, $rhs, $op);
        } else {
            // We found an expression (firstPart) but don't know what to do with the rest of this line.
            throw new RuntimeException("unexpected $op in $line");
        }
    }

    public function handleMethodInvocation(string $line) {
        $name = explode("(", $line);
        // Some methods will have a part of the params in the first token.
        // E.g x.call(4);
        $methodName = $name[0];
        $parts = explode(".", $methodName);
        if (count($parts) == 1) {
            // A straight call to a method.
            // call()
            $targetObject = null;
        } else {
            // A call on another target
            // x.call()
            $targetObject = $this->parseExpression($parts[0]);
            $methodName = $parts[1];
        }
        $args = [];

        // Remove the methodName, just get the rest
        // Remove the last ) and everything after it to find the params for this method.
        // TODO this could be improved upon.
        $openBracketIndex = strpos($line, "(");
        $endParamIndex = strrpos($line, ")");
        $paramsString = substr($line, $openBracketIndex + 1, $endParamIndex - $openBracketIndex - 1);

        // Ignore empty string for things like call() has no params.
        if (!empty($paramsString)) {
            $params = explode(",", $paramsString);
            foreach ($params as $p) {
                $args[] = $this->parseExpression(trim($p));
            }
        }
        return new MethodInvocationStatement($targetObject, $methodName, $args);
    }
}