<?php

use MatthiasMullie\Minify;

/**
 * JS minifier test case.
 */
class JSTest extends PHPUnit_Framework_TestCase
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
    public function minify($input, $expected)
    {
        $input = (array) $input;
        foreach ($input as $js) {
            $this->minifier->add($js);
        }
        $result = $this->minifier->minify();

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

        // backtick string (allow string interpolation)
        $tests[] = array(
            'var str=`Hi, ${name}`',
            'var str=`Hi, ${name}`',
        );

        // regex delimiters need to be treated as strings
        // (two forward slashes could look like a comment)
        $tests[] = array(
            '/abc\/def\//.test("abc")',
            '/abc\/def\//.test("abc")',
        );
        $tests[] = array(
            'var a = /abc\/def\//.test("abc")',
            'var a=/abc\/def\//.test("abc")',
        );

        // don't confuse multiple slashes for regexes
        $tests[] = array(
            'a = b / c; d = e / f',
            'a=b/c;d=e/f',
        );

        // mixture of quotes starting in comment/regex, to make sure strings are
        // matched correctly, not inside comment/regex.
        $tests[] = array(
            '/abc"def/.test("abc")',
            '/abc"def/.test("abc")',
        );
        $tests[] = array(
            '/* Bogus " */var test="test";',
            'var test="test"',
        );

        // replace comments
        $tests[] = array(
            '/* This is a JS comment */',
            '',
        );

        // make sure no ; is added in places it shouldn't
        $tests[] = array(
            'if(true){}else{}',
            'if(!0){}else{}',
        );
        $tests[] = array(
            'do{i++}while(i<1)',
            'do{i++}while(i<1)',
        );

        $tests[] = array(
            'if(true)statement;else statement',
            'if(!0)statement;else statement',
        );

        $tests[] = array(
            'for (i = 0; (i < 10); i++) statement',
            'for(i=0;(i<10);i++)statement',
        );
        $tests[] = array(
            '-1
             +2',
            '-1+2',
        );
        $tests[] = array(
            '-1+
             2',
            '-1+2',
        );
        $tests[] = array(
            'alert("this is a test");',
            'alert("this is a test")',
        );

        // test where newline should be preserved (for ASI) or semicolon added
        $tests[] = array(
            'function(){console.log("this is a test");}',
            'function(){console.log("this is a test")}',
        );
        $tests[] = array(
            'alert("this is a test")
alert("this is another test")',
            'alert("this is a test")
alert("this is another test")',
        );
        $tests[] = array(
            'a=b+c
             d=e+f',
            'a=b+c
d=e+f',
        );
        $tests[] = array(
            'a++

             ++b',
            'a++
++b',
        );
        $tests[] = array(
            '!a
             !b',
            '!a
!b',
        );
        $tests[] = array(
            // don't confuse with 'if'
            'digestif
            (true)
            statement',
            'digestif(!0)
statement',
        );
        $tests[] = array(
            'if
             (
                 (
                     true
                 )
                 &&
                 (
                     true
                 )
            )
            statement',
            'if((!0)&&(!0))
statement',
        );
        $tests[] = array(
            'if
             (
                 true
             )
             {
             }
             else
             {
             }',
            'if(!0){}
else{}',
        );
        $tests[] = array(
            'do
             {
                 i++
             }
             while
             (
                 i<1
             )',
            'do{i++}
while(i<1)',
        );
        $tests[] = array(
            'if ( true )
                 statement
             else
                 statement',
            'if(!0)
statement
else statement',
        );

        // test if whitespace around keywords is properly collapsed
        $tests[] = array(
            'var
             variable
             =
             "value";',
            'var variable="value"',
        );
        $tests[] = array(
            'var variable = {
                 test:
                 {
                 }
             }',
            'var variable={test:{}}',
        );
        $tests[] = array(
            'if ( true ) {
             } else {
             }',
            'if(!0){}else{}',
        );
        $tests[] = array(
            '53  instanceof  String',
            '53 instanceof String',
        );

        // remove whitespace around operators
        $tests[] = array(
            'a = 1 + 2',
            'a=1+2',
        );
        $tests[] = array(
            'object  .  property',
            'object.property',
        );
        $tests[] = array(
            'object
                .property',
            'object.property',
        );
        $tests[] = array(
            'alert ( "this is a test" );',
            'alert("this is a test")',
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
            'object.property',
        );

        // add comment in between whitespace that needs to be stripped
        $tests[] = array(
            'var test=true,test2=false',
            'var test=!0,test2=!1',
        );
        $tests[] = array(
            'var testtrue="testing if true as part of varname is ignored as it should"',
            'var testtrue="testing if true as part of varname is ignored as it should"',
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
            'if(currentElement.attr(\'type\')!=\'text\'){currentElement.remove()}
else newElement=currentElement',
        );
        $tests[] = array(
            'var jsBackend =
             {
                 debug: false,
                 current: {}
             }',
            'var jsBackend={debug:!1,current:{}}',
        );
        $tests[] = array(
            'var utils =
             {
                 debug: false
             }
             utils.array =
             {
             }',
            'var utils={debug:!1}
utils.array={}',
        );
        $tests[] = array(
            'rescape = /\'|\\\\/g,

            // blablabla here was some more code but the point was that somewhere
            // down below, there would be a closing quote which would cause the
            // regex (confused for escaped closing tag) not to be recognized,
            // taking the opening single quote & looking for a string.
            // So here\'s <-- the closing quote
            runescape = \'blabla\'',
            'rescape=/\'|\\\\/g,runescape=\'blabla\'',
        );
        $tests[] = array(
            'var rsingleTag = (/^<(\w+)\s*\/?>(?:<\/\1>|)$/)',
            'var rsingleTag=(/^<(\w+)\s*\/?>(?:<\/\1>|)$/)',
        );
        $tests[] = array(
            'if (this.sliding)       return this.$element.one(\'slid.bs.carousel\', function () { that.to(pos) }) // yes, "slid"
if (activeIndex == pos) return this.pause().cycle()',
            'if(this.sliding)return this.$element.one(\'slid.bs.carousel\',function(){that.to(pos)})
if(activeIndex==pos)return this.pause().cycle()',
        );
        $tests[] = array(
            'if (e.which == 38 && index > 0)                 index--                        // up
if (e.which == 40 && index < $items.length - 1) index++                        // down',
            'if(e.which==38&&index>0)index--
if(e.which==40&&index<$items.length-1)index++',
        );

        // replace associative array key references by property notation
        $tests[] = array(
            'array["key"][\'key2\']',
            'array.key.key2',
        );
        $tests[] = array(
            'array[ "key" ][ \'key2\' ]',
            'array.key.key2',
        );
        $tests[] = array(
            'array["a","b","c"]',
            'array["a","b","c"]',
        );
        //
        $tests[] = array(
            "['loader']",
            "['loader']",
        );
        $tests[] = array(
            'array["dont-replace"][\'key2\']',
            'array["dont-replace"].key2',
        );

        // shorten bools
        $tests[] = array(
            'while(true){break}',
            'for(;;){break}',
        );
        // make sure we don't get "missing while after do-loop body"
        $tests[] = array(
            'do{break}while(true)',
            'do{break}while(!0)',
        );
        $tests[] = array(
            "do break\nwhile(true)",
            "do break\nwhile(!0)",
        );
        $tests[] = array(
            "do{break}while(true){alert('test')}",
            "do{break}while(!0){alert('test')}",
        );
        $tests[] = array(
            "do break\nwhile(true){alert('test')}",
            "do break\nwhile(!0){alert('test')}",
        );
        // nested do-while & while
        $tests[] = array(
            "do{while(true){break}break}while(true){alert('test')}",
            "do{for(;;){break}break}while(!0){alert('test')}",
        );
        $tests[] = array(
            "do{while(true){break}break}while(true){alert('test')}while(true){break}",
            "do{for(;;){break}break}while(!0){alert('test')}for(;;){break}",
        );
        $tests[] = array(
            "do{while(true){break}break}while(true){alert('test')}while(true){break}do{while(true){break}break}while(true){alert('test')}while(true){break}",
            "do{for(;;){break}break}while(!0){alert('test')}for(;;){break}do{for(;;){break}break}while(!0){alert('test')}for(;;){break}",
        );

        // https://github.com/matthiasmullie/minify/issues/10
        $tests[] = array(
            '// first mutation patch
// second mutation patch
// third mutation patch
// fourth mutation patch',
            '',
        );
        $tests[] = array(
            '/////////////////////////
// first mutation patch
// second mutation patch
// third mutation patch
// fourth mutation patch
/////////////////////////',
            '',
        );

        // https://github.com/matthiasmullie/minify/issues/14
        $tests[] = array(
            'function foo (a, b)
{
    return a / b;
}
function foo (a, b)
{
    return a / b;
}',
            'function foo(a,b){return a/b}
function foo(a,b){return a/b}',
        );

        // https://github.com/matthiasmullie/minify/issues/15
        $tests[] = array(
            'if ( !data.success )
    deferred.reject(); else
    deferred.resolve(data);',
            'if(!data.success)
deferred.reject();else deferred.resolve(data)',
        );
        $tests[] = array(
            "if ( typeof jQuery === 'undefined' )
    throw new Error('.editManager.js: jQuery is required and must be loaded first');",
            "if(typeof jQuery==='undefined')
throw new Error('.editManager.js: jQuery is required and must be loaded first')",
        );

        // https://github.com/matthiasmullie/minify/issues/27
        $tests[] = array(
            '$.expr[":"]',
            '$.expr[":"]',
        );

        // https://github.com/matthiasmullie/minify/issues/31
        $tests[] = array(
            "$(_this).attr('src',this.src).trigger('adapt',['loader'])",
            "$(_this).attr('src',this.src).trigger('adapt',['loader'])",
        );

        // https://github.com/matthiasmullie/minify/issues/33
        $tests[] = array(
            '$.fn.alert             = Plugin
$.fn.alert.Constructor = Alert',
            '$.fn.alert=Plugin
$.fn.alert.Constructor=Alert',
        );

        // https://github.com/matthiasmullie/minify/issues/34
        $tests[] = array(
            'a.replace("\\\\","");hi="This   is   a   string"',
            'a.replace("\\\\","");hi="This   is   a   string"',
        );

        // https://github.com/matthiasmullie/minify/issues/35
        $tests[] = array(
            array(
                '// script that ends with comment',
                'var test=1',
            ),
            'var test=1',
        );

        // https://github.com/matthiasmullie/minify/issues/37
        $tests[] = array(
            'function () { ;;;;;;;; }',
            'function(){}',
        );

        // https://github.com/matthiasmullie/minify/issues/40
        $tests[] = array(
            "for(v=1,_=b;;){}",
            "for(v=1,_=b;;){}",
        );

        // https://github.com/matthiasmullie/minify/issues/41
        $tests[] = array(
            "conf.zoomHoverIcons['default']",
            "conf.zoomHoverIcons['default']",
        );

        // https://github.com/matthiasmullie/minify/issues/42
        $tests[] = array(
            'for(i=1;i<2;i++);',
            'for(i=1;i<2;i++);',
        );
        $tests[] = array(
            'if(1){for(i=1;i<2;i++);}',
            'if(1){for(i=1;i<2;i++);}',
        );

        // https://github.com/matthiasmullie/minify/issues/43
        $tests[] = array(
            '{"key":"3","key2":"value","key3":"3"}',
            '{"key":"3","key2":"value","key3":"3"}',
        );

        // https://github.com/matthiasmullie/minify/issues/44
        $tests[] = array(
            'return ["x"]',
            'return["x"]',
        );

        // https://github.com/matthiasmullie/minify/issues/50
        $tests[] = array(
            'do{var dim=this._getDaysInMonth(year,month-1);if(day<=dim){break}month++;day-=dim}while(true)}',
            'do{var dim=this._getDaysInMonth(year,month-1);if(day<=dim){break}month++;day-=dim}while(!0)}',
        );

        // https://github.com/matthiasmullie/minify/issues/53
        $tests[] = array(
            'a.validator.addMethod("accept", function (b, c, d) {
    var e, f, g = "string" == typeof d ?
        d.replace(/\s/g, "").replace(/,/g, "|") :
        "image/*", h = this.optional(c);
    if (h)return h;
    if ("file" === a(c).attr("type") && (g = g.replace(/\*/g, ".*"), c.files && c.files.length))
        for (e = 0; e < c.files.length; e++)
            if (f = c.files[e], !f.type.match(new RegExp(".?(" + g + ")$", "i")))
                return !1;
    return !0
}',
            'a.validator.addMethod("accept",function(b,c,d){var e,f,g="string"==typeof d?d.replace(/\s/g,"").replace(/,/g,"|"):"image/*",h=this.optional(c);if(h)return h;if("file"===a(c).attr("type")&&(g=g.replace(/\*/g,".*"),c.files&&c.files.length))
for(e=0;e<c.files.length;e++)
if(f=c.files[e],!f.type.match(new RegExp(".?("+g+")$","i")))
return !1;return !0}',
        );

        // https://github.com/matthiasmullie/minify/issues/54
        $tests[] = array(
            'function a() {
  if (true)
    return
  if (false)
    return
}',
            'function a(){if(!0)
return
if(!1)
return}',
        );

        // https://github.com/matthiasmullie/minify/issues/56
        $tests[] = array(
            'var timeRegex = /^([2][0-3]|[01]?[0-9])(:[0-5][0-9])?$/
if (start_time.match(timeRegex) == null) {}',
            'var timeRegex=/^([2][0-3]|[01]?[0-9])(:[0-5][0-9])?$/
if(start_time.match(timeRegex)==null){}',
        );

        // https://github.com/matthiasmullie/minify/issues/58
        // stripped of redundant code to expose problem case
        $tests[] = array(
            <<<'BUG'
function inspect() {
    escapedString.replace(/abc/g, '\\\'');
}
function isJSON() {
    str.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
}
BUG
,
            <<<'BUG'
function inspect(){escapedString.replace(/abc/g,'\\\'')}
function isJSON(){str.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,']')}
BUG
        );

        // https://github.com/matthiasmullie/minify/issues/59
        $tests[] = array(
            'isPath:function(e) {
    return /\//.test(e);
}',
            'isPath:function(e){return/\//.test(e)}',
        );

        // https://github.com/matthiasmullie/minify/issues/64
        $tests[] = array(
            '    var d3_nsPrefix = {
        svg: "http://www.w3.org/2000/svg",
        xhtml: "http://www.w3.org/1999/xhtml",
        xlink: "http://www.w3.org/1999/xlink",
        xml: "http://www.w3.org/XML/1998/namespace",
        xmlns: "http://www.w3.org/2000/xmlns/"
    };',
            'var d3_nsPrefix={svg:"http://www.w3.org/2000/svg",xhtml:"http://www.w3.org/1999/xhtml",xlink:"http://www.w3.org/1999/xlink",xml:"http://www.w3.org/XML/1998/namespace",xmlns:"http://www.w3.org/2000/xmlns/"}',
        );

        return $tests;
    }
}
