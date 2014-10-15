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
If the $path parameter is false, the result will not be written anywhere.

*CAUTION: If you have CSS with relative paths (to imports, images, ...), you should always specify a target path! Then those relative paths will be adjusted in accordance with the new path.*

```php
$minifier->minify('/target/path.js');
```

### setMaxImportSize($size) *(CSS only)*

The CSS minifier will automatically embed referenced files (like images, fonts, ...) into the CSS file, so they don't have to be fetched over multiple connections.

However, for really large files, it's likely better to load them separately (as it would increase the CSS file load time if they were included.)

This method allows the max size of files to import into the CSS file to be set (in kB). The default size is 5.

```php
$minifier->setMaxImportSize(10);
```

### setImportExtensions(extensions) *(CSS only)*

The CSS minifier will automatically embed referenced files (like images, fonts, ...) into the CSS file, so they don't have to be fetched over multiple connections.

This methods allows the type of files to be specified, along with there data:mime type.

The default embedded file types are gif, png, jpg, jpeg, svg & woff.

```php
$extensions = array(
    'gif' => 'data:image/gif',
    'png' => 'data:image/png',
);

$minifier->setImportExtensions($extensions);
```
