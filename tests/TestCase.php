<?php

namespace Tests;

use Database\Seeders\CitySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\StateSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * This method boots the application using our custom bootstrap/app.php which
     * returns the configured Application instance.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Setup reference data required for factory relationships.
     */
    protected function seedReferenceData(): void
    {
        $this->seed(CountrySeeder::class);
        $this->seed(StateSeeder::class);
        $this->seed(CitySeeder::class);
    }

    /**
     * Override setUp to automatically seed reference data when using RefreshDatabase.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Check if the test uses RefreshDatabase and if database needs seeding
        if (in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, class_uses_recursive($this))) {
            $this->seedReferenceData();
        }
    }
}
