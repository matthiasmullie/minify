<?php
namespace MatthiasMullie\Minify;

/**
 * Minify\JS class
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
 * @version 1.3.0
 *
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved.
 * @license MIT License
 */
class JS extends Minify
{
    /**
     * This array will hold content of strings and regular expressions that have
     * been extracted from the JS source code, so we can reliably match "code",
     * without having to worry about potential "code-like" characters inside.
     *
     * @var array
     */
    public $extracted = array();

    /**
     * List of JavaScript operators that accept a <variable, value, ...> after
     * them. We'll insert semicolons if they're missing at EOL, but some
     * end of lines are not the end of a statement, like with these operators.
     *
     * Note: Most operators are fine, we've only removed !, ++ and --.
     * There can't be a newline separating ! and whatever it is negating.
     * ++ & -- have to be joined with the value they're in-/decrementing.
     *
     * Will be loaded from /data/js/operators_before.txt
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Expressions_and_Operators
     * @var array
     */
    protected $operatorsBefore = array();

    /**
     * List of JavaScript operators that accept a <variable, value, ...> before
     * them. We'll insert semicolons if they're missing at EOL, but some end of
     * lines are not the end of a statement, like when continued by one of these
     * operators on the newline.
     *
     * Note: Most operators are fine, we've only removed ), ], ++ and --.
     * ++ & -- have to be joined with the value they're in-/decrementing.
     * ) & ] are "special" in that they have lots or usecases. () for example
     * is used for function calls, for grouping, in if() and for(), ...
     *
     * Will be loaded from /data/js/operators_after.txt
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Expressions_and_Operators
     * @var array
     */
    protected $operatorsAfter = array();

    /**
     * List of JavaScript reserved words that accept a <variable, value, ...>
     * after them. We'll insert semicolons if they're missing at EOL, but some
     * end of lines are not the end of a statement, like with these keywords.
     *
     * E.g.: we shouldn't insert a ; after this else
     * else
     *     console.log('this is quite fine')
     *
     * Will be loaded from /data/js/reserved_before.txt
     *
     * @see https://mathiasbynens.be/notes/reserved-keywords
     * @var array
     */
    protected $keywordsBefore = array();

    /**
     * List of JavaScript reserved words that accept a <variable, value, ...>
     * before them. We'll insert semicolons if they're missing at EOL, but some
     * end of lines are not the end of a statement, like when continued by one
     * of these keywords on the newline.
     *
     * E.g.: we shouldn't insert a ; before this instanceof
     * variable
     *     instanceof String
     *
     * Will be loaded from /data/js/reserved_after.txt
     *
     * @see https://mathiasbynens.be/notes/reserved-keywords
     * @var array
     */
    protected $keywordsAfter = array();

    public function __construct() {
        parent::__construct();

        $this->keywordsBefore = file(__DIR__ . '/data/js/keywords_before.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->keywordsAfter = file(__DIR__ . '/data/js/keywords_after.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->operatorsBefore = file(__DIR__ . '/data/js/operators_before.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->operatorsAfter = file(__DIR__ . '/data/js/operators_after.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * Minify the data.
     * Perform JS optimizations.
     *
     * @param string[optional] $path Path to write the data to.
     * @return string The minified data.
     */
    public function minify($path = null)
    {
        $content = '';

        // loop files
        foreach ($this->data as $source => $js) {
            // combine js
            $content .= $js;
        }

        /*
         * Let's first take out strings, comments and regular expressions.
         * All of these can contain JS code-like characters, and we should make
         * sure any further magic ignores anything inside of these.
         *
         * Consider this example, where we should not strip any whitespace:
         * var str = "a   test";
         *
         * Comments will be removed altogether, strings and regular expressions
         * will be replaced by placeholder text, which we'll restore later.
         */
        $this->extractStrings();
        $this->stripComments();
        $this->extractRegex();
        $content = $this->replace($content);

        // @todo: blah blah comment
        $content = $this->terminateStatements($content);
        $content = $this->stripWhitespace($content);

        /*
         * Earlier, we extracted strings & regular expressions and replaced them
         * with placeholder text. This will restore them.
         */
        $this->restoreStrings();
        $this->restoreRegex();
        $content = $this->replace($content);

        // save to path
        if($path !== null) $this->save($content, $path);

        return $content;
    }

    /**
     * Strings are a pattern we need to match, in order to ignore potential
     * code-like content inside them, but we just want all of the string
     * content to remain untouched.
     *
     * This method will replace all string content with simple STRING#
     * placeholder text, so we've rid all strings from characters that may be
     * misinterpreted. Original string content will be saved in $this->extracted
     * and after doing all other minifying, we can restore the original content
     * via restoreStrings()
     */
    protected function extractStrings() {
        // PHP only supports $this inside anonymous functions since 5.4
        $minifier = $this;
        $callback = function($match) use ($minifier) {
            $placeholder = 'STRING' . count($minifier->extracted);
            $minifier->extracted[$placeholder] = $match[2];
            return $match[1] . $placeholder . $match[1];
        };

        $this->registerPattern('/^([\'"])(.*?)(?<!\\\\)\\1/s', $callback, true);
    }

    /**
     * This method will restore are strings that were replaced with placeholder
     * text in extractStrings(). The original content was saved in
     * $this->extracted.
     */
    protected function restoreStrings() {
        // PHP only supports $this inside anonymous functions since 5.4
        $minifier = $this;
        $callback = function($match) use ($minifier) {
            $original = $minifier->extracted[$match[2]];
            unset($minifier->extracted[$match[2]]);
            return $match[1] . $original . $match[1];
        };

        $this->registerPattern('/^([\'"])(.*?)(?<!\\\\)\\1/s', $callback, true);
    }

    /**
     * Strip comments from source code.
     */
    protected function stripComments()
    {
        /*
         * We'll move character by character to find matches.
         * Downside is that it's quite impossible to do a lookbehind assertion,
         * to make sure the / starting the comment is not escaped, in which case
         * the slashes don't form a comment, as in this example:
         * /abc\/def\//.test("abc")
         */
        $this->registerPattern('/^\\\\\//', '\\0', true);

        // single-line comments
        $this->registerPattern('/^\/\/.*$[\r\n]*/m', '', true);

        // multi-line comments
        $this->registerPattern('/^\/\*.*?\*\//s', '', true);
    }

    /**
     * JS kan have /-delimited regular expressions, like: /ab+c/.match(string)
     *
     * The content inside the regex can contain characters that may be confused
     * for JS code: e.g. it could contain whitespace it needs to match & we
     * don't want to strip whitespace in there.
     *
     * The regex can be pretty simple: we don't have to care about comments,
     * (which also use slashes) because stripComments() will have stripped those
     * already.
     *
     * This method will replace all string content with simple REGEX#
     * placeholder text, so we've rid all regular expressions from characters
     * that may be misinterpreted. Original regex content will be saved in
     * $this->extracted and after doing all other minifying, we can restore the
     * original content via restoreRegex()
     */
    protected function extractRegex() {
        // PHP only supports $this inside anonymous functions since 5.4
        $minifier = $this;
        $callback = function($match) use ($minifier) {
            $placeholder = 'REGEX' . count($minifier->extracted);
            $minifier->extracted[$placeholder] = $match[1];
            return '/' . $placeholder . '/';
        };

        $this->registerPattern('/^\/(.+?)(?<!\\\\)\//s', $callback, true);
    }

    /**
     * This method will restore are regular expressions that were replaced with
     * placeholder text in extractRegex(). The original content was saved in
     * $this->extracted.
     */
    protected function restoreRegex() {
        // PHP only supports $this inside anonymous functions since 5.4
        $minifier = $this;
        $callback = function($match) use ($minifier) {
            $original = $minifier->extracted[$match[1]];
            unset($minifier->extracted[$match[1]]);
            return '/' . $original . '/';
        };

        $this->registerPattern('/^\/(.+?)(?<!\\\\)\//s', $callback, true);
    }

    /**
     * JavaScript statements should be terminated with a semicolon. However,
     * it is acceptable to not terminate statements with a semicolon and the
     * Automatic Semicolon Insertion will take over.
     *
     * @see http://dailyjs.com/2012/04/19/semicolons/
     * "If the parser encounters a new line or curly brace, and it is used to
     * break up tokens that otherwise don’t belong together, then it will insert
     * a semicolon."
     *
     * This method will make sure all statements are properly terminated with a
     * semicolon, so we can reliably strip whitespace later (which may be used
     * to terminate a statement).
     *
     * @param string $content The content to terminate statements for.
     * @return string
     */
    protected function terminateStatements($content) {
        // collapse newlines that end in (or next line begin with) an operator
        // that allows statements to be broken up over multiple lines
        $before = $this->getOperatorsForRegex($this->operatorsBefore, '/');
        $after = $this->getOperatorsForRegex($this->operatorsAfter, '/');
        $content = preg_replace('/(' . implode('|', $before) . ')\s*$\s*/m', '\\1', $content);
        $content = preg_replace('/\s*^\s*(' . implode('|', $after) . ')/m', '\\1', $content);

        // collapse newlines around reserved words into single space:
        $before = $this->getKeywordsForRegex($this->keywordsBefore, '/');
        $after = $this->getKeywordsForRegex($this->keywordsAfter, '/');
        $content = preg_replace('/(' . implode('|', $before) . ')\s*$\s*/m', '\\1 ', $content);
        $content = preg_replace('/\s*^\s*(' . implode('|', $after) . ')/m', ' \\1', $content);

		// collapse consecutive whitespace with newline into just newline
		$content = preg_replace('/\s*\n\s*/', "\n", $content);

		// statements like if(...) with a single line of conditional code should
		// not have a semicolon between the statement & the conditional code
		// (like if(...);)
		$keywords = array('if', 'for', 'while', 'catch', 'switch');
		$content = preg_replace('/(\b(' . implode('|', $keywords) . ')\s*\(.*\))\n/', '\\1', $content);

		// terminate remaining non-empty lines by ;
        $content = preg_replace('/;?\s*\n/', ';', $content);

        // semicolons don't make sense at end of source, where ASI will kick in
		$content = trim($content, ';');

        /*
         * We also don't really want to terminate statements preceded/followed
         * by curly braces (which we've ignored completely up until now): ASI
         * will kick in here & we're all about minifying.
         */
        $content = preg_replace('/([\{\}]\s*);/s', '\\1', $content);
        $content = preg_replace('/;(\s*[\{\}])/s', '\\1', $content);

        return $content;
    }

    /**
     * Strip whitespace.
     *
     * @param string $content The content to strip the whitespace for.
     * @return string
     */
    protected function stripWhitespace($content)
    {
        // get rid of unneeded whitespace around operators
        $operators =
            $this->getOperatorsForRegex($this->operatorsBefore) +
            $this->getOperatorsForRegex($this->operatorsAfter);
        $content = preg_replace('/\s*(' . implode('|', $operators) . ')\s*/', '\\1', $content);

        // collapse remaining whitespace into a single space
        $content = preg_replace('/(\s+)/', ' ', $content);

        return trim($content);
    }

    /**
     * We'll strip whitespace around certain operators with regular expressions.
     * This will prepare the given array by escaping all characters.
     *
     * @param array $operators
     * @param string $delimiter
     * @return array
     */
    protected function getOperatorsForRegex(array $operators, $delimiter = '/') {
        // escape operators for use in regex
        $delimiter = array_fill(0, count($operators), $delimiter);
        $escaped = array_map('preg_quote', $operators, $delimiter);

        $operators = array_combine($operators, $escaped);

        // make sure + and - can't be mistaken for ++ and --, which are special
        $operators['+'] = '(?<!\+)\+(?!\+)';
        $operators['-'] = '(?<!\-)\-(?!\-)';

        return $operators;
    }

    /**
     * We'll strip whitespace around certain keywords with regular expressions.
     * This will prepare the given array by escaping all characters.
     *
     * @param array $keywords
     * @param string $delimiter
     * @return array
     */
    protected function getKeywordsForRegex(array $keywords, $delimiter = '/') {
        // escape keywords for use in regex
        $delimiter = array_fill(0, count($keywords), $delimiter);
        $escaped = array_map('preg_quote', $keywords, $delimiter);

        // add word boundaries
        array_walk($keywords, function($value) {
            return '\b' . $value . '\b';
        });

        $keywords = array_combine($keywords, $escaped);

        return $keywords;
    }
}
