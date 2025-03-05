<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Form\PaymentFormType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\BasketManager;
use App\Service\Order\OrderCheckout;
use App\Service\Payment\PaymentMethodFactory;
use App\Service\Payment\PaymentMethodValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class OrderController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route(path: '/order', name: "order_execute")]
    public function basketPayment(): Response
    {

        return $this->render('order/show.html.twig', [
            'msg' => "Thank you for your order!",
        ]);
    }
}
