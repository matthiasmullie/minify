<?php
require_once __DIR__.'/../minify.php';
require_once __DIR__.'/../css.php';
require_once __DIR__.'/../exception.php';
require_once 'PHPUnit/Framework/TestCase.php';

use MatthiasMullie\Minify;

/**
 * CSS minifier test case.
 */
class CSSTest extends PHPUnit_Framework_TestCase
{
    private $minifier;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->minifier = new Minify\CSS();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->minifier = null;
        parent::tearDown();
    }

    /**
     * Test CSS minifier rules, provided by dataProvider
     *
     * @test
     * @dataProvider dataProvider
     */
    public function failure($input, $expected, $options)
    {
        $this->minifier->add($input);
        $result = $this->minifier->minify(false, $options);

        $this->assertEquals($result, $expected);
    }

    /**
     * Test cases [input, expected result, options]
     *
     * @return array
     */
    public function dataProvider()
    {
        $tests = array();

        $tests[] = array(
            __DIR__.'/sample/css/combine_imports/index.css',
            file_get_contents(__DIR__.'/sample/css/combine_imports/import.css')."\n",
            Minify\CSS::COMBINE_IMPORTS
        );

        $tests[] = array(
            'color: #FF00FF;',
            'color: #F0F;',
            Minify\CSS::SHORTEN_HEX
        );

        $tests[] = array(
            __DIR__.'/sample/css/import_files/index.css',
            'background: url(data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/sample/css/import_files/file.png')).');'."\n",
            Minify\CSS::IMPORT_FILES
        );

        $tests[] = array(
            '/* This is a CSS comment */',
            '',
            Minify\CSS::STRIP_COMMENTS
        );

        $tests[] = array(
            'body { color: red; }',
            'body{color:red}',
            Minify\CSS::STRIP_WHITESPACE
        );

        return $tests;
    }
}
