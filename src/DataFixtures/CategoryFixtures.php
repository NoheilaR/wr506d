<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Xylis\FakerCinema\Provider\Movie as MovieProvider;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $faker->addProvider(new MovieProvider($faker));

        // On limite à 8 genres uniques
        $genres = [];
        while (count($genres) < 20) {
            $genres[] = $faker->movieGenre;
            $genres = array_unique($genres);
        }

        foreach ($genres as $index => $genre) {
            $category = new Category();
            $category->setName($genre);
          //  $category->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($category);
            $this->addReference('category_' . $index, $category);
        }

        $manager->flush();
    }
}
