<?php

namespace {
    require __DIR__.'/../vendor/autoload.php';
}

namespace PHPUnit\Framework
{
    // compatibility for when these tests are run with PHPUnit<6.0 (which we
    // still do because PHPUnit=6.0 stopped supporting a lot of PHP versions)
    if (!class_exists('PHPUnit\Framework\TestCase')) {
        abstract class TestCase extends \PHPUnit_Framework_TestCase
        {
        }
    }
}
