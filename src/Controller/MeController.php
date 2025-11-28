<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class MeController extends AbstractController
{
    #[Route('/api/me', name: 'get_current_user', methods: ['GET'])]
    public function getCurrentUser(?UserInterface $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non connecté'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'roles' => $user->getRoles(),
        ];

        return new JsonResponse($userData);
    }
}
