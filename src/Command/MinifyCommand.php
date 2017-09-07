<?php

namespace MatthiasMullie\Minify\Command;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Class MinifyCommand.
 */
class MinifyCommand extends Command
{
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('minify')
            ->setDescription('Minify js or css')
            ->addArgument(
                'from',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Input files to minify'
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'Type of file to minify, js or css (Default is auto detected according to the extension name.)'
            )
            ->addOption(
                'preserveComments',
                'c',
                InputOption::VALUE_NONE,
                'Save preserved comments in minified files'
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
                InputOption::VALUE_NONE,
                'Append to the file (Default is overwrite)'
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from = $input->getArgument('from');
        $type = $input->getOption('type');
        $preserveComments = $input->getOption('preserveComments');
        $outputFile = $input->getOption('output');
        $appendFile = $input->getOption('append');
        if ($outputFile === null && $appendFile) {
            throw new RuntimeException('Append file used without output file specification!');
        }
        if (!is_array($from)) {
            $from = (array) $from;
        }
        if (empty($type)) {
            $autoDetectedType = self::getFileExt($from[0]);
            foreach ($from as $fromFile) {
                $fileExt = self::getFileExt($fromFile);
                if (strcasecmp($fileExt, $autoDetectedType) !== 0) {
                    throw new RuntimeException('Type of input files is not all the same!');
                }
            }
            $type = $autoDetectedType;
        }
        if (empty($type)) {
            throw new RuntimeException('Error: cannot find the type of input file!');
        }
        switch (strtolower($type)) {
            case 'css':
                $minifier = new CSS();
                break;
            case 'js':
                $minifier = new JS();
                break;
            default:
                throw new RuntimeException("Unsupported type: {$type}");
        }
        foreach ($from as $fromFile) {
            if (!file_exists($fromFile)) {
                throw new RuntimeException("File '{$fromFile}' not found!");
            }
            $minifier->add($fromFile);
        }
        if ($preserveComments) {
            $minifier->setLeavePreservedComments(true);
        }
        $result = $minifier->minify();
        if ($outputFile === null) {
            $output->writeln($result, OutputInterface::OUTPUT_RAW);
        } elseif ($appendFile) {
            file_put_contents($outputFile, $result, FILE_APPEND);
        } else {
            file_put_contents($outputFile, $result);
        }

        return 0;
    }

    /**
     * @param $fileName
     *
     * @return string
     */
    public static function getFileExt($fileName)
    {
        return ltrim(strrchr($fileName, '.'), '.');
    }
}
