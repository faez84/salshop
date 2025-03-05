<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;
    public function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $this->userRepository = $em->getRepository(User::class);
    }

    public function testRegister(): void
    {
        $crawler = $this->client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('button', 'Register');

        $this->client->submitForm('Register', [
            'registration_form[email]' => 'doescNotExist@example.com',
            'registration_form[password][first]' => 'password',
            'registration_form[password][second]' => 'password',
            'registration_form[agreeTerms]' => true,
        ]);

      //  self::assertResponseRedirects('/register');
      //  $this->client->followRedirect();

        // Ensure we do not reveal if the user exists or not.
        self::assertSelectorTextContains('.alert-danger', 'This value is not valid.');

        $this->client->submitForm('Register', [
            'registration_form[email]' => 'doesNotExist@example.com',
            'registration_form[password][first]' => '1Qq!1111',
            'registration_form[password][second]' => '1Qq!1111',
            'registration_form[agreeTerms]' => true,
        ]);

        self::assertResponseRedirects('/cp/userHome');
    }
}
