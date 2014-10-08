<?php
require_once __DIR__ . '/../../Minify.php';
require_once __DIR__ . '/../../CSS.php';
require_once __DIR__ . '/../../Exception.php';

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
    public function minify($input, $expected, $debug = false)
    {
        $this->minifier->debug((bool) $debug);

        $this->minifier->add($input);
        $result = $this->minifier->minify();

        $this->assertEquals($expected, $result);
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

        $this->assertEquals($expected, $result);
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
            __DIR__ . '/sample/combine_imports/index.css',
            'body{color:red}',
        );

        $tests[] = array(
            'color:#FF00FF;',
            'color:#F0F;',
        );

        $tests[] = array(
            __DIR__ . '/sample/import_files/index.css',
            'background:url(data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/sample/import_files/file.png')) . ');',
        );

        $tests[] = array(
            '/* This is a CSS comment */',
            '',
        );

        $tests[] = array(
            'body { color: red; }',
            'body{color:red}',
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
        );

        // whitespace inside strings shouldn't be replaced
        $tests[] = array(
            'content:"preserve   whitespace',
            'content:"preserve   whitespace',
        );

        return $tests;
    }

    public function dataProviderPaths() {
        $tests = array();

        $source = __DIR__ . '/sample/convert_relative_path/source';
        $target = __DIR__ . '/sample/convert_relative_path/target';

        $tests[] = array(
            $source . '/external.css',
            $target . '/external.css',
            file_get_contents($source . '/external.css'),
            0
        );

        return $tests;
    }
}
