<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\UserRepository;
use App\State\UserPasswordHasher;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('PUBLIC_ACCESS')",
            normalizationContext: ['groups' => ['user:read']]
        ),
        new Get(
            security: "is_granted('PUBLIC_ACCESS')",
            normalizationContext: ['groups' => ['user:read']]
        ),
        new Post(
            uriTemplate: '/users',
            security: "is_granted('PUBLIC_ACCESS')",
            denormalizationContext: ['groups' => ['user:write']],
            normalizationContext: ['groups' => ['user:read']],
            processor: UserPasswordHasher::class
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN') or object == user",
            denormalizationContext: ['groups' => ['user:write']],
            normalizationContext: ['groups' => ['user:read']],
            processor: UserPasswordHasher::class
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:write', 'user:read', 'comment:read', 'movie:read'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['user:write', 'user:read', 'comment:read', 'movie:read'])]
    #[Assert\Length(max: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['user:write', 'user:read', 'comment:read', 'movie:read'])]
    #[Assert\Length(max: 100)]
    private ?string $prenom = null;

    #[ORM\Column]
    #[Groups(['user:read', 'user:write'])]
    private array $roles = [];

    // ✅ NOUVEAU : Limite de requêtes API par heure
    #[ORM\Column(type: 'integer', options: ['default' => 50])]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\Positive]
    private int $apiRateLimit = 50;

    // ✅ API Key Management
    #[ORM\Column(length: 64, unique: true, nullable: true)]
    #[Assert\Length(exactly: 64)]
    #[Groups(['user:read'])]
    private ?string $apiKeyHash = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Assert\Length(exactly: 16)]
    #[Groups(['user:read'])]
    private ?string $apiKeyPrefix = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read', 'user:write'])]
    private bool $apiKeyEnabled = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $apiKeyCreatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $apiKeyLastUsedAt = null;

    #[ORM\Column(updatable: false)]
    private ?string $password = null;

    #[Groups(['user:write'])]
    #[Assert\NotBlank(groups: ['user:write'])]
    #[Assert\Length(min: 6)]
    private ?string $plainPassword = null;

    // 2FA fields
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $twoFactorSecret = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $twoFactorEnabled = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $twoFactorBackupCodes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    // ✅ NOUVEAU : Getter/Setter pour apiRateLimit
    public function getApiRateLimit(): int
    {
        return $this->apiRateLimit;
    }

    public function setApiRateLimit(int $apiRateLimit): static
    {
        $this->apiRateLimit = $apiRateLimit;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    // ✅ API Key Management Methods

    public function getApiKeyHash(): ?string
    {
        return $this->apiKeyHash;
    }

    public function setApiKeyHash(?string $apiKeyHash): static
    {
        $this->apiKeyHash = $apiKeyHash;
        return $this;
    }

    public function getApiKeyPrefix(): ?string
    {
        return $this->apiKeyPrefix;
    }

    public function setApiKeyPrefix(?string $apiKeyPrefix): static
    {
        $this->apiKeyPrefix = $apiKeyPrefix;
        return $this;
    }

    public function isApiKeyEnabled(): bool
    {
        return $this->apiKeyEnabled;
    }

    public function setApiKeyEnabled(bool $apiKeyEnabled): static
    {
        $this->apiKeyEnabled = $apiKeyEnabled;
        return $this;
    }

    public function getApiKeyCreatedAt(): ?\DateTimeImmutable
    {
        return $this->apiKeyCreatedAt;
    }

    public function setApiKeyCreatedAt(?\DateTimeImmutable $apiKeyCreatedAt): static
    {
        $this->apiKeyCreatedAt = $apiKeyCreatedAt;
        return $this;
    }

    public function getApiKeyLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->apiKeyLastUsedAt;
    }

    public function setApiKeyLastUsedAt(?\DateTimeImmutable $apiKeyLastUsedAt): static
    {
        $this->apiKeyLastUsedAt = $apiKeyLastUsedAt;
        return $this;
    }

    /**
     * Generates a new API key for the user
     * Returns the plain API key (visible only once)
     */
    public function generateApiKey(): string
    {
        // Generate 32 random bytes
        $randomBytes = random_bytes(32);

        // Convert to hexadecimal (64 characters)
        $apiKey = bin2hex($randomBytes);

        // Extract prefix (first 16 characters)
        $prefix = substr($apiKey, 0, 16);

        // Hash the complete key with SHA-256 (64 characters)
        $hash = hash('sha256', $apiKey);

        // Store hash and prefix
        $this->apiKeyHash = $hash;
        $this->apiKeyPrefix = $prefix;
        $this->apiKeyCreatedAt = new \DateTimeImmutable();
        $this->apiKeyEnabled = true;

        // Return the plain key (will not be stored)
        return $apiKey;
    }

    /**
     * Revokes the API key (deletes it)
     */
    public function revokeApiKey(): void
    {
        $this->apiKeyHash = null;
        $this->apiKeyPrefix = null;
        $this->apiKeyEnabled = false;
        $this->apiKeyCreatedAt = null;
        $this->apiKeyLastUsedAt = null;
    }

    /**
     * Updates the last used timestamp
     */
    public function updateApiKeyLastUsedAt(): void
    {
        $this->apiKeyLastUsedAt = new \DateTimeImmutable();
    }

    // 2FA Methods

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function setTwoFactorSecret(?string $twoFactorSecret): static
    {
        $this->twoFactorSecret = $twoFactorSecret;
        return $this;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function setTwoFactorEnabled(bool $twoFactorEnabled): static
    {
        $this->twoFactorEnabled = $twoFactorEnabled;
        return $this;
    }

    public function getTwoFactorBackupCodes(): ?array
    {
        return $this->twoFactorBackupCodes;
    }

    public function setTwoFactorBackupCodes(?array $twoFactorBackupCodes): static
    {
        $this->twoFactorBackupCodes = $twoFactorBackupCodes;
        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);
        return $data;
    }
}
