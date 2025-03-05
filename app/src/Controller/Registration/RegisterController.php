<?php

declare(strict_types=1);

namespace App\Controller\Registration;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegisterController extends AbstractController
{
    #[Route("/register", name: "app_register")]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $password = $form->get('password')->getData();
                $user->setPassword($userPasswordHasher->hashPassword($user, $password));
                $entityManager->persist($user);
                try {
                    $entityManager->flush();
                } catch (UniqueConstraintViolationException) {
                    return $this->render("registration/register.html.twig", [
                        'registrationForm' => $form,
                        'error' => ['Erorororro']
                    ]);
                }
                return $this->redirectToRoute('app_user_home');
            }
        }

        return $this->render("registration/register.html.twig", [
            'registrationForm' => $form,
            'error' => $form->getErrors()
        ]);
    }
}
