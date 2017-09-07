<?php

use Symfony\Component\Console\Tester\CommandTester;
use MatthiasMullie\Minify\Command\MinifyCommand;
use \org\bovigo\vfs\vfsStream;
use \org\bovigo\vfs\vfsStreamDirectory;

/**
 * Minify cli test.
 */
class CommandTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @var vfsStreamDirectory
     */
    private $rootDir;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->rootDir = vfsStream::setup('test');
        $this->commandTester = new CommandTester(new MinifyCommand());
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->commandTester = null;
        parent::tearDown();
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionMessage Not enough arguments (missing: "from").
     */
    public function runCommandWithoutArguments()
    {
        $this->commandTester->execute(array(), array());
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionMessage Append file used without output file specification!
     */
    public function runCommandWithAppendWithoutOutput()
    {
        $this->commandTester->execute(array('from' => 'test.css', '--append' => true), array());
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionMessage File 'test.css' not found!
     */
    public function runCommandWithNonexistentFile()
    {
        $this->commandTester->execute(array('from' => 'test.css'), array());
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionMessage Type of input files is not all the same!
     */
    public function runCommandWithDifferentFileTypes()
    {
        $this->commandTester->execute(array('from' => array('test.css', 'test.js')), array());
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionMessage Unsupported type: php
     */
    public function runCommandWithUnsupportedFileType()
    {
        $this->commandTester->execute(array('from' => array('test.php')), array());
    }

    /**
     * @test
     * @expectedException \Symfony\Component\Console\Exception\RuntimeException
     * @expectedExceptionMessage Error: cannot find the type of input file!
     */
    public function runCommandWithoutFileType()
    {
        $this->commandTester->execute(array('from' => 'test'), array());
    }

    /**
     * @test
     */
    public function runCommandWithCssFile()
    {
        vfsStream::newFile('test.css')
            ->withContent('/*!
                 * Bootstrap v3.3.7 (http://getbootstrap.com)
                 * Copyright 2011-2016 Twitter, Inc.
                 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
                 */
                /*! normalize.css v3.0.3 | MIT License | github.com/necolas/normalize.css */
                html {
                    font-family: sans-serif;
                    -webkit-text-size-adjust: 100%;
                    -ms-text-size-adjust: 100%;
                }
                body {
                    margin: 0;
                }')
            ->at($this->rootDir);
        $this->commandTester->execute(array('from' => array(vfsStream::url('test/test.css'))), array());
        $this->assertEquals('html{font-family:sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}body{margin:0}'.PHP_EOL, $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function runCommandWithPreserveComments()
    {
        vfsStream::newFile('test.css')
            ->withContent('/*!
                 * Bootstrap v3.3.7 (http://getbootstrap.com)
                 * Copyright 2011-2016 Twitter, Inc.
                 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
                 */
                /*! normalize.css v3.0.3 | MIT License | github.com/necolas/normalize.css */
                html {
                    font-family: sans-serif;
                    -webkit-text-size-adjust: 100%;
                    -ms-text-size-adjust: 100%;
                }
                body {
                    margin: 0;
                }')
            ->at($this->rootDir);
        $this->commandTester->execute(array(
                'from' => array(vfsStream::url('test/test.css')),
                '--preserveComments' => true,
            ), array());
        $this->assertEquals('/*! * Bootstrap v3.3.7 (http://getbootstrap.com) * Copyright 2011-2016 Twitter,Inc. * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE) */ /*! normalize.css v3.0.3 | MIT License | github.com/necolas/normalize.css */ html{font-family:sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}body{margin:0}'.PHP_EOL, $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function runCommandWithJsFile()
    {
        vfsStream::newFile('test.js')
            ->withContent('
                var indexOf = arr.indexOf;

                var class2type = {};
                
                var toString = class2type.toString;
                
                var hasOwn = class2type.hasOwnProperty;
                
                var support = {};
                ')
            ->at($this->rootDir);
        $this->commandTester->execute(array('from' => array(vfsStream::url('test/test.js'))), array());
        $this->assertEquals('var indexOf=arr.indexOf;var class2type={};var toString=class2type.toString;var hasOwn=class2type.hasOwnProperty;var support={}'.PHP_EOL, $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function runCommandWithTwoFiles()
    {
        vfsStream::newFile('test1.css')
            ->withContent('
                html {
                    font-family: sans-serif;
                    -webkit-text-size-adjust: 100%;
                    -ms-text-size-adjust: 100%;
                }
                ')
            ->at($this->rootDir);
        vfsStream::newFile('test2.css')
            ->withContent('
                body {
                    margin: 0;
                }')
            ->at($this->rootDir);
        $this->commandTester->execute(array('from' => array(
            vfsStream::url('test/test1.css'),
            vfsStream::url('test/test2.css'),
        )), array());
        $this->assertEquals('html{font-family:sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}body{margin:0}'.PHP_EOL, $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function runCommandWithOutputFile()
    {
        vfsStream::newFile('test.css')
            ->withContent('
                html {
                    font-family: sans-serif;
                    -webkit-text-size-adjust: 100%;
                    -ms-text-size-adjust: 100%;
                }
                ')
            ->at($this->rootDir);
        $this->commandTester->execute(
            array(
                'from' => array(vfsStream::url('test/test.css')),
                '--output' => vfsStream::url('test/output.css'),
            ),
            array());
        $this->assertStringEqualsFile(vfsStream::url('test/output.css'), 'html{font-family:sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}');
    }

    /**
     * @test
     */
    public function runCommandWithAppendToFile()
    {
        vfsStream::newFile('test.css')
            ->withContent('
                html {
                    font-family: sans-serif;
                    -webkit-text-size-adjust: 100%;
                    -ms-text-size-adjust: 100%;
                }
                ')
            ->at($this->rootDir);
        vfsStream::newFile('output.css')
            ->withContent('test text')
            ->at($this->rootDir);
        $this->commandTester->execute(
            array(
                'from' => array(vfsStream::url('test/test.css')),
                '--output' => vfsStream::url('test/output.css'),
                '--append' => true,
            ),
            array());
        $this->assertStringEqualsFile(vfsStream::url('test/output.css'), 'test texthtml{font-family:sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}');
    }

    /**
     * @test
     */
    public function runCommandWithFileTypeSpecification()
    {
        vfsStream::newFile('test.js')
            ->withContent('return {
                l: ((116 * y) - 16) / 100,  // [0,100]
                a: ((500 * (x - y)) + 128) / 255,   // [-128,127]
                b: ((200 * (y - z)) + 128) / 255    // [-128,127]
            };')
            ->at($this->rootDir);
        $this->commandTester->execute(
            array(
                'from' => array(vfsStream::url('test/test.js')),
                '--type' => 'css',
            ),
            array());
        $this->assertEquals('return{l:((116 * y) - 16) / 100,// [0,100] a:((500 * (x - y)) + 128) / 255,// [-128,127] b:((200 * (y - z)) + 128) / 255 // [-128,127]};'.PHP_EOL, $this->commandTester->getDisplay());
    }
}
