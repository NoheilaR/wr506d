<?php

namespace App\EventSubscriber;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CommentAuthorSubscriber implements EventSubscriberInterface
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // Seulement pour les nouveaux commentaires
        if (!$entity instanceof Comment) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof UserInterface) {
            $entity->setAuthor($token->getUser());
        }
    }
}
