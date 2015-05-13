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

        // override save method, there's no point in writing the result out here
        $this->minifier = $this->getMockBuilder('\MatthiasMullie\Minify\CSS')
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
     * Test CSS minifier rules, provided by dataProvider.
     *
     * @test
     * @dataProvider dataProvider
     */
    public function minify($input, $expected)
    {
        $input = (array) $input;
        foreach ($input as $css) {
            $this->minifier->add($css);
        }
        $result = $this->minifier->minify();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test conversion of relative paths, provided by dataProviderPaths.
     *
     * @test
     * @dataProvider dataProviderPaths
     */
    public function convertRelativePath($source, $target, $expected)
    {
        $source = (array) $source;
        foreach ($source as $path => $css) {
            $this->minifier->add($css);

            // $source also accepts an array where the key is a bogus path
            if (is_string($path)) {
                $object = new ReflectionObject($this->minifier);
                $property = $object->getProperty('data');
                $property->setAccessible(true);
                $data = $property->getValue($this->minifier);

                // keep content, but make it appear from the given path
                $data[$path] = array_pop($data);
                $property->setValue($this->minifier, $data);
                $property->setAccessible(false);
            }
        }

        $result = $this->minifier->minify($target);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test minifier import configuration methods.
     *
     * @test
     */
    public function setConfig()
    {
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
            __DIR__.'/sample/combine_imports/index.css',
            'body{color:red}',
        );
        $tests[] = array(
            __DIR__.'/sample/combine_imports/index2.css',
            'body{color:red}',
        );
        $tests[] = array(
            __DIR__.'/sample/combine_imports/index3.css',
            'body{color:red}body{color:red}',
        );
        $tests[] = array(
            __DIR__.'/sample/combine_imports/index4.css',
            '@media only screen{body{color:red}}@media only screen{body{color:red}}',
        );
        $tests[] = array(
            __DIR__.'/sample/combine_imports/index5.css',
            'body{color:red}body{color:red}',
        );
        $tests[] = array(
            __DIR__.'/sample/combine_imports/index6a.css',
            'body{color:red}',
        );

        // shorthand hex color codes
        $tests[] = array(
            'color:#FF00FF;',
            'color:#F0F;',
        );

        // import files
        $tests[] = array(
            __DIR__.'/sample/import_files/index.css',
            'body{background:url(data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/sample/import_files/file.png')).')}',
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
            'content:"preserve   whitespace"',
            'content:"preserve   whitespace"',
        );

        $tests[] = array(
            'html
            body {
                color: red;
            }',
            'html body{color:red}',
        );

        $tests[] = array(
<<<'JS'
p * i ,  html
/* remove spaces */

/* " comments have no escapes \*/
body/* keep */ /* space */p,
p  [ remove ~= " spaces  " ]  :nth-child( 3 + 2n )  >  b span   i  ,   div::after

{
  /* comment */
    content  :  " escapes \" allowed \\" ;
    content:  "  /* string */  "  !important ;
      width: calc( 100% - 3em + 5px ) ;
  margin-top : 0;
  margin-bottom : 0;
  margin-left : 10px;
  margin-right : 10px;
}
JS
        ,
            'p * i,html body p,p [remove~=" spaces  "] :nth-child(3+2n)>b span i,div::after{content:" escapes \\" allowed \\\\";content:"  /* string */  "!important;width:calc(100% - 3em + 5px);margin-top:0;margin-bottom:0;margin-left:10px;margin-right:10px}',
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

        // strip BOM
        $tests[] = array(
            __DIR__.'/sample/bom/bom.css',
            'body{color:red}',
        );

        // https://github.com/matthiasmullie/minify/issues/22
        $tests[] = array(
            'p { background-position: -0px -64px; }',
            'p{background-position:0 -64px}',
        );

        // https://github.com/matthiasmullie/minify/issues/23
        $tests[] = array(
            'ul.pagination {
display: block;
min-height: 1.5rem;
margin-left: -0.3125rem;
}',
            'ul.pagination{display:block;min-height:1.5rem;margin-left:-0.3125rem}',
        );

        // edge cases for stripping zeroes
        $tests[] = array(
            'p { margin: -0.0rem; }',
            'p{margin:0}',
        );
        $tests[] = array(
            'p { margin: -0.01rem; }',
            'p{margin:-0.01rem}',
        );
        $tests[] = array(
            'p { margin: .0; }',
            'p{margin:0}',
        );
        $tests[] = array(
            'p { margin: .0%; }',
            'p{margin:0}',
        );
        $tests[] = array(
            'p { margin: 1.0; }',
            'p{margin:1}',
        );
        $tests[] = array(
            'p { margin: 1.0px; }',
            'p{margin:1px}',
        );
        $tests[] = array(
            'p { margin: 1.1; }',
            'p{margin:1.1}',
        );
        $tests[] = array(
            'p { margin: 1.1em; }',
            'p{margin:1.1em}',
        );
        $tests[] = array(
            'p { margin: 00px; }',
            'p{margin:0}',
        );
        $tests[] = array(
            'p.class00 { background-color: #000000; color: #000; }',
            'p.class00{background-color:#000;color:#000}',
        );

        // https://github.com/matthiasmullie/minify/issues/24
        $tests[] = array(
            '.col-1-1 { width: 100.00%; }',
            '.col-1-1{width:100%}',
        );

        // https://github.com/matthiasmullie/minify/issues/25
        $tests[] = array(
            'p { background-color: #000000; color: #000; }',
            'p{background-color:#000;color:#000}',
        );

        // https://github.com/matthiasmullie/minify/issues/26
        $tests[] = array(
            '.hr > :first-child { width: 0.0001%; }',
            '.hr>:first-child{width:0.0001%}',
        );

        // https://github.com/matthiasmullie/minify/issues/28
        $tests[] = array(
            "@font-face { src: url(//netdna.bootstrapcdn.com/font-awesome/4.2.0/fonts/fontawesome-webfont.eot?v=4.2.0); }",
            "@font-face{src:url(//netdna.bootstrapcdn.com/font-awesome/4.2.0/fonts/fontawesome-webfont.eot?v=4.2.0)}",
        );

        // https://github.com/matthiasmullie/minify/issues/31
        $tests[] = array(
            "dfn,em,img{}",
            "dfn,em,img{}",
        );

        // https://github.com/matthiasmullie/minify/issues/49
        $tests[] = array(
            __DIR__.'/sample/import_files/issue49.css',
            '.social-btn a[href*="facebook"]{background-image:url(data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/sample/import_files/facebook.png')).')}' .
            '.social-btn a[href*="vimeo"]{background-image:url(data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/sample/import_files/vimeo.png')).')}' .
            '.social-btn a[href*="instagram"]{background-image:url(data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/sample/import_files/instagram.png')).')}'
        );

        return $tests;
    }

    /**
     * @return array [input, expected result]
     */
    public function dataProviderPaths()
    {
        $tests = array();

        $source = __DIR__.'/sample/convert_relative_path/source';
        $target = __DIR__.'/sample/convert_relative_path/target';

        // external link
        $tests[] = array(
            $source.'/external.css',
            $target.'/external.css',
            file_get_contents($source.'/external.css'),
        );

        // absolute path
        $tests[] = array(
            $source.'/absolute.css',
            $target.'/absolute.css',
            file_get_contents($source.'/absolute.css'),
        );

        // relative paths
        $tests[] = array(
            $source.'/relative.css',
            $target.'/relative.css',
            '@import url(image.jpg);',
        );
        $tests[] = array(
            $source.'/../source/relative.css',
            $target.'/target/relative.css',
            '@import url(../image.jpg);',
        );

        // https://github.com/matthiasmullie/minify/issues/29
        $tests[] = array(
            $source.'/issue29.css',
            $target.'/issue29.css',
            "@import url('http://myurl.de');",
        );

        // https://github.com/matthiasmullie/minify/issues/38
        $tests[] = array(
            $source.'/relative.css',
            null, // no output file
            file_get_contents($source.'/relative.css'),
        );

        // https://github.com/matthiasmullie/minify/issues/39
        $tests[] = array(
            $source.'/issue39.css',
            null, // no output file
            // relative paths should remain untouched
            "@font-face{font-family:'blackcat';src:url(../webfont/blackcat.eot);src:url(../webfont/blackcat.eot?#iefix) format('embedded-opentype'),url(../webfont/blackcat.svg#blackcat) format('svg'),url(../webfont/blackcat.woff) format('woff'),url(../webfont/blackcat.ttf) format('truetype');font-weight:normal;font-style:normal}",
        );
        $tests[] = array(
            $source.'/issue39.css',
            $target.'/issue39.css',
            // relative paths should remain untouched
            "@font-face{font-family:'blackcat';src:url(../webfont/blackcat.eot);src:url(../webfont/blackcat.eot?#iefix) format('embedded-opentype'),url(../webfont/blackcat.svg#blackcat) format('svg'),url(../webfont/blackcat.woff) format('woff'),url(../webfont/blackcat.ttf) format('truetype');font-weight:normal;font-style:normal}",
        );
        $tests[] = array(
            $source.'/issue39.css',
            $target.'/target/issue39.css',
            // relative paths should have changed
            "@font-face{font-family:'blackcat';src:url(../../webfont/blackcat.eot);src:url(../../webfont/blackcat.eot?#iefix) format('embedded-opentype'),url(../../webfont/blackcat.svg#blackcat) format('svg'),url(../../webfont/blackcat.woff) format('woff'),url(../../webfont/blackcat.ttf) format('truetype');font-weight:normal;font-style:normal}",
        );

        // https://github.com/forkcms/forkcms/issues/1121
        $tests[] = array(
            $source.'/nested/nested.css',
            $target.'/nested.css',
            '@import url(image.jpg);',
        );

        // https://github.com/forkcms/forkcms/issues/1186
        $tests[] = array(
            array(
                // key is a bogus path
                '/Users/mathias/Documents/— Projecten/PROJECT_NAAM/Web/src/Backend/Core/Layout/Css/screen.css' => '@import url("imports/typography.css");',
            ),
            '/Users/mathias/Documents/— Projecten/PROJECT_NAAM/Web/src/Backend/Cache/MinifiedCss/some-hash.css',
            '@import url(../../Core/Layout/Css/imports/typography.css);',
        );

        $sourceRelative = 'tests/css/sample/convert_relative_path/source';
        $targetRelative = 'tests/css/sample/convert_relative_path/target';

        // from and/or to are relative links
        $tests[] = array(
            $sourceRelative.'/relative.css',
            $target.'/relative.css',
            '@import url(image.jpg);',
        );
        // note: relative target only works if the file already exists: it has
        // to be able to realpath()
        $tests[] = array(
            $source.'/relative.css',
            $targetRelative.'/relative.css',
            '@import url(image.jpg);',
        );
        $tests[] = array(
            $sourceRelative.'/relative.css',
            $targetRelative.'/relative.css',
            '@import url(image.jpg);',
        );

        return $tests;
    }
}
