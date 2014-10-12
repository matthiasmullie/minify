<?php

namespace MatthiasMullie\Minify;

/**
 * Minify abstract class
 *
 * This source file can be used to write minifiers for multiple file types.
 *
 * The class is documented in the file itself. If you find any bugs help me out and report them. Reporting can be done by sending an email to minify@mullie.eu.
 * If you report a bug, make sure you give me enough information (include your code).
 *
 * @author Matthias Mullie <minify@mullie.eu>
 *
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved.
 * @license MIT License
 */
abstract class Minify
{
    /**
     * The data to be minified
     *
     * @var array
     */
    protected $data = array();

    /**
     * Array of patterns to match.
     *
     * @var array
     */
    protected $patterns = array();

    /**
     * Init the minify class - optionally, code may be passed along already.
     *
     * @param string[optional] $css
     */
    public function __construct()
    {
        // it's possible to add the css through the constructor as well ;)
        $arguments = func_get_args();
        if(func_num_args()) call_user_func_array(array($this, 'add'), $arguments);
    }

    /**
     * Add a file or straight-up code to be minified.
     *
     * @param string $data
     */
    public function add($data)
    {
        // this method can be overloaded
        foreach (func_get_args() as $data) {
            // redefine var
            $data = (string) $data;

            // load data
            $value = $this->load($data);
            $key = ($data != $value) ? $data : 0;

            // initialize key
            if(!array_key_exists($key, $this->data)) $this->data[$key] = '';

            // store data
            $this->data[$key] .= $value;
        }
    }

    /**
     * Load data.
     *
     * @param  string $data Either a path to a file or the content itself.
     * @return string
     */
    protected function load($data)
    {
        // check if the data is a file
        if (@file_exists($data) && is_file($data)) {
            // grab content
            return @file_get_contents($data);
        }

        // no file, just return the data itself
        else return $data;
    }

    /**
     * Save to file
     *
     * @param  string    $content The minified data.
     * @param  string    $path    The path to save the minified data to.
     * @throws Exception
     */
    protected function save($content, $path)
    {
        // create file & open for writing
        if(($handler = @fopen($path, 'w')) === false) throw new Exception('The file "' . $path . '" could not be opened. Check if PHP has enough permissions.');

        // write to file
        if(@fwrite($handler, $content) === false) throw new Exception('The file "' . $path . '" could not be written to. Check if PHP has enough permissions.');

        // close the file
        @fclose($handler);
    }

    /**
     * Minify the data.
     *
     * @param  string[optional] $path Path to write the data to.
     * @return string           The minified data.
     */
    abstract public function minify($path = null);

    /**
     * Register a pattern to execute against the source content.
     * Patterns should always include caret (= start from the beginning of the
     * string) - processing will be performed by traversing the content
     * character by character, so we need the pattern to start matching
     * exactly at the first character of the content at that point.
     *
     * @param  string          $pattern     PCRE pattern.
     * @param  string|callable $replacement Replacement value for matched pattern.
     * @throws Exception
     */
    protected function registerPattern($pattern, $replacement = '')
    {
        // make sure pattern actually starts at beginning of content - we'll be
        // looping the content character by character, executing patterns
        // starting on exactly that character.
        if ($pattern[1] !== '^') $pattern = $pattern[0] . '^' . substr($pattern, 1);

        $this->patterns[] = array($pattern, $replacement);
    }

    /**
     * We can't "just" run some regular expressions against JavaScript: it's a
     * complex language. E.g. having an occurrence of // xyz would be a comment,
     * unless it's used within a string. Of you could have something that looks
     * like a 'string', but inside a comment.
     * The only way to accurately replace these pieces is to traverse the JS one
     * character at a time and try to find whatever starts first.
     *
     * @param  string $content The content to replace patterns in.
     * @return string The (manipulated) content.
     */
    protected function replace($content)
    {
        // every character that has been processed will be moved to this string
        $processed = '';

        // update will keep shrinking, character by character, until all of it
        // has been processed
        while ($content) {
            for ($i = 0; $i < count($this->patterns); $i++) {
                list($pattern, $replacement) = $this->patterns[$i];

                // replace pattern occurrences starting at this character
                list($content, $replacement, $match) = $this->replacePattern($pattern, $content, $replacement);

                // pattern matched or content replaced; save replacement value
                if ($match != '' || $replacement != '') {
                    // make sure that the replaced content is not touched by any
                    // other patterns
                    $processed .= $replacement;
                    $content = (string) substr($content, strlen($replacement));
                    continue 2;
                }
            }

            // add this character to $processed & strip it from $content, moving
            // on to the next character
            $processed .= $content[0];
            $content = substr($content, 1);
        }

        // clear registered patterns
        $this->patterns = array();

        return $processed;
    }

    /**
     * This is where a pattern is matched against $content and the matches
     * are replaced by their respective value.
     * This function will be called plenty of times, where $content will always
     * move up 1 character.
     *
     * @param  string          $pattern     Pattern to match.
     * @param  string          $content     Content to match pattern against.
     * @param  string|callable $replacement Replacement value.
     * @return array           [content, replacement, match]
     */
    protected function replacePattern($pattern, $content, $replacement)
    {
        if (is_callable($replacement)) {
            return $this->replaceWithCallback($pattern, $content, $replacement);
        } else {
            return $this->replaceWithString($pattern, $content, $replacement);
        }
    }

    /**
     * Replaces pattern by a value from a callback, via preg_replace_callback.
     *
     * @param  string          $pattern     Pattern to match.
     * @param  string          $content     Content to match pattern against.
     * @param  string|callable $replacement Replacement value.
     * @return array           [content, replacement, match]
     */
    protected function replaceWithCallback($pattern, $content, $replacement)
    {
        $matched = '';
        $replaced = '';

        /*
         * Instead of just passing the $replacement callback, we'll wrap another
         * callback around it to also allow us to catch the match & replacement
         * value.
         */
        $callback = function ($match) use ($replacement, &$replaced, &$matched) {
            $matched = $match;
            $replaced = call_user_func($replacement, $match);

            return $replaced;
        };
        $content = preg_replace_callback($pattern, $callback, $content, 1, $count);

        return array($content, $replaced, $matched);
    }

    /**
     * Replaces pattern by a value from a callback, via preg_replace.
     *
     * @param  string          $pattern     Pattern to match.
     * @param  string          $content     Content to match pattern against.
     * @param  string|callable $replacement Replacement value.
     * @return array           [content, replacement, match]
     */
    protected function replaceWithString($pattern, $content, $replacement)
    {
        /*
         * This preg_match is really only meant to capture $match, which we can
         * then also use to deduce the replacement value. We can't just assume
         * $replacement as replacement value, because it may be a back-reference
         * (e.g. \\1)
         */
        if (!preg_match($pattern, $content, $match)) {
            return array($content, '', '');
        }

        $untouched = strlen($content) - strlen($match[0]);
        $content = preg_replace($pattern, $replacement, $content, 1, $count);
        $replaced = (string) substr($content, 0, strlen($content) - $untouched);

        return array($content, $replaced, $match[0]);
    }
}
