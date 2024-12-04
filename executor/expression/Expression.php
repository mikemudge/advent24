<?php

namespace expression;

interface Expression {

    const TRUE = true;

    public function calculate(\ProgramContext $context): mixed;
}