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

        // Escaped quotes should not terminate string
        $tests[] = array(
            'alert("Escaped quote which is same as string quotes: \"; should not match")',
            'alert("Escaped quote which is same as string quotes: \"; should not match")',
            Minify\JS::ALL
        );

        $tests[] = array(
            '/* This is a JS comment */',
            '',
            Minify\JS::STRIP_COMMENTS
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

        // operators
        $tests[] = array(
            'a = 1 + 2',
            'a=1+2',
            Minify\JS::STRIP_WHITESPACE
        );

        $tests[] = array(
            'alert ( "this is a test" );',
            'alert("this is a test");',
            Minify\JS::STRIP_WHITESPACE
        );

        // Strip newlines & replace line-endings-as-terminator with ;
        $tests[] = array(
            'alert ( "this is a test" )
alert ( "this is another test" )',
            'alert("this is a test");alert("this is another test")',
            Minify\JS::STRIP_WHITESPACE
        );

        // Strip newlines & replace line-endings-as-terminator with ;
        // Note that the ; inserted after the first function block is not
        // strictly needed - see comment in JS.php
        $tests[] = array(
            '    function one()
    {
        console . log( "one" );
    }
    function two()
    {
        console . log( "two" );
    }',
            'function one(){console.log("one");};function two(){console.log("two");}',
            Minify\JS::STRIP_WHITESPACE
        );

        $tests[] = array(
            'alert("this is a test");',
            'alert("this is a test")',
            Minify\JS::STRIP_SEMICOLONS
        );

        $tests[] = array(
            'function(){console.log("this is a test");}',
            'function(){console.log("this is a test")}',
            Minify\JS::STRIP_SEMICOLONS
        );

        return $tests;
    }
}
