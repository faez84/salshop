<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Address;
use App\Form\AddressType;
use App\Service\Address\AddressLineBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AddressController extends AbstractController
{
    public function __construct(
        private AddressLineBuilder $addressBuilder,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security
    ) {
    }

    #[Route(path: '/basket/show_user_address', name: "show_user_address")]
    public function showUserAddresses(Request $request): Response
    {
        $addresses = $this->addressBuilder->build();
        return $this->render('address/userAddresses.html.twig', [
            'addresses' => $addresses
        ]);
    }

    #[Route(path: '/basket/add_user_address', name: "add_user_address")]
    public function addUserAddress(Request $request): Response
    {
        $form = $this->createForm(AddressType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $address = $form->getData();
            $address->setUser($this->security->getUser());
            $this->entityManager->persist($address);
            $this->entityManager->flush();
        }

        return $this->render('address/addUserAddresses.html.twig', [
            'form' => $form
        ]);
    }
}
