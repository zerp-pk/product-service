<?php

namespace Zerp\ProductService\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Zerp\ProductService\Providers\ProductServiceServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [ProductServiceServiceProvider::class];
    }
}
