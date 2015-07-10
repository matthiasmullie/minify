<?php

use MatthiasMullie\Minify;

/**
 * JS minifier compile test case.
 */
class JSCompileTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Minify\JS
     */
    private $minifier;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        // override save method, there's no point in writing the result out here
        $this->minifier = $this->getMockBuilder('\MatthiasMullie\Minify\JS')
            ->setMethods(array('save'))
            ->getMock();
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
    public function compile($input, $expected)
    {        	
        $input = (array) $input;
        foreach ($input as $js) {
            $this->minifier->add($js);
        }
        $result = $this->minifier->compile();
		
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array [input, expected result]
     */
    public function dataProvider()
    {
        $tests = array();

        // escaped quotes should not terminate string
        $tests[] = array(
            'alert("Escaped quote which is same as string quotes: \"; should not match")',
            'alert("Escaped quote which is same as string quotes: \"; should not match")',
        );
		
        $tests[] = array(
            '53  instanceof  String',
            '53  instanceof  String',
        );

        // remove whitespace around operators
        $tests[] = array(
            'a = 1 + 2',
            'a = 1 + 2',
        );
        $tests[] = array(
            'object
                .property',
            'object
                .property',
        );
        $tests[] = array(
            'alert ( "this is a test" );',
            'alert ( "this is a test" );',
        );

        // mix of ++ and +: three consecutive +es will be interpreted as ++ +
        $tests[] = array(
            'a++ +b',
            'a++ +b',
        );
        $tests[] = array(
            'a+ ++b',
            'a+ ++b', // +++ would actually be allowed as well
        );

        // SyntaxError: identifier starts immediately after numeric literal
        $tests[] = array(
            '42 .toString()',
            '42 .toString()',
        );

        // add comment in between whitespace that needs to be stripped
        $tests[] = array(
            'object
                // haha, some comment, just to make things harder!
                .property',
            'object
                // haha, some comment, just to make things harder!
                .property',
        );
		
        // random bits of code that tripped errors during development
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
        );

        return $tests;
    }
}
