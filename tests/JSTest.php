<?php
require_once __DIR__.'/../Minify.php';
require_once __DIR__.'/../JS.php';
require_once __DIR__.'/../Exception.php';
require_once 'PHPUnit/Framework/TestCase.php';

use MatthiasMullie\Minify;

/**
 * JS minifier test case.
 */
class JSTest extends PHPUnit_Framework_TestCase
{
    private $minifier;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->minifier = new Minify\JS();
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
     * Test JS minifier rules, provided by dataProvider
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
     * Test cases [input, expected result, options]
     *
     * @return array
     */
    public function dataProvider()
    {
        $tests = array();

        $tests[] = array(
            '/* This is a JS comment */',
            '',
            Minify\JS::STRIP_COMMENTS
        );

        $tests[] = array(
            'alert ( "this is a test" );',
            'alert("this is a test");',
            Minify\JS::STRIP_WHITESPACE
        );

        // https://github.com/matthiasmullie/minify/issues/10
        $tests[] = array(
            '// first mutation patch
// second mutation patch
// third mutation patch
// fourth mutation patch',
            '',
            Minify\JS::STRIP_COMMENTS
        );

        // https://github.com/matthiasmullie/minify/issues/10
        $tests[] = array(
            '/////////////////////////
// first mutation patch
// second mutation patch
// third mutation patch
// fourth mutation patch
/////////////////////////',
            '',
            Minify\JS::STRIP_COMMENTS
        );

        return $tests;
    }
}
