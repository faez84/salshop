<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManager;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class Api extends ApiTestCase
{
    use ResetDatabase, Factories;
    protected static ?string $token = null;
    
    protected static ?string $admintToken = null;
    
    public $client;
    protected EntityManager $em;

    public function setUp(): void 
    {
        $this->getToken();
        $this->getAdminToken();
        $this->client = static::createClient(); 
        $this->em = static::getContainer()->get('doctrine.orm.entity_manager');
    }
    
    public function getToken()
    {

            UserFactory::createOne(["email" => "admin@admin.com", "password" => "1Qq!1111"]);
            $user = UserFactory::findBy(["email" => "admin@admin.com"]);
 

            $response = static::createClient()->request('POST', '/api/login_check', [
            'json' => [
                        'username' => 'admin@admin.com',
                        'password' => '1Qq!1111'
                    ],
            ]);
            static::$token = json_decode($response->getContent(), true)['token'];
    }

    public function getAdminToken()
    {

            UserFactory::createOne(["email" => "adminuser@admin.com", "password" => "1Qq!1111", "Roles" => ["ROLE_ADMIN"]]);
            $response = static::createClient()->request('POST', '/api/login_check', [
            'json' => [
                        'username' => 'adminuser@admin.com',
                        'password' => '1Qq!1111'
                    ],
            ]);
            static::$admintToken = json_decode($response->getContent(), true)['token'];
        
    }

    public function getRefreshToken(): string
    {
        UserFactory::createOne(["email" => "admin2@admin.com", "password" => "1Qq!1111"]);
        $response = static::createClient()->request('POST', '/api/login_check', [
        'json' => [
                    'username' => 'admin2@admin.com',
                    'password' => '1Qq!1111'
                ],
        ]); 


        return (string) json_decode($response->getContent(), true)['refresh_token'];
    }

    public function get(string $url)
    {
      
         $this->client->request("GET", $url, [
            'headers' => [
                'Content-Type' => 'application/json',
 
            ],
            'auth_bearer' => static::$token
        ]);

        return $this->client->getResponse();
    }

    public function post(string $url, array $data)
    {
        $client = static::createClient();
        $response = $client->request("POST", $url, [
            "json" => $data,
            "auth_bearer" => static::$token,
            "headers" => [
                'Content-Type: application/ld+json; charset=utf-8',
            ]
        ]);
      
        return $client->getResponse();
    }

    public function postAdmin(string $url, array $data)
    {
        $client = static::createClient();
        $response = $client->request("POST", $url, [
            "json" => $data,
            "auth_bearer" => static::$admintToken,
            "headers" => [
                'Content-Type: application/ld+json; charset=utf-8',
            ]
        ]);
      
        return $client->getResponse();
    }

    public function delete(string $url)
    {
        $client = static::createClient();
        $response = $client->request("DELETE", $url, [
            "auth_bearer" => static::$token,
            "headers" => [
                'Content-Type: application/json;',
            ]
        ]);
      
        return $client->getResponse();
    }

    public function deleteAdmin(string $url)
    {
        $client = static::createClient();
        $response = $client->request("DELETE", $url, [
            "auth_bearer" => static::$admintToken,
            "headers" => [
                'Content-Type: application/json;',
            ]
        ]);
      
        return $client->getResponse();
    }
    
    public function putAdmin(string $url, array $data)
    {
        $client = static::createClient();
        $response = $client->request("PUT", $url, [
            "json" => $data,
            "auth_bearer" => static::$admintToken,
            "headers" => [
                'Content-Type: application/ld+json; charset=utf-8',
            ]
        ]);
      
        return $client->getResponse();
    }

    public function patchAdmin(string $url, array $data)
    {
        $client = static::createClient();
        $response = $client->request("PATCH", $url, [
            "json" => $data,
            "auth_bearer" => static::$admintToken,
            "headers" => [
                'Content-Type: application/merge-patch+json; charset=utf-8',
            ]
        ]);
      
        return $client->getResponse();
    }
}
