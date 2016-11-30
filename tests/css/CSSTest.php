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
     * Test loop while importing file.
     *
     * @test
     *
     * @expectedException MatthiasMullie\Minify\Exceptions\FileImportException
     */
    public function fileImportLoop()
    {
        $testFile = __DIR__.'/sample/loop/first.css';

        $this->minifier->add($testFile);

        $this->minifier->minify();
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

        // passing in an array of css inputs
        $tests[] = array(
            [
                __DIR__.'/sample/combine_imports/index.css',
                __DIR__.'/sample/bom/bom.css',
                'p { width: 55px , margin: 0 0 0 0}',
            ],
            'body{color:red}body{color:red}p{width:55px,margin:0 0 0 0}',
        );

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
            'ul.pagination{display:block;min-height:1.5rem;margin-left:-.3125rem}',
        );

        // edge cases for stripping zeroes
        $tests[] = array(
            'p { margin: -0.0rem; }',
            'p{margin:0rem}',
        );
        $tests[] = array(
            'p { margin: -0.01rem; }',
            'p{margin:-.01rem}',
        );
        $tests[] = array(
            'p { margin: .0; }',
            'p{margin:0}',
        );
        $tests[] = array(
            'p { margin: .0%; }',
            'p{margin:0%}',
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
            'p { margin: 0.1px; }',
            'p{margin:.1px}',
        );
        $tests[] = array(
            'p { margin: 01.1px; }',
            'p{margin:1.1px}',
        );
        $tests[] = array(
            'p { margin: 0.060px; }',
            'p{margin:.06px}',
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
            '.hr>:first-child{width:.0001%}',
        );

        // https://github.com/matthiasmullie/minify/issues/28
        $tests[] = array(
            '@font-face { src: url(//netdna.bootstrapcdn.com/font-awesome/4.2.0/fonts/fontawesome-webfont.eot?v=4.2.0); }',
            '@font-face{src:url(//netdna.bootstrapcdn.com/font-awesome/4.2.0/fonts/fontawesome-webfont.eot?v=4.2.0)}',
        );

        // https://github.com/matthiasmullie/minify/issues/31
        $tests[] = array(
            'dfn,em,img{color:red}',
            'dfn,em,img{color:red}',
        );

        // https://github.com/matthiasmullie/minify/issues/49
        $tests[] = array(
            __DIR__.'/sample/import_files/issue49.css',
            '.social-btn a[href*="facebook"]{background-image:url(data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/sample/import_files/facebook.png')).')}'.
            '.social-btn a[href*="vimeo"]{background-image:url(data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/sample/import_files/vimeo.png')).')}'.
            '.social-btn a[href*="instagram"]{background-image:url(data:image/png;base64,'.base64_encode(file_get_contents(__DIR__.'/sample/import_files/instagram.png')).')}',
        );

        // https://github.com/matthiasmullie/minify/issues/68
        $tests[] = array(
            __DIR__.'/sample/external_imports/issue68.css',
            '@import url(http://localhost/file.css);body{background:green}',
        );

        // https://github.com/matthiasmullie/minify/issues/67
        $tests[] = array(
            'body { }
p { color: #fff; }',
            'p{color:#fff}',
        );
        $tests[] = array(
            'body {}
p { color: #fff; }
h1 { }
strong { color: red; }',
            'p{color:#fff}strong{color:red}',
        );

        // https://github.com/matthiasmullie/minify/issues/74
        $tests[] = array(
            "@media only screen and (-webkit-min-device-pixel-ratio: 1.5),
only screen and (min--moz-device-pixel-ratio: 1.5),
only screen and (min-device-pixel-ratio: 1.5) {

    #fancybox-loading,.fancybox-close,.fancybox-prev span,.fancybox-next span {
        background-image: url('/path/to/image.png');
        background-size: 44px 152px;
    }

    #fancybox-loading div {
        background-image: url('/path/to/image.gif');
        background-size: 24px 24px;
    }
}",
            '@media only screen and (-webkit-min-device-pixel-ratio:1.5),only screen and (min--moz-device-pixel-ratio:1.5),only screen and (min-device-pixel-ratio:1.5){#fancybox-loading,.fancybox-close,.fancybox-prev span,.fancybox-next span{background-image:url(/path/to/image.png);background-size:44px 152px}#fancybox-loading div{background-image:url(/path/to/image.gif);background-size:24px 24px}}',
        );

        // https://github.com/matthiasmullie/minify/issues/92
        $tests[] = array(
            '@media (min-width:320px) {
    /* smartphones, iPhone, portrait 480x320 phones */
    p {
        background-color: red;
    }
}
@media (min-width:1025px) {
    /* big landscape tablets, laptops, and desktops */
    /* LEFT EMPTY OF ANY SELECTORS */
}
@media (min-width:1281px) {
    /* hi-res laptops and desktops */
    p {
        background-color: blue;
    }
}',
            '@media (min-width:320px){p{background-color:red}}@media (min-width:1281px){p{background-color:blue}}',
        );

        // https://github.com/matthiasmullie/minify/issues/103
        $tests[] = array(
            'background:url(http://example.com/test.png);',
            'background:url(http://example.com/test.png);',
        );

        // https://github.com/matthiasmullie/minify/issues/104
        $tests[] = array(
            '@media screen and (min-aspect-ratio: 16/9) { p { color: red } }',
            '@media screen and (min-aspect-ratio:16/9){p{color:red}}',
        );

        // https://github.com/matthiasmullie/minify/issues/107
        $tests[] = array(
            '@import "https://font.googleapis.com/css?family=RobotoDraft:regular&lang=en";',
            '@import "https://font.googleapis.com/css?family=RobotoDraft:regular&lang=en";',
        );
        $tests[] = array(
            '@import "https://font.googleapis.com/css?family=RobotoDraft:regular&amp;lang=en";',
            '@import "https://font.googleapis.com/css?family=RobotoDraft:regular&amp;lang=en";',
        );

        // https://github.com/matthiasmullie/minify/issues/109
        $tests[] = array(
            'p{font-weight:bold;}',
            'p{font-weight:700}',
        );
        $tests[] = array(
            'p {
    font-weight : normal
}',
            'p{font-weight:400}',
        );
        $tests[] = array(
            'p{background-color:#ff0000}',
            'p{background-color:red}',
        );
        $tests[] = array(
            'p{color:#F0E68C}',
            'p{color:khaki}',
        );

        // https://github.com/matthiasmullie/minify/issues/137
        $tests[] = array(
            'p{width: calc(35% - 0px);}',
            'p{width:calc(35%)}',
        );
        $tests[] = array(
            'p{width: calc(0px + 35%);}',
            'p{width:calc(35%)}',
        );
        $tests[] = array(
            'p{width: calc(0px - 35%);}',
            'p{width:calc(-35%)}',
        );
        $tests[] = array(
            'p{width: calc(0px + 35% - 0px);}',
            'p{width:calc(35%)}',
        );
        $tests[] = array(
            'p{width: calc(5% + 0px + 35% - 0px + 5%);}',
            'p{width:calc(5% + 35% + 5%)}',
        );
        $tests[] = array(
            'p{width:calc(35% + (10% + 0px))}',
            'p{width:calc(35% + (10%))}',
        );
        $tests[] = array(
            'p{width:calc(35% + (10% + 0px + 10%))}',
            'p{width:calc(35% + (10% + 10%))}',
        );

        // https://github.com/matthiasmullie/minify/issues/139
        $tests[] = array(
            __DIR__.'/sample/line_endings/lf/parent.css',
            'p{color:green}body{color:red}',
        );
        $tests[] = array(
            __DIR__.'/sample/line_endings/cr/parent.css',
            'p{color:green}body{color:red}',
        );
        $tests[] = array(
            __DIR__.'/sample/line_endings/crlf/parent.css',
            'p{color:green}body{color:red}',
        );

        // https://github.com/matthiasmullie/minify/issues/145
        $tests[] = array(
            '/* some imports */
@import \'./css1.css\';
@import url(\'https://www.google.com/main.css\');

.empty-rule{
}

body{
     background: white;
}',
            '@import "./css1.css";@import url(\'https://www.google.com/main.css\');body{background:white}',
        );

        // https://github.com/matthiasmullie/minify/commit/3253a81d07cd01afcb651e309900d8ad58a052da#commitcomment-19223603
        $tests[] = array(
            'p{border: 1px solid #f00000;}',
            'p{border:1px solid #f00000}',
        );

        // https://github.com/matthiasmullie/minify/issues/149
        $tests[] = array(
            ".headerWrapper{
    background:url(/_media/images/general/bg_top_right.png) no-repeat;
    background:url('') no-repeat;
    background-position:100% 0;
    width:100%;
    min-height: 0;
}",
            ".headerWrapper{background:url(/_media/images/general/bg_top_right.png) no-repeat;background:url('') no-repeat;background-position:100% 0;width:100%;min-height:0}",
        );
        $tests[] = array(
            ".headerWrapper{
    /*background:url(/_media/images/general/bg_top_right.png) no-repeat;*/
    background:url('') no-repeat;
    background-position:100% 0;
    width:100%;
    min-height: 0;
}",
            ".headerWrapper{background:url('') no-repeat;background-position:100% 0;width:100%;min-height:0}",
        );

        // https://github.com/matthiasmullie/minify/issues/150
        $tests[] = array(
            '.text { box-shadow: 0 0 1em -0.5em #000; }',
            '.text{box-shadow:0 0 1em -.5em #000}',
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
            '@import url(stylesheet.css);',
        );
        $tests[] = array(
            $source.'/../source/relative.css',
            $target.'/target/relative.css',
            '@import url(../stylesheet.css);',
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
            "@font-face{font-family:'blackcat';src:url(../webfont/blackcat.eot);src:url(../webfont/blackcat.eot?#iefix) format('embedded-opentype'),url(../webfont/blackcat.svg#blackcat) format('svg'),url(../webfont/blackcat.woff) format('woff'),url(../webfont/blackcat.ttf) format('truetype');font-weight:400;font-style:normal}",
        );
        $tests[] = array(
            $source.'/issue39.css',
            $target.'/issue39.css',
            // relative paths should remain untouched
            "@font-face{font-family:'blackcat';src:url(../webfont/blackcat.eot);src:url(../webfont/blackcat.eot?#iefix) format('embedded-opentype'),url(../webfont/blackcat.svg#blackcat) format('svg'),url(../webfont/blackcat.woff) format('woff'),url(../webfont/blackcat.ttf) format('truetype');font-weight:400;font-style:normal}",
        );
        $tests[] = array(
            $source.'/issue39.css',
            $target.'/target/issue39.css',
            // relative paths should have changed
            "@font-face{font-family:'blackcat';src:url(../../webfont/blackcat.eot);src:url(../../webfont/blackcat.eot?#iefix) format('embedded-opentype'),url(../../webfont/blackcat.svg#blackcat) format('svg'),url(../../webfont/blackcat.woff) format('woff'),url(../../webfont/blackcat.ttf) format('truetype');font-weight:400;font-style:normal}",
        );

        // https://github.com/forkcms/forkcms/issues/1121
        $tests[] = array(
            $source.'/nested/nested.css',
            $target.'/nested.css',
            '@import url(stylesheet.css);',
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

        // https://github.com/matthiasmullie/minify/issues/77#issuecomment-172844822
        $tests[] = array(
            $source.'/get-params.css',
            $target.'/get-params.css',
            '@import url(../source/some-file.css?some=param);',
        );

        $sourceRelative = 'tests/css/sample/convert_relative_path/source';
        $targetRelative = 'tests/css/sample/convert_relative_path/target';

        // from and/or to are relative links
        $tests[] = array(
            $sourceRelative.'/relative.css',
            $target.'/relative.css',
            '@import url(stylesheet.css);',
        );
        // note: relative target only works if the file already exists: it has
        // to be able to realpath()
        $tests[] = array(
            $source.'/relative.css',
            $targetRelative.'/relative.css',
            '@import url(stylesheet.css);',
        );
        $tests[] = array(
            $sourceRelative.'/relative.css',
            $targetRelative.'/relative.css',
            '@import url(stylesheet.css);',
        );

        $source = __DIR__.'/sample/symlink';
        $target = __DIR__.'/sample/symlink/target';
        $sourceRelative = 'tests/css/sample/symlink';
        $targetRelative = 'tests/css/sample/symlink/target';

        // import symlinked files: relative, absolute & mix
        $tests[] = array(
            $source.'/import_symlinked_file.css',
            $target.'/import_symlinked_file.css',
            '',
        );
        $tests[] = array(
            $sourceRelative.'/import_symlinked_file.css',
            $targetRelative.'/import_symlinked_file.css',
            '',
        );
        $tests[] = array(
            $source.'/import_symlinked_file.css',
            $targetRelative.'/import_symlinked_file.css',
            '',
        );
        $tests[] = array(
            $sourceRelative.'/import_symlinked_file.css',
            $target.'/import_symlinked_file.css',
            '',
        );

        // move symlinked files: relative, absolute & mix
        $tests[] = array(
            $source.'/move_symlinked_file.css',
            $target.'/move_symlinked_file.css',
            'body{background-url:url(../assets/symlink.bmp)}',
        );
        $tests[] = array(
            $sourceRelative.'/move_symlinked_file.css',
            $targetRelative.'/move_symlinked_file.css',
            'body{background-url:url(../assets/symlink.bmp)}',
        );
        $tests[] = array(
            $source.'/move_symlinked_file.css',
            $targetRelative.'/move_symlinked_file.css',
            'body{background-url:url(../assets/symlink.bmp)}',
        );
        $tests[] = array(
            $source.'/move_symlinked_file.css',
            $targetRelative.'/move_symlinked_file.css',
            'body{background-url:url(../assets/symlink.bmp)}',
        );

        // import symlinked folders: relative, absolute & mix
        $tests[] = array(
            $source.'/import_symlinked_folder.css',
            $target.'/import_symlinked_folder.css',
            '',
        );
        $tests[] = array(
            $sourceRelative.'/import_symlinked_folder.css',
            $targetRelative.'/import_symlinked_folder.css',
            '',
        );
        $tests[] = array(
            $source.'/import_symlinked_folder.css',
            $targetRelative.'/import_symlinked_folder.css',
            '',
        );
        $tests[] = array(
            $sourceRelative.'/import_symlinked_folder.css',
            $target.'/import_symlinked_folder.css',
            '',
        );

        // move symlinked folders: relative, absolute & mix
        $tests[] = array(
            $source.'/move_symlinked_folder.css',
            $target.'/move_symlinked_folder.css',
            'body{background-url:url(../assets_symlink/asset.bmp)}',
        );
        $tests[] = array(
            $sourceRelative.'/move_symlinked_folder.css',
            $targetRelative.'/move_symlinked_folder.css',
            'body{background-url:url(../assets_symlink/asset.bmp)}',
        );
        $tests[] = array(
            $source.'/move_symlinked_folder.css',
            $targetRelative.'/move_symlinked_folder.css',
            'body{background-url:url(../assets_symlink/asset.bmp)}',
        );
        $tests[] = array(
            $sourceRelative.'/move_symlinked_folder.css',
            $target.'/move_symlinked_folder.css',
            'body{background-url:url(../assets_symlink/asset.bmp)}',
        );

        // https://github.com/matthiasmullie/minify/issues/100
        $tests[] = array(
            array(
                // key is a bogus path
                '/var/www/app/www/turysta/something/css/original.css' => '.ui-icon { width: 16px; height: 16px; background-image: url(/_lay/jqueryui/ui-icons_72b42d_256x240.png); }',
            ),
            '/var/www/app/www/turysta/shared/_cache/_css/minified.css',
            '.ui-icon{width:16px;height:16px;background-image:url(/_lay/jqueryui/ui-icons_72b42d_256x240.png)}',
        );

        return $tests;
    }
}
