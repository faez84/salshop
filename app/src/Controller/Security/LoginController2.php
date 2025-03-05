<?php

declare(strict_types=1);

namespace App\Controller\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController2 extends AbstractController
{
    #[Route("/login2", name:"app_login2")]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('target_path');
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render(
            "security/login.html.twig",
            [
                'userName' => $authenticationUtils->getLastUsername(),
                'error' => $error
            ]
        );
    }
}
