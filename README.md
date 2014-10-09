# Minify

[![Build status](https://api.travis-ci.org/matthiasmullie/minify.svg?branch=master)](https://travis-ci.org/matthiasmullie/minify)
[![Latest version](http://img.shields.io/packagist/v/matthiasmullie/minify.svg)](https://packagist.org/packages/matthiasmullie/minify)
[![Downloads total](http://img.shields.io/packagist/dt/matthiasmullie/minify.svg)](https://packagist.org/packages/matthiasmullie/minify)
[![License](http://img.shields.io/packagist/l/matthiasmullie/minify.svg)](https://github.com/matthiasmullie/minify/blob/master/LICENSE)


## Usage

### CSS

    use MatthiasMullie\Minify;

    $sourcePath = '/path/to/source/css/file.css';
    $minifier = new Minify($file);

    // we can even add another file, they'll then be joined in 1 output file
    $sourcePath2 = '/path/to/second/source/css/file.css';
    $minifier->add($sourcePath2);

    // or we can just add plain CSS
    $css = 'body { color: #000000; }';
    $minifier->add($css);

    // save minified file to disk
    $minifiedPath = '/path/to/minified/css/file.css';
    $minifier->minify($minifiedPath);

    // or just output the content
    echo $minifier->minify();

### JS

    // just look at the CSS example; it's exactly the same, but with JS files :)


## Methods
Available methods, for both CSS & JS minifier, are:

### __construct(/* overload paths */)

The object constructor accepts 0, 1 or multiple paths of files, or even complete CSS/JS content, that should be minified.
All CSS/JS passed along, will be combined into 1 minified file.

    use MatthiasMullie\Minify;
    $minifier = new Minify\JS($path1, $path2);

### add($path, /* overload paths */)

This is roughly equivalent to the constructor.

    $minifier->add($path3);
    $minifier->add($js);

### minify($path)

This will minify the files' content, save the result to $path and return the resulting content.
If the $path parameter is false, the result will not be written anywhere.

*CAUTION: If you have CSS with relative paths (to imports, images, ...), you should always specify a target path! Then those relative paths will be adjusted in accordance with the new path.*

    $minifier->minify('/target/path.js');


## Installation

Simply add a dependency on matthiasmullie/minify to your project's composer.json file if you use [Composer](https://getcomposer.org/) to manage the dependencies of your project:

    {
        "require": {
            "matthiasmullie/minify": "1.3.*"
        }
    }

Although it's recommended to use Composer, you can actually include these files anyway you want.


## License
Minify is [MIT](http://opensource.org/licenses/MIT) licensed.
