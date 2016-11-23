#!/bin/env php
<?php

return Symfony\CS\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers([
        '-psr0',
        'concat_with_spaces',
        'newline_after_open_tag',
        'ordered_use',
        'short_array_syntax',
    ])
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->in(__DIR__)
    )
    ;
