<?php

use MatthiasMullie\Minify;
use Scrapbook\Adapters\MemoryStore;
use Scrapbook\Psr6\Pool;

/**
 * Tests common functions of abstract Minify class by using JS implementation.
 */
class CommonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function construct()
    {
        $path1 = __DIR__.'/sample/source/script1.js';
        $path2 = __DIR__.'/sample/source/script2.js';
        $content1 = file_get_contents($path1);
        $content2 = file_get_contents($path2);

        // 1 source in constructor
        $minifier = new Minify\JS($content1);
        $result = $minifier->minify();

        $this->assertEquals($content1, $result);

        // multiple sources in constructor
        $minifier = new Minify\JS($content1, $content2);
        $result = $minifier->minify();

        $this->assertEquals($content1.';'.$content2, $result);

        // file in constructor
        $minifier = new Minify\JS($path1);
        $result = $minifier->minify();

        $this->assertEquals($content1, $result);

        // multiple files in constructor
        $minifier = new Minify\JS($path1, $path2);
        $result = $minifier->minify();

        $this->assertEquals($content1.';'.$content2, $result);
    }

    /**
     * @test
     */
    public function add()
    {
        $path1 = __DIR__.'/sample/source/script1.js';
        $path2 = __DIR__.'/sample/source/script2.js';
        $content1 = file_get_contents($path1);
        $content2 = file_get_contents($path2);

        // 1 source in add
        $minifier = new Minify\JS();
        $minifier->add($content1);
        $result = $minifier->minify();

        $this->assertEquals($content1, $result);

        // multiple sources in add
        $minifier = new Minify\JS();
        $minifier->add($content1);
        $minifier->add($content2);
        $result = $minifier->minify();

        $this->assertEquals($content1.';'.$content2, $result);

        // file in add
        $minifier = new Minify\JS();
        $minifier->add($path1);
        $result = $minifier->minify();

        $this->assertEquals($content1, $result);

        // multiple files in add
        $minifier = new Minify\JS();
        $minifier->add($path1);
        $minifier->add($path2);
        $result = $minifier->minify();

        $this->assertEquals($content1.';'.$content2, $result);
    }

    /**
     * @test
     */
    public function save()
    {
        $path = __DIR__.'/sample/source/script1.js';
        $content = file_get_contents($path);
        $savePath = __DIR__.'/sample/target/script1.js';

        $minifier = new Minify\JS($path);
        $minifier->minify($savePath);

        $this->assertEquals(file_get_contents($savePath), $content);
    }

    /**
     * @test
     */
    public function gzip()
    {
        $path = __DIR__.'/sample/source/script1.js';
        $content = file_get_contents($path);
        $savePath = __DIR__.'/sample/target/script1.js.gz';

        $minifier = new Minify\JS($path);
        $minifier->gzip($savePath, 9);

        $this->assertEquals(file_get_contents($savePath), gzencode($content, 9, FORCE_GZIP));
    }

    /**
     * @test
     */
    public function cache()
    {
        $path = __DIR__.'/sample/source/script1.js';
        $content = file_get_contents($path);

        $cache = new MemoryStore;
        $pool = new Pool($cache);
        $item = $pool->getItem('cache-script1');

        $minifier = new Minify\JS($path);
        $item = $minifier->cache($item);

        $this->assertEquals($item->get(), $content);
    }
}
