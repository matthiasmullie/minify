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
    /**
     * Minify the data.
     * Perform JS optimizations.
     *
     * @param  string[optional] $path    The path the data should be written to.
     * @return string           The minified data.
     */
    public function minify($path = false)
    {
        $content = '';

        // loop files
        foreach ($this->data as $source => $js) {
            // combine js
            $content .= $js;
        }

        /*
         * If comments should be stripped, we can just replace these matches
         * with nothing; otherwise, we just want to match them and replace with
         * their original content (similar to how strings are matched just to
         * make sure the rest of the patterns, like whitespace, ignore them)
         */
        $content = $this->stripComments($content);
        $content = $this->stripWhitespace($content);

        // @todo: strip whitespace that remains after comments have been parsed

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
        /*
         * Strings are a pattern we need to match, in order to ignore potential
         * code-like content inside them, but we just want all of the string
         * content to remain untouched.
         */
        $this->registerPattern('/^([\'"]).*?(?<!\\\\)\\1/s', '\\0', true);

        /*
         * Make sure that escaped slashes are ignored when matching comments:
         * e.g. RegExp(/abc\//) <- this is a valid regular expression, not
         * the start of a comment.
         */
        $this->registerPattern('/^\\\\\//', '\\0', true);

        // single-line comments
        $this->registerPattern('/^\/\/.*$[\r\n]*/m', '', true);

        // multi-line comments
        $this->registerPattern('/^\/\*.*?\*\//s', '', true);

        return $this->replace($content);
    }

    /**
     * Strip whitespace.
     *
     * Part of stripping whitespace may be adding ;'s to terminate statements
     * where statements were auto-terminated by a newline.
     *
     * @param  string $content The content to strip the whitespace for.
     * @return string
     */
    protected function stripWhitespace($content)
    {
        /*
         * Strings are a pattern we need to match, in order to ignore potential
         * code-like content inside them, but we just want all of the string
         * content to remain untouched.
         */
        $this->registerPattern('/^([\'"]).*?(?<!\\\\)\\1/s', '\\0', true);

        /*
         * Regular expressions are //-delimited in JS and should remain
         * untouched, just like strings. / is also an operator, so I'm
         * extracting them before replacing whitespace around operators
         */
        $this->registerPattern('/^(\/).*?(?<!\\\\)\\1/s', '\\0', true); // @todo: merge with above

        /*
         * Operators where whitespace can safely be ignored
         * Operator list at:
         * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators
         */
        $operators = array(
            // arithmetic
            '+', '-', '*', '/', '%', '++', '--', // @todo: slash can be
            // assignment
            '=', '+=', '-=', '*=', '/=', '%=',
            '<<=', '>>=', '>>>=', '&=', '^=', '|=',
            // bitwise
            '&', '|', '^', '~', '<<', '>>', '>>>',
            // comparison
            '==', '===', '!=', '!==', '>', '<', '>=', '<=',
            // logical
            '&&', '||', '!',
            // string
            // + and += already added
            // member
            '.', '[', ']',
            // conditional
            '?', ':',
            // comma
            ',',

            // function call
            '(', ')',
            // object literal ({ & } are also used as block delimiter, but
            // we can strip whitespace around that too)
            '{', '}', ':',
            // statement terminator
            ';',
        );

        $delimiter = array_fill(0, count($operators), '/');
        $operators = array_map('preg_quote', $operators, $delimiter);
        $this->registerPattern('/^\s*('. implode('|', $operators) .')\s*/s', '\\1');

        /*
         * We cheated when stripping whitespace; we can not safely strip line-
         * breaking whitespace after ) and }, as per these examples:
         * * console.log('abc')
         * * var a=function(){}
         *
         * Both of these are statements that have to be terminated with ; unless
         * they're followed by some operator, e.g.:
         * * }else{
         * * function(){
         * We don't want ; to be added after these ) & } (rather: we want the
         * redundant whitespace to be removed)
         *
         * Note that this will also add a semicolon after a blocks where it may
         * not be needed, like:
         * function abc(){};
         *
         * It'd be quite complex to figure out if the closing brace belongs to
         * a statement where ; can be omitted (for, function, if, switch, try,
         * and while). Since it won't break anything if the semicolon is there,
         * I'll ignore that ;)
         */
        $operators = array_merge($operators, array('else', 'while', 'catch', 'finally', '$'));
        $this->registerPattern('/^([\)\}])(?!('. implode('|', $operators) .'))/s', '\\1;');

        /*
         * Now that we've removed all whitespace around operators, all remaining
         * line-breaking whitespace should be end-of-line statements, which
         * should be terminated with ; and have surrounding whitespace removed.
         */
        $this->registerPattern('/^\s*\n\s*/s', ';');

        // All other whitespace can be reduced to 1 space
        $this->registerPattern('/^\s+/s', ' ');

        // Last ; right before the end of a block can safely be discarded
        $this->registerPattern('/^;\}/', '}');

        // trim last ; & remaining whitespace
        return trim(trim($this->replace($content)), ';');
    }
}
