<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Unit extends \Codeception\Module
{
    public function getPageFixtureDir()
    {
        return realpath(__DIR__.'/../../_data/page_fixtures');
    }
}
