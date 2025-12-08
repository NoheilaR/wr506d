<?php
// src/Command/UpdateUserRateLimitsCommand.php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-rate-limits',
    description: 'Update rate limits based on user roles',
)]
class UpdateUserRateLimitsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            $roles = $user->getRoles();

            if (in_array('ROLE_ADMIN', $roles, true)) {
                $user->setApiRateLimit(1000); // Admins : 1000 requêtes
            } elseif (in_array('ROLE_EDITOR', $roles, true)) {
                $user->setApiRateLimit(500); // ✅ Éditeurs : 500 requêtes
            } else {
                $user->setApiRateLimit(50); // ✅ Users normaux : 50 requêtes
            }

            $io->info(sprintf(
                'User %s (%s): %d requests/hour',
                $user->getEmail(),
                implode(', ', $roles),
                $user->getApiRateLimit()
            ));
        }

        $this->entityManager->flush();

        $io->success('Rate limits updated successfully!');

        return Command::SUCCESS;
    }
}
