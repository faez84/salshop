<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Factory\CategoryFactory;
use App\Factory\ProductFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;
    public function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $this->userRepository = $em->getRepository(User::class);
        UserFactory::createOne(['email' => 'test2@test.com', 'password' => '1Qq!1111', 'roles' => ['ROLE_USER']]);

        parent::setUp();
    }


    public function testRegister(): void
    {
        $category = CategoryFactory::createOne();
        $response = ProductFactory::createOne(  [
            "title" => "test2@test.com",
            "artNum" => "12345",
            "description" => "description",
            "quantity" => 1,
            "price" => 44.5,
            "image" => "No Image",
            "category" => $category
            ]
        );
       
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findBy(['email' => 'test2@test.com'])[0];

        // simulate $testUser being logged in
        $this->client->loginUser($testUser);
        $crawler = $this->client->request('GET', '/product/1');

        $this->assertResponseIsSuccessful();
 
    }
}
