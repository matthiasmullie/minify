<?php
namespace MatthiasMullie\Minify;

/**
 * JavaScript minifier.
 *
 * Please report bugs on https://github.com/matthiasmullie/minify/issues
 *
 * @author Matthias Mullie <minify@mullie.eu>
 * @author Tijs Verkoyen <minify@verkoyen.eu>
 *
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved.
 * @license MIT License
 */
class JS extends Minify
{
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
     * @var string[]
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
     * is used for function calls, for grouping, in if () and for (), ...
     *
     * Will be loaded from /data/js/operators_after.txt
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Expressions_and_Operators
     * @var string[]
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
     * @var string[]
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
     * @var string[]
     */
    protected $keywordsAfter = array();

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        call_user_func_array(array('parent', '__construct'), func_get_args());

        $dataDir = __DIR__ . '/../data/js/';
        $options = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES;
        $this->keywordsBefore = file($dataDir . 'keywords_before.txt', $options);
        $this->keywordsAfter = file($dataDir . 'keywords_after.txt', $options);
        $this->operatorsBefore = file($dataDir . 'operators_before.txt', $options);
        $this->operatorsAfter = file($dataDir . 'operators_after.txt', $options);
    }

    /**
     * Minify the data.
     * Perform JS optimizations.
     *
     * @param  string[optional] $path Path to write the data to.
     * @return string           The minified data.
     */
    public function minify($path = null)
    {
        $content = '';

        // loop files
        foreach ($this->data as $source => $js) {
            // combine js (separate sources with semicolon)
            $content .= $js . ';';
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

        $content = $this->stripWhitespace($content);

        /*
         * Earlier, we extracted strings & regular expressions and replaced them
         * with placeholder text. This will restore them.
         */
        $content = $this->restoreExtractedData($content);

        // save to path
        if ($path !== null) {
            $this->save($content, $path);
        }

        return $content;
    }

    /**
     * Strip comments from source code.
     */
    protected function stripComments()
    {
        // single-line comments
        $this->registerPattern('/\/\/.*$[\r\n]*/m', '');

        // multi-line comments
        $this->registerPattern('/\/\*.*?\*\//s', '');
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
    protected function extractRegex()
    {
        // PHP only supports $this inside anonymous functions since 5.4
        $minifier = $this;
        $callback = function ($match) use ($minifier) {
            $count = count($minifier->extracted);
            $placeholder = '/REGEX' . $count . '/';
            $minifier->extracted[$placeholder] = '/' . $match[1] . '/';

            return $placeholder;
        };

        // it's a regex if we can find an opening (not preceded by variable,
        // value or similar) & (non-escaped) closing /,
        $before = $this->getOperatorsForRegex($this->operatorsBefore, '/');
        $this->registerPattern('/^\s*\K\/(.*?(?<!\\\\)(\\\\\\\\)*)\//', $callback);
        $this->registerPattern('/(?:' . implode('|', $before) . ')\s*\K\/(.*?(?<!\\\\)(\\\\\\\\)*)\//', $callback);
    }

    /**
     * Strip whitespace.
     *
     * We won't strip *all* whitespace, but as much as possible. The thing that
     * we'll preserve are newlines we're unsure about.
     * JavaScript doesn't require statements to be terminated with a semicolon.
     * It will automatically fix missing semicolons with ASI (automatic semi-
     * colon insertion) at the end of line causing errors (without semicolon.)
     *
     * Because it's sometimes hard to tell if a newline is part of a statement
     * that should be terminated or not, we'll just leave some of them alone.
     *
     * @param  string $content The content to strip the whitespace for.
     * @return string
     */
    protected function stripWhitespace($content)
    {
        // uniform line endings, make them all line feed
        $content = str_replace(array("\r\n", "\r"), "\n", $content);

        // collapse all non-line feed whitespace into a single space
        $content = preg_replace('/[^\S\n]+/', ' ', $content);

        // strip leading & trailing whitespace
        $content = str_replace(array(" \n", "\n "), "\n", $content);

        // collapse consecutive line feeds into just 1
        $content = preg_replace('/\n+/', "\n", $content);

        // strip whitespace that ends in (or next line begin with) an operator
        // that allows statements to be broken up over multiple lines
        $before = $this->getOperatorsForRegex($this->operatorsBefore, '/');
        $after = $this->getOperatorsForRegex($this->operatorsAfter, '/');
        $content = preg_replace('/(' . implode('|', $before) . ')\s+/', '\\1', $content);
        $content = preg_replace('/\s+(' . implode('|', $after) . ')/', '\\1', $content);

        // make sure + and - can't be mistaken for, or joined into ++ and --
        $content = preg_replace('/(?<![\+\-])\s*([\+\-])/', '\\1', $content);
        $content = preg_replace('/([\+\-])\s*(?!\\1)/', '\\1', $content);

        // collapse whitespace around reserved words into single space
        $before = $this->getKeywordsForRegex($this->keywordsBefore, '/');
        $after = $this->getKeywordsForRegex($this->keywordsAfter, '/');
        $content = preg_replace('/(' . implode('|', $before) . ')\s+/', '\\1 ', $content);
        $content = preg_replace('/\s+(' . implode('|', $after) . ')/', ' \\1', $content);

        /*
         * We didn't strip whitespace after a couple of operators because they
         * could be used in different contexts and we can't be sure it's ok to
         * strip the newlines. However, we can safely strip any non-line feed
         * whitespace that follows them.
         */
        $operators = $this->getOperatorsForRegex($this->operatorsBefore + $this->operatorsAfter, '/');
        $content = preg_replace('/([\}\)\]])[^\S\n]+(?!' . implode('|', $operators) . ')/', '\\1', $content);

        /*
         * We also don't really want to terminate statements followed by closing
         * curly braces (which we've ignored completely up until now): ASI will
         * kick in here & we're all about minifying.
         */
        $content = preg_replace('/;\}/s', '}', $content);

        // get rid of remaining whitespace af beginning/end, as well as
        // semicolon, which doesn't make sense there: ASI will kick in here too
        return trim($content, "\n ;");
    }

    /**
     * We'll strip whitespace around certain operators with regular expressions.
     * This will prepare the given array by escaping all characters.
     *
     * @param  string[] $operators
     * @param  string   $delimiter
     * @return string[]
     */
    protected function getOperatorsForRegex(array $operators, $delimiter = '/')
    {
        // escape operators for use in regex
        $delimiter = array_fill(0, count($operators), $delimiter);
        $escaped = array_map('preg_quote', $operators, $delimiter);

        $operators = array_combine($operators, $escaped);

        // ignore + & - for now, they'll get special treatment
        unset($operators['+'], $operators['-']);

        // dot can not just immediately follow a number; it can be confused
        // between decimal point, or calling a method on it, e.g. 42 .toString()
        $operators['.'] = '(?<![0-9]\s)\.';

        return $operators;
    }

    /**
     * We'll strip whitespace around certain keywords with regular expressions.
     * This will prepare the given array by escaping all characters.
     *
     * @param  string[] $keywords
     * @param  string   $delimiter
     * @return string[]
     */
    protected function getKeywordsForRegex(array $keywords, $delimiter = '/')
    {
        // escape keywords for use in regex
        $delimiter = array_fill(0, count($keywords), $delimiter);
        $escaped = array_map('preg_quote', $keywords, $delimiter);

        // add word boundaries
        array_walk($keywords, function ($value) {
            return '\b' . $value . '\b';
        });

        $keywords = array_combine($keywords, $escaped);

        return $keywords;
    }
}
