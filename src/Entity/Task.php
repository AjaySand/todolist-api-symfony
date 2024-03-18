<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JsonSerializable;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: '"title" is required.')]
    #[Assert\Length(max: 255, maxMessage: 'The title cannot be longer than {{ limit }} characters.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\Type(type: '\DateTimeInterface', message: '"deadline" must be a valid date.')]
    private ?\DateTimeInterface $deadline = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank(message: '"status" is required.')]
    private ?int $status = null;

    #[ORM\ManyToOne(inversedBy: 'tasks', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotBlank(message: '"user_id" is required.')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getDeadline(): ?\DateTimeInterface
    {
        return $this->deadline;
    }

    public function setDeadline(?\DateTimeInterface $deadline): static
    {
        $this->deadline = $deadline;

        return $this;
    }

    public function setDeadlineFromString(?string $deadline): static
    {
        if ($deadline) {
            $this->deadline = new \DateTimeImmutable($deadline);
        }

        return $this;
    }


    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'deadline' => $this->getDeadline(),
            'status' => $this->getStatus(),
            'user' => $this->getUser(),
        ];
    }
}
