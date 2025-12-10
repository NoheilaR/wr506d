<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-api-key',
    description: 'Generate an API key for a user',
)]
class GenerateApiKeyCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        // Find user by email
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('User with email "%s" not found', $email));
            return Command::FAILURE;
        }

        // Check if user already has an API key
        if ($user->getApiKeyHash() !== null) {
            $io->warning('User already has an API key. It will be regenerated and the old one will be revoked.');
        }

        // Generate new API key
        $apiKey = $user->generateApiKey();

        // Persist changes
        $this->entityManager->flush();

        // Display success message with key details
        $io->success(sprintf('API Key generated for user: %s', $email));

        $io->warning('IMPORTANT: Copy this key now. It will NOT be shown again!');

        $io->section('API Key Details');
        $io->table(
            ['Field', 'Value'],
            [
                ['API Key', $apiKey],
                ['Prefix', $user->getApiKeyPrefix()],
                ['Status', $user->isApiKeyEnabled() ? 'Enabled' : 'Disabled'],
                ['Created at', $user->getApiKeyCreatedAt()->format('Y-m-d H:i:s')],
            ]
        );

        $io->note([
            'Store this key in a secure location.',
            'The key will never be displayed again.',
            'Use the header: X-API-Key: ' . $apiKey,
        ]);

        return Command::SUCCESS;
    }
}
