<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Factory\CategoryFactory;
use App\Factory\ProductFactory;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CategoryControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    public function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        UserFactory::createOne(['email' => 'test@test.com', 'password' => '1Qq!1111', 'roles' => ['ROLE_USER']]);

        parent::setUp();
    }

    public function testRegister(): void
    {
        $category = CategoryFactory::createOne();
        $response = ProductFactory::createOne(  [
            "title" => "title1",
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
        $testUser = $userRepository->findBy(['email' => 'test@test.com'])[0];

        // simulate $testUser being logged in
        $this->client->loginUser($testUser);
        $crawler = $this->client->request('GET', '/category/1/products');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', text: 'title1');
    }
}
