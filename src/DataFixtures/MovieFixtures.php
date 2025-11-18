<?php

namespace App\DataFixtures;

use App\Entity\Movie;
use App\Entity\Actor;
use App\Entity\Category;
use App\Entity\Director;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Xylis\FakerCinema\Provider\Movie as MovieProvider;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class MovieFixtures extends Fixture implements DependentFixtureInterface
{
    private const MOVIE_COUNT = 100;

    public function load(ObjectManager $manager): void
    {
        $factory = new Factory();
        $faker = $factory->create();
        $faker->addProvider(new MovieProvider($faker));

        $entities = $this->loadEntities($manager);

        if (!$this->validateEntities($entities)) {
            return;
        }

        $this->displayEntityCounts($entities);

        for ($i = 0; $i < self::MOVIE_COUNT; $i++) {
            $movie = $this->createMovie($faker, $i, $entities);
            $manager->persist($movie);
        }

        $manager->flush();
    }

    private function loadEntities(ObjectManager $manager): array
    {
        return [
            'actors' => $manager->getRepository(Actor::class)->findAll(),
            'categories' => $manager->getRepository(Category::class)->findAll(),
            'directors' => $manager->getRepository(Director::class)->findAll(),
        ];
    }

    private function validateEntities(array $entities): bool
    {
        if (empty($entities['actors'])) {
            echo "Aucun acteur trouvé dans la base de données\n";
            return false;
        }
        if (empty($entities['categories'])) {
            echo "Aucune catégorie trouvée dans la base de données\n";
            return false;
        }
        if (empty($entities['directors'])) {
            echo "Aucun réalisateur trouvé dans la base de données\n";
            return false;
        }

        return true;
    }

    private function displayEntityCounts(array $entities): void
    {
        echo "Nombre d'acteurs trouvés : " . count($entities['actors']) . "\n";
        echo "Nombre de catégories trouvées : " . count($entities['categories']) . "\n";
        echo "Nombre de réalisateurs trouvés : " . count($entities['directors']) . "\n";
    }

    private function createMovie(Generator $faker, int $index, array $entities): Movie
    {
        $movie = new Movie();

        $movie->setName($faker->movie);
        $movie->setDescription($faker->overview);
        $movie->setDuration($faker->numberBetween(80, 180));
        $movie->setReleaseDate($faker->dateTimeBetween('-30 years', 'now'));
        $movie->setImage("https://placehold.co/600x600/117fe8/white?text=Movie+$index");
        $movie->setNbEntries($faker->numberBetween(10000, 5000000));
        $movie->setUrl($faker->url);
        $movie->setBudget($faker->randomFloat(2, 1000000, 500000000));

        $this->attachRelations($movie, $faker, $entities);

        return $movie;
    }

    private function attachRelations(Movie $movie, Generator $faker, array $entities): void
    {
        $movie->setDirector($faker->randomElement($entities['directors']));

        $randomActors = $faker->randomElements($entities['actors'], $faker->numberBetween(1, 3));
        foreach ($randomActors as $actor) {
            $movie->addActor($actor);
        }

        $randomCategories = $faker->randomElements($entities['categories'], $faker->numberBetween(1, 2));
        foreach ($randomCategories as $category) {
            $movie->addCategory($category);
        }
    }

    public function getDependencies(): array
    {
        return [
            ActorFixtures::class,
            CategoryFixtures::class,
            DirectorFixtures::class,
        ];
    }
}
