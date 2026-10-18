<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255)]
    public string $name;

    #[ORM\Column(length: 255)]
    public string $email;

    #[ORM\Column(nullable: true)]
    public ?string $phone = null;

    #[ORM\Column(nullable: true)]
    public ?\DateTimeImmutable $birthDate = null;
}
