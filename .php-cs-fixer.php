<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__);

$config = new PhpCsFixer\Config();

return $config
    ->setRules(array(
        '@Symfony' => true,
        'array_syntax' => array('syntax' => 'long'),
        'single_line_throw' => false,
        'yoda_style' => array('equal' => false, 'identical' => false, 'less_and_greater' => false),
        '@PSR12' => true,
        'class_definition' => false, // @see https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/5463
        'visibility_required' => array('elements' => array('property', 'method')),
    ))
    ->setFinder($finder)
    ->setUsingCache(false);
