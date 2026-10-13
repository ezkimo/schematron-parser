<?php

declare(strict_types=1);

namespace MMNewmedia\Schematron\Console;

use MMNewmedia\Schematron\Ast\XPathTokenizer;
use MMNewmedia\Schematron\Compiler\ExpressionCompiler;
use MMNewmedia\Schematron\Compiler\ExpressionNormalizer;
use MMNewmedia\Schematron\Compiler\FunctionLoader;
use MMNewmedia\Schematron\Compiler\XPathAstCompiler;
use MMNewmedia\Schematron\Parser\XPathAstParser;
use MMNewmedia\Schematron\SchematronCompilerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CompileSchematronCommand extends Command
{
    public function __construct()
    {
        parent::__construct('compile');
        $this->setDescription('Compile Schematron rule classes from a .sch file');
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, 'Path to the source Schematron (.sch) file')
            ->addArgument('output', InputArgument::REQUIRED, 'Path where generated rule class files will be written')
            ->addArgument('namespace', InputArgument::REQUIRED, 'PHP namespace for the generated rule classes (e.g., MyProject\\Schematron\\Rules)');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = (string) $input->getArgument('source');
        $outputDir = (string) $input->getArgument('output');
        $namespace = (string) $input->getArgument('namespace');

        if (!file_exists($source)) {
            $output->writeln('<error>Error: Source file not found: ' . $source . '</error>');
            return Command::FAILURE;
        }

        $packageRoot = dirname(__DIR__, 3);

        $functionLoader = new FunctionLoader(
            $packageRoot . '/src/Schematron/Compiler/Functions',
            'MMNewmedia\\Schematron\\Compiler\\Functions',
        );

        $expressionCompiler = new ExpressionCompiler(
            new XPathAstParser(new XPathTokenizer()),
            new XPathAstCompiler($functionLoader->load()),
            new ExpressionNormalizer(),
        );

        $service = new SchematronCompilerService($expressionCompiler, $outputDir);
        $service->compile($source, $namespace);

        $output->writeln('Compiled Schematron rules from ' . $source . ' to ' . $outputDir);

        return Command::SUCCESS;
    }
}
