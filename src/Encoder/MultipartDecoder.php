<?php

namespace App\Encoder;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

final class MultipartDecoder implements DecoderInterface
{
    public const FORMAT = 'multipart';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function decode(string $data, string $format, array $context = []): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $decodedData = [];

        // Décode les champs texte qui sont en JSON
        foreach ($request->request->all() as $key => $value) {
            try {
                // Tente de décoder le JSON
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                $decodedData[$key] = $decoded;
            } catch (\JsonException $e) {
                // Si ce n'est pas du JSON, garde la valeur telle quelle
                $decodedData[$key] = $value;
            }
        }

        // Ajoute les fichiers uploadés
        return array_merge($decodedData, $request->files->all());
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}
