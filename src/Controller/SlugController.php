<?php

namespace App\Controller;

use App\Service\SlugifyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Movie;
use DateTimeImmutable;

class SlugController extends AbstractController
{
    #[Route('/slug/{text}', name: 'slugify_text')]
    public function slugify(string $text, SlugifyService $slugifyService): Response
    {
        $slug = $slugifyService->slugify($text);

        return $this->render('slug/index.html.twig', [
            'original' => $text,
            'slug' => $slug,
        ]);
    }
    #[Route('/add-movie', name: 'add_movie')]
    public function add(EntityManagerInterface $em): Response
    {
        $movie = new Movie();
        $movie->setTitle('Inception');
        $movie->setReleasedAt(new DateTimeImmutable('2010-07-16'));

        $em->persist($movie);
        $em->flush();

        return new Response('Film ajouté et log créé !');
    }
}
