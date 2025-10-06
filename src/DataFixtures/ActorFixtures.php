<?php

namespace App\DataFixtures;

use App\Entity\Actor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Xylis\FakerCinema\Provider\Person;

class ActorFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $faker->addProvider(new Person($faker));

        for ($i = 0; $i < 100; $i++) {
            $actor = new Actor();
            $actor->setFirstname($faker->firstName());
            $actor->setLastname($faker->lastName());
            $actor->setBio($faker->paragraph());
            // $actor->setCreatedAt(new \DateTimeImmutable());

            // Génère une date de naissance aléatoire entre il y a 80 ans et il y a 15 ans
            $dob = $faker->dateTimeBetween('-80 years', '-15 years');
            $actor->setDob($dob);

            // 30% de chance d'avoir une date de décès
            if ($faker->boolean(30)) {
                // S'assure que la date de décès est après la date de naissance
                $dod = $faker->dateTimeBetween($dob, 'now');
                $actor->setDod($dod);
            }
            // Utilisation de placehold.co pour les images d'acteur
            $actor->setPhoto("https://placehold.co/300x300/e8117f/white?text=Actor+$i");

            $manager->persist($actor);
            $this->addReference('actor_' . $i, $actor);
        }

        $manager->flush();
    }
}
