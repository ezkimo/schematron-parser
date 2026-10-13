<?php

declare(strict_types=1);

$autoload = $GLOBALS['_composer_autoload_path']
    ?? dirname(__DIR__) . '/vendor/autoload.php';

require $autoload;
unset($GLOBALS['_composer_autoload_path']);

use MMNewmedia\Schematron\Console\CompileSchematronCommand;
use Symfony\Component\Console\Application;

$app = new Application('schematron-parser', '1.0.0');
$app->addCommand(new CompileSchematronCommand());
$app->setDefaultCommand('compile', true);
$app->run();
