# Minify

[![Build status](https://api.travis-ci.org/matthiasmullie/minify.svg?branch=master)](https://travis-ci.org/matthiasmullie/minify)
[![Latest version](http://img.shields.io/packagist/v/matthiasmullie/minify.svg)](https://packagist.org/packages/matthiasmullie/minify)
[![Downloads total](http://img.shields.io/packagist/dt/matthiasmullie/minify.svg)](https://packagist.org/packages/matthiasmullie/minify)
[![License](http://img.shields.io/packagist/l/matthiasmullie/minify.svg)](https://github.com/matthiasmullie/minify/blob/master/LICENSE)

## Methods
Available methods, for both CSS & JS minifier, are:

### __construct(/* overload paths */)
The object constructor accepts 0, 1 or multiple paths of files, or even complete CSS content, that should be minified.
All CSS passed along, will be combined into 1 minified file.

    use MatthiasMullie\Minify;
    $minifier = new Minify\CSS($path1, $path2);

### add($path, /* overload paths */)
This is roughly equivalent to the constructor.

    $minifier->add($path3);
    $minifier->add($css);

### minify($path)
This will minify the files' content, save the result to $path and return the resulting content.
If the $path parameter is false, the result will not be written anywhere. CAUTION: Only use this for "simple" CSS: if no target directory ($path) is known, relative uris to e.g. images can not be fixed!

    $minifier->minify('/target/path.css');


## Example usage
    $file1 = '/path/to/file1.css';
    $file2 = '/yet/another/path/to/file2.css';
    $file3 = '/and/another/path/to/file3.css';
    $css = 'body { color: #000000; }';

    // constructor can be overloaded with multiple files
    $minifier = new Minify\CSS($file1, $file2);

    // or files can be added individually
    $minifier->add($file3);

    // or even css content can be loaded
    $minifier->add($css);

    // minify & write to file
    $minifier->minify('/target/path.css');

## License
Minify is [MIT](http://opensource.org/licenses/MIT) licensed.

## CLI script
[Baki Goxhaj](https://github.com/banago) developed a [CLI tool](https://github.com/banago/CLI-Minify) using this library - could be useful to automate minification of your project's CSS/JS files in e.g. some build or deployment script.
