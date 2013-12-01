<?php
require_once __DIR__.'/../Minify.php';
require_once __DIR__.'/../CSS.php';
require_once __DIR__.'/../Exception.php';
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
    public function minify($input, $expected, $options)
    {
        $this->minifier->add($input);
        $result = $this->minifier->minify(false, $options);

        $this->assertEquals($result, $expected);
    }

    /**
     * Test conversion of relative paths, provided by dataProviderPaths
     *
     * @test
     * @dataProvider dataProviderPaths
     */
    public function convertRelativePath($source, $target, $expected, $options) {
        $this->minifier->add($source);
        $result = $this->minifier->minify($target, $options);

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

        /*
         * https://github.com/forkcms/forkcms/issues/387
         *
         * CSS backslash.
         * * Backslash escaped by backslash in CSS
         * * Double CSS backslashed escaped twice for in PHP string
         */
        $tests[] = array(
            '.iconic.map-pin:before { content: "\\\\"; }',
            '.iconic.map-pin:before{content:"\\\\"}',
            Minify\CSS::ALL
        );

        return $tests;
    }

    /**
     *
     */
    public function dataProviderPaths() {
        $tests = array();

        $source = __DIR__.'/sample/css/convert_relative_path/source';
        $target = __DIR__.'/sample/css/convert_relative_path/target';

        $tests[] = array(
            $source . '/external.css',
            $target . '/external.css',
            file_get_contents($source . '/external.css'),
            0
        );

        return $tests;
    }
}
