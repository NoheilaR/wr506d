<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Repository\MovieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'partial',
    'categories.name' => 'partial',
    'actors.lastname' => 'partial',
    'actors.firstname' => 'partial'
])]
#[ApiFilter(DateFilter::class, properties: [
    'actors.dob'
])]
#[ApiFilter(RangeFilter::class, properties: [
    'duration'
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
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du film est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "La durée doit être positive")]
    #[Assert\Range(
        min: 1,
        max: 600,
        notInRangeMessage: "La durée doit être comprise entre {{ min }} et {{ max }} minutes"
    )]
    private ?int $duration = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Assert\LessThanOrEqual("today", message: "La date de sortie ne peut pas être dans le futur")]
    private ?\DateTimeInterface $releaseDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "L'image doit être une URL valide")]
    private ?string $image = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, mappedBy: 'movies')]
    private Collection $categories;

    /**
     * @var Collection<int, Actor>
     */
    #[ORM\ManyToMany(targetEntity: Actor::class, mappedBy: 'movies')]
    private Collection $actors;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->actors = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
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
            $category->addMovie($this);
        }
        return $this;
    }

    public function removeCategory(Category $category): static
    {
        if ($this->categories->removeElement($category)) {
            $category->removeMovie($this);
        }
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
            $actor->addMovie($this);
        }
        return $this;
    }

    public function removeActor(Actor $actor): static
    {
        if ($this->actors->removeElement($actor)) {
            $actor->removeMovie($this);
        }
        return $this;
    }
}
