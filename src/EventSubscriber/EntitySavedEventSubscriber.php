<?php

namespace App\EventSubscriber;

use App\Entity\Movie;
use App\Event\EntitySavedEvent;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EntitySavedEventSubscriber implements EventSubscriberInterface
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getSubscribedEvents(): array
    {
        return [Events::postPersist];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Movie) {
            $event = new EntitySavedEvent($entity);
            $this->dispatcher->dispatch($event, EntitySavedEvent::class);
        }
    }
}
