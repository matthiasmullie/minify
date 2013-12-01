<?php
namespace MatthiasMullie\Minify;

/**
 * MinifyJS class
 *
 * This source file can be used to minify JavaScript files.
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
 * @version 1.2.0
 *
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved.
 * @license MIT License
 */
class JS extends Minify
{
    const STRIP_COMMENTS = 1;
    const STRIP_WHITESPACE = 2;

    /**
     * Minify the data.
     * Perform JS optimizations.
     *
     * @param  string[optional] $path    The path the data should be written to.
     * @param  int[optional]    $options The minify options to be applied.
     * @return string           The minified data.
     */
    public function minify($path = false, $options = self::ALL)
    {
        $content = '';

        // loop files
        foreach ($this->data as $source => $js) {
            // combine js
            $content .= $js;
        }

        /*
         * Strings are a pattern we need to match, in order to ignore potential
         * code-like content inside them, but we just want all of the string
         * content to remain untouched.
         */
        $this->registerPattern('/^([\'"]).*?\\1/s', '\\0');

        if($options & static::STRIP_COMMENTS) $content = $this->stripComments($content);
        if($options & static::STRIP_WHITESPACE) $content = $this->stripWhitespace($content);

        $content = $this->replace($content);

        // save to path
        if($path !== false) $this->save($content, $path);

        return $content;
    }

    /**
     * Strip comments from source code.
     *
     * @param  string $content The content to strip the comments for.
     * @return string
     */
    protected function stripComments($content)
    {
        // single-line comments
        $this->registerPattern('/^\/\/.*$[\r\n]*/m', '');

        // multi-line comments
        $this->registerPattern('/^\/\*.*?\*\//s', '');

        return $content;
    }

    /**
     * Strip whitespace.
     *
     * @param  string $content The content to strip the whitespace for.
     * @return string
     */
    protected function stripWhitespace($content)
    {
        // newlines > linefeed
        $this->registerPattern('/^(\r\n|\r)/m', "\n");

        // empty lines > collapse
        $this->registerPattern('/^\n\s+/', "\n");

        // redundant whitespace > remove
        $this->registerPattern('/^([{}\[\]\(\)=><&\|;:,\?!\+-])[ \t]+/', '\\1');
        $this->registerPattern('/^[ \t]+(?=[{}\[\]\(\)=><&\|;:,\?!\+-])/', '');

        // redundant semicolons (followed by another semicolon or closing curly bracket) > remove
        $this->registerPattern('/^;\s*(?=[;}])/s', '');

        /*
         * @todo: we could remove all line feeds, but then we have to be certain that all statements are properly
         * terminated with a semi-colon. So we'd first have to parse the statements to see which require a semi-colon,
         * add it if it's not present, and then remove the line feeds. The semi-colon just before a closing curly
         * bracket can then also be omitted.
         */

        return $content;
    }
}
