<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Repository\MovieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['movie:list']],
            security: "is_granted('PUBLIC_ACCESS')"
        ),
        new Get(
            normalizationContext: ['groups' => ['movie:read']],
            security: "is_granted('PUBLIC_ACCESS')"
        ),
        new Post(
            normalizationContext: ['groups' => ['movie:read']],
            denormalizationContext: ['groups' => ['movie:write']],
            security: "is_granted('ROLE_AUTHOR')"
        ),
        new Put(
            normalizationContext: ['groups' => ['movie:read']],
            denormalizationContext: ['groups' => ['movie:write']],
            security: "is_granted('ROLE_EDITOR') or (is_granted('ROLE_AUTHOR') and object.getAuthor() == user)"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_AUTHOR') and object.getAuthor() == user)"
        )
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'partial',
    'categories.name' => 'partial',
    'actors.lastname' => 'partial',
    'actors.firstname' => 'partial',
    'director.lastname' => 'partial',
    'director.firstname' => 'partial',
    'author' => 'exact'
])]
#[ApiFilter(DateFilter::class, properties: [
    'actors.dob',
    'createdAt',
    'releaseDate'
])]
#[ApiFilter(RangeFilter::class, properties: [
    'duration',
    'nbEntries',
    'budget'
])]
#[ApiFilter(OrderFilter::class, properties: [
    'releaseDate',
    'createdAt'
], arguments: ['orderParameterName' => 'order'])]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['movie:list', 'movie:read', 'comment:read', 'actor:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du film est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Groups(['movie:list', 'movie:read', 'movie:write', 'comment:read', 'actor:read'])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Groups(['movie:read', 'movie:write'])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "La durée doit être positive")]
    #[Assert\Range(
        min: 1,
        max: 600,
        notInRangeMessage: "La durée doit être comprise entre {{ min }} et {{ max }} minutes"
    )]
    #[Groups(['movie:read', 'movie:write'])]
    private ?int $duration = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Assert\LessThanOrEqual("today", message: "La date de sortie ne peut pas être dans le futur")]
    #[Groups(['movie:list', 'movie:read', 'movie:write', 'actor:read'])]
    private ?\DateTimeInterface $releaseDate = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: "Le nombre d'entrées doit être positif ou nul")]
    #[Groups(['movie:read', 'movie:write'])]
    private ?int $nbEntries = null;

    #[ORM\ManyToOne(targetEntity: Director::class, inversedBy: 'movies')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le réalisateur est obligatoire")]
    #[Groups(['movie:read', 'movie:write'])]
    private ?Director $director = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "L'URL doit être valide", requireTld: false)]
    #[Groups(['movie:read', 'movie:write'])]
    private ?string $url = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\PositiveOrZero(message: "Le budget doit être positif ou nul")]
    #[Groups(['movie:read', 'movie:write'])]
    private ?float $budget = null;

    #[ORM\Column(updatable: false)]
    #[Groups(['movie:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['movie:read'])]
    private ?User $author = null;

    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['movie:list', 'movie:read', 'movie:write', 'actor:read'])]
    private ?MediaObject $poster = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'movies')]
    #[ORM\JoinTable(name: 'movie_category')]
    #[Groups(['movie:read', 'movie:write'])]
    private Collection $categories;

    /**
     * @var Collection<int, Actor>
     */
    #[ORM\ManyToMany(targetEntity: Actor::class, inversedBy: 'movies')]
    #[ORM\JoinTable(name: 'movie_actor')]
    #[Groups(['movie:read'])]
    private Collection $actors;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'movie', orphanRemoval: true)]
    #[Groups(['movie:read'])]
    private Collection $comments;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->actors = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeInterface $releaseDate): static
    {
        $this->releaseDate = $releaseDate;
        return $this;
    }

    public function getNbEntries(): ?int
    {
        return $this->nbEntries;
    }

    public function setNbEntries(?int $nbEntries): static
    {
        $this->nbEntries = $nbEntries;
        return $this;
    }

    public function getDirector(): ?Director
    {
        return $this->director;
    }

    public function setDirector(Director $director): static
    {
        $this->director = $director;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getBudget(): ?float
    {
        return $this->budget;
    }

    public function setBudget(?float $budget): static
    {
        $this->budget = $budget;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }
        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);
        return $this;
    }

    /**
     * @return Collection<int, Actor>
     */
    public function getActors(): Collection
    {
        return $this->actors;
    }

    public function addActor(Actor $actor): static
    {
        if (!$this->actors->contains($actor)) {
            $this->actors->add($actor);
        }
        return $this;
    }

    public function removeActor(Actor $actor): static
    {
        $this->actors->removeElement($actor);
        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setMovie($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getMovie() === $this) {
                $comment->setMovie(null);
            }
        }
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getPoster(): ?MediaObject
    {
        return $this->poster;
    }

    public function setPoster(?MediaObject $poster): static
    {
        $this->poster = $poster;
        return $this;
    }

    /**
     * Durée formatée (virtuel)
     */
    #[Groups(['movie:list', 'movie:read'])]
    public function getFormattedDuration(): ?string
    {
        if ($this->duration === null) {
            return null;
        }

        $hours = intdiv($this->duration, 60);
        $minutes = $this->duration % 60;

        return "{$hours}h {$minutes}min";
    }

    /**
     * Nombre d'acteurs (virtuel)
     */
    #[Groups(['movie:list', 'movie:read'])]
    public function getActorCount(): int
    {
        return $this->actors->count();
    }
}
