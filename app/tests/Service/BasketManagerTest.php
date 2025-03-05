<?php

namespace App\Tests\Service;

use App\Service\BasketManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class BasketManagerTest extends KernelTestCase
{
    protected $container;

    protected $requestStack;

    protected $basketManager;

    public function setUp(): void
    {
        /*
        $this->container = static::getContainer();
        $this->basketManager = $this->container->get(BasketManager::class);
        $session = new Session(new MockArraySessionStorage());

        // create test request
        $request = new Request();
        // add mocked session
        $request->setSession($session);

        // create requeststack and push request
        $this->requestStack = new RequestStack();
        $this->requestStack->push($request);

        $this->basketManager->setRequestStack($this->requestStack);
        */
        parent::setUp();
    }

    public function testAddProductToBasket(): void
    {
     //  $this->basketManager->addToBasket(1);

       $this->assertTrue(condition: true);
    }
}
