<?php

use expression\ArrayExpression;
use expression\ArrayValue;
use expression\BooleanExpression;
use expression\BooleanValue;
use expression\Expression;
use expression\FloatValue;
use expression\InExpression;
use expression\IntValue;
use expression\MathExpression;
use expression\StringValue;
use expression\VarExpression;
use parser\Line;
use parser\ParserException;
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
        foreach($lines as $i => $line) {
            $line = new Line($line, $i);
            // Only parse lines which aren't empty, and aren't comments.
            if ($line->isComment() || $line->isEmpty()) {
                continue;
            }
            try {
                $this->parseStatement($line);
            } catch (Throwable $t) {
                echo("Error parsing line $line\n");
                throw $t;
            }
        }
        // Check that the stack was successfully emptied?
        return $root->getStatements();
    }

    public function parseStatement(Line $line) {
        // Remove any semicolon from the end of the line.
//        $line = trim($line,";");

        $firstToken = $line->getNextToken();

        switch ($firstToken) {
            case '}':
                // End block
                $this->currentBlock = array_pop($this->stack);
                return;
            case 'while':
                // loop stuff;
                $condition = $this->parseBooleanExpression($line);
                $this->nestedBlock(new LoopStatement($condition));
                return;
            case 'if':
                // Conditional stuff.
                $condition = $this->parseBooleanExpression($line);
                $this->nestedBlock(new ConditionStatement($condition));
                return;
            default:
                // Handle other statements.
                $statement = $this->handleStatement($firstToken, $line);
                $this->currentBlock->append($statement);
        }
    }

    private function handleStatement(string $firstToken, Line $line) {
        $next = $line->getCurrentChar();
        if (ctype_alnum($next)) {
            // The first token may be a type def if the second token begins with an alpha numeric char.
            $type = $firstToken;
            $firstToken = $line->getNextToken();
            $next = $line->getCurrentChar();
        }

        // Check for an array variable being assigned to.
        if ($next == "[") {
            // TODO types can also be arrays like String[]
            $line->consumeChar();
            $next = $line->getCurrentChar();
            if ($next == "]") {
                $line->consumeChar();
                // Its an X[] which implies this is a type.
                $type = "array of $firstToken";
                $firstToken = $line->getNextToken();
                $next = $line->getCurrentChar();
            } else {
                // should be an array assignment, but we haven't seen the equals yet?
                // A simple array access doesn't do anything as a statement?
                // something like x[i] = ...
                $index = $this->arrayIndex($line);
                $next = $line->getCurrentChar();

                if ($next == "=") {
                    $line->consumeChar();
                    $expression = $this->parseExpression($line);
                    return new ArrayAssignmentStatement($firstToken, $index, $expression);
                }
            }
        }

        // Then check that this is actually an assignment.
        if ($next == "=") {
            $line->consumeChar();
            $expression = $this->parseExpression($line);
            return new AssignmentStatement($firstToken, $expression);
        }

        // Otherwise we need to check for method calls which are an expression.
        // Those handle things like referencing a method on an array or another target object.
        return $this->parseStatementExpression($firstToken, $line);
    }

    private function parseBooleanExpression(Line $line): BooleanExpression {
        $lhs = $this->parseExpression($line);

        // TODO a boolean variable alone should be considered acceptable here too?

        // TODO better reading for a comparator?
        $compare = $line->getCurrentChar();
        $line->consumeChar();
        $compare2 = $line->getCurrentChar();
        if ($compare2 == "=") {
            // <= >= == !=
            $compare .= $compare2;
            $line->consumeChar();
        }

        $rhs = $this->parseExpression($line);
        return new BooleanExpression($lhs, $rhs, $compare);
    }

    private function parseExpression(Line $line): Expression {

        $target = $this->parseValue($line);

        // After a first value is parsed we need to check for continuations.
        return $this->greedyExpression($target, $line);
    }

    private function nestedBlock(BlockStatement $block): void {
        $this->currentBlock->append($block);
        // Put the current block aside as we want to build the new block now.
        $this->stack[] = $this->currentBlock;
        $this->currentBlock = $block;
    }

    private function arrayIndex(Line $line) {
        // Read the array index expression.
        $index = $this->parseExpression($line);
        $closeBracket = $line->getCurrentChar();
        if ($closeBracket != "]") {
            throw new ParserException("Array access didn't end with ]" , $line);
        }
        // Use up the ]
        $line->consumeChar();
        return $index;
    }

    /**
     * parse an expression which can be used as a statement.
     * Can this be anything other than a method call?
     * The target of the call could be complex though.
     * E.g x.call(), a[i].call(), a.one().two()
     */
    private function parseStatementExpression(string $firstToken, Line $line): MethodInvocationStatement {
        // Default to just be a var.
        $expr = new VarExpression($firstToken);

        $next = $line->getCurrentChar();
        if ($next == "(") {
            // Appears to be a method call
            $targetObject = null;
            $methodName = $firstToken;
            $line->consumeChar();
            $args = $this->parseArguments($line);
            $expr = new MethodInvocationStatement($targetObject, $methodName, $args);
        } else if ($next == ".") {
            // Handle an access on $firstToken.
            $expr = $this->parseMethodCallOn($expr, $line);
        } else if ($next == "[") {
            $index = $this->arrayIndex($line);
            $expr = new ArrayExpression($firstToken, $index);
            // TODO an array expression is not a valid statement?
            // however there may be a method call on it?
        }

        return $expr;
    }

    /**
     * After parsing an existing expression, we need to check if its continued somehow.
     * E.g x + 1, x.call(), x[i] etc.
     */
    private function greedyExpression(Expression $expr, Line $line): Expression {
        // can we use parseStatementExpression to get method invocations?
        if ($line->isComplete()) {
            return $expr;
        }
        $next = $line->getCurrentChar();
        if ($next == ")" || $next == "]" || $next == ",") {
            // method arguments or array index accesses end with these.
            // A nested call (x + 1) * 2 would also have the lhs end with the )
            // TODO need to handle starting a nested expression with (
            return $expr;
        }
        if ($next == ".") {
            $expr = $this->parseMethodCallOn($expr, $line);
        } else if ($next == "[") {
            $line->consumeChar();
            $index = $this->arrayIndex($line);
            $expr = new ArrayExpression($expr, $index);
        } else if (in_array($next, ["+", "-", "*", "/"])) {
            // Consume the operation.
            $line->consumeChar();
            $rhs = $this->parseExpression($line);
            $expr = new MathExpression($expr, $rhs, $next);
        } else {
            // No known continuations
            throw new ParserException("Not sure what to do with $next", $line);
        }

        // Recursively find more things.
        return $this->greedyExpression($expr, $line);
    }

    private function parseArguments(Line $line): array {
        $args = [];
        $cur = $line->getCurrentChar();
        if ($cur == ")") {
            return $args;
        }

        $args[] = $this->parseExpression($line);
        while($line->getCurrentChar() == ",") {
            $line->consumeChar();
            $args[] = $this->parseExpression($line);
        }
        // Should end with a )
        if ($line->getCurrentChar() != ")") {
            throw new ParserException("Unexpected $cur after method args, expecting , or )", $line);
        }
        $line->consumeChar();
        return $args;
    }

    /**
     * Parse a value/expression in a non greedy way
     * Can be a constant primitive, a variable or a method call.
     */
    private function parseValue(Line $line): Expression {
        // Check for constant values, string, int bool etc first.
        $cur = $line->getCurrentChar();
        if ($cur == "\"") {
            $line->consumeChar();
            $string = $line->readUntil("\"");
            // Consume the trailing " and return
            $line->consumeChar();
            return new StringValue($string);
        }
        echo("parseValue got a $cur\n");
        $firstToken = $line->getNextToken();
        if (ctype_digit($cur)) {
            // Looks like a numeric value
            if (is_int($firstToken)) {
                return new IntValue($firstToken);
            } else {
                return new FloatValue((float) $firstToken);
            }
        }

        if ($firstToken == "true" || $firstToken == "false") {
            return new BooleanValue($firstToken == "true");
        }

        // Handle method calls and array accesses.
        $next = $line->getCurrentChar();
        if ($next == "(") {
            // Appears to be a method call
            $targetObject = null;
            $methodName = $firstToken;
            $line->consumeChar();
            $args = $this->parseArguments($line);
            return new MethodInvocationStatement($targetObject, $methodName, $args);
        }

        return new VarExpression($firstToken);
    }

    private function parseMethodCallOn(Expression $target, Line $line): MethodInvocationStatement {
        // consume the .
        $line->consumeChar();
        $methodName = $line->getNextToken();

        if ($line->getCurrentChar() != "(") {
            // Can't access properties of a target
            // Anything else this could be, it starts with x.y?
            throw new ParserException("Expecting method call $methodName on $target", $line);
        }
        $line->consumeChar();

        $args = $this->parseArguments($line);
        return new MethodInvocationStatement($target, $methodName, $args);
    }
}