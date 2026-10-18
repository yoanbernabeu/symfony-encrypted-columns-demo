<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\KeyManagement\Bridge\DoctrineOrm\Attribute\BlindIndexed;
use Symfony\Component\KeyManagement\Bridge\DoctrineOrm\Attribute\Encrypted;

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
    #[Encrypted(key: 'customer.contact')]
    public string $email;

    #[ORM\Column(length: 64, nullable: true)]
    #[BlindIndexed('email', 'customer-email')]
    public ?string $emailIndex = null;

    #[ORM\Column(nullable: true)]
    #[Encrypted(key: 'customer.contact')]
    public ?string $phone = null;

    #[ORM\Column(nullable: true)]
    #[Encrypted(key: 'customer.identity')]
    public ?\DateTimeImmutable $birthDate = null;
}
