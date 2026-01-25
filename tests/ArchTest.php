<?php

declare(strict_types=1);

arch('all source files use strict types')
    ->expect('PoorPlebs\PackageTemplate')
    ->toUseStrictTypes();

arch('all test files use strict types')
    ->expect('PoorPlebs\PackageTemplate\Tests')
    ->toUseStrictTypes();

arch('source files have proper namespace')
    ->expect('PoorPlebs\PackageTemplate')
    ->toBeClasses();

arch('no debugging functions in source code')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray'])
    ->not->toBeUsed();
