<?php

namespace ElliottLawson\Converse\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    
    protected function getPackageProviders($app)
    {
        return [
            \ElliottLawson\Converse\ConverseServiceProvider::class,
        ];
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
