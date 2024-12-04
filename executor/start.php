<?php

// Make all warnings notices etc into errors.
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

function my_autoloader($class): void {
    // Convert namespaces to directory paths.
    $path = str_replace("\\", DIRECTORY_SEPARATOR, $class);

//    echo("Autoloading $path\n");
    include $path . '.php';
}

spl_autoload_register('my_autoloader');

$mainFile = $argv[1];
$classPath = dirname($mainFile);

$program = new Program($classPath, $mainFile);
$program->parseAll();

$program->execute();
