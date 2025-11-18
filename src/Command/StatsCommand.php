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
            ->addOption(
                'log-file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Chemin du fichier de log (facultatif)'
            )
            ->addOption(
                'send-mail',
                null,
                InputOption::VALUE_OPTIONAL,
                'Adresse email du destinataire (facultatif)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Commande de statistiques interactives');

        $type = $io->choice(
            'Quel type de statistiques veux-tu afficher ?',
            ['movies', 'actors', 'categories', 'images', 'all'],
            'all'
        );

        $stats = $this->collectStats();
        $result = $this->generateStatsForType($type, $stats);

        $this->displayResults($io, $result, $type);
        $this->handleLogFile($input, $io, $result['outputText']);
        $this->handleEmail($input, $io, $result['outputText']);

        return Command::SUCCESS;
    }

    private function collectStats(): array
    {
        $images = $this->scanImagesDirectory();

        return [
            'nbMovies' => $this->movieRepository->count([]),
            'nbActors' => $this->actorRepository->count([]),
            'nbCategories' => $this->categoryRepository->count([]),
            'nbMedia' => $this->mediaObjectRepository->count([]),
            'images' => $images['files'],
            'totalSizeMb' => $images['totalSizeMb'],
        ];
    }

    private function scanImagesDirectory(): array
    {
        $path = __DIR__ . '/../../public/uploads/actors';
        $images = [];
        $totalSize = 0;

        if (!is_dir($path)) {
            return ['files' => [], 'totalSizeMb' => 0];
        }

        $files = scandir($path);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $path . '/' . $file;
            $size = filesize($filePath);
            $totalSize += $size;
            $images[] = [$file, round($size / 1024, 2) . ' Ko'];
        }

        return [
            'files' => $images,
            'totalSizeMb' => round($totalSize / 1024 / 1024, 2),
        ];
    }

    private function generateStatsForType(string $type, array $stats): array
    {
        return match ($type) {
            'movies' => $this->getMovieStats($stats),
            'actors' => $this->getActorStats($stats),
            'categories' => $this->getCategoryStats($stats),
            'images' => $this->getImageStats($stats),
            'all' => $this->getAllStats($stats),
            default => ['table' => [], 'outputText' => '', 'headers' => []],
        };
    }

    private function getMovieStats(array $stats): array
    {
        return [
            'table' => [['Films', $stats['nbMovies']]],
            'outputText' => " Nombre total de films : {$stats['nbMovies']}",
            'headers' => ['Nom de l\'entité', 'Valeur'],
        ];
    }

    private function getActorStats(array $stats): array
    {
        return [
            'table' => [['Acteurs', $stats['nbActors']]],
            'outputText' => " Nombre d'acteurs : {$stats['nbActors']}",
            'headers' => ['Nom de l\'entité', 'Valeur'],
        ];
    }

    private function getCategoryStats(array $stats): array
    {
        $categories = $this->categoryRepository->findAll();
        $table = [];

        foreach ($categories as $category) {
            $table[] = [
                $category->getName(),
                $category->getMovies()->count() . ' film(s)'
            ];
        }

        return [
            'table' => $table,
            'outputText' => "Nombre total de catégories : {$stats['nbCategories']}",
            'headers' => ['Nom de la catégorie', 'Nombre de films'],
        ];
    }

    private function getImageStats(array $stats): array
    {
        $table = empty($stats['images']) ? [] : $stats['images'];
        $table[] = [' Total', "{$stats['totalSizeMb']} Mo"];

        return [
            'table' => $table,
            'outputText' => "Nombre d'images : {$stats['nbMedia']} | "
                . "Poids total : {$stats['totalSizeMb']} Mo",
            'headers' => ['Nom du fichier', 'Taille'],
        ];
    }

    private function getAllStats(array $stats): array
    {
        return [
            'table' => [
                ['Films', $stats['nbMovies']],
                ['Acteurs', $stats['nbActors']],
                ['Catégories', $stats['nbCategories']],
                ['Images', $stats['nbMedia']],
                ['Poids total', "{$stats['totalSizeMb']} Mo"],
            ],
            'outputText' => " {$stats['nbMovies']} films | "
                . " {$stats['nbActors']} acteurs | "
                . " {$stats['nbCategories']} catégories | "
                . " {$stats['nbMedia']} images ({$stats['totalSizeMb']} Mo)",
            'headers' => ['Nom de l\'entité', 'Valeur'],
        ];
    }

    private function displayResults(SymfonyStyle $io, array $result, string $type): void
    {
        $io->section('Résultats');

        if ($type === 'images' && empty($result['table'])) {
            $io->warning('Aucune image trouvée dans le dossier.');
            return;
        }

        $io->table($result['headers'], $result['table']);
        $io->success('Statistiques générées avec succès ');
    }

    private function handleLogFile(InputInterface $input, SymfonyStyle $io, string $content): void
    {
        $logFile = $input->getOption('log-file');

        if (!$logFile) {
            return;
        }

        file_put_contents($logFile, $content . PHP_EOL, FILE_APPEND);
        $io->writeln(" Résultat enregistré dans le fichier : $logFile");
    }

    private function handleEmail(InputInterface $input, SymfonyStyle $io, string $content): void
    {
        $sendMail = $input->getOption('send-mail');

        if (!$sendMail) {
            return;
        }

        $email = (new Email())
            ->from('noreply@monapp.com')
            ->to($sendMail)
            ->subject(' Statistiques de la base de données')
            ->text($content);

        $this->mailer->send($email);
        $io->writeln(" Email envoyé à : $sendMail");
    }
}
