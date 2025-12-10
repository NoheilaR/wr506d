<?php

namespace App\Security;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // Check if the request has an API key in the header
        return $request->headers->has('X-API-KEY');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = $request->headers->get('X-API-KEY');

        if (null === $apiKey || '' === $apiKey) {
            throw new CustomUserMessageAuthenticationException('No API key provided');
        }

        // Validate API key length (should be 64 characters - hex representation of 32 bytes)
        if (strlen($apiKey) !== 64) {
            throw new CustomUserMessageAuthenticationException('Invalid API key format');
        }

        // Extract prefix (first 16 characters) for optimized lookup
        $prefix = substr($apiKey, 0, 16);

        // Find user by prefix (indexed, fast lookup)
        $user = $this->userRepository->findOneByApiKeyPrefix($prefix);

        if (null === $user) {
            throw new CustomUserMessageAuthenticationException('Invalid API key');
        }

        // Verify the complete API key hash
        $apiKeyHash = hash('sha256', $apiKey);

        if ($apiKeyHash !== $user->getApiKeyHash()) {
            throw new CustomUserMessageAuthenticationException('Invalid API key');
        }

        // Check if API key is enabled
        if (!$user->isApiKeyEnabled()) {
            throw new CustomUserMessageAuthenticationException('API key is disabled');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Update last used timestamp
        $user = $token->getUser();
        if ($user instanceof \App\Entity\User) {
            $user->updateApiKeyLastUsedAt();
            $this->entityManager->flush();
        }

        // On success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ], Response::HTTP_UNAUTHORIZED);
    }
}
