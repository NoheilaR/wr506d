<?php

namespace App\Command;

use App\Repository\ActorRepository;
use App\Repository\CategoryRepository;
use App\Repository\MovieRepository;
use App\Repository\MediaObjectRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:stats',
    description: 'Affiche les statistiques sur la base de données'
)]
class StatsCommand extends Command
{
    public function __construct(
        private MovieRepository $movieRepository,
        private ActorRepository $actorRepository,
        private CategoryRepository $categoryRepository,
        private MediaObjectRepository $mediaObjectRepository,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Affiche les statistiques sur la base de données')
            ->addOption('log-file', null, InputOption::VALUE_OPTIONAL, 'Chemin du fichier de log (facultatif)')
            ->addOption('send-mail', null, InputOption::VALUE_OPTIONAL, 'Adresse email du destinataire (facultatif)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('📊 Commande de statistiques interactives');

        $type = $io->choice(
            'Quel type de statistiques veux-tu afficher ?',
            ['movies', 'actors', 'categories', 'images', 'all'],
            'all'
        );

        $logFile = $input->getOption('log-file');
        $sendMail = $input->getOption('send-mail');

        $nbMovies = $this->movieRepository->count([]);
        $nbActors = $this->actorRepository->count([]);
        $nbCategories = $this->categoryRepository->count([]);
        $nbMedia = $this->mediaObjectRepository->count([]);

        $totalSize = 0;
        $path = __DIR__ . '/../../public/uploads/actors';
        $images = [];

        if (is_dir($path)) {
            foreach (scandir($path) as $file) {
                if ($file !== '.' && $file !== '..') {
                    $size = filesize($path . '/' . $file);
                    $totalSize += $size;
                    $images[] = [$file, round($size / 1024, 2) . ' Ko'];
                }
            }
        }

        $totalSizeMb = round($totalSize / 1024 / 1024, 2);
        $table = [];
        $outputText = '';

        switch ($type) {
            case 'movies':
                $table[] = ['Films', $nbMovies];
                $outputText = "🎬 Nombre total de films : $nbMovies";
                break;

            case 'actors':
                $table[] = ['Acteurs', $nbActors];
                $outputText = "🧑‍🎤 Nombre d'acteurs : $nbActors";
                break;

            case 'categories':
                $categories = $this->categoryRepository->findAll();
                foreach ($categories as $category) {
                    $table[] = [$category->getName(), $category->getMovies()->count() . ' film(s)'];
                }
                $outputText = "📂 Nombre total de catégories : $nbCategories";
                break;

            case 'images':
                if (empty($images)) {
                    $io->warning('Aucune image trouvée dans le dossier.');
                } else {
                    $table = $images;
                }
                $table[] = ['💾 Total', "{$totalSizeMb} Mo"];
                $outputText = "🖼️ Nombre d'images : $nbMedia | 💾 Poids total : {$totalSizeMb} Mo";
                break;

            case 'all':
                $table = [
                    ['Films', $nbMovies],
                    ['Acteurs', $nbActors],
                    ['Catégories', $nbCategories],
                    ['Images', $nbMedia],
                    ['Poids total', "{$totalSizeMb} Mo"],
                ];
                $outputText = "🎬 $nbMovies films | 🧑‍🎤 $nbActors acteurs | 📂 $nbCategories catégories | 🖼️ $nbMedia images ({$totalSizeMb} Mo)";
                break;
        }

        $io->section('Résultats');

        $headers = match ($type) {
            'categories' => ['Nom de la catégorie', 'Nombre de films'],
            'images' => ['Nom du fichier', 'Taille'],
            default => ['Nom de l\'entité', 'Valeur'],
        };

        $io->table($headers, $table);
        $io->success('Statistiques générées avec succès ✅');

        if ($logFile) {
            file_put_contents($logFile, $outputText . PHP_EOL, FILE_APPEND);
            $io->writeln("🗂️ Résultat enregistré dans le fichier : $logFile");
        }

        if ($sendMail) {
            $email = (new Email())
                ->from('noreply@monapp.com')
                ->to($sendMail)
                ->subject('📊 Statistiques de la base de données')
                ->text($outputText);

            $this->mailer->send($email);
            $io->writeln("📧 Email envoyé à : $sendMail");
        }

        return Command::SUCCESS;
    }
}
