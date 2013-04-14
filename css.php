<?php
namespace MatthiasMullie\Minify;

/**
 * MinifyCSS class
 *
 * This source file can be used to minify CSS files.
 *
 * The class is documented in the file itself. If you find any bugs help me out and report them. Reporting can be done by sending an email to minify@mullie.eu.
 * If you report a bug, make sure you give me enough information (include your code).
 *
 * License
 * Copyright (c) 2012, Matthias Mullie. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author Matthias Mullie <minify@mullie.eu>
 * @author Tijs Verkoyen <minify@verkoyen.eu>
 * @version 1.1.0
 *
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved.
 * @license MIT License
 */
class CSS extends Minify
{
    const STRIP_COMMENTS = 1;
    const STRIP_WHITESPACE = 2;
    const SHORTEN_HEX = 4;
    const COMBINE_IMPORTS = 8;
    const IMPORT_FILES = 16;

    /**
     * Files larger than this value (in kB) will not be imported into the CSS.
     * Importing files into the CSS as data-uri will save you some connections,
     * but we should only import relatively small decorative images so that our
     * CSS file doesn't get too bulky.
     *
     * @var int
     */
    const FILE_MAX_SIZE = 5;

    /**
     * Combine CSS from import statements.
     * @import's will be loaded and their content merged into the original file, to save HTTP requests.
     *
     * @param  string $source The file to combine imports for.
     * @return string
     */
    protected function combineImports($source)
    {
        // little "hack" for internal use
        $content = @func_get_arg(1);

        // load the content
        if($content === false) $content = $this->load($source);

        // validate data
        if($content == $source) throw new Exception('The data for "' . $source . '" could not be loaded, please make sure the path is correct.');

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

                            # do not fetch data uris
                            (?!(
                                ["\']?
                                data:
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

        // find all relative imports in css (for now we don't support imports with media, and imports should use url(xxx))
        if (preg_match_all($importRegex, $content, $matches, PREG_SET_ORDER)) {
            $search = array();
            $replace = array();

            // loop the matches
            foreach ($matches as $match) {
                // get the path for the file that will be imported
                $importPath = dirname($source) . '/' . $match['path'];

                // only replace the import with the content if we can grab the content of the file
                if (@file_exists($importPath) && is_file($importPath)) {
                    // grab content
                    $importContent = @file_get_contents($importPath);

                    // fix relative paths
                    $importContent = $this->move($importPath, $source, $importContent);

                    // check if this is only valid for certain media
                    if($match['media']) $importContent = '@media ' . $match['media'] . '{' . "\n" . $importContent . "\n" . '}';

                    // add to replacement array
                    $search[] = $match[0];
                    $replace[] = $importContent;
                }
            }

            // replace the import statements
            $content = str_replace($search, $replace, $content);

            // ge recursive (if imports have occurred)
            if($search) $content = $this->combineImports($source, $content);
        }

        return $content;
    }

    /**
     * Convert relative paths based upon 1 path to another.
     *
     * E.g.
     * ../images/img.gif based upon /home/forkcms/frontend/core/layout/css, should become
     * ../../core/layout/images/img.gif based upon /home/forkcms/frontend/cache/minified_css
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
        if(strpos($path, '/') === 0) return $path;

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
            if($count) $from = dirname($from);
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
            if(isset($to[$i]) && $from[$i] == $to[$i]) unset($from[$i], $to[$i]);
            else break;
        }

        /*
         * At this point:
         * $path = images/img.gif
         * $from = array('core', 'layout')
         * $to = array('cache', 'minified_css')
         */

        // add .. for every directory that needs to be traversed for new path
        $new = str_repeat('../', count($to));
        if(empty($new)) $new = '/';

        /*
         * At this point:
         * $path = images/img.gif
         * $from = array('core', 'layout')
         * $to = *no longer matters*
         * $new = ../../
         */

        // add path, relative from this point, to traverse to image
        $new .= implode('/', $from);

        // if $from contained no elements, we still have a redundant trailing slash
        if(empty($from)) $new = rtrim($new, '/');

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
     * @url(image.jpg) images will be loaded and their content merged into the original file, to save HTTP requests.
     *
     * @param  string $source The file to import files for.
     * @return string
     */
    protected function importFiles($source)
    {
        // little "hack" for internal use
        $content = @func_get_arg(1);

        // load the content
        if($content === false) $content = $this->load($source);

        // validate data
        if($content == $source) throw new Exception('The data for "' . $source . '" could not be loaded, please make sure the path is correct.');

        if (preg_match_all('/url\((["\']?)((?!["\']?data:).*?\.(gif|png|jpg|jpeg|svg|woff))\\1\)/i', $content, $matches, PREG_SET_ORDER)) {
            $search = array();
            $replace = array();

            // loop the matches
            foreach ($matches as $match) {
                // get the path for the file that will be imported
                $path = $match[2];
                $path = dirname($source) . '/' . $path;
                $extension = $match[3];

                // only replace the import with the content if we can grab the content of the file
                if (@file_exists($path) && is_file($path) && (filesize($path) <= (static::FILE_MAX_SIZE * 1024) || in_array($extension, array('svg', 'woff')))) {
                    // grab content
                    $importContent = @file_get_contents($path);

                    // base-64-ize
                    $importContent = base64_encode($importContent);

                    // build replacement
                    $search[] = $match[0];

                    switch ($match[3]) {
                        case 'woff':
                            $replace[] = 'url(data:application/x-font-woff;base64,' . $importContent  . ')';
                            break;

                        case 'svg':
                            $replace[] = 'url(data:image/svg+xml;base64,' . $importContent  . ')';
                            break;

                        default:
                            $replace[] = 'url(data:image/' . $match[3] . ';base64,' . $importContent  . ')';
                            break;
                    }
                }
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
     * @param  string[optional] $path    The path the data should be written to.
     * @param  int[optional]    $options The minify options to be applied.
     * @return string           The minified data.
     */
    public function minify($path = false, $options = self::ALL)
    {
        $content = '';

        // loop files
        foreach ($this->data as $source => $css) {
            // if we're saving to a new path, we'll have to fix the relative paths
            if($source !== 0) $css = $this->move($source, $path, $css);

            // combine css
            $content .= $css;
        }

        if($options & static::COMBINE_IMPORTS) $content = $this->combineImports($path, $content);
        if($options & static::SHORTEN_HEX) $content = $this->shortenHex($content);
        if($options & static::IMPORT_FILES) $content = $this->importFiles($path, $content);
        if($options & static::STRIP_COMMENTS) $content = $this->stripComments($content);
        if($options & static::STRIP_WHITESPACE) $content = $this->stripWhitespace($content);

        // save to path
        if($path !== false) $this->save($content, $path);

        return $content;
    }

    /**
     * Moving a css file should update all relative urls.
     * Relative references (e.g. ../images/image.gif) in a certain css file, will have to be updated when a file is
     * being saved at another location (e.g. ../../images/image.gif, if the new CSS file is 1 folder deeper)
     *
     * @param  string $source      The file to update relative urls for.
     * @param  string $destination The path the data will be written to.
     * @return string
     */
    protected function move($source, $destination)
    {
        // little "hack" for internal use
        $content = @func_get_arg(2);

        // load the content
        if($content === false) $content = $this->load($source);

        // validate data
        if($content == $source) throw new Exception('The data for "' . $source . '" could not be loaded, please make sure the path is correct.');

        // regex to match paths
        $pathsRegex = '/

        # enable possiblity of giving multiple subpatterns same name
        (?J)

        # url(xxx)

            # open url()
            url\(

                # open path enclosure
                (?P<quotes>["\'])?

                    # fetch path
                    (?P<path>

                        # do not fetch data uris
                        (?!(
                            ["\']?
                            data:
                        ))

                        .+?
                    )

                # close path enclosure
                (?(quotes)(?P=quotes))

            # close url()
            \)

        |

        # @import "xxx"

            # import statement
            @import

            # whitespace
            \s+

                # we don\'t have to check for @import url(), because the condition above will already catch these

                # open path enclosure
                (?P<quotes>["\'])

                    # fetch path
                    (?P<path>

                        # do not fetch data uris
                        (?!(
                            ["\']?
                            data:
                        ))

                        .+?
                    )

                # close path enclosure
                (?P=quotes)

        /ix';

        // find all relative urls in css
        if (preg_match_all($pathsRegex, $content, $matches, PREG_SET_ORDER)) {
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
                if($type == 'url') $replace[] = 'url(' . $url . ')';
                elseif($type == 'import') $replace[] = '@import "' . $url . '"';
            }

            // replace urls
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }

    /**
     * Shorthand hex color codes.
     * #FF0000 -> #F00
     *
     * @param  string $content The file/content to shorten the hex color codes for.
     * @return string
     */
    protected function shortenHex($content)
    {
        // load the content
        $content = $this->load($content);

        // shorthand hex color codes
        $content = preg_replace('/(?<![\'"])#([0-9a-z])\\1([0-9a-z])\\2([0-9a-z])\\3(?![\'"])/i', '#$1$2$3', $content);

        return $content;
    }

    /**
     * Strip comments.
     *
     * @param  string $content The file/content to strip the comments for.
     * @return string
     */
    protected function stripComments($content)
    {
        // load the content
        $content = $this->load($content);

        // strip comments
        $content = preg_replace('/\/\*(.*?)\*\//is', '', $content);

        return $content;
    }

    /**
     * Strip whitespace.
     *
     * @param  string $content The file/content to strip the whitespace for.
     * @return string
     */
    protected function stripWhitespace($content)
    {
        // load the content
        $content = $this->load($content);

        // semicolon/space before closing bracket > replace by bracket
        $content = preg_replace('/;?\s*}/', '}', $content);

        // bracket, colon, semicolon or comma preceeded or followed by whitespace > remove space
        $content = preg_replace('/\s*([\{:;,])\s*/', '$1', $content);

        // preceeding/trailing whitespace > remove
        $content = preg_replace('/^\s*|\s*$/m', '', $content);

        // newlines > remove
        $content = preg_replace('/\n/', '', $content);

        return $content;
    }
}
