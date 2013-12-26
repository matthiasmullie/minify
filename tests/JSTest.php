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
    public function minify($input, $expected)
    {
        $this->minifier->add($input);
        $result = $this->minifier->minify(false);

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
        );

        // Regex delimiters need to be treated as strings
        // Two forward slashes could look like a comment
        $tests[] = array(
            '/abc\/def\//.test("abc")',
            '/abc\/def\//.test("abc")',
        );

        $tests[] = array(
            '/* This is a JS comment */',
            '',
        );

        // https://github.com/matthiasmullie/minify/issues/10
        $tests[] = array(
            '// first mutation patch
// second mutation patch
// third mutation patch
// fourth mutation patch',
            '',
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
        );

        // operators
        $tests[] = array(
            'a = 1 + 2',
            'a=1+2',
        );

        $tests[] = array(
            'alert ( "this is a test" );',
            'alert("this is a test")',
        );

        // Strip newlines & replace line-endings-as-terminator with ;
        $tests[] = array(
            'alert ( "this is a test" )
alert ( "this is another test" )',
            'alert("this is a test");alert("this is another test")',
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
            'function one(){console.log("one")};function two(){console.log("two")}',
        );

        // Make sure no ; is added in places it shouldn't
        $tests[] = array(
            'if(true){}else{}',
            'if(true){}else{}',
        );
        $tests[] = array(
            'do{i++}while(i<1)',
            'do{i++}while(i<1)',
        );

        $tests[] = array(
            'alert("this is a test");',
            'alert("this is a test")',
        );

        $tests[] = array(
            'function(){console.log("this is a test");}',
            'function(){console.log("this is a test")}',
        );

        $tests[] = array(
            'object
                .property',
            'object.property',
        );

        $tests[] = array(
            'a = b + c
             d = e + f',
            'a=b+c;d=e+f',
        );

        $tests[] = array(
            '
				// check if it isn\'t a text-element
				if(currentElement.attr(\'type\') != \'text\')
				{
					// remove the current one
					currentElement.remove();
				}

				// already a text element
				else newElement = currentElement;
',
            'if(currentElement.attr(\'type\')!=\'text\'){currentElement.remove()}else newElement=currentElement',
        );

        return $tests;
    }
}
