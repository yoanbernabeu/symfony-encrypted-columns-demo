<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $customers = [
            ['Ada Lovelace', 'ada@example.com', '+33 6 12 34 56 78', '1815-12-10'],
            ['Alan Turing', 'alan@example.com', '+33 6 98 76 54 32', '1912-06-23'],
            ['Grace Hopper', 'grace@example.com', '+33 7 11 22 33 44', '1906-12-09'],
        ];

        foreach ($customers as [$name, $email, $phone, $birthDate]) {
            $customer = new Customer();
            $customer->name = $name;
            $customer->email = $email;
            $customer->phone = $phone;
            $customer->birthDate = new \DateTimeImmutable($birthDate);
            $manager->persist($customer);
        }

        $manager->flush();
    }
}
