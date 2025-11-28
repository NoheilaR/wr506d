<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Movie;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class MovieProcessor implements ProcessorInterface
  {
      public function __construct(
          private ProcessorInterface $decorated,
          private Security $security,
          private LoggerInterface $logger
      ) {
      }

      public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context =
  []): mixed
      {
          if ($data instanceof Movie) {
              try {
                  // Only set author on creation (POST), not on updates
                  if ($operation instanceof \ApiPlatform\Metadata\Post) {
                      $user = $this->security->getUser();
                      $this->logger->info('User from security: ' . (is_object($user) ? get_class($user) . '
  ID: ' . $user->getId() : 'null'));

                      if (!$user instanceof User) {
                          $this->logger->error('User not instanceof User in MovieProcessor');
                          throw new AccessDeniedException('Utilisateur invalide pour créer un film.');
                      }

                      // Auto-assign the authenticated user as author
                      $data->setAuthor($user);
                      $this->logger->info('Author set to user ID: ' . $user->getId());
                  }
              } catch (\Exception $e) {
                  $this->logger->error('Erreur dans MovieProcessor: ' . $e->getMessage());
                  throw $e;
              }
          }

          return $this->decorated->process($data, $operation, $uriVariables, $context);
      }
  }
