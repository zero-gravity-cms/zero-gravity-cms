<?php

namespace Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module;

class Unit extends Module
{
    public function getPageFixtureDir(): string
    {
        /* @phpstan-ignore-next-line this always returns a string, but phpstan thinks otherwise */
        return codecept_data_dir('page_fixtures');
    }
}
