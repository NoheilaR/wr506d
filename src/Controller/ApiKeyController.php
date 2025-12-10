<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiKeyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get API key status for the authenticated user
     */
    #[Route('/api/me/api-key', name: 'get_api_key', methods: ['GET'])]
    public function getApiKey(?UserInterface $user): JsonResponse
    {
        if (!$user || !$user instanceof \App\Entity\User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connecté'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $hasApiKey = $user->getApiKeyHash() !== null;

        if (!$hasApiKey) {
            return new JsonResponse([
                'hasApiKey' => false,
                'message' => 'No API key generated yet'
            ]);
        }

        return new JsonResponse([
            'hasApiKey' => true,
            'prefix' => $user->getApiKeyPrefix(),
            'enabled' => $user->isApiKeyEnabled(),
            'createdAt' => $user->getApiKeyCreatedAt()?->format(\DateTimeInterface::ATOM),
            'lastUsedAt' => $user->getApiKeyLastUsedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }

    /**
     * Generate a new API key (revokes the old one if exists)
     */
    #[Route('/api/me/api-key', name: 'generate_api_key', methods: ['POST'])]
    public function generateApiKey(?UserInterface $user): JsonResponse
    {
        if (!$user || !$user instanceof \App\Entity\User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connecté'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        // Generate new API key (automatically revokes old one)
        $apiKey = $user->generateApiKey();

        // Persist changes
        $this->entityManager->flush();

        return new JsonResponse([
            'apiKey' => $apiKey,
            'prefix' => $user->getApiKeyPrefix(),
            'enabled' => $user->isApiKeyEnabled(),
            'createdAt' => $user->getApiKeyCreatedAt()?->format(\DateTimeInterface::ATOM),
            'warning' => 'Cette clé ne sera plus jamais affichée. Copiez-la maintenant !',
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Enable or disable the API key
     */
    #[Route('/api/me/api-key', name: 'toggle_api_key', methods: ['PATCH'])]
    public function toggleApiKey(Request $request, ?UserInterface $user): JsonResponse
    {
        if (!$user || !$user instanceof \App\Entity\User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connecté'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        if ($user->getApiKeyHash() === null) {
            return new JsonResponse(
                ['error' => 'No API key generated yet'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        // Parse JSON body
        $data = json_decode($request->getContent(), true);

        if (!isset($data['enabled']) || !is_bool($data['enabled'])) {
            return new JsonResponse(
                ['error' => 'Invalid request. "enabled" field (boolean) is required'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $user->setApiKeyEnabled($data['enabled']);
        $this->entityManager->flush();

        return new JsonResponse([
            'enabled' => $user->isApiKeyEnabled(),
            'message' => $user->isApiKeyEnabled() ? 'API key enabled' : 'API key disabled',
        ]);
    }

    /**
     * Revoke the API key (deletes it permanently)
     */
    #[Route('/api/me/api-key', name: 'revoke_api_key', methods: ['DELETE'])]
    public function revokeApiKey(?UserInterface $user): JsonResponse
    {
        if (!$user || !$user instanceof \App\Entity\User) {
            return new JsonResponse(
                ['error' => 'Utilisateur non connecté'],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        if ($user->getApiKeyHash() === null) {
            return new JsonResponse(
                ['error' => 'No API key to revoke'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $user->revokeApiKey();
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'API key revoked successfully',
        ]);
    }
}
