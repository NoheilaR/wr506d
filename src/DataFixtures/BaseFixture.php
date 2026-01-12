<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Faker\Factory;
use Faker\Generator;

abstract class BaseFixture extends Fixture
{
    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function createFaker(?string $locale = null): Generator
    {
        return Factory::create($locale);
    }
}
