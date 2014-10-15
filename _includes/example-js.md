```php
use MatthiasMullie\Minify;

$sourcePath = '/path/to/source/css/file.js';
$minifier = new Minify\JS($file);

// we can even add another file, they'll then be
// joined in 1 output file
$sourcePath2 = '/path/to/second/source/css/file.js';
$minifier->add($sourcePath2);

// or we can just add plain js
$js = 'var test = 1';
$minifier->add($js);

// save minified file to disk
$minifiedPath = '/path/to/minified/js/file.js';
$minifier->minify($minifiedPath);

// or just output the content
echo $minifier->minify();
```
