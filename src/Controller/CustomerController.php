<?php

namespace App\Controller;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class CustomerController extends AbstractController
{
    #[Route('/', name: 'customer_index')]
    public function index(EntityManagerInterface $em, #[MapQueryParameter] string $email = ''): Response
    {
        $repository = $em->getRepository(Customer::class);

        return $this->render('customer/index.html.twig', [
            'customers' => $repository->findAll(),
            'email' => $email,
            'found' => '' !== $email ? $repository->findOneBy(['email' => $email]) : null,
        ]);
    }

    #[Route('/customers', name: 'customer_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $customer = new Customer();
        $customer->name = $request->request->getString('name');
        $customer->email = $request->request->getString('email');
        $customer->phone = $request->request->getString('phone') ?: null;
        $customer->birthDate = ($birthDate = $request->request->getString('birthDate')) ? new \DateTimeImmutable($birthDate) : null;

        $em->persist($customer);
        $em->flush();

        return $this->redirectToRoute('customer_index');
    }
}
