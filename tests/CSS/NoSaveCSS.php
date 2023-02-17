<?php

namespace MatthiasMullie\Minify\Tests\CSS;

use MatthiasMullie\Minify\CSS;

class NoSaveCSS extends CSS
{
    protected function save($content, $path)
    {
        // do nothing
    }
}
