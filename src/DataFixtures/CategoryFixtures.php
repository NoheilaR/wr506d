<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Persistence\ObjectManager;
use Xylis\FakerCinema\Provider\Movie as MovieProvider;

class CategoryFixtures extends BaseFixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = $this->createFaker();
        $faker->addProvider(new MovieProvider($faker));

        // On limite à 20 genres uniques
        $genres = [];
        $targetCount = 20;
        $genresCount = 0;
        while ($genresCount < $targetCount) {
            $genres[] = $faker->format('movieGenre');
            $genres = array_unique($genres);
            $genresCount = count($genres);
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
