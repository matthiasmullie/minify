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
     * Test JS minifier rules, provided by dataProvider.
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
     * @return array [input, expected result]
     */
    public function dataProvider()
    {
        $tests = array();

        // adding multiple files
        $tests[] = array(
            [
                __DIR__.'/sample/source/script1.js',
                __DIR__.'/sample/source/script2.js',
            ],
            'var test=1;var test=2',
        );

        // adding multiple files and string
        $tests[] = array(
            [
                __DIR__.'/sample/source/script1.js',
                'console.log(test)',
                __DIR__.'/sample/source/script2.js',
            ],
            'var test=1;console.log(test);var test=2',
        );

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
            '/abc\/def\//.test("abc\/def\/")',
            '/abc\/def\//.test("abc\/def\/")',
        );
        $tests[] = array(
            // there's an escape mess here; below regex represent this JS line:
            // /abc\/def\\\//.test("abc/def\\/")
            '/abc\/def\\\\\//.test("abc/def\\\/")',
            '/abc\/def\\\\\//.test("abc/def\\\/")',
        );
        $tests[] = array(
            // escape mess, this represents:
            // /abc\/def\\\\\//.test("abc/def\\\\/")
            '/abc\/def\\\\\\\\\//.test("abc/def\\\\\\\\/")',
            '/abc\/def\\\\\\\\\//.test("abc/def\\\\\\\\/")',
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
        $tests[] = array(
            '(2 + 4) / 3 + 5 / 1',
            '(2+4)/3+5/1',
        );

        $tests[] = array(
            'a=4/
            2',
            'a=4/2',
        );

        // mixture of quotes starting in comment/regex, to make sure strings are
        // matched correctly, not inside comment/regex
        // additionally test catching of empty strings as well
        $tests[] = array(
            '/abc"def/.test("abc")',
            '/abc"def/.test("abc")',
        );
        $tests[] = array(
            '/abc"def/.test(\'\')',
            '/abc"def/.test(\'\')',
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
          'for ( i = 0; ; i++ ) statement',
          'for(i=0;;i++)statement',
        );
        $tests[] = array(
            'for (i = 0; (i < 10); i++) statement',
            'for(i=0;(i<10);i++)statement',
        );
        $tests[] = array(
          'alert("test");;alert("test2")',
          'alert("test");alert("test2")',
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
            'if(!0)
{}
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
            'if(currentElement.attr(\'type\')!=\'text\')
{currentElement.remove()}
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
            'function foo(a,b)
{return a/b}
function foo(a,b)
{return a/b}',
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
            'for(v=1,_=b;;){}',
            'for(v=1,_=b;;){}',
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
        $tests[] = array(
            'for(i in list);',
            'for(i in list);',
        );
        $tests[] = array(
            'if(1){for(i in list);}',
            'if(1){for(i in list);}',
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
return!1;return!0}',
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

        // https://github.com/matthiasmullie/minify/issues/66
        $tests[] = array(
            "$(coming.wrap).bind('onReset', function () {
    try {
        $(this).find('iframe').hide().attr('src', '//about:blank').end().empty();
    } catch (e) {}
});",
            "$(coming.wrap).bind('onReset',function(){try{\$(this).find('iframe').hide().attr('src','//about:blank').end().empty()}catch(e){}})",
        );

        // https://github.com/matthiasmullie/minify/issues/89
        $tests[] = array(
            'for(;;ja||(ja=true)){}',
            'for(;;ja||(ja=!0)){}',
        );

        // https://github.com/matthiasmullie/minify/issues/91
        $tests[] = array(
            'if(true){if(true)console.log("test")else;}',
            'if(!0){if(!0)console.log("test")}',
        );

        // https://github.com/matthiasmullie/minify/issues/99
        $tests[] = array(
            '"object";"object2";"0";"1"',
            '"object";"object2";"0";"1"',
        );

        // https://github.com/matthiasmullie/minify/issues/102
        $tests[] = array(
            'var pb = {};',
            'var pb={}',
        );
        $tests[] = array(
            'pb.Initialize = function(settings) {};',
            'pb.Initialize=function(settings){}',
        );

        // https://github.com/matthiasmullie/minify/issues/108
        $tests[] = array(
            'function isHtmlNamespace(node) {
            var ns;
            return typeof node.namespaceURI == UNDEF || ((ns = node.namespaceURI) === null || ns == "http://www.w3.org/1999/xhtml");
        }',
            'function isHtmlNamespace(node){var ns;return typeof node.namespaceURI==UNDEF||((ns=node.namespaceURI)===null||ns=="http://www.w3.org/1999/xhtml")}',
        );

        // https://github.com/matthiasmullie/minify/issues/115
        $tests[] = array(
            'if(typeof i[s].token=="string")/keyword|support|storage/.test(i[s].token)&&n.push(i[s].regex);else if(typeof i[s].token=="object")for(var u=0,a=i[s].token.length;u<a;u++)if(/keyword|support|storage/.test(i[s].token[u])){}',
            'if(typeof i[s].token=="string")/keyword|support|storage/.test(i[s].token)&&n.push(i[s].regex);else if(typeof i[s].token=="object")for(var u=0,a=i[s].token.length;u<a;u++)if(/keyword|support|storage/.test(i[s].token[u])){}',
        );

        // https://github.com/matthiasmullie/minify/issues/120
        $tests[] = array(
            'function myFuncName() {
    function otherFuncName() {
        if (condition) {
            a = b / 1; // comment 1
        } else if (condition) {
            a = c / 2; // comment 2
        } else if (condition) {
            a = d / 3; // comment 3
        } else {
            a = 0;
        }
    }
};',
            'function myFuncName(){function otherFuncName(){if(condition){a=b/1}else if(condition){a=c/2}else if(condition){a=d/3}else{a=0}}}',
        );

        // https://github.com/matthiasmullie/minify/issues/128
        $tests[] = array(
            'angle = (i - 3) * (Math.PI * 2) / 12; // THE ANGLE TO MARK.',
            'angle=(i-3)*(Math.PI*2)/12',
        );

        // https://github.com/matthiasmullie/minify/issues/124
        $tests[] = array(
            'return cond ? document._getElementsByXPath(\'.//*\' + cond, element) : [];',
            'return cond?document._getElementsByXPath(\'.//*\'+cond,element):[]',
        );
        $tests[] = array(
            'Sizzle.selectors = {
    match: {
        PSEUDO: /:((?:[\w\u00c0-\uFFFF-]|\\.)+)(?:\(([\'"]*)((?:\([^\)]+\)|[^\2\(\)]*)+)\2\))?/
    },
    attrMap: {
        "class": "className"
    }
}',
            'Sizzle.selectors={match:{PSEUDO:/:((?:[\w\u00c0-\uFFFF-]|\\.)+)(?:\(([\'"]*)((?:\([^\)]+\)|[^\2\(\)]*)+)\2\))?/},attrMap:{"class":"className"}}',
        );

        // https://github.com/matthiasmullie/minify/issues/130
        $tests[] = array(
            'function func(){}
func()
{ alert(\'hey\'); }',
            'function func(){}
func()
{alert(\'hey\')}',
        );

        // https://github.com/matthiasmullie/minify/issues/133
        $tests[] = array(
            'if ( args[\'message\'] instanceof Array ) { args[\'message\'] = args[\'message\'].join( \' \' );}',
            'if(args.message instanceof Array){args.message=args.message.join(\' \')}',
        );

        // https://github.com/matthiasmullie/minify/issues/134
        $tests[] = array(
            'e={true:!0,false:!1}',
            'e={true:!0,false:!1}',
        );

        // https://github.com/matthiasmullie/minify/issues/134
        $tests[] = array(
            'if (\'x\'+a in foo && \'y\'+b[a].z in bar)',
            'if(\'x\'+a in foo&&\'y\'+b[a].z in bar)',
        );

        // https://github.com/matthiasmullie/minify/issues/136
        $tests[] = array(
            'XPRSHelper.isManagable = function(presetId){ if (presetId in XPRSHelper.presetTypes){ return (XPRSHelper.presetTypes[presetId]["GROUP"] in {"FEATURES":true,"SLIDESHOWS":true,"GALLERIES":true}); } return false; };',
            'XPRSHelper.isManagable=function(presetId){if(presetId in XPRSHelper.presetTypes){return(XPRSHelper.presetTypes[presetId].GROUP in{"FEATURES":!0,"SLIDESHOWS":!0,"GALLERIES":!0})}return!1}',
        );

        // https://github.com/matthiasmullie/minify/issues/138
        $tests[] = array(
            'matchers.push(/^[0-9]*$/.source);',
            'matchers.push(/^[0-9]*$/.source)',
        );
        $tests[] = array(
            'matchers.push(/^[0-9]*$/.source);
String(dateString).match(/^[0-9]*$/);',
            'matchers.push(/^[0-9]*$/.source);String(dateString).match(/^[0-9]*$/)',
        );

        // https://github.com/matthiasmullie/minify/issues/139
        $tests[] = array(
            __DIR__.'/sample/line_endings/lf/script.js',
            'var a=1',
        );
        $tests[] = array(
            __DIR__.'/sample/line_endings/cr/script.js',
            'var a=1',
        );
        $tests[] = array(
            __DIR__.'/sample/line_endings/crlf/script.js',
            'var a=1',
        );

        // https://github.com/matthiasmullie/minify/issues/142
        $tests[] = array(
            'return {
    l: ((116 * y) - 16) / 100,  // [0,100]
    a: ((500 * (x - y)) + 128) / 255,   // [-128,127]
    b: ((200 * (y - z)) + 128) / 255    // [-128,127]
};',
            'return{l:((116*y)-16)/100,a:((500*(x-y))+128)/255,b:((200*(y-z))+128)/255}',
        );

        // https://github.com/matthiasmullie/minify/issues/143
        $tests[] = array(
            "if(nutritionalPortionWeightUnit == 'lbs' && blockUnit == 'oz'){
itemFat = (qty * (fat/nutritionalPortionWeight))/16;
itemProtein = (qty * (protein/nutritionalPortionWeight))/16;
itemCarbs = (qty * (carbs/nutritionalPortionWeight))/16;
itemKcal = (qty * (kcal/nutritionalPortionWeight))/16;
}",
            "if(nutritionalPortionWeightUnit=='lbs'&&blockUnit=='oz'){itemFat=(qty*(fat/nutritionalPortionWeight))/16;itemProtein=(qty*(protein/nutritionalPortionWeight))/16;itemCarbs=(qty*(carbs/nutritionalPortionWeight))/16;itemKcal=(qty*(kcal/nutritionalPortionWeight))/16}",
        );
        $tests[] = array(
            'itemFat = (qty * (fat/nutritionalPortionWeight))/16;
itemFat = (qty * (fat/nutritionalPortionWeight))/(28.3495*16);',
            'itemFat=(qty*(fat/nutritionalPortionWeight))/16;itemFat=(qty*(fat/nutritionalPortionWeight))/(28.3495*16)',
        );

        // https://github.com/matthiasmullie/minify/issues/146
        $tests[] = array(
            'rnoContent = /^(?:GET|HEAD)$/,
rprotocol = /^\/\//,
/* ...
 */
prefilters = {};',
            'rnoContent=/^(?:GET|HEAD)$/,rprotocol=/^\/\//,prefilters={}',
        );
        $tests[] = array(
            'elem.getAttribute("type")!==null)+"/"+elem.type
var rprotocol=/^\/\//,prefilters={}',
            'elem.getAttribute("type")!==null)+"/"+elem.type
var rprotocol=/^\/\//,prefilters={}',
        );
        $tests[] = array(
            'map: function( elems, callback, arg ) {
                for ( i in elems ) {
                    value = callback( elems[ i ], i, arg );
                    if ( value != null ) {
                        ret.push( value );
                    }
                }

                return concat.apply( [], ret );
            }',
            'map:function(elems,callback,arg){for(i in elems){value=callback(elems[i],i,arg);if(value!=null){ret.push(value)}}
return concat.apply([],ret)}',
        );

        // https://github.com/matthiasmullie/minify/issues/167
        $tests[] = array(
            'this.valueMap.false',
            'this.valueMap.false',
        );
        $tests[] = array(
            'this.valueMap . false',
            'this.valueMap.false',
        );
        $tests[] = array(
            'false!==true',
            '!1!==!0',
        );

        // https://github.com/matthiasmullie/minify/issues/164
        $tests[] = array(
            'Calendar.createElement = function(type, parent) {
    var el = null;
    if (document.createElementNS) {
        // use the XHTML namespace; IE won\'t normally get here unless
        // _they_ "fix" the DOM2 implementation.
        el = document.createElementNS("http://www.w3.org/1999/xhtml", type);
    } else {
        el = document.createElement(type);
    }
    if (typeof parent != "undefined") {
        parent.appendChild(el);
    }
    return el;
};',
            'Calendar.createElement=function(type,parent){var el=null;if(document.createElementNS){el=document.createElementNS("http://www.w3.org/1999/xhtml",type)}else{el=document.createElement(type)}
if(typeof parent!="undefined"){parent.appendChild(el)}
return el}',
        );
        $tests[] = array(
            "$(this).find('iframe').hide().attr('src', '//about:blank').end().empty();",
            "$(this).find('iframe').hide().attr('src','//about:blank').end().empty()",
        );

        // https://github.com/matthiasmullie/minify/issues/163
        $tests[] = array(
            'q = d / 4 / b.width()',
            'q=d/4/b.width()',
        );

        // https://github.com/matthiasmullie/minify/issues/182
        $tests[] = array(
            'label = input.val().replace(/\\\\/g, \'/\').replace(/.*\//, \'\');',
            'label=input.val().replace(/\\\\/g,\'/\').replace(/.*\//,\'\')',
        );

        // https://github.com/matthiasmullie/minify/issues/178
        $tests[] = array(
            'lunr.SortedSet.prototype.add = function () {
  var i, element

  for (i = 0; i < arguments.length; i++) {
    element = arguments[i]
    if (~this.indexOf(element)) continue
    this.elements.splice(this.locationFor(element), 0, element)
  }

  this.length = this.elements.length
}',
            'lunr.SortedSet.prototype.add=function(){var i,element
for(i=0;i<arguments.length;i++){element=arguments[i]
if(~this.indexOf(element))continue
this.elements.splice(this.locationFor(element),0,element)}
this.length=this.elements.length}',
        );

        // https://github.com/matthiasmullie/minify/issues/185
        $tests[] = array(
            'var thisPos = indexOf(stack, this);
~thisPos ? stack.splice(thisPos + 1) : stack.push(this)
~thisPos ? keys.splice(thisPos, Infinity, key) : keys.push(key)
if (~indexOf(stack, value)) value = cycleReplacer.call(this, key, value)',
            'var thisPos=indexOf(stack,this);~thisPos?stack.splice(thisPos+1):stack.push(this)
~thisPos?keys.splice(thisPos,Infinity,key):keys.push(key)
if(~indexOf(stack,value))value=cycleReplacer.call(this,key,value)',
        );

        // https://github.com/matthiasmullie/minify/issues/186
        $tests[] = array(
            'd/=60;z("/foo/.")
/*! This comment should be removed by the minify process */

var str1 = "//this-text-shoudl-remain-intact";
var str2 = "some other string here";',
            'd/=60;z("/foo/.")
var str1="//this-text-shoudl-remain-intact";var str2="some other string here"',
        );

        // https://github.com/matthiasmullie/minify/issues/189
        $tests[] = array(
            '(function() {
  window.Selector = Class.create({
    initialize: function(expression) {
      this.expression = expression.strip();
    },

    findElements: function(rootElement) {
      return Prototype.Selector.select(this.expression, rootElement);
    },

    match: function(element) {
      return Prototype.Selector.match(element, this.expression);
    },

    toString: function() {
      return this.expression;
    },

    inspect: function() {
      return "#<Selector: " + this.expression + ">";
    }
  });

  Object.extend(Selector, {
    matchElements: function(elements, expression) {
      var match = Prototype.Selector.match,
          results = [];

      for (var i = 0, length = elements.length; i < length; i++) {
        var element = elements[i];
        if (match(element, expression)) {
          results.push(Element.extend(element));
        }
      }
      return results;
    },

    findElement: function(elements, expression, index) {
      index = index || 0;
      var matchIndex = 0, element;
      for (var i = 0, length = elements.length; i < length; i++) {
        element = elements[i];
        if (Prototype.Selector.match(element, expression) && index === matchIndex++) {
          return Element.extend(element);
        }
      }
    },

    findChildElements: function(element, expressions) {
      var selector = expressions.toArray().join(\', \');
      return Prototype.Selector.select(selector, element || document);
    }
  });
})();

function someOtherFunction() {
}',
            '(function(){window.Selector=Class.create({initialize:function(expression){this.expression=expression.strip()},findElements:function(rootElement){return Prototype.Selector.select(this.expression,rootElement)},match:function(element){return Prototype.Selector.match(element,this.expression)},toString:function(){return this.expression},inspect:function(){return"#<Selector: "+this.expression+">"}});Object.extend(Selector,{matchElements:function(elements,expression){var match=Prototype.Selector.match,results=[];for(var i=0,length=elements.length;i<length;i++){var element=elements[i];if(match(element,expression)){results.push(Element.extend(element))}}
return results},findElement:function(elements,expression,index){index=index||0;var matchIndex=0,element;for(var i=0,length=elements.length;i<length;i++){element=elements[i];if(Prototype.Selector.match(element,expression)&&index===matchIndex++){return Element.extend(element)}}},findChildElements:function(element,expressions){var selector=expressions.toArray().join(\', \');return Prototype.Selector.select(selector,element||document)}})})();function someOtherFunction(){}',
        );

        // https://github.com/matthiasmullie/minify/issues/190
        $tests[] = array(
            'function fullwidth_portfolio_carousel_slide( $arrow ) {
                    var $the_portfolio = $arrow.parents(\'.et_pb_fullwidth_portfolio\'),
                        $portfolio_items = $the_portfolio.find(\'.et_pb_portfolio_items\'),
                        $the_portfolio_items = $portfolio_items.find(\'.et_pb_portfolio_item\'),
                        $active_carousel_group = $portfolio_items.find(\'.et_pb_carousel_group.active\'),
                        slide_duration = 700,
                        items = $portfolio_items.data(\'items\'),
                        columns = $portfolio_items.data(\'portfolio-columns\'),
                        item_width = $active_carousel_group.innerWidth() / columns, //$active_carousel_group.children().first().innerWidth(),
                        original_item_width = ( 100 / columns ) + \'%\';

                    if ( \'undefined\' == typeof items ) {
                        return;
                    }

                    if ( $the_portfolio.data(\'carouseling\') ) {
                        return;
                    }

                    $the_portfolio.data(\'carouseling\', true);

                    $active_carousel_group.children().each(function(){
                        $(this).css({\'width\': $(this).innerWidth() + 1, \'position\':\'absolute\', \'left\': ( $(this).innerWidth() * ( $(this).data(\'position\') - 1 ) ) });
                    });
                }',
            'function fullwidth_portfolio_carousel_slide($arrow){var $the_portfolio=$arrow.parents(\'.et_pb_fullwidth_portfolio\'),$portfolio_items=$the_portfolio.find(\'.et_pb_portfolio_items\'),$the_portfolio_items=$portfolio_items.find(\'.et_pb_portfolio_item\'),$active_carousel_group=$portfolio_items.find(\'.et_pb_carousel_group.active\'),slide_duration=700,items=$portfolio_items.data(\'items\'),columns=$portfolio_items.data(\'portfolio-columns\'),item_width=$active_carousel_group.innerWidth()/columns,original_item_width=(100/columns)+\'%\';if(\'undefined\'==typeof items){return}
if($the_portfolio.data(\'carouseling\')){return}
$the_portfolio.data(\'carouseling\',!0);$active_carousel_group.children().each(function(){$(this).css({\'width\':$(this).innerWidth()+1,\'position\':\'absolute\',\'left\':($(this).innerWidth()*($(this).data(\'position\')-1))})})}',
        );

        $tests[] = array(
            'if("some   string" /*or comment*/)/regex/',
            'if("some   string")/regex/',
        );

        // https://github.com/matthiasmullie/minify/issues/195
        $tests[] = array(
            '"function"!=typeof/./&&"object"!=typeof Int8Array',
            '"function"!=typeof/./&&"object"!=typeof Int8Array',
        );
        $tests[] = array(
            'if (true || /^(https?:)?\/\//.test(\'xxx\')) alert(1);',
            'if(!0||/^(https?:)?\/\//.test(\'xxx\'))alert(1)',
        );

        // https://github.com/matthiasmullie/minify/issues/196
        $tests[] = array(
            'if ( true ) {
    console.log(true);
// ...comment number 2 (something with dots?)
} else {
    console.log(false);
}',
            'if(!0){console.log(!0)}else{console.log(!1)}',
        );

        // https://github.com/matthiasmullie/minify/issues/197
        $tests[] = array(
            'if(!e.allow_html_data_urls&&V.test(k)&&!/^data:image\//i.test(k))return',
            'if(!e.allow_html_data_urls&&V.test(k)&&!/^data:image\//i.test(k))return',
        );

        // https://github.com/matthiasmullie/minify/issues/199
        $tests[] = array(
            '// This case was fixed on version 1.3.50
// function () {
//    return false;
// };

// Next two cases failed since version 1.3.49
// function () {
//    return false; //.click();
// };

// function () {
//    ;//;
// }',
            '',
        );

        // https://github.com/matthiasmullie/minify/issues/204
        $tests[] = array(
            'data = data.replace(this.video.reUrlYoutube, iframeStart + \'//www.youtube.com/embed/$1\' + iframeEnd);',
            'data=data.replace(this.video.reUrlYoutube,iframeStart+\'//www.youtube.com/embed/$1\'+iframeEnd)'
        );
        $tests[] = array(
            'pattern = /(\/)\'/;
a = \'b\';',
            'pattern=/(\/)\'/;a=\'b\'',
        );

        // https://github.com/matthiasmullie/minify/issues/205
        $tests[] = array(
            'return { lineComment: parserConfig.slashComments ? "//" : null }',
            'return{lineComment:parserConfig.slashComments?"//":null}',
        );
        $tests[] = array(
            '\'//\'.match(/\/|\'/);',
            '\'//\'.match(/\/|\'/)',
        );

        // https://github.com/matthiasmullie/minify/issues/209
        $tests[] = array(
            'var my_regexes = [/[a-z]{3}\//g, \'a string\', 1];',
            'var my_regexes=[/[a-z]{3}\//g,\'a string\',1]',
        );

        // https://github.com/matthiasmullie/minify/issues/211
        $tests[] = array(
            'if (last){
  for(i=1;i<3;i++);
} else if (first){
  for(i in list);
} else {
  while(this.rm(name, check, false));
}',
            'if(last){for(i=1;i<3;i++);}else if(first){for(i in list);}else{while(this.rm(name,check,!1));}',
        );
        $tests[] = array(
            'if(0){do{}while(1)}',
            'if(0){do{}while(1)}',
        );
        $tests[] = array(
            'if(0){do{}while(1);}',
            'if(0){do{}while(1);}',
        );

        // https://github.com/matthiasmullie/minify/issues/214
        $tests[] = array(
            '/\/|\'/;
\'.ctd_panel_content .ctd_preview\';',
            '/\/|\'/;\'.ctd_panel_content .ctd_preview\'',
        );

        // https://github.com/matthiasmullie/minify/issues/218
        $tests[] = array(
            "inside: {
    'rule': /@[\w-]+/
    // See rest below
}",
            "inside:{'rule':/@[\w-]+/}",
        );
        $tests[] = array(
            "inside: {
    'rule': /@[\w-]+/ // See rest below
}",
            "inside:{'rule':/@[\w-]+/}",
        );
        $tests[] = array(
            "inside: {
    'rule': /@[\w-]+/// See rest below
}",
            "inside:{'rule':/@[\w-]+/}",
        );
        $tests[] = array(
            "(1 + 2) / 3 / 4",
            "(1+2)/3/4",
        );

        // https://github.com/matthiasmullie/minify/issues/221
        $tests[] = array(
            '$export.F*/Version\/10\.\d+(\.\d+)? Safari\//.test(userAgent)',
            '$export.F*/Version\/10\.\d+(\.\d+)? Safari\//.test(userAgent)',
        );
        $tests[] = array(
            'new RegExp(/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/)',
            'new RegExp(/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/)',
        );

        // https://github.com/matthiasmullie/minify/issues/227
        $tests[] = array(
            __DIR__.'/sample/bugs/227/original.js',
            file_get_contents(__DIR__.'/sample/bugs/227/minified.js'),
        );

        // https://github.com/matthiasmullie/minify/issues/229
        $tests[] = array(
            '// Source: wp-includes/js/twemoji.min.js
var twemoji=function(){"use strict";function a(a,b){return document.createTextNode(b?a.replace(s,""):a)}function b(a){return a.replace(u,h)}function c(a,b){return"".concat(b.base,b.size,"/",a,b.ext)}function d(a,b){for(var c,e,f=a.childNodes,g=f.length;g--;)c=f[g],e=c.nodeType,3===e?b.push(c):1!==e||"ownerSVGElement"in c||v.test(c.nodeName.toLowerCase())||d(c,b);return b}function e(a){return o(a.indexOf(t)<0?a.replace(s,""):a)}function f(b,c){for(var f,g,h,i,j,k,l,m,n,o,p,q,s,t=d(b,[]),u=t.length;u--;){for(h=!1,i=document.createDocumentFragment(),j=t[u],k=j.nodeValue,m=0;l=r.exec(k);){if(n=l.index,n!==m&&i.appendChild(a(k.slice(m,n),!0)),p=l[0],q=e(p),m=n+p.length,s=c.callback(q,c)){o=new Image,o.onerror=c.onerror,o.setAttribute("draggable","false"),f=c.attributes(p,q);for(g in f)f.hasOwnProperty(g)&&0!==g.indexOf("on")&&!o.hasAttribute(g)&&o.setAttribute(g,f[g]);o.className=c.className,o.alt=p,o.src=s,h=!0,i.appendChild(o)}o||i.appendChild(a(p,!1)),o=null}h&&(m<k.length&&i.appendChild(a(k.slice(m),!0)),j.parentNode.replaceChild(i,j))}return b}function g(a,c){return m(a,function(a){var d,f,g=a,h=e(a),i=c.callback(h,c);if(i){g="<img ".concat(\'class="\',c.className,\'" \',\'draggable="false" \',\'alt="\',a,\'"\',\' src="\',i,\'"\'),d=c.attributes(a,h);for(f in d)d.hasOwnProperty(f)&&0!==f.indexOf("on")&&g.indexOf(" "+f+"=")===-1&&(g=g.concat(" ",f,\'="\',b(d[f]),\'"\'));g=g.concat("/>")}return g})}function h(a){return q[a]}function i(){return null}function j(a){return"number"==typeof a?a+"x"+a:a}function k(a){var b="string"==typeof a?parseInt(a,16):a;return b<65536?w(b):(b-=65536,w(55296+(b>>10),56320+(1023&b)))}function l(a,b){return b&&"function"!=typeof b||(b={callback:b}),("string"==typeof a?g:f)(a,{callback:b.callback||c,attributes:"function"==typeof b.attributes?b.attributes:i,base:"string"==typeof b.base?b.base:p.base,ext:b.ext||p.ext,size:b.folder||j(b.size||p.size),className:b.className||p.className,onerror:b.onerror||p.onerror})}function m(a,b){return String(a).replace(r,b)}function n(a){r.lastIndex=0;var b=r.test(a);return r.lastIndex=0,b}function o(a,b){for(var c=[],d=0,e=0,f=0;f<a.length;)d=a.charCodeAt(f++),e?(c.push((65536+(e-55296<<10)+(d-56320)).toString(16)),e=0):55296<=d&&d<=56319?e=d:c.push(d.toString(16));return c.join(b||"-")}var p={base:"https://twemoji.maxcdn.com/2/",ext:".png",size:"72x72",className:"emoji",convert:{fromCodePoint:k,toCodePoint:o},onerror:function(){this.parentNode&&this.parentNode.replaceChild(a(this.alt,!1),this)},parse:l,replace:m,test:n},q={"&":"&amp;","<":"&lt;",">":"&gt;","\'":"&#39;",\'"\':"&quot;"},r=/\ud83d[\udc68-\udc69](?:\ud83c[\udffb-\udfff])?\u200d(?:\u2695\ufe0f|\u2696\ufe0f|\u2708\ufe0f|\ud83c[\udf3e\udf73\udf93\udfa4\udfa8\udfeb\udfed]|\ud83d[\udcbb\udcbc\udd27\udd2c\ude80\ude92])|(?:\ud83c[\udfcb\udfcc]|\ud83d\udd75|\u26f9)(?:\ufe0f|\ud83c[\udffb-\udfff])\u200d[\u2640\u2642]\ufe0f|(?:\ud83c[\udfc3\udfc4\udfca]|\ud83d[\udc6e\udc71\udc73\udc77\udc81\udc82\udc86\udc87\ude45-\ude47\ude4b\ude4d\ude4e\udea3\udeb4-\udeb6]|\ud83e[\udd26\udd37-\udd39\udd3d\udd3e\uddd6-\udddd])(?:\ud83c[\udffb-\udfff])?\u200d[\u2640\u2642]\ufe0f|\ud83d\udc68\u200d\u2764\ufe0f\u200d\ud83d\udc8b\u200d\ud83d\udc68|\ud83d\udc68\u200d\ud83d\udc68\u200d\ud83d\udc66\u200d\ud83d\udc66|\ud83d\udc68\u200d\ud83d\udc68\u200d\ud83d\udc67\u200d\ud83d[\udc66\udc67]|\ud83d\udc68\u200d\ud83d\udc69\u200d\ud83d\udc66\u200d\ud83d\udc66|\ud83d\udc68\u200d\ud83d\udc69\u200d\ud83d\udc67\u200d\ud83d[\udc66\udc67]|\ud83d\udc69\u200d\u2764\ufe0f\u200d\ud83d\udc8b\u200d\ud83d[\udc68\udc69]|\ud83d\udc69\u200d\ud83d\udc69\u200d\ud83d\udc66\u200d\ud83d\udc66|\ud83d\udc69\u200d\ud83d\udc69\u200d\ud83d\udc67\u200d\ud83d[\udc66\udc67]|\ud83d\udc68\u200d\u2764\ufe0f\u200d\ud83d\udc68|\ud83d\udc68\u200d\ud83d\udc66\u200d\ud83d\udc66|\ud83d\udc68\u200d\ud83d\udc67\u200d\ud83d[\udc66\udc67]|\ud83d\udc68\u200d\ud83d\udc68\u200d\ud83d[\udc66\udc67]|\ud83d\udc68\u200d\ud83d\udc69\u200d\ud83d[\udc66\udc67]|\ud83d\udc69\u200d\u2764\ufe0f\u200d\ud83d[\udc68\udc69]|\ud83d\udc69\u200d\ud83d\udc66\u200d\ud83d\udc66|\ud83d\udc69\u200d\ud83d\udc67\u200d\ud83d[\udc66\udc67]|\ud83d\udc69\u200d\ud83d\udc69\u200d\ud83d[\udc66\udc67]|\ud83c\udff3\ufe0f\u200d\ud83c\udf08|\ud83c\udff4\u200d\u2620\ufe0f|\ud83d\udc41\u200d\ud83d\udde8|\ud83d\udc68\u200d\ud83d[\udc66\udc67]|\ud83d\udc69\u200d\ud83d[\udc66\udc67]|\ud83d\udc6f\u200d\u2640\ufe0f|\ud83d\udc6f\u200d\u2642\ufe0f|\ud83e\udd3c\u200d\u2640\ufe0f|\ud83e\udd3c\u200d\u2642\ufe0f|\ud83e\uddde\u200d\u2640\ufe0f|\ud83e\uddde\u200d\u2642\ufe0f|\ud83e\udddf\u200d\u2640\ufe0f|\ud83e\udddf\u200d\u2642\ufe0f|(?:[\u0023\u002a\u0030-\u0039])\ufe0f?\u20e3|(?:(?:\ud83c[\udfcb\udfcc]|\ud83d[\udd74\udd75\udd90]|[\u261d\u26f7\u26f9\u270c\u270d])(?:\ufe0f|(?!\ufe0e))|\ud83c[\udf85\udfc2-\udfc4\udfc7\udfca]|\ud83d[\udc42\udc43\udc46-\udc50\udc66-\udc69\udc6e\udc70-\udc78\udc7c\udc81-\udc83\udc85-\udc87\udcaa\udd7a\udd95\udd96\ude45-\ude47\ude4b-\ude4f\udea3\udeb4-\udeb6\udec0\udecc]|\ud83e[\udd18-\udd1c\udd1e\udd1f\udd26\udd30-\udd39\udd3d\udd3e\uddd1-\udddd]|[\u270a\u270b])(?:\ud83c[\udffb-\udfff]|)|\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc65\udb40\udc6e\udb40\udc67\udb40\udc7f|\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc73\udb40\udc63\udb40\udc74\udb40\udc7f|\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc77\udb40\udc6c\udb40\udc73\udb40\udc7f|\ud83c\udde6\ud83c[\udde8-\uddec\uddee\uddf1\uddf2\uddf4\uddf6-\uddfa\uddfc\uddfd\uddff]|\ud83c\udde7\ud83c[\udde6\udde7\udde9-\uddef\uddf1-\uddf4\uddf6-\uddf9\uddfb\uddfc\uddfe\uddff]|\ud83c\udde8\ud83c[\udde6\udde8\udde9\uddeb-\uddee\uddf0-\uddf5\uddf7\uddfa-\uddff]|\ud83c\udde9\ud83c[\uddea\uddec\uddef\uddf0\uddf2\uddf4\uddff]|\ud83c\uddea\ud83c[\udde6\udde8\uddea\uddec\udded\uddf7-\uddfa]|\ud83c\uddeb\ud83c[\uddee-\uddf0\uddf2\uddf4\uddf7]|\ud83c\uddec\ud83c[\udde6\udde7\udde9-\uddee\uddf1-\uddf3\uddf5-\uddfa\uddfc\uddfe]|\ud83c\udded\ud83c[\uddf0\uddf2\uddf3\uddf7\uddf9\uddfa]|\ud83c\uddee\ud83c[\udde8-\uddea\uddf1-\uddf4\uddf6-\uddf9]|\ud83c\uddef\ud83c[\uddea\uddf2\uddf4\uddf5]|\ud83c\uddf0\ud83c[\uddea\uddec-\uddee\uddf2\uddf3\uddf5\uddf7\uddfc\uddfe\uddff]|\ud83c\uddf1\ud83c[\udde6-\udde8\uddee\uddf0\uddf7-\uddfb\uddfe]|\ud83c\uddf2\ud83c[\udde6\udde8-\udded\uddf0-\uddff]|\ud83c\uddf3\ud83c[\udde6\udde8\uddea-\uddec\uddee\uddf1\uddf4\uddf5\uddf7\uddfa\uddff]|\ud83c\uddf4\ud83c\uddf2|\ud83c\uddf5\ud83c[\udde6\uddea-\udded\uddf0-\uddf3\uddf7-\uddf9\uddfc\uddfe]|\ud83c\uddf6\ud83c\udde6|\ud83c\uddf7\ud83c[\uddea\uddf4\uddf8\uddfa\uddfc]|\ud83c\uddf8\ud83c[\udde6-\uddea\uddec-\uddf4\uddf7-\uddf9\uddfb\uddfd-\uddff]|\ud83c\uddf9\ud83c[\udde6\udde8\udde9\uddeb-\udded\uddef-\uddf4\uddf7\uddf9\uddfb\uddfc\uddff]|\ud83c\uddfa\ud83c[\udde6\uddec\uddf2\uddf3\uddf8\uddfe\uddff]|\ud83c\uddfb\ud83c[\udde6\udde8\uddea\uddec\uddee\uddf3\uddfa]|\ud83c\uddfc\ud83c[\uddeb\uddf8]|\ud83c\uddfd\ud83c\uddf0|\ud83c\uddfe\ud83c[\uddea\uddf9]|\ud83c\uddff\ud83c[\udde6\uddf2\uddfc]|\ud800\udc00|\ud83c[\udccf\udd8e\udd91-\udd9a\udde6-\uddff\ude01\ude32-\ude36\ude38-\ude3a\ude50\ude51\udf00-\udf20\udf2d-\udf35\udf37-\udf7c\udf7e-\udf84\udf86-\udf93\udfa0-\udfc1\udfc5\udfc6\udfc8\udfc9\udfcf-\udfd3\udfe0-\udff0\udff4\udff8-\udfff]|\ud83d[\udc00-\udc3e\udc40\udc44\udc45\udc51-\udc65\udc6a-\udc6d\udc6f\udc79-\udc7b\udc7d-\udc80\udc84\udc88-\udca9\udcab-\udcfc\udcff-\udd3d\udd4b-\udd4e\udd50-\udd67\udda4\uddfb-\ude44\ude48-\ude4a\ude80-\udea2\udea4-\udeb3\udeb7-\udebf\udec1-\udec5\uded0-\uded2\udeeb\udeec\udef4-\udef8]|\ud83e[\udd10-\udd17\udd1d\udd20-\udd25\udd27-\udd2f\udd3a\udd3c\udd40-\udd45\udd47-\udd4c\udd50-\udd6b\udd80-\udd97\uddc0\uddd0\uddde-\udde6]|[\u23e9-\u23ec\u23f0\u23f3\u2640\u2642\u2695\u26ce\u2705\u2728\u274c\u274e\u2753-\u2755\u2795-\u2797\u27b0\u27bf\ue50a]|(?:\ud83c[\udc04\udd70\udd71\udd7e\udd7f\ude02\ude1a\ude2f\ude37\udf21\udf24-\udf2c\udf36\udf7d\udf96\udf97\udf99-\udf9b\udf9e\udf9f\udfcd\udfce\udfd4-\udfdf\udff3\udff5\udff7]|\ud83d[\udc3f\udc41\udcfd\udd49\udd4a\udd6f\udd70\udd73\udd76-\udd79\udd87\udd8a-\udd8d\udda5\udda8\uddb1\uddb2\uddbc\uddc2-\uddc4\uddd1-\uddd3\udddc-\uddde\udde1\udde3\udde8\uddef\uddf3\uddfa\udecb\udecd-\udecf\udee0-\udee5\udee9\udef0\udef3]|[\u00a9\u00ae\u203c\u2049\u2122\u2139\u2194-\u2199\u21a9\u21aa\u231a\u231b\u2328\u23cf\u23ed-\u23ef\u23f1\u23f2\u23f8-\u23fa\u24c2\u25aa\u25ab\u25b6\u25c0\u25fb-\u25fe\u2600-\u2604\u260e\u2611\u2614\u2615\u2618\u2620\u2622\u2623\u2626\u262a\u262e\u262f\u2638-\u263a\u2648-\u2653\u2660\u2663\u2665\u2666\u2668\u267b\u267f\u2692-\u2694\u2696\u2697\u2699\u269b\u269c\u26a0\u26a1\u26aa\u26ab\u26b0\u26b1\u26bd\u26be\u26c4\u26c5\u26c8\u26cf\u26d1\u26d3\u26d4\u26e9\u26ea\u26f0-\u26f5\u26f8\u26fa\u26fd\u2702\u2708\u2709\u270f\u2712\u2714\u2716\u271d\u2721\u2733\u2734\u2744\u2747\u2757\u2763\u2764\u27a1\u2934\u2935\u2b05-\u2b07\u2b1b\u2b1c\u2b50\u2b55\u3030\u303d\u3297\u3299])(?:\ufe0f|(?!\ufe0e))/g,s=/\uFE0F/g,t=String.fromCharCode(8205),u=/[&<>\'"]/g,v=/^(?:iframe|noframes|noscript|script|select|style|textarea)$/,w=String.fromCharCode;return p}();
// Source: wp-includes/js/wp-emoji.min.js
!function(a,b){function c(){function c(){return!j.implementation.hasFeature||j.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#Image","1.1")}function d(){if(!k){if("undefined"==typeof a.twemoji){if(l>600)return;return a.clearTimeout(h),h=a.setTimeout(d,50),void l++}g=a.twemoji,k=!0,i&&new i(function(a){for(var b,c,d,g,h=a.length;h--;){if(b=a[h].addedNodes,c=a[h].removedNodes,d=b.length,1===d&&1===c.length&&3===b[0].nodeType&&"IMG"===c[0].nodeName&&b[0].data===c[0].alt&&"load-failed"===c[0].getAttribute("data-error"))return;for(;d--;){if(g=b[d],3===g.nodeType){if(!g.parentNode)continue;if(m)for(;g.nextSibling&&3===g.nextSibling.nodeType;)g.nodeValue=g.nodeValue+g.nextSibling.nodeValue,g.parentNode.removeChild(g.nextSibling);g=g.parentNode}!g||1!==g.nodeType||g.className&&"string"==typeof g.className&&g.className.indexOf("wp-exclude-emoji")!==-1||e(g.textContent)&&f(g)}}}).observe(j.body,{childList:!0,subtree:!0}),f(j.body)}}function e(a){var b=/[\u203C\u2049\u20E3\u2122\u2139\u2194-\u2199\u21A9\u21AA\u2300\u231A\u231B\u2328\u2388\u23CF\u23E9-\u23F3\u23F8-\u23FA\u24C2\u25AA\u25AB\u25B6\u25C0\u25FB-\u25FE\u2600-\u2604\u260E\u2611\u2614\u2615\u2618\u261D\u2620\u2622\u2623\u2626\u262A\u262E\u262F\u2638\u2639\u263A\u2648-\u2653\u2660\u2663\u2665\u2666\u2668\u267B\u267F\u2692\u2693\u2694\u2696\u2697\u2699\u269B\u269C\u26A0\u26A1\u26AA\u26AB\u26B0\u26B1\u26BD\u26BE\u26C4\u26C5\u26C8\u26CE\u26CF\u26D1\u26D3\u26D4\u26E9\u26EA\u26F0-\u26F5\u26F7-\u26FA\u26FD\u2702\u2705\u2708-\u270D\u270F\u2712\u2714\u2716\u271D\u2721\u2728\u2733\u2734\u2744\u2747\u274C\u274E\u2753\u2754\u2755\u2757\u2763\u2764\u2795\u2796\u2797\u27A1\u27B0\u27BF\u2934\u2935\u2B05\u2B06\u2B07\u2B1B\u2B1C\u2B50\u2B55\u3030\u303D\u3297\u3299]/,c=/[\uDC00-\uDFFF]/;return!!a&&(c.test(a)||b.test(a))}function f(a,d){var e;return!b.supports.everything&&g&&a&&("string"==typeof a||a.childNodes&&a.childNodes.length)?(d=d||{},e={base:c()?b.svgUrl:b.baseUrl,ext:c()?b.svgExt:b.ext,className:d.className||"emoji",callback:function(a,c){switch(a){case"a9":case"ae":case"2122":case"2194":case"2660":case"2663":case"2665":case"2666":return!1}return!(b.supports.everythingExceptFlag&&!/^1f1(?:e[6-9a-f]|f[0-9a-f])-1f1(?:e[6-9a-f]|f[0-9a-f])$/.test(a)&&!/^(1f3f3-fe0f-200d-1f308|1f3f4-200d-2620-fe0f)$/.test(a))&&"".concat(c.base,a,c.ext)},onerror:function(){g.parentNode&&(this.setAttribute("data-error","load-failed"),g.parentNode.replaceChild(j.createTextNode(g.alt),g))}},"object"==typeof d.imgAttr&&(e.attributes=function(){return d.imgAttr}),g.parse(a,e)):a}var g,h,i=a.MutationObserver||a.WebKitMutationObserver||a.MozMutationObserver,j=a.document,k=!1,l=0,m=a.navigator.userAgent.indexOf("Trident/7.0")>0;return b&&(b.DOMReady?d():b.readyCallback=d),{parse:f,test:e}}a.wp=a.wp||{},a.wp.emoji=new c}(window,window._wpemojiSettings);',
            'var twemoji=function(){"use strict";function a(a,b){return document.createTextNode(b?a.replace(s,""):a)}function b(a){return a.replace(u,h)}function c(a,b){return"".concat(b.base,b.size,"/",a,b.ext)}function d(a,b){for(var c,e,f=a.childNodes,g=f.length;g--;)c=f[g],e=c.nodeType,3===e?b.push(c):1!==e||"ownerSVGElement"in c||v.test(c.nodeName.toLowerCase())||d(c,b);return b}function e(a){return o(a.indexOf(t)<0?a.replace(s,""):a)}function f(b,c){for(var f,g,h,i,j,k,l,m,n,o,p,q,s,t=d(b,[]),u=t.length;u--;){for(h=!1,i=document.createDocumentFragment(),j=t[u],k=j.nodeValue,m=0;l=r.exec(k);){if(n=l.index,n!==m&&i.appendChild(a(k.slice(m,n),!0)),p=l[0],q=e(p),m=n+p.length,s=c.callback(q,c)){o=new Image,o.onerror=c.onerror,o.setAttribute("draggable","false"),f=c.attributes(p,q);for(g in f)f.hasOwnProperty(g)&&0!==g.indexOf("on")&&!o.hasAttribute(g)&&o.setAttribute(g,f[g]);o.className=c.className,o.alt=p,o.src=s,h=!0,i.appendChild(o)}o||i.appendChild(a(p,!1)),o=null}h&&(m<k.length&&i.appendChild(a(k.slice(m),!0)),j.parentNode.replaceChild(i,j))}return b}function g(a,c){return m(a,function(a){var d,f,g=a,h=e(a),i=c.callback(h,c);if(i){g="<img ".concat(\'class="\',c.className,\'" \',\'draggable="false" \',\'alt="\',a,\'"\',\' src="\',i,\'"\'),d=c.attributes(a,h);for(f in d)d.hasOwnProperty(f)&&0!==f.indexOf("on")&&g.indexOf(" "+f+"=")===-1&&(g=g.concat(" ",f,\'="\',b(d[f]),\'"\'));g=g.concat("/>")}return g})}function h(a){return q[a]}function i(){return null}function j(a){return"number"==typeof a?a+"x"+a:a}function k(a){var b="string"==typeof a?parseInt(a,16):a;return b<65536?w(b):(b-=65536,w(55296+(b>>10),56320+(1023&b)))}function l(a,b){return b&&"function"!=typeof b||(b={callback:b}),("string"==typeof a?g:f)(a,{callback:b.callback||c,attributes:"function"==typeof b.attributes?b.attributes:i,base:"string"==typeof b.base?b.base:p.base,ext:b.ext||p.ext,size:b.folder||j(b.size||p.size),className:b.className||p.className,onerror:b.onerror||p.onerror})}function m(a,b){return String(a).replace(r,b)}function n(a){r.lastIndex=0;var b=r.test(a);return r.lastIndex=0,b}function o(a,b){for(var c=[],d=0,e=0,f=0;f<a.length;)d=a.charCodeAt(f++),e?(c.push((65536+(e-55296<<10)+(d-56320)).toString(16)),e=0):55296<=d&&d<=56319?e=d:c.push(d.toString(16));return c.join(b||"-")}var p={base:"https://twemoji.maxcdn.com/2/",ext:".png",size:"72x72",className:"emoji",convert:{fromCodePoint:k,toCodePoint:o},onerror:function(){this.parentNode&&this.parentNode.replaceChild(a(this.alt,!1),this)},parse:l,replace:m,test:n},q={"&":"&amp;","<":"&lt;",">":"&gt;","\'":"&#39;",\'"\':"&quot;"},r=/\ud83d[\udc68-\udc69](?:\ud83c[\udffb-\udfff])?\u200d(?:\u2695\ufe0f|\u2696\ufe0f|\u2708\ufe0f|\ud83c[\udf3e\udf73\udf93\udfa4\udfa8\udfeb\udfed]|\ud83d[\udcbb\udcbc\udd27\udd2c\ude80\ude92])|(?:\ud83c[\udfcb\udfcc]|\ud83d\udd75|\u26f9)(?:\ufe0f|\ud83c[\udffb-\udfff])\u200d[\u2640\u2642]\ufe0f|(?:\ud83c[\udfc3\udfc4\udfca]|\ud83d[\udc6e\udc71\udc73\udc77\udc81\udc82\udc86\udc87\ude45-\ude47\ude4b\ude4d\ude4e\udea3\udeb4-\udeb6]|\ud83e[\udd26\udd37-\udd39\udd3d\udd3e\uddd6-\udddd])(?:\ud83c[\udffb-\udfff])?\u200d[\u2640\u2642]\ufe0f|\ud83d\udc68\u200d\u2764\ufe0f\u200d\ud83d\udc8b\u200d\ud83d\udc68|\ud83d\udc68\u200d\ud83d\udc68\u200d\ud83d\udc66\u200d\ud83d\udc66|\ud83d\udc68\u200d\ud83d\udc68\u200d\ud83d\udc67\u200d\ud83d[\udc66\udc67]|\ud83d\udc68\u200d\ud83d\udc69\u200d\ud83d\udc66\u200d\ud83d\udc66|\ud83d\udc68\u200d\ud83d\udc69\u200d\ud83d\udc67\u200d\ud83d[\udc66\udc67]|\ud83d\udc69\u200d\u2764\ufe0f\u200d\ud83d\udc8b\u200d\ud83d[\udc68\udc69]|\ud83d\udc69\u200d\ud83d\udc69\u200d\ud83d\udc66\u200d\ud83d\udc66|\ud83d\udc69\u200d\ud83d\udc69\u200d\ud83d\udc67\u200d\ud83d[\udc66\udc67]|\ud83d\udc68\u200d\u2764\ufe0f\u200d\ud83d\udc68|\ud83d\udc68\u200d\ud83d\udc66\u200d\ud83d\udc66|\ud83d\udc68\u200d\ud83d\udc67\u200d\ud83d[\udc66\udc67]|\ud83d\udc68\u200d\ud83d\udc68\u200d\ud83d[\udc66\udc67]|\ud83d\udc68\u200d\ud83d\udc69\u200d\ud83d[\udc66\udc67]|\ud83d\udc69\u200d\u2764\ufe0f\u200d\ud83d[\udc68\udc69]|\ud83d\udc69\u200d\ud83d\udc66\u200d\ud83d\udc66|\ud83d\udc69\u200d\ud83d\udc67\u200d\ud83d[\udc66\udc67]|\ud83d\udc69\u200d\ud83d\udc69\u200d\ud83d[\udc66\udc67]|\ud83c\udff3\ufe0f\u200d\ud83c\udf08|\ud83c\udff4\u200d\u2620\ufe0f|\ud83d\udc41\u200d\ud83d\udde8|\ud83d\udc68\u200d\ud83d[\udc66\udc67]|\ud83d\udc69\u200d\ud83d[\udc66\udc67]|\ud83d\udc6f\u200d\u2640\ufe0f|\ud83d\udc6f\u200d\u2642\ufe0f|\ud83e\udd3c\u200d\u2640\ufe0f|\ud83e\udd3c\u200d\u2642\ufe0f|\ud83e\uddde\u200d\u2640\ufe0f|\ud83e\uddde\u200d\u2642\ufe0f|\ud83e\udddf\u200d\u2640\ufe0f|\ud83e\udddf\u200d\u2642\ufe0f|(?:[\u0023\u002a\u0030-\u0039])\ufe0f?\u20e3|(?:(?:\ud83c[\udfcb\udfcc]|\ud83d[\udd74\udd75\udd90]|[\u261d\u26f7\u26f9\u270c\u270d])(?:\ufe0f|(?!\ufe0e))|\ud83c[\udf85\udfc2-\udfc4\udfc7\udfca]|\ud83d[\udc42\udc43\udc46-\udc50\udc66-\udc69\udc6e\udc70-\udc78\udc7c\udc81-\udc83\udc85-\udc87\udcaa\udd7a\udd95\udd96\ude45-\ude47\ude4b-\ude4f\udea3\udeb4-\udeb6\udec0\udecc]|\ud83e[\udd18-\udd1c\udd1e\udd1f\udd26\udd30-\udd39\udd3d\udd3e\uddd1-\udddd]|[\u270a\u270b])(?:\ud83c[\udffb-\udfff]|)|\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc65\udb40\udc6e\udb40\udc67\udb40\udc7f|\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc73\udb40\udc63\udb40\udc74\udb40\udc7f|\ud83c\udff4\udb40\udc67\udb40\udc62\udb40\udc77\udb40\udc6c\udb40\udc73\udb40\udc7f|\ud83c\udde6\ud83c[\udde8-\uddec\uddee\uddf1\uddf2\uddf4\uddf6-\uddfa\uddfc\uddfd\uddff]|\ud83c\udde7\ud83c[\udde6\udde7\udde9-\uddef\uddf1-\uddf4\uddf6-\uddf9\uddfb\uddfc\uddfe\uddff]|\ud83c\udde8\ud83c[\udde6\udde8\udde9\uddeb-\uddee\uddf0-\uddf5\uddf7\uddfa-\uddff]|\ud83c\udde9\ud83c[\uddea\uddec\uddef\uddf0\uddf2\uddf4\uddff]|\ud83c\uddea\ud83c[\udde6\udde8\uddea\uddec\udded\uddf7-\uddfa]|\ud83c\uddeb\ud83c[\uddee-\uddf0\uddf2\uddf4\uddf7]|\ud83c\uddec\ud83c[\udde6\udde7\udde9-\uddee\uddf1-\uddf3\uddf5-\uddfa\uddfc\uddfe]|\ud83c\udded\ud83c[\uddf0\uddf2\uddf3\uddf7\uddf9\uddfa]|\ud83c\uddee\ud83c[\udde8-\uddea\uddf1-\uddf4\uddf6-\uddf9]|\ud83c\uddef\ud83c[\uddea\uddf2\uddf4\uddf5]|\ud83c\uddf0\ud83c[\uddea\uddec-\uddee\uddf2\uddf3\uddf5\uddf7\uddfc\uddfe\uddff]|\ud83c\uddf1\ud83c[\udde6-\udde8\uddee\uddf0\uddf7-\uddfb\uddfe]|\ud83c\uddf2\ud83c[\udde6\udde8-\udded\uddf0-\uddff]|\ud83c\uddf3\ud83c[\udde6\udde8\uddea-\uddec\uddee\uddf1\uddf4\uddf5\uddf7\uddfa\uddff]|\ud83c\uddf4\ud83c\uddf2|\ud83c\uddf5\ud83c[\udde6\uddea-\udded\uddf0-\uddf3\uddf7-\uddf9\uddfc\uddfe]|\ud83c\uddf6\ud83c\udde6|\ud83c\uddf7\ud83c[\uddea\uddf4\uddf8\uddfa\uddfc]|\ud83c\uddf8\ud83c[\udde6-\uddea\uddec-\uddf4\uddf7-\uddf9\uddfb\uddfd-\uddff]|\ud83c\uddf9\ud83c[\udde6\udde8\udde9\uddeb-\udded\uddef-\uddf4\uddf7\uddf9\uddfb\uddfc\uddff]|\ud83c\uddfa\ud83c[\udde6\uddec\uddf2\uddf3\uddf8\uddfe\uddff]|\ud83c\uddfb\ud83c[\udde6\udde8\uddea\uddec\uddee\uddf3\uddfa]|\ud83c\uddfc\ud83c[\uddeb\uddf8]|\ud83c\uddfd\ud83c\uddf0|\ud83c\uddfe\ud83c[\uddea\uddf9]|\ud83c\uddff\ud83c[\udde6\uddf2\uddfc]|\ud800\udc00|\ud83c[\udccf\udd8e\udd91-\udd9a\udde6-\uddff\ude01\ude32-\ude36\ude38-\ude3a\ude50\ude51\udf00-\udf20\udf2d-\udf35\udf37-\udf7c\udf7e-\udf84\udf86-\udf93\udfa0-\udfc1\udfc5\udfc6\udfc8\udfc9\udfcf-\udfd3\udfe0-\udff0\udff4\udff8-\udfff]|\ud83d[\udc00-\udc3e\udc40\udc44\udc45\udc51-\udc65\udc6a-\udc6d\udc6f\udc79-\udc7b\udc7d-\udc80\udc84\udc88-\udca9\udcab-\udcfc\udcff-\udd3d\udd4b-\udd4e\udd50-\udd67\udda4\uddfb-\ude44\ude48-\ude4a\ude80-\udea2\udea4-\udeb3\udeb7-\udebf\udec1-\udec5\uded0-\uded2\udeeb\udeec\udef4-\udef8]|\ud83e[\udd10-\udd17\udd1d\udd20-\udd25\udd27-\udd2f\udd3a\udd3c\udd40-\udd45\udd47-\udd4c\udd50-\udd6b\udd80-\udd97\uddc0\uddd0\uddde-\udde6]|[\u23e9-\u23ec\u23f0\u23f3\u2640\u2642\u2695\u26ce\u2705\u2728\u274c\u274e\u2753-\u2755\u2795-\u2797\u27b0\u27bf\ue50a]|(?:\ud83c[\udc04\udd70\udd71\udd7e\udd7f\ude02\ude1a\ude2f\ude37\udf21\udf24-\udf2c\udf36\udf7d\udf96\udf97\udf99-\udf9b\udf9e\udf9f\udfcd\udfce\udfd4-\udfdf\udff3\udff5\udff7]|\ud83d[\udc3f\udc41\udcfd\udd49\udd4a\udd6f\udd70\udd73\udd76-\udd79\udd87\udd8a-\udd8d\udda5\udda8\uddb1\uddb2\uddbc\uddc2-\uddc4\uddd1-\uddd3\udddc-\uddde\udde1\udde3\udde8\uddef\uddf3\uddfa\udecb\udecd-\udecf\udee0-\udee5\udee9\udef0\udef3]|[\u00a9\u00ae\u203c\u2049\u2122\u2139\u2194-\u2199\u21a9\u21aa\u231a\u231b\u2328\u23cf\u23ed-\u23ef\u23f1\u23f2\u23f8-\u23fa\u24c2\u25aa\u25ab\u25b6\u25c0\u25fb-\u25fe\u2600-\u2604\u260e\u2611\u2614\u2615\u2618\u2620\u2622\u2623\u2626\u262a\u262e\u262f\u2638-\u263a\u2648-\u2653\u2660\u2663\u2665\u2666\u2668\u267b\u267f\u2692-\u2694\u2696\u2697\u2699\u269b\u269c\u26a0\u26a1\u26aa\u26ab\u26b0\u26b1\u26bd\u26be\u26c4\u26c5\u26c8\u26cf\u26d1\u26d3\u26d4\u26e9\u26ea\u26f0-\u26f5\u26f8\u26fa\u26fd\u2702\u2708\u2709\u270f\u2712\u2714\u2716\u271d\u2721\u2733\u2734\u2744\u2747\u2757\u2763\u2764\u27a1\u2934\u2935\u2b05-\u2b07\u2b1b\u2b1c\u2b50\u2b55\u3030\u303d\u3297\u3299])(?:\ufe0f|(?!\ufe0e))/g,s=/\uFE0F/g,t=String.fromCharCode(8205),u=/[&<>\'"]/g,v=/^(?:iframe|noframes|noscript|script|select|style|textarea)$/,w=String.fromCharCode;return p}();!function(a,b){function c(){function c(){return!j.implementation.hasFeature||j.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#Image","1.1")}function d(){if(!k){if("undefined"==typeof a.twemoji){if(l>600)return;return a.clearTimeout(h),h=a.setTimeout(d,50),void l++}g=a.twemoji,k=!0,i&&new i(function(a){for(var b,c,d,g,h=a.length;h--;){if(b=a[h].addedNodes,c=a[h].removedNodes,d=b.length,1===d&&1===c.length&&3===b[0].nodeType&&"IMG"===c[0].nodeName&&b[0].data===c[0].alt&&"load-failed"===c[0].getAttribute("data-error"))return;for(;d--;){if(g=b[d],3===g.nodeType){if(!g.parentNode)continue;if(m)for(;g.nextSibling&&3===g.nextSibling.nodeType;)g.nodeValue=g.nodeValue+g.nextSibling.nodeValue,g.parentNode.removeChild(g.nextSibling);g=g.parentNode}!g||1!==g.nodeType||g.className&&"string"==typeof g.className&&g.className.indexOf("wp-exclude-emoji")!==-1||e(g.textContent)&&f(g)}}}).observe(j.body,{childList:!0,subtree:!0}),f(j.body)}}function e(a){var b=/[\u203C\u2049\u20E3\u2122\u2139\u2194-\u2199\u21A9\u21AA\u2300\u231A\u231B\u2328\u2388\u23CF\u23E9-\u23F3\u23F8-\u23FA\u24C2\u25AA\u25AB\u25B6\u25C0\u25FB-\u25FE\u2600-\u2604\u260E\u2611\u2614\u2615\u2618\u261D\u2620\u2622\u2623\u2626\u262A\u262E\u262F\u2638\u2639\u263A\u2648-\u2653\u2660\u2663\u2665\u2666\u2668\u267B\u267F\u2692\u2693\u2694\u2696\u2697\u2699\u269B\u269C\u26A0\u26A1\u26AA\u26AB\u26B0\u26B1\u26BD\u26BE\u26C4\u26C5\u26C8\u26CE\u26CF\u26D1\u26D3\u26D4\u26E9\u26EA\u26F0-\u26F5\u26F7-\u26FA\u26FD\u2702\u2705\u2708-\u270D\u270F\u2712\u2714\u2716\u271D\u2721\u2728\u2733\u2734\u2744\u2747\u274C\u274E\u2753\u2754\u2755\u2757\u2763\u2764\u2795\u2796\u2797\u27A1\u27B0\u27BF\u2934\u2935\u2B05\u2B06\u2B07\u2B1B\u2B1C\u2B50\u2B55\u3030\u303D\u3297\u3299]/,c=/[\uDC00-\uDFFF]/;return!!a&&(c.test(a)||b.test(a))}function f(a,d){var e;return!b.supports.everything&&g&&a&&("string"==typeof a||a.childNodes&&a.childNodes.length)?(d=d||{},e={base:c()?b.svgUrl:b.baseUrl,ext:c()?b.svgExt:b.ext,className:d.className||"emoji",callback:function(a,c){switch(a){case"a9":case"ae":case"2122":case"2194":case"2660":case"2663":case"2665":case"2666":return!1}return!(b.supports.everythingExceptFlag&&!/^1f1(?:e[6-9a-f]|f[0-9a-f])-1f1(?:e[6-9a-f]|f[0-9a-f])$/.test(a)&&!/^(1f3f3-fe0f-200d-1f308|1f3f4-200d-2620-fe0f)$/.test(a))&&"".concat(c.base,a,c.ext)},onerror:function(){g.parentNode&&(this.setAttribute("data-error","load-failed"),g.parentNode.replaceChild(j.createTextNode(g.alt),g))}},"object"==typeof d.imgAttr&&(e.attributes=function(){return d.imgAttr}),g.parse(a,e)):a}var g,h,i=a.MutationObserver||a.WebKitMutationObserver||a.MozMutationObserver,j=a.document,k=!1,l=0,m=a.navigator.userAgent.indexOf("Trident/7.0")>0;return b&&(b.DOMReady?d():b.readyCallback=d),{parse:f,test:e}}a.wp=a.wp||{},a.wp.emoji=new c}(window,window._wpemojiSettings)',
        );

        // known minified files to help doublecheck changes in places not yet
        // anticipated in these tests
        $files = glob(__DIR__.'/sample/minified/*.js');
        foreach ($files as $file) {
            $content = trim(file_get_contents($file));
            $tests[] = array($content, $content);
        }
        // update tests' expected results for cross-system compatibility
        foreach ($tests as &$test) {
            if (!empty($test[1])) {
                $test[1] = str_replace("\r", '', $test[1]);
            }
        }

        return $tests;
    }
}
