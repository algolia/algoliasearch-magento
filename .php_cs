<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__.DIRECTORY_SEPARATOR.'code');

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers([
        'short_array_syntax',
        'multiline_array_trailing_comma',
        'single_array_no_trailing_comma',
        'concat_with_spaces',
        'single_quote',
        'unalign_double_arrow', 
        '-pre_increment',
    ])->finder($finder);