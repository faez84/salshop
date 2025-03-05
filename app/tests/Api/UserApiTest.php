<?php

namespace App\Tests\Api;

use App\Factory\UserFactory;

class UserApiTest extends Api
{

    public function testCreateUser(): void
    {
        $response = $this->post( '/api/users', [
            "email" => "test@test.com",
            "password" => "1Qq!1111",
            "roles" => [""]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateEmptyEmailUser(): void
    {
        $response = $this->post( '/api/users', [
            "email" => "",
            "password" => "1Qq!1111",
            "roles" => [""]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['hydra:description' => 'email: This value should not be blank.']);
    }

    public function testCreateEmptyPassUser(): void
    {
        $response = $this->post( '/api/users', [
            "email" => "test@test.com",
            "password" => "",
            "roles" => [""]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['hydra:description' => 'password: This value should not be blank.']);
    }

    public function testCreateInValidPassUser(): void
    {
        $response = $this->post( '/api/users', [
            "email" => "test@test.com",
            "password" => "11111",
            "roles" => [""]
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['detail' => 'password: This value is not valid.']);
    }

    public function testGetUsers()
    {
        $response = $this->get("/api/users");
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }
    public function testGetUser()
    {
        UserFactory::createOne(["email" => "test@test.com", "password" => "1Qq!1111"]);
        $response = $this->get("/api/users/1");
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['@id' => '/api/users/1']);
    }

    public function testGetNotFoundUser()
    {
       // UserFactory::createOne(["email" => "test@test.com", "password" => "1Qq!1111"]);
        
        $response = $this->get("/api/users/30000");
        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(["detail" => "Not Found"]);
    }
    // public function testRefreshToken()
    // {
    //     $refreshToken = $this->getRefreshToken();
    //     $response = $this->post("/api/token/refresh", [
    //             "refresh_token" => $refreshToken
    //     ]);
    //     $this->assertResponseIsSuccessful();
    //   //  $this->assertJsonContains(["detail" => "Not Found"]);
    // }
    
    // public function testDeleteUser()
    // {
    //     UserFactory::createOne(["email" => "test@test.com", "password" => "1Qq!1111"]);
    //     $response = $this->delete("/api/users/1");
    //     $this->assertResponseIsSuccessful();
    //     $this->assertResponseStatusCodeSame(204);
    // }

}
