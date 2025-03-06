<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__);

$config = new PhpCsFixer\Config();

return $config
    ->setRules(array(
        '@Symfony' => true,
        '@PER-CS' => true,
        'array_syntax' => array('syntax' => 'long'),
        'concat_space' => array('spacing' => 'one'),
        'single_line_throw' => false,
        'yoda_style' => array('equal' => false, 'identical' => false, 'less_and_greater' => false),
        'visibility_required' => array('elements' => array('property', 'method')),
        'phpdoc_align' => array('align' => 'left'),
        'trailing_comma_in_multiline' => array('elements' => array('arrays')),
    ))
    ->setFinder($finder)
    ->setUsingCache(false);
