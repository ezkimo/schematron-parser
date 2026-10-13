<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use MMNewmedia\Schematron\Console\CompileSchematronCommand;
use Symfony\Component\Console\Application;

$app = new Application('schematron-parser', '1.0.0');
$app->addCommand(new CompileSchematronCommand());
$app->setDefaultCommand('compile', true);
$app->run();