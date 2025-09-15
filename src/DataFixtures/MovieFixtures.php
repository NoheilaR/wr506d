<?php

namespace App\DataFixtures;

use App\Entity\Movie;
use App\Entity\Actor;
use App\Entity\Category;
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

        // Charger les acteurs et catégories directement depuis la base de données
        $actorRepository = $manager->getRepository(Actor::class);
        $categoryRepository = $manager->getRepository(Category::class);

        $actors = $actorRepository->findAll();
        $categories = $categoryRepository->findAll();

        // Vérifier que les acteurs et catégories existent
        if (empty($actors)) {
            echo "Aucun acteur trouvé dans la base de données\n";
            return;
        }
        if (empty($categories)) {
            echo "Aucune catégorie trouvée dans la base de données\n";
            return;
        }

        echo "Nombre d'acteurs trouvés : " . count($actors) . "\n";
        echo "Nombre de catégories trouvées : " . count($categories) . "\n";

        for ($i = 0; $i < 10; $i++) {
            $movie = new Movie();

            $movie->setName($faker->movie);
            $movie->setDescription($faker->overview);
            $movie->setDuration($faker->numberBetween(80, 180));
            $movie->setReleaseDate($faker->dateTimeBetween('-30 years', 'now'));
            $movie->setImage("https://picsum.photos/400/600?random=" . $i);

            // Ajouter des acteurs (par exemple, 1 à 3 acteurs aléatoires)
            $numActors = $faker->numberBetween(1, 3);
            $randomActors = $faker->randomElements($actors, $numActors);
            foreach ($randomActors as $actor) {
                $movie->addActor($actor);
                echo "Ajout de l'acteur ID {$actor->getId()} au film $i\n";
            }

            // Ajouter des catégories (par exemple, 1 à 2 catégories aléatoires)
            $numCategories = $faker->numberBetween(1, 2);
            $randomCategories = $faker->randomElements($categories, $numCategories);
            foreach ($randomCategories as $category) {
                $movie->addCategory($category);
                echo "Ajout de la catégorie {$category->getName()} au film $i\n";
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
        ];
    }
}
