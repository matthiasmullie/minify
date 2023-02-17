<?php

namespace MatthiasMullie\Minify\Tests\JS;

use MatthiasMullie\Minify\JS;

class NoSaveJS extends JS
{
    protected function save($content, $path)
    {
        // do nothing
    }
}
