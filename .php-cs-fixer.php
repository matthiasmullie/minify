<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
return $config->setRules(array(
    '@Symfony' => true,
    'array_syntax' => array('syntax' => 'long'),
    'visibility_required' => array('elements' => array('property', 'method')),
))
    ->setFinder($finder)
    ->setUsingCache(false);
