<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\Command\OrderFinalize;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyBundles\RedisBundle\Redis\ClientInterface;

class OrderController extends AbstractController
{
    public function __construct( private MessageBusInterface $messageBus)
    {
    }

    #[Route(path: '/order', name: "order_execute")]
    public function basketPayment(): Response
    {

        return $this->render('order/show.html.twig', [
            'msg' => "Thank you for your order!",
        ]);
    }

    #[Route(path: '/message/order', name: "order_execute")]
    public function orderMessage(): Response
    {
        $this->messageBus->dispatch(new OrderFinalize('1', "paypal"));
        //$this->messageBus->dispatch(new SearchQuery('search'));
        return $this->render('index.html.twig', [
            'mesg' => "Thank you for your order!",
        ]);
    }
}
