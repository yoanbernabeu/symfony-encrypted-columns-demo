<?php

namespace App\Controller;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\KeyManagement\BlindIndexInterface;
use Symfony\Component\Routing\Attribute\Route;

class CustomerController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'app.blind_index.customer_email')]
        private BlindIndexInterface $emailIndex,
    ) {
    }

    #[Route('/', name: 'customer_index')]
    public function index(EntityManagerInterface $em, #[MapQueryParameter] string $email = ''): Response
    {
        $repository = $em->getRepository(Customer::class);
        $email = mb_strtolower(trim($email));

        // L'empreinte de l'email cherché, calculée comme celle stockée en base
        $tag = '' !== $email ? $this->emailIndex->of($email) : null;

        return $this->render('customer/index.html.twig', [
            'customers' => $repository->findAll(),
            'email' => $email,
            'found' => $tag ? $repository->findOneBy(['email' => $email]) : null,
            'tag' => $tag,
            'foundByIndex' => $tag ? $repository->findOneBy(['emailIndex' => $tag]) : null,
        ]);
    }

    #[Route('/customers', name: 'customer_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $customer = new Customer();
        $customer->name = $request->request->getString('name');
        // Normalisé à l'entrée, pour que la même adresse donne toujours la même empreinte
        $customer->email = mb_strtolower(trim($request->request->getString('email')));
        $customer->phone = $request->request->getString('phone') ?: null;
        $customer->birthDate = ($birthDate = $request->request->getString('birthDate')) ? new \DateTimeImmutable($birthDate) : null;

        $em->persist($customer);
        $em->flush();

        return $this->redirectToRoute('customer_index');
    }
}
