<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\Command\OrderFinalize;

use App\Service\Handler\CreatOrderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;


class OrderTransactionController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
        private CreatOrderHandler $createOrdergHandler
    ) {}

    public function __invoke(Request $request)
    {
        $data = $this->requestStack->getCurrentRequest()->getContent();
        $data = json_decode($data, true);

        return new JsonResponse(['text' => $this->createOrdergHandler->handle($data)]);
    }
}
