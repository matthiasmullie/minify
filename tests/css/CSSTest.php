<?php

use MatthiasMullie\Minify;

/**
 * CSS minifier test case.
 */
class CSSTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Minify\CSS
     */
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
     * Test CSS minifier rules, provided by dataProvider.
     *
     * @test
     * @dataProvider dataProvider
     */
    public function minify($input, $expected)
    {
        $this->minifier->add($input);
        $result = $this->minifier->minify();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test conversion of relative paths, provided by dataProviderPaths.
     *
     * @test
     * @dataProvider dataProviderPaths
     */
    public function convertRelativePath($source, $target, $expected, $options)
    {
        $this->minifier->add($source);
        $result = $this->minifier->minify($target, $options);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test minifier import configuration methods.
     *
     * @test
     */
    public function setConfig() {
        $this->minifier->setMaxImportSize(10);
        $this->minifier->setImportExtensions(array('gif' => 'data:image/gif'));

        $object = new ReflectionObject($this->minifier);

        $property = $object->getProperty('maxImportSize');
        $property->setAccessible(true);
        $this->assertEquals($property->getValue($this->minifier), 10);

        $property = $object->getProperty('importExtensions');
        $property->setAccessible(true);
        $this->assertEquals($property->getValue($this->minifier), array('gif' => 'data:image/gif'));

    }

    /**
     * @return array [input, expected result]
     */
    public function dataProvider()
    {
        $tests = array();

        // try importing, with both @import syntax types & media queries
        $tests[] = array(
            __DIR__ . '/sample/combine_imports/index.css',
            'body{color:red}',
        );
        $tests[] = array(
            __DIR__ . '/sample/combine_imports/index2.css',
            'body{color:red}',
        );
        $tests[] = array(
            __DIR__ . '/sample/combine_imports/index3.css',
            'body{color:red}body{color:red}',
        );
        $tests[] = array(
            __DIR__ . '/sample/combine_imports/index4.css',
            '@media only screen{body{color:red}}@media only screen{body{color:red}}',
        );

        // shorthand hex color codes
        $tests[] = array(
            'color:#FF00FF;',
            'color:#F0F;',
        );

        // import files
        $tests[] = array(
            __DIR__ . '/sample/import_files/index.css',
            'body{background:url(data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/sample/import_files/file.png')) . ')}',
        );

        // strip comments
        $tests[] = array(
            '/* This is a CSS comment */',
            '',
        );

        // strip whitespace
        $tests[] = array(
            'body { color: red; }',
            'body{color:red}',
        );

        // whitespace inside strings shouldn't be replaced
        $tests[] = array(
            'content:"preserve   whitespace',
            'content:"preserve   whitespace',
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

        return $tests;
    }

    /**
     * @return array [input, expected result]
     */
    public function dataProviderPaths()
    {
        $tests = array();

        $source = __DIR__ . '/sample/convert_relative_path/source';
        $target = __DIR__ . '/sample/convert_relative_path/target';

        // external link
        $tests[] = array(
            $source . '/external.css',
            $target . '/external.css',
            file_get_contents($source . '/external.css'),
            0
        );

        // absolute path
        $tests[] = array(
            $source . '/absolute.css',
            $target . '/absolute.css',
            file_get_contents($source . '/absolute.css'),
            0
        );

        // relative paths
        $tests[] = array(
            $source . '/relative.css',
            $target . '/relative.css',
            file_get_contents($source . '/relative.css'),
            0
        );
        $tests[] = array(
            $source . '/relative.css',
            $target . '/target/relative.css',
            '@import url(../../target/image.jpg);',
            0
        );

        return $tests;
    }
}
