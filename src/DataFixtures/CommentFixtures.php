<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Movie;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Charger les films et utilisateurs EXISTANTS
        $movieRepository = $manager->getRepository(Movie::class);
        $userRepository = $manager->getRepository(User::class);

        $movies = $movieRepository->findAll();
        $users = $userRepository->findAll();

        if (empty($movies)) {
            echo "❌ Aucun film trouvé dans la base de données. Créez d'abord des films.\n";
            return;
        }

        if (empty($users)) {
            echo "❌ Aucun utilisateur trouvé dans la base de données. Créez d'abord des utilisateurs.\n";
            return;
        }

        echo "✅ Nombre de films trouvés : " . count($movies) . "\n";
        echo "✅ Nombre d'utilisateurs trouvés : " . count($users) . "\n";

        // Créer entre 200 et 300 commentaires
        $nbComments = $faker->numberBetween(200, 300);
        echo "📝 Création de {$nbComments} commentaires...\n";

        for ($i = 0; $i < $nbComments; $i++) {
            $comment = new Comment();

            // Contenu du commentaire (2 à 5 phrases)
            $comment->setContent($faker->paragraph($faker->numberBetween(2, 5)));

            // Note aléatoire (70% des commentaires ont une note)
            if ($faker->boolean(70)) {
                $comment->setRating($faker->numberBetween(1, 5));
            }

            // Associer un film et un utilisateur aléatoires
            $comment->setMovie($faker->randomElement($movies));
            $comment->setAuthor($faker->randomElement($users));

            $manager->persist($comment);

            // Afficher la progression tous les 50 commentaires
            if (($i + 1) % 50 === 0) {
                echo "  → " . ($i + 1) . " commentaires créés...\n";
            }
        }

        $manager->flush();
        echo "✅ {$nbComments} commentaires créés avec succès !\n";
    }

    public function getDependencies(): array
    {
        return [
            MovieFixtures::class,
            // Pas de UserFixtures car on utilise les vrais utilisateurs
        ];
    }
}
