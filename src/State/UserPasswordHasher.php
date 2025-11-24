<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<User, User>
 */
class UserPasswordHasher implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $processor,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * @param User $data
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): User {
        // ✅ Hash seulement si plainPassword est renseigné ET non vide
        if ($data->getPlainPassword() && trim($data->getPlainPassword()) !== '') {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $data,
                $data->getPlainPassword()
            );
            $data->setPassword($hashedPassword);
            $data->eraseCredentials();
        }

        // Attribution automatique du rôle ROLE_USER si aucun rôle n'est défini
        if (empty($data->getRoles()) || $data->getRoles() === ['ROLE_USER']) {
            $data->setRoles(['ROLE_USER']);
        }

        return $this->processor->process($data, $operation, $uriVariables, $context);
    }
}
