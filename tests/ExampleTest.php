<?php

declare(strict_types=1);

use PoorPlebs\PackageTemplate\ExampleFile;

covers(ExampleFile::class);

it('can be instantiated', function (): void {
    $instance = new ExampleFile();

    expect($instance)->toBeInstanceOf(ExampleFile::class);
});
