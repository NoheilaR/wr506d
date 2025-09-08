<?php

namespace App\EventListener;

use App\Entity\LogAction;
use App\Event\EntitySavedEvent;
use Doctrine\ORM\EntityManagerInterface;

class EntitySavedListener
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(EntitySavedEvent $event): void
    {
        $movie = $event->getMovie();

        $log = new LogAction();
        $log->setAction('Film enregistré : ' . $movie->getTitle());
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($log);
        $this->em->flush();
    }
}
