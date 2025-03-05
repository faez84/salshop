<?php

namespace App\Tests\Api;

use App\Factory\CategoryFactory;


class CategoryApiTest extends Api
{

    public function testCreateCategory(): void
    {
        $response = $this->postAdmin( '/api/categories', [
            "title" => "test@test.com",
            "parent" => null
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }
 
    public function testCreateNotValidCategory(): void
    {
        $response = $this->postAdmin( '/api/categories', [
            "parent" => null
        ]);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateChildCategory(): void
    {
        $category = CategoryFactory::createOne();
        $response = $this->postAdmin( '/api/categories', [
            "title" => "test@test.com",
            "parent" => "/api/categories/" . $category->getId()
            ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }

    public function testDeleteCategory(): void
    {
        $category = CategoryFactory::createOne();
        $response = $this->deleteAdmin( '/api/categories/1');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteNotFounCategory(): void
    {
        $response = $this->deleteAdmin( '/api/categories/2');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchCategory(): void
    {
        $category = CategoryFactory::createOne([
            "title" => "original title",
            "parent" => null
            ]);

            $response = $this->patchAdmin( '/api/categories/1', [
                "title" => "after update"
                ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }
}
