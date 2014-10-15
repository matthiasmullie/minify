```php
use MatthiasMullie\Minify;

$sourcePath = '/path/to/source/css/file.css';
$minifier = new Minify\css($file);

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
