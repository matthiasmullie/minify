<?php

require __DIR__.'/vendor/autoload.php';

use MatthiasMullie\Minify;

$minifier = new Minify\CSS();

/**
 * @return array [input, expected result]
 */
function dataProviderPaths()
{
    $tests = array();

    $source = __DIR__.'/tests/css/sample/convert_relative_path/source';
    $target = __DIR__.'/tests/css/sample/convert_relative_path/target';

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
    $tests = [];
    $source = __DIR__.'/tests/css/sample/symlink';
    $target = __DIR__.'/tests/css/sample/symlink/target';
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

    return $tests;
}

/**
 * Test conversion of relative paths, provided by dataProviderPaths.
 *
 * @test
 * @dataProvider dataProviderPaths
 */
function convertRelativePath($source, $target, $expected)
{
    global $minifier;

    $source = (array) $source;
    foreach ($source as $path => $css) {
        $minifier->add($css);

        // $source also accepts an array where the key is a bogus path
        if (is_string($path)) {
            $object = new ReflectionObject($minifier);
            $property = $object->getProperty('data');
            $property->setAccessible(true);
            $data = $property->getValue($minifier);

            // keep content, but make it appear from the given path
            $data[$path] = array_pop($data);
            $property->setValue($minifier, $data);
            $property->setAccessible(false);
        }
    }

    $result = $minifier->minify($target);

    echo 'expected -> ' . $expected;
    echo "\n";
    echo 'result -> ' . $result;
    //$this->assertEquals($expected, $result);
}

$paths = dataProviderPaths();

$index = 0;
foreach($paths as $path) {
    echo ++$index . ' : ';echo "\n";
    convertRelativePath($path[0], $path[1], $path[2]);
    echo "\n";echo "\n";echo "\n";
}