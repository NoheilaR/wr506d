<?php

namespace App\DataFixtures;

use App\Entity\Actor;
use App\Entity\MediaObject;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Faker\Factory;
use Symfony\Component\HttpFoundation\File\File;

class ActorFixtures extends Fixture
{
    private const ACTOR_COUNT = 100;
    private const IMAGE_PLACEHOLDER = 'https://placehold.co/300x300/png?text=Actor+';

    public function load(ObjectManager $manager): void
    {
        $factory = new Factory();
        $faker = $factory->create();
        $tmpDir = $this->prepareTempDirectory();

        for ($i = 0; $i < self::ACTOR_COUNT; $i++) {
            $actor = $this->createActor($faker, $i, $tmpDir, $manager);
            $manager->persist($actor);
            $this->addReference('actor_' . $i, $actor);
        }

        $manager->flush();
        $this->cleanupTempDirectory($tmpDir);
    }

    private function prepareTempDirectory(): string
    {
        $tmpDir = sys_get_temp_dir() . '/actor_fixtures';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
        return $tmpDir;
    }

    private function createActor(
        Generator $faker,
        int $index,
        string $tmpDir,
        ObjectManager $manager
    ): Actor {
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

        $this->attachPhoto($actor, $index, $tmpDir, $manager);
        $actor->setPhotoName("https://placehold.co/300x300/e8117f/white?text=Actor+$index");

        return $actor;
    }

    private function attachPhoto(
        Actor $actor,
        int $index,
        string $tmpDir,
        ObjectManager $manager
    ): void {
        try {
            $imageUrl = self::IMAGE_PLACEHOLDER . ($index + 1);
            $imageContent = file_get_contents($imageUrl);

            if ($imageContent === false) {
                return;
            }

            $tmpFile = $tmpDir . '/actor_' . $index . '.png';
            file_put_contents($tmpFile, $imageContent);

            $mediaObject = new MediaObject();
            $file = new File($tmpFile);
            $mediaObject->file = $file;

            $manager->persist($mediaObject);
            $actor->setPhoto($mediaObject);
        } catch (\Exception $e) {
            echo "Erreur téléchargement image pour actor $index: " . $e->getMessage() . "\n";
        }
    }

    private function cleanupTempDirectory(string $tmpDir): void
    {
        if (!is_dir($tmpDir)) {
            return;
        }

        $files = glob("$tmpDir/*");
        if ($files) {
            array_map('unlink', $files);
        }

        if (is_dir($tmpDir)) {
            rmdir($tmpDir);
        }
    }
}
