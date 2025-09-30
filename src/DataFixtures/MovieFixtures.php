<?php

namespace App\DataFixtures;

use App\Entity\Movie;
use App\Entity\Actor;
use App\Entity\Category;
use App\Entity\Director;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Xylis\FakerCinema\Provider\Movie as MovieProvider;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class MovieFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $faker->addProvider(new MovieProvider($faker));

        // Charger acteurs, catégories, réalisateurs
        $actorRepository = $manager->getRepository(Actor::class);
        $categoryRepository = $manager->getRepository(Category::class);
        $directorRepository = $manager->getRepository(Director::class);

        $actors = $actorRepository->findAll();
        $categories = $categoryRepository->findAll();
        $directors = $directorRepository->findAll();

        if (empty($actors)) {
            echo "Aucun acteur trouvé dans la base de données\n";
            return;
        }
        if (empty($categories)) {
            echo "Aucune catégorie trouvée dans la base de données\n";
            return;
        }
        if (empty($directors)) {
            echo "Aucun réalisateur trouvé dans la base de données\n";
            return;
        }

        echo "Nombre d'acteurs trouvés : " . count($actors) . "\n";
        echo "Nombre de catégories trouvées : " . count($categories) . "\n";
        echo "Nombre de réalisateurs trouvés : " . count($directors) . "\n";

        for ($i = 0; $i < 100; $i++) {
            $movie = new Movie();

            $movie->setName($faker->movie);
            $movie->setDescription($faker->overview);
            $movie->setDuration($faker->numberBetween(80, 180));
            $movie->setReleaseDate($faker->dateTimeBetween('-30 years', 'now'));
            $movie->setImage("https://placehold.co/600x600/117fe8/white?text=Movie+$i");

            // ✅ nouveaux champs
            $movie->setNbEntries($faker->numberBetween(10000, 5000000));
            $movie->setUrl($faker->url);
            $movie->setBudget($faker->randomFloat(2, 1000000, 500000000));

            // Associer un réalisateur
            $movie->setDirector($faker->randomElement($directors));

            // Acteurs (1 à 3)
            $randomActors = $faker->randomElements($actors, $faker->numberBetween(1, 3));
            foreach ($randomActors as $actor) {
                $movie->addActor($actor);
            }

            // Catégories (1 à 2)
            $randomCategories = $faker->randomElements($categories, $faker->numberBetween(1, 2));
            foreach ($randomCategories as $category) {
                $movie->addCategory($category);
            }

            $manager->persist($movie);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ActorFixtures::class,
            CategoryFixtures::class,
            DirectorFixtures::class, // ajout des réalisateurs
        ];
    }
}
