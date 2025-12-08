<?php

namespace App\DataFixtures;

use App\Entity\Director;
use Doctrine\Persistence\ObjectManager;

class DirectorFixtures extends BaseFixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = $this->createFaker('fr_FR');

        for ($i = 0; $i < 30; $i++) {
            $director = new Director();
            $director->setLastname($faker->lastName);
            $director->setFirstname($faker->firstName);
            $director->setDob($faker->dateTimeBetween('-80 years', '-30 years'));

            // 1 chance sur 4 que le réalisateur soit décédé
            if ($faker->boolean(25)) {
                $director->setDod($faker->dateTimeBetween($director->getDob(), 'now'));
            }

            $manager->persist($director);
        }

        $manager->flush();
    }
}
