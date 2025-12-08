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
        $io->title('📊 Commande de statistiques interactives');

        $type = $io->choice(
            'Quel type de statistiques veux-tu afficher ?',
            ['movies', 'actors', 'categories', 'images', 'all'],
            'all'
        );

        $stats = $this->collectStats();
        $result = $this->generateOutput($type, $stats, $io);

        $this->displayResults($io, $type, $result);
        $this->handleLogFile($input, $result['text']);
        $this->handleEmail($input, $result['text'], $io);

        return Command::SUCCESS;
    }

    private function collectStats(): array
    {
        $imageData = $this->calculateImageStats();

        return [
            'nbMovies' => $this->movieRepository->count([]),
            'nbActors' => $this->actorRepository->count([]),
            'nbCategories' => $this->categoryRepository->count([]),
            'nbMedia' => $this->mediaObjectRepository->count([]),
            'images' => $imageData['images'],
            'totalSizeMb' => $imageData['totalSizeMb'],
        ];
    }

    private function calculateImageStats(): array
    {
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

        return [
            'images' => $images,
            'totalSizeMb' => round($totalSize / 1024 / 1024, 2),
        ];
    }

    private function generateOutput(string $type, array $stats, SymfonyStyle $io): array
    {
        $table = [];
        $outputText = '';

        switch ($type) {
            case 'movies':
                $table[] = ['Films', $stats['nbMovies']];
                $outputText = "🎬 Nombre total de films : {$stats['nbMovies']}";
                break;

            case 'actors':
                $table[] = ['Acteurs', $stats['nbActors']];
                $outputText = "🧑‍🎤 Nombre d'acteurs : {$stats['nbActors']}";
                break;

            case 'categories':
                $result = $this->generateCategoryOutput($stats['nbCategories']);
                $table = $result['table'];
                $outputText = $result['text'];
                break;

            case 'images':
                $result = $this->generateImageOutput($stats, $io);
                $table = $result['table'];
                $outputText = $result['text'];
                break;

            case 'all':
                $table = [
                    ['Films', $stats['nbMovies']],
                    ['Acteurs', $stats['nbActors']],
                    ['Catégories', $stats['nbCategories']],
                    ['Images', $stats['nbMedia']],
                    ['Poids total', "{$stats['totalSizeMb']} Mo"],
                ];
                $outputText = "🎬 {$stats['nbMovies']} films | 🧑‍🎤 {$stats['nbActors']} acteurs | "
                    . "📂 {$stats['nbCategories']} catégories | "
                    . "🖼️ {$stats['nbMedia']} images ({$stats['totalSizeMb']} Mo)";
                break;
        }

        return ['table' => $table, 'text' => $outputText];
    }

    private function generateCategoryOutput(int $nbCategories): array
    {
        $categories = $this->categoryRepository->findAll();
        $table = [];
        foreach ($categories as $category) {
            $table[] = [
                $category->getName(),
                $category->getMovies()->count() . ' film(s)'
            ];
        }
        $text = "📂 Nombre total de catégories : $nbCategories";
        return ['table' => $table, 'text' => $text];
    }

    private function generateImageOutput(array $stats, SymfonyStyle $io): array
    {
        $table = [];
        if (empty($stats['images'])) {
            $io->warning('Aucune image trouvée dans le dossier.');
        }
        $table = $stats['images'];
        $table[] = ['💾 Total', "{$stats['totalSizeMb']} Mo"];
        $text = "🖼️ Nombre d'images : {$stats['nbMedia']} | "
            . "💾 Poids total : {$stats['totalSizeMb']} Mo";
        return ['table' => $table, 'text' => $text];
    }

    private function displayResults(SymfonyStyle $io, string $type, array $result): void
    {
        $io->section('Résultats');

        $headers = match ($type) {
            'categories' => ['Nom de la catégorie', 'Nombre de films'],
            'images' => ['Nom du fichier', 'Taille'],
            default => ['Nom de l\'entité', 'Valeur'],
        };

        $io->table($headers, $result['table']);
        $io->success('Statistiques générées avec succès');
    }

    private function handleLogFile(InputInterface $input, string $outputText): void
    {
        $logFile = $input->getOption('log-file');
        if ($logFile) {
            file_put_contents($logFile, $outputText . PHP_EOL, FILE_APPEND);
        }
    }

    private function handleEmail(InputInterface $input, string $outputText, SymfonyStyle $io): void
    {
        $sendMail = $input->getOption('send-mail');
        if ($sendMail) {
            $email = (new Email())
                ->from('noreply@monapp.com')
                ->to($sendMail)
                ->subject('📊 Statistiques de la base de données')
                ->text($outputText);

            $this->mailer->send($email);
            $io->writeln("📧 Email envoyé à : $sendMail");
        }
    }
}
