<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Comment;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;  // ✅ FIX : Import bundle (pas core)
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class CommentProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $decorated,
        private Security $security,  // ✅ FIX : Type bundle Security (compatible getUser())
        private LoggerInterface $logger
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Comment) {
            try {
                $user = $this->security->getUser();

                if (!$user instanceof User) {
                    $this->logger->error('User not instanceof User in CommentProcessor');
                    throw new AccessDeniedException('Utilisateur invalide pour créer un commentaire.');
                }

                $this->logger->info('User from security: ' . get_class($user) . ' ID: ' . $user->getId());

                $data->setAuthor($user);
                $this->logger->info('Author set to user ID: ' . $user->getId());
            } catch (\Exception $e) {
                $this->logger->error('Erreur dans CommentProcessor: ' . $e->getMessage());
                throw $e;
            }
        }

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
