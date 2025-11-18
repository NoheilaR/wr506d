<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Xylis\FakerCinema\Provider\Movie as MovieProvider;

class CategoryFixtures extends Fixture
{
    private const GENRE_COUNT = 20;

    public function load(ObjectManager $manager): void
    {
        $factory = new Factory();
        $faker = $factory->create();
        $faker->addProvider(new MovieProvider($faker));

        $genres = $this->generateUniqueGenres($faker);

        foreach ($genres as $index => $genre) {
            $category = new Category();
            $category->setName($genre);
            $manager->persist($category);
            $this->addReference('category_' . $index, $category);
        }

        $manager->flush();
    }

    private function generateUniqueGenres(Generator $faker): array
    {
        $genres = [];
        $maxGenres = self::GENRE_COUNT;
        $currentCount = 0;

        while ($currentCount < $maxGenres) {
            $genre = $faker->movieGenre;

            if (!isset($genres[$genre])) {
                $genres[$genre] = $genre;
                $currentCount++;
            }
        }

        return array_values($genres);
    }
}
