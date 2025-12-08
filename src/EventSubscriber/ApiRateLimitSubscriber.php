<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\RateLimiter\Policy\SlidingWindowLimiter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Entity\User;

class ApiRateLimitSubscriber implements EventSubscriberInterface
{
    private CacheStorage $storage;

    public function __construct(
        private RateLimiterFactory $anonymousApiLimiter,
        #[Autowire(service: 'cache.app')]
        CacheInterface $cache,
        private TokenStorageInterface $tokenStorage
    ) {
        $this->storage = new CacheStorage($cache);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 2],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        if (str_starts_with($request->getPathInfo(), '/api/docs') ||
            str_starts_with($request->getPathInfo(), '/api/graphql/graphiql')) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        $isAuthenticated = $user instanceof User;

        $limiterData = $this->createLimiterForUser($user, $request, $isAuthenticated);
        $limiter = $limiterData['limiter'];
        $userCustomLimit = $limiterData['limit'];
        $identifier = $limiterData['identifier'];

        $limitInfo = $limiter->consume();

        // Ligne du prof : vérification explicite
        $consumed = $userCustomLimit - $limitInfo->getRemainingTokens();
        $isAccepted = $consumed <= $userCustomLimit;

        $request->attributes->set('_rate_limit', [
            'limit' => $limitInfo->getLimit(),
            'remaining' => $limitInfo->getRemainingTokens(),
            'reset' => $limitInfo->getRetryAfter()->getTimestamp(),
            'user' => $isAuthenticated ? $user->getUserIdentifier() : 'anonymous',
            'consumed' => $consumed,
        ]);

        if (!$isAccepted) {
            $retryAfter = $limitInfo->getRetryAfter();
            $response = new JsonResponse(
                [
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => $retryAfter->getTimestamp(),
                    'limit' => $userCustomLimit,
                    'consumed' => $consumed,
                    'user' => $isAuthenticated ? $user->getUserIdentifier() : 'anonymous',
                ],
                429
            );

            $response->headers->set('Retry-After', (string) $retryAfter->getTimestamp());
            $response->headers->set('X-RateLimit-Limit', (string) $userCustomLimit);
            $response->headers->set('X-RateLimit-Remaining', '0');
            $response->headers->set('X-RateLimit-Reset', (string) $retryAfter->getTimestamp());

            $event->setResponse($response);
        }
    }

    // ✅ CETTE MÉTHODE ÉTAIT MANQUANTE
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $rateLimitInfo = $request->attributes->get('_rate_limit');
        if (!$rateLimitInfo) {
            return;
        }

        $response->headers->set('X-RateLimit-Limit', (string) $rateLimitInfo['limit']);
        $response->headers->set('X-RateLimit-Remaining', (string) $rateLimitInfo['remaining']);
        $response->headers->set('X-RateLimit-Reset', (string) $rateLimitInfo['reset']);
        $response->headers->set('X-Debug-User', $rateLimitInfo['user']);
        $response->headers->set('X-Debug-Consumed', (string) $rateLimitInfo['consumed']);
    }

    private function createLimiterForUser($user, $request, bool $isAuthenticated): array
    {
        if ($isAuthenticated && $user instanceof User) {
            $identifier = $user->getUserIdentifier();
            $userCustomLimit = $user->getApiRateLimit();

            $limiter = new SlidingWindowLimiter(
                id: 'user_' . $identifier,
                limit: $userCustomLimit,
                interval: $this->createOneHourInterval(),
                storage: $this->storage
            );

            return [
                'limiter' => $limiter,
                'limit' => $userCustomLimit,
                'identifier' => $identifier,
            ];
        }

        $identifier = $request->getClientIp() ?? 'unknown';
        return [
            'limiter' => $this->anonymousApiLimiter->create($identifier),
            'limit' => 10,
            'identifier' => $identifier,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function createOneHourInterval(): \DateInterval
    {
        return \DateInterval::createFromDateString('1 hour');
    }
}
