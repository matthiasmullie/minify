# Minify

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

### minify($path, $options)
This will minify the files' content, save the result to $path and return the resulting content.
If the $path parameter is false, the result will not be written anywhere. CAUTION: Only use this for "simple" CSS: if no target directory ($path) is known, relative uris to e.g. images can not be fixed!
The $options parameter allow you to define the exact minifying actions that should be performed.

    $minifier->minify('/target/path.css', Minify\CSS::ALL);

## Options
Both CSS & JS minifiers accept, as 2nd argument to ->minify, options to finegrain the exact minifying actions that should happen.
Multiple options can be combined using |, like:

    $minifier->minify($path, Minify\CSS::COMBINE_IMPORTS | Minify\CSS::IMPORT_FILES);

### CSS
* **Minify\CSS::ALL**
Applies all below options
* **Minify\CSS::STRIP_COMMENTS**
Strips /* CSS comments */
* **Minify\CSS::STRIP_WHITESPACE**
Strips redundant whitespace
* **Minify\CSS::SHORTEN_HEX**
Shortens hexadecimal color codes where applicable (e.g. #000000 -> #000)
* **Minify\CSS::COMBINE_IMPORTS**
Will include @import'ed files into the original document, saving connections to fetch the imported files.
This will make sure that relative paths (to e.g. images, other @imports, ..) in @import'ed files are adjusted to the correct location relative to the original file.
* **Minify\CSS::IMPORT_FILES**
This will import (small) referenced images & woff's as data-uri into the CSS, thus saving connections to fetch the images.

### JS
* **Minify\JS::ALL**
Applies all below options
* **Minify\JS::STRIP_COMMENTS**
Strips /* JS */ // comments
* **Minify\JS::STRIP_WHITESPACE**
Strips redundant whitespace

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

    // minify (all options) & write to file
    $minifier->minify('/target/path.css', Minify\CSS::ALL);

## License
Minify is [MIT](http://opensource.org/licenses/MIT) licensed.
