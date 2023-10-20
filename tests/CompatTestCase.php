<?php

namespace MatthiasMullie\Minify\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

if (
    method_exists('PHPUnit\\Framework\\TestCase', 'expectException')
    || !method_exists('PHPUnit\\Framework\\TestCase', 'setExpectedException')
) {
    class CompatTestCase extends PHPUnitTestCase
    {
    }
} else {
    class CompatTestCase extends PHPUnitTestCase
    {
        public function expectException($exception)
        {
            parent::setExpectedException($exception);
        }
    }
}
