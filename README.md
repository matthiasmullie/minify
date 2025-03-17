# Minify

[![Build status](https://img.shields.io/github/actions/workflow/status/matthiasmullie/minify/test.yml?branch=master&style=flat-square)](https://github.com/matthiasmullie/minify/actions/workflows/test.yml)
[![Code coverage](http://img.shields.io/codecov/c/gh/matthiasmullie/minify?style=flat-square)](https://codecov.io/gh/matthiasmullie/minify)
[![Latest version](http://img.shields.io/packagist/v/matthiasmullie/minify?style=flat-square)](https://packagist.org/packages/matthiasmullie/minify)
[![Downloads total](http://img.shields.io/packagist/dt/matthiasmullie/minify?style=flat-square)](https://packagist.org/packages/matthiasmullie/minify)
[![License](http://img.shields.io/packagist/l/matthiasmullie/minify?style=flat-square)](https://github.com/matthiasmullie/minify/blob/master/LICENSE)


Removes whitespace, strips comments, combines files (incl. `@import` statements and small assets in CSS files), and optimizes/shortens a few common programming patterns, such as:

**JavaScript**
* `object['property']` -> `object.property`
* `true`, `false` -> `!0`, `!1`
* `while(true)` -> `for(;;)`

**CSS**
* `@import url("http://path")` -> `@import "http://path"`
* `#ff0000`, `#ff00ff` -> `red`, `#f0f`
* `-0px`, `50.00px` -> `0`, `50px`
* `bold` -> `700`
* `p {}` -> removed

And it comes with a huge test suite.


## Usage

### CSS

```php
use MatthiasMullie\Minify;

$sourcePath = '/path/to/source/css/file.css';
$minifier = new Minify\CSS($sourcePath);

// we can even add another file, they'll then be
// joined in 1 output file
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
```

### JS

```php
// just look at the CSS example; it's exactly the same, but with the JS class & JS files :)
```


### CLI

```
vendor/bin/minify /path/to/source/*.css -o /path/to/minified/css/file.css /path/to/source/*.js -o /path/to/minified/js/file.js
```

Multiple source files can be passed, both CSS and JS. Define an output file for each file type with the `--output` or `-o` option. If an output file is not defined, the minified contents will be sent to `STDOUT`.

You can also have each input file generate it's own minified file rather than having them be combined into a single file by defining an output path with an asterisk (`*`) that will be replaced with the input filename (ex. `-o "/path/to/minified/js/*.min.js"`), however you'll want to make sure that you wrap the path in quotes so that your terminal doesn't try to parse the path itself.

#### Options

  * `--import-ext`/`-e` - Defines an extension that will be imported in CSS (ex. `-e "gif|data:image/gif" -e "png|data:image/png"`)
  * `--gzip`/`-g` - `gzencode()`s the minified content
  * `--max-import-size`/`-m` - The maximum import size (in kB) for CSS
  * `--output`/`-o` - The file path to write the minified content to
  * `--help`/`-h` - Displays information about the CLI tool (you can also pass `help` as the first argument)


## Methods

Available methods, for both CSS & JS minifier, are:

### __construct(/* overload paths */)

The object constructor accepts 0, 1 or multiple paths of files, or even complete CSS/JS content, that should be minified.
All CSS/JS passed along, will be combined into 1 minified file.

```php
use MatthiasMullie\Minify;
$minifier = new Minify\JS($path1, $path2);
```

### add($path, /* overload paths */)

This is roughly equivalent to the constructor.

```php
$minifier->add($path3);
$minifier->add($js);
```

### minify($path)

This will minify the files' content, save the result to $path and return the resulting content.
If the $path parameter is omitted, the result will not be written anywhere.

*CAUTION: If you have CSS with relative paths (to imports, images, ...), you should always specify a target path! Then those relative paths will be adjusted in accordance with the new path.*

```php
$minifier->minify('/target/path.js');
```

### gzip($path, $level)

Minifies and optionally saves to a file, just like `minify()`, but it also `gzencode()`s the minified content.

```php
$minifier->gzip('/target/path.js');
```

### setMaxImportSize($size) *(CSS only)*

The CSS minifier will automatically embed referenced files (like images, fonts, ...) into the minified CSS, so they don't have to be fetched over multiple connections.

However, for really large files, it's likely better to load them separately (as it would increase the CSS load time if they were included.)

This method allows the max size of files to import into the minified CSS to be set (in kB). The default size is 5.

```php
$minifier->setMaxImportSize(10);
```

### setImportExtensions($extensions) *(CSS only)*

The CSS minifier will automatically embed referenced files (like images, fonts, ...) into minified CSS, so they don't have to be fetched over multiple connections.

This methods allows the type of files to be specified, along with their data:mime type.

The default embedded file types are gif, png, jpg, jpeg, svg, apng, avif, webp, woff and woff2.

```php
$extensions = array(
    'gif' => 'data:image/gif',
    'png' => 'data:image/png',
);

$minifier->setImportExtensions($extensions);
```


## Installation

Simply add a dependency on `matthiasmullie/minify` to your composer.json file if you use [Composer](https://getcomposer.org/) to manage the dependencies of your project:

```sh
composer require matthiasmullie/minify
```

Although it's recommended to use Composer, you can actually [include these files](https://github.com/matthiasmullie/minify/issues/83) anyway you want.


## License

Minify is [MIT](http://opensource.org/licenses/MIT) licensed.
