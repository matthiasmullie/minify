<?php
namespace MatthiasMullie\Minify;

/**
 * CSS minifier.
 *
 * Please report bugs on https://github.com/matthiasmullie/minify/issues
 *
 * @author Matthias Mullie <minify@mullie.eu>
 * @author Tijs Verkoyen <minify@verkoyen.eu>
 *
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved.
 * @license MIT License
 */
class CSS extends Minify
{
    /**
     * @var int
     */
    protected $maxImportSize = 5;

    /**
     * @var string[]
     */
    protected $importExtensions = array(
        'gif' => 'data:image/gif',
        'png' => 'data:image/png',
        'jpg' => 'data:image/jpg',
        'jpeg' => 'data:image/jpeg',
        'svg' => 'data:image/svg+xml',
        'woff' => 'data:application/x-font-woff',
    );

    /**
     * Set the maximum size if files to be imported.
     *
     * Files larger than this size (in kB) will not be imported into the CSS.
     * Importing files into the CSS as data-uri will save you some connections,
     * but we should only import relatively small decorative images so that our
     * CSS file doesn't get too bulky.
     *
     * @param int $size Size in kB
     */
    public function setMaxImportSize($size) {
        $this->maxImportSize = $size;
    }

    /**
     * Set the type of extensions to be imported into the CSS (to save network
     * connections).
     * Keys of the array should be the file extensions & respective values
     * should be the data type.
     *
     * @param string[] $extensions Array of file extensions
     */
    public function setImportExtensions(array $extensions) {
        $this->importExtensions = $extensions;
    }

    /**
     * Combine CSS from import statements.
     * @import's will be loaded and their content merged into the original file,
     * to save HTTP requests.
     *
     * @param  string $source  The file to combine imports for.
     * @param  string $content The CSS content to combine imports for.
     * @return string
     */
    protected function combineImports($source, $content)
    {
        // the regex to match import statements
        $importRegex = '/

            # import statement
            @import

            # whitespace
            \s+

                # (optional) open url()
                (?P<url>url\()?

                    # open path enclosure
                    (?P<quotes>["\']?)

                        # fetch path
                        (?P<path>

                            # do not fetch data uris or external sources
                            (?!(
                                ["\']?
                                (data|https?):
                            ))

                            .+?
                        )

                    # close path enclosure
                    (?P=quotes)

                # (optional) close url()
                (?(url)\))

                # (optional) trailing whitespace
                \s*

                # (optional) media statement(s)
                (?P<media>.*?)

                # (optional) trailing whitespace
                \s*

            # (optional) closing semi-colon
            ;?

            /ix';

        // find all relative imports in css (for now we don't support imports
        // with media, and imports should use url(xxx))
        if (preg_match_all($importRegex, $content, $matches, PREG_SET_ORDER)) {
            $search = array();
            $replace = array();

            // loop the matches
            foreach ($matches as $match) {
                // get the path for the file that will be imported
                $importPath = dirname($source) . '/' . $match['path'];

                // only replace the import with the content if we can grab the
                // content of the file
                if (@file_exists($importPath) && is_file($importPath)) {
                    // grab content
                    $importContent = @file_get_contents($importPath);

                    // fix relative paths
                    $importContent = $this->move($importPath, $source, $importContent);

                    // check if this is only valid for certain media
                    if ($match['media']) {
                        $importContent = '@media ' . $match['media'] . '{' . "\n" . $importContent . "\n" . '}';
                    }

                    // add to replacement array
                    $search[] = $match[0];
                    $replace[] = $importContent;
                }
            }

            // replace the import statements
            $content = str_replace($search, $replace, $content);

            // ge recursive (if imports have occurred)
            if ($search) {
                $content = $this->combineImports($source, $content);
            }
        }

        return $content;
    }

    /**
     * Convert relative paths based upon 1 path to another.
     *
     * E.g.
     * ../images/img.gif (relative to /home/forkcms/frontend/core/layout/css)
     * should become:
     * ../../core/layout/images/img.gif (relative to
     * /home/forkcms/frontend/cache/minified_css)
     *
     * @param  string $path The relative path that needs to be converted.
     * @param  string $from The original base path.
     * @param  string $to   The new base path.
     * @return string The new relative path.
     */
    protected function convertRelativePath($path, $from, $to)
    {
        // make sure we're dealing with directories
        $from = @is_file($from) ? dirname($from) : $from;
        $to = @is_file($to) ? dirname($to) : $to;

        // deal with different operating systems' directory structure
        $path = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $path), '/');
        $from = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $from), '/');
        $to = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $to), '/');

        // if we're not dealing with a relative path, just return absolute
        if (strpos($path, '/') === 0) {
            return $path;
        }

        /*
         * Example:
         * $path = ../images/img.gif
         * $from = /home/forkcms/frontend/cache/compiled_templates/../../core/layout/css
         * $to = /home/forkcms/frontend/cache/minified_css
         */

        // normalize paths
        do {
            $path = preg_replace('/[^\.\.\/]+?\/\.\.\//', '', $path, -1, $count);
        } while ($count);
        do {
            $from = preg_replace('/[^\/]+?\/\.\.\//', '', $from, -1, $count);
        } while ($count);
        do {
            $to = preg_replace('/[^\/]+?\/\.\.\//', '', $to, -1, $count);
        } while ($count);

        /*
         * At this point:
         * $path = ../images/img.gif
         * $from = /home/forkcms/frontend/core/layout/css
         * $to = /home/forkcms/frontend/cache/minified_css
         */

        // resolve path the relative url is based upon
        do {
            $path = preg_replace('/^\.\.\//', '', $path, 1, $count);

            // for every level up, adjust dirname
            if ($count) {
                $from = dirname($from);
            }
        } while ($count);

        /*
         * At this point:
         * $path = images/img.gif
         * $from = /home/forkcms/frontend/core/layout
         * $to = /home/forkcms/frontend/cache/minified_css
         */

        // compare paths & strip identical parents
        $from = explode('/', $from);
        $to = explode('/', $to);
        foreach ($from as $i => $chunk) {
            if (isset($to[$i]) && $from[$i] == $to[$i]) {
                unset($from[$i], $to[$i]);
            } else {
                break;
            }
        }

        /*
         * At this point:
         * $path = images/img.gif
         * $from = array('core', 'layout')
         * $to = array('cache', 'minified_css')
         */

        // add .. for every directory that needs to be traversed for new path
        $new = str_repeat('../', count($to));
        $new = $new ?: '/';

        /*
         * At this point:
         * $path = images/img.gif
         * $from = array('core', 'layout')
         * $to = *no longer matters*
         * $new = ../../
         */

        // add path, relative from this point, to traverse to image
        $new .= implode('/', $from);

        // if $from contained no elements, we still have a redundant trailing /
        if (!$from) {
            $new = rtrim($new, '/');
        }

        /*
         * At this point:
         * $path = images/img.gif
         * $from = *no longer matters*
         * $to = *no longer matters*
         * $new = ../../core/layout
         */

        // add remaining path
        $new .= '/' . $path;

        /*
         * At this point:
         * $path = *no longer matters*
         * $from = *no longer matters*
         * $to = *no longer matters*
         * $new = ../../core/layout/images/img.gif
         */

        // Tada!
        return $new;
    }

    /**
     * Import files into the CSS, base64-ized.
     * @url(image.jpg) images will be loaded and their content merged into the
     * original file, to save HTTP requests.
     *
     * @param  string $source  The file to import files for.
     * @param  string $content The CSS content to import files for.
     * @return string
     */
    protected function importFiles($source, $content)
    {
        $extensions = array_keys($this->importExtensions);
        $regex = '/url\((["\']?)((?!["\']?data:).*?\.(' . implode('|', $extensions) . '))\\1\)/i';
        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            $search = array();
            $replace = array();

            // loop the matches
            foreach ($matches as $match) {
                // get the path for the file that will be imported
                $path = $match[2];
                $path = dirname($source) . '/' . $path;
                $extension = $match[3];

                // only replace the import with the content if we're able to get
                // the content of the file, and it's relatively small
                $import = @file_exists($path);
                $import &= is_file($path);
                $import &= filesize($path) <= $this->maxImportSize * 1024;
                if (!$import) {
                    continue;
                }

                // grab content
                $importContent = @file_get_contents($path);

                // base-64-ize
                $importContent = base64_encode($importContent);

                // build replacement
                $search[] = $match[0];
                $replace[] = 'url(' . $this->importExtensions[$extension] . ';base64,' . $importContent  . ')';
            }

            // replace the import statements
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }

    /**
     * Minify the data.
     * Perform CSS optimizations.
     *
     * @param  string[optional] $path Path to write the data to.
     * @return string           The minified data.
     */
    public function minify($path = null)
    {
        $content = '';

        // loop files
        foreach ($this->data as $source => $css) {
            // if we'll save to a new path, we'll have to fix the relative paths
            if ($source !== 0) {
                $css = $this->move($source, $path, $css);
            }

            // combine css
            $content .= $css;
        }

        $content = $this->combineImports($path, $content);
        $content = $this->shortenHex($content);
        $content = $this->importFiles($path, $content);
        $content = $this->stripComments($content);
        $content = $this->stripWhitespace($content);

        // save to path
        if ($path !== null) {
            $this->save($content, $path);
        }

        return $content;
    }

    /**
     * Moving a css file should update all relative urls.
     * Relative references (e.g. ../images/image.gif) in a certain css file,
     * will have to be updated when a file is being saved at another location
     * (e.g. ../../images/image.gif, if the new CSS file is 1 folder deeper)
     *
     * @param  string $source      The file to update relative urls for.
     * @param  string $destination The path the data will be written to.
     * @param  string $content     The CSS content to update relative urls for.
     * @return string
     */
    protected function move($source, $destination, $content)
    {
        /*
         * Relative path references will usually be enclosed by url(). @import
         * is an exception, where url() is not necessary around the path (but is
         * allowed).
         * This *could* be 1 regular expression, where both regular expressions
         * in this array are on different sides of a |. But we're using named
         * patterns in both regexes, the same name on both regexes. This is only
         * possible with a (?J) modifier, but that only works after a fairly
         * recent PCRE version. That's why I'm doing 2 separate regular
         * expressions & combining the matches after executing of both.
         */
        $regexes = array(
            // url(xxx)
            '/
            # open url()
            url\(

                # open path enclosure
                (?P<quotes>["\'])?

                    # fetch path
                    (?P<path>

                        # do not fetch data uris or external sources
                        (?!(
                            ["\']?
                            (data|https?):
                        ))

                        .+?
                    )

                # close path enclosure
                (?(quotes)(?P=quotes))

            # close url()
            \)

            /ix',

            // @import "xxx"
            '/
            # import statement
            @import

            # whitespace
            \s+

                # we don\'t have to check for @import url(), because the
                # condition above will already catch these

                # open path enclosure
                (?P<quotes>["\'])

                    # fetch path
                    (?P<path>

                        # do not fetch data uris or external sources
                        (?!(
                            ["\']?
                            (data|https?):
                        ))

                        .+?
                    )

                # close path enclosure
                (?P=quotes)

            /ix'
        );

        // find all relative urls in css
        $matches = array();
        foreach ($regexes as $regex) {
            if (preg_match_all($regex, $content, $regexMatches, PREG_SET_ORDER)) {
                $matches = array_merge($matches, $regexMatches);
            }
        }

        $search = array();
        $replace = array();

        // loop all urls
        foreach ($matches as $match) {
            // determine if it's a url() or an @import match
            $type = (strpos($match[0], '@import') === 0 ? 'import' : 'url');

            // fix relative url
            $url = $this->convertRelativePath($match['path'], dirname($source), dirname($destination));

            // build replacement
            $search[] = $match[0];
            if ($type == 'url') {
                $replace[] = 'url(' . $url . ')';
            } elseif ($type == 'import') {
                $replace[] = '@import "' . $url . '"';
            }
        }

        // replace urls
        $content = str_replace($search, $replace, $content);

        return $content;
    }

    /**
     * Shorthand hex color codes.
     * #FF0000 -> #F00
     *
     * @param  string $content The CSS content to shorten the hex color codes for.
     * @return string
     */
    protected function shortenHex($content)
    {
        $content = preg_replace('/(?<![\'"])#([0-9a-z])\\1([0-9a-z])\\2([0-9a-z])\\3(?![\'"])/i', '#$1$2$3', $content);

        return $content;
    }

    /**
     * Strip comments.
     *
     * @param  string $content The CSS content to strip the comments for.
     * @return string
     */
    protected function stripComments($content)
    {
        $content = preg_replace('/\/\*(.*?)\*\//is', '', $content);

        return $content;
    }

    /**
     * Strip whitespace.
     *
     * @param  string $content The CSS content to strip the whitespace for.
     * @return string
     */
    protected function stripWhitespace($content)
    {
        // remove semicolon/whitespace followed by closing bracket
        $content = preg_replace('/;?\s*}/', '}', $content);

        // remove whitespace following bracket, colon, semicolon or comma
        $content = preg_replace('/\s*([\{:;,])\s*/', '$1', $content);

        // remove leading & trailing whitespace
        $content = preg_replace('/^\s*|\s*$/m', '', $content);

        // remove newlines
        $content = preg_replace('/\n/', '', $content);

        return $content;
    }
}
