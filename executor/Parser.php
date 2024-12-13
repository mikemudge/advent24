<?php

use expression\AndExpression;
use expression\ArrayExpression;
use expression\ArrayValue;
use expression\BooleanExpression;
use expression\BooleanValue;
use expression\Expression;
use expression\FloatValue;
use expression\InExpression;
use expression\IntValue;
use expression\MathExpression;
use expression\OrExpression;
use expression\StringValue;
use expression\VarExpression;
use parser\Line;
use parser\ParserException;
use statement\ArrayAssignmentStatement;
use statement\AssignmentStatement;
use statement\BlockStatement;
use statement\ConditionStatement;
use statement\LoopControlStatement;
use statement\LoopStatement;
use statement\MethodInvocationStatement;

class Parser {

    private array $stack;
    private BlockStatement $currentBlock;
    private array $loopStack;
    private ?BlockStatement $currentLoop;

    public function __construct() {
        $this->stack = [];
        $this->loopStack = [];
        $this->currentLoop = null;
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
        $cur = $line->getCurrentChar();
        if ($cur == "}") {
            // End block
            if ($this->currentBlock === $this->currentLoop) {
                $this->currentLoop = array_pop($this->loopStack);
            }
            $this->currentBlock = array_pop($this->stack);
            return;
        }

        // Otherwise read a token and decide what to do with it.
        $firstToken = $line->getNextToken();
        if ($firstToken == 'break') {
            // exit the current loop
            if (!$this->currentLoop) {
                throw new ParserException("No loop to break from", $line);
            }
            $this->currentBlock->append(new LoopControlStatement("break"));
        } else if ($firstToken == 'continue') {
            if (!$this->currentLoop) {
                throw new ParserException("No loop to continue", $line);
            }
            // exit the current loop
            $this->currentBlock->append(new LoopControlStatement("continue"));
        } else if ($firstToken == 'while') {
            $condition = $this->parseBooleanExpression($line);
            $this->nestedLoop(new LoopStatement($condition));
        } else if ($firstToken == "for") {
            // init must be an assignment?
            $firstToken = $line->getNextToken();
            $init = $this->handleStatement($firstToken, $line);
            $line->expectChar(";");
            $condition = $this->parseBooleanExpression($line);
            $line->expectChar(";");
            $firstToken = $line->getNextToken();
            $loopStatement = $this->handleStatement($firstToken, $line);
            $this->nestedLoop(new LoopStatement($condition, $init, $loopStatement));
        } else if ($firstToken == "if") {
            $condition = $this->parseBooleanExpression($line);
            $this->nestedBlock(new ConditionStatement($condition));
        } else {
            // Handle other statements, like assignments or method calls?
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
        if ($next == "+") {
            $var = new VarExpression($firstToken);
            $symbol = $line->consumeSymbol();
            if ($symbol == "++") {
                $inc = new IntValue(1);
            } else if ($symbol == "+=") {
                $inc = $this->parseExpression($line);
            } else {
                throw new ParserException("Unknown symbol $symbol", $line);
            }
            return new AssignmentStatement($firstToken, new MathExpression($var, $inc, "+"));
        }

        // Otherwise we need to check for method calls which are an expression.
        // Those handle things like referencing a method on an array or another target object.
        return $this->parseStatementExpression($firstToken, $line);
    }

    private function parseBooleanExpression(Line $line): BooleanExpression {
        $lhs = $this->parseExpression($line);

        if ($lhs instanceof BooleanExpression) {
            return $lhs;
        } else {
            // parsed an expression which is not boolean in nature?
            throw new ParserException("Expecting a boolean condition", $line);
        }
    }

    private function parseExpression(Line $line): Expression {

        $target = $this->parseValue($line);

        // After a first value is parsed we need to check for continuations.
        return $this->greedyExpression($target, $line);
    }

    private function nestedLoop(LoopStatement $loop): void {
        if ($this->currentLoop) {
            $this->loopStack[] = $this->currentLoop;
        }
        $this->currentLoop = $loop;
        $this->nestedBlock($loop);
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
        if (in_array($next, [")", "]", ",", ";", "{"])) {
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
        } else if (in_array($next, ["&", "|"])) {
            if ($expr instanceof BooleanExpression) {
                $symbol = $line->consumeSymbol();
                $rhs = $this->parseBooleanExpression($line);
                if ($symbol == "&&") {
                    $expr = new AndExpression($expr, $rhs);
                } else if ($symbol == "||") {
                    $expr = new OrExpression($expr, $rhs);
                } else {
                    throw new ParserException("Unknown symbol $symbol", $line);
                }
            } else {
                // Don't extend non boolean expressions.
                // E.g (x > 0 && x < 10) will parse lhs = x and rhs = "0 && ..."
                // If we return 0 for rhs then we have lhs = (x > 0) as a boolean so we can continue with &&
                return $expr;
            }
        } else if (in_array($next, ["<", ">", "=", "!"])) {
            $compare = $line->consumeSymbol();
            $rhs = $this->parseExpression($line);
            $expr = new BooleanExpression($expr, $rhs, $compare);
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
        if ($cur == ")" || $cur == "]") {
            return $args;
        }

        $args[] = $this->parseExpression($line);
        while($line->getCurrentChar() == ",") {
            $line->consumeChar();
            $args[] = $this->parseExpression($line);
        }
        // Should end with a ) or ]
        if ($line->getCurrentChar() != ")" && $line->getCurrentChar() != "]") {
            throw new ParserException("Unexpected $cur after method args, expecting , ] or )", $line);
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
        if ($cur == "[") {
            // TODO support non empty array.
            $line->consumeChar();
            $values = $this->parseArguments($line);
            // Consume the end ] as well.
            $line->consumeChar();
            return new ArrayValue($values);
        }
        $firstToken = $line->getNextToken();
        if (ctype_digit($cur) || $cur == "-") {
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

        if ($line->isComplete()) {
            return new VarExpression($firstToken);
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

        // Any other continuation symbol means that this value is complete as a var access.
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