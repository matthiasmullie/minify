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
    const STRIP_SEMICOLONS = 4;

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
        $this->registerPattern('/^([\'"]).*?(?<!\\\\)\\1/s', '\\0');

        /*
         * If comments should be stripped, we can just replace these matches
         * with nothing; otherwise, we just want to match them and replace with
         * their original content (similar to how strings are matched just to
         * make sure the rest of the patterns, like whitespace, ignore them)
         */
        $commentsReplacement = $options & static::STRIP_COMMENTS ? '' : '\\1';
        $content = $this->stripComments($content, $commentsReplacement);

        if($options & static::STRIP_WHITESPACE) $content = $this->stripWhitespace($content);
        if($options & static::STRIP_SEMICOLONS) $content = $this->stripSemicolons($content);

        $content = $this->replace($content);

        // save to path
        if($path !== false) $this->save($content, $path);

        return $content;
    }

    /**
     * Strip comments from source code.
     *
     * @param  string $content The content to strip the comments for.
     * @param  string[optional] $replacement The replacement for matched comments.
     * @return string
     */
    protected function stripComments($content, $replacement = '')
    {
        // @todo: also register these when comments are _not_ stripped, to make sure only whitespace stripping doesn't affect anything inside comments

        // single-line comments
        $this->registerPattern('/^\/\/.*$[\r\n]*/m', $replacement);

        // multi-line comments
        $this->registerPattern('/^\/\*.*?\*\//s', $replacement);

        return $content;
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
         * Operators where whitespace can safely be ignored
         * Operator list at:
         * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators
         */
        $operators = array(
            // arithmetic
            '+', '-', '*', '/', '%', '++', '--',
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
         * Now that we've removed all whitespace around operators, all remaining
         * line-breaking whitespace should be end-of-statements, which should be
         * terminated with ; and have their surrounding whitespace removed.
         */
        $this->registerPattern('/^\s*?;?\s*?$\s+/m', ';');

        // All other whitespace can be reduced to 1 space
        $this->registerPattern('/^\s+/s', ' ');

        /*
         * We cheated when stripping whitespace; we can not safely strip line-
         * breaking whitespace after ) and }, as per these examples:
         * * console.log('abc')
         * * var a=function(){}
         *
         * Both of these are statements that have to be terminated with ; if
         * they're followed by unicode letter, $ or _ (valid variable names)
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
        $this->registerPattern('/^([\)\}])(?=[_\$\p{L}]+)/', '\\1;');

        // trim remaining whitespace
        return trim($content);
    }

    /**
     * Statement terminator ; can be omitted if it's the last statement in its
     * scope.
     *
     * @param $content
     * @return string
     */
    protected function stripSemicolons($content) {
        // Last ; right before the end of a block can safely be discarded
        $this->registerPattern('/^;\}/', '}');

        // trim last ;
        return trim($content, ';');
    }
}
