<?php

namespace MatthiasMullie\Minify\Command;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MinifyCommand extends Command
{
    protected function configure()
    {
        $this->setName('minify')
            ->setDescription('Minify js or css')
            ->addArgument(
                'from',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'From which files you wanna to minify'
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'Which type of file you wanna minify? js or css (Default is auto detected according to the extension name.)'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'The output file (Default is STDOUT)'
            )
            ->addOption(
                'append',
                'a',
                InputOption::VALUE_OPTIONAL,
                'Append to the file (Default is overwrite)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from = $input->getArgument('from');
        $type = $input->getOption('type');
        $outputFile = $input->getOption('output');
        $appendFile = $input->getOption('append');

        if (!is_array($from)) {
            $from = (array) $from;
        }

        if (empty($type)) {
            $autoDetectedType = self::getFileExt($from[0]);
            foreach ($from as $fromFile) {
                $fileExt = self::getFileExt($fromFile);
                if (strcasecmp($fileExt, $autoDetectedType) !== 0) {
                    $output->writeln('<error>Error: type of input files is not all the same!</error>');

                    return 1;
                }
            }

            $type = $autoDetectedType;
        }

        if (empty($type)) {
            $output->writeln('<error>Error: cannot find the type of input file!</error>');

            return 1;
        }

        switch (strtolower($type)) {
            case 'css':
                $minifier = new CSS();
                break;
            case 'js':
                $minifier = new JS();
                break;
            default:
                $output->writeln("<error>Error: Unsupported type: $type</error>");

                return 3;
        }

        foreach ($from as $fromFile) {
            if (!file_exists($fromFile)) {
                $output->writeln("<error>Error: File '{$fromFile}' not found!</error>");

                return 2;
            }
            $minifier->add($fromFile);
        }

        $result = $minifier->minify();

        if (empty($outputFile) && empty($appendFile)) {
            $output->writeln($result, OutputInterface::OUTPUT_RAW);
        } else {
            if (!empty($outputFile)) {
                file_put_contents($outputFile, $result);
            }

            if (!empty($appendFile)) {
                file_put_contents($appendFile, $result, FILE_APPEND);
            }
        }

        return 0;
    }

    public static function getFileExt($fileName)
    {
        return ltrim(strrchr($fileName, '.'), '.');
    }
}
