<?php

namespace App\DataFixtures;

use App\Entity\Actor;
use App\Entity\MediaObject;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\HttpFoundation\File\File;

class ActorFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Créer le dossier temporaire si nécessaire
        $tmpDir = sys_get_temp_dir() . '/actor_fixtures';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        for ($i = 0; $i < 100; $i++) {
            $actor = new Actor();
            $actor->setFirstname($faker->firstName());
            $actor->setLastname($faker->lastName());
            $actor->setBio($faker->paragraph());

            $dob = $faker->dateTimeBetween('-80 years', '-15 years');
            $actor->setDob($dob);

            if ($faker->boolean(30)) {
                $dod = $faker->dateTimeBetween($dob, 'now');
                $actor->setDod($dod);
            }

            // Créer un MediaObject pour la photo de l'acteur
            try {
                $imageUrl = "https://placehold.co/300x300/png?text=Actor+" . ($i + 1);
                $imageContent = @file_get_contents($imageUrl);

                if ($imageContent !== false) {
                    $tmpFile = $tmpDir . '/actor_' . $i . '.png';
                    file_put_contents($tmpFile, $imageContent);

                    // Créer le MediaObject
                    $mediaObject = new MediaObject();
                    $file = new File($tmpFile);
                    $mediaObject->file = $file;

                    $manager->persist($mediaObject);

                    // Lier le MediaObject à l'acteur
                    $actor->setPhoto($mediaObject);
                }
            } catch (\Exception $e) {
                // Si le téléchargement échoue, on continue sans photo
                echo "Erreur téléchargement image pour actor $i: " . $e->getMessage() . "\n";
            }

            // Garder aussi l'ancien champ photoName pour la compatibilité
            $actor->setPhotoName("https://placehold.co/300x300/e8117f/white?text=Actor+$i");

            $manager->persist($actor);
            $this->addReference('actor_' . $i, $actor);
        }

        $manager->flush();

        // Nettoie les fichiers temporaires
        if (is_dir($tmpDir)) {
            $files = glob("$tmpDir/*");
            if ($files) {
                array_map('unlink', $files);
            }
            @rmdir($tmpDir);
        }
    }
}
