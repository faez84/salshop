<?php

namespace App\Tests\Api;

use App\Factory\CategoryFactory;
use App\Factory\ProductFactory;

class ProductApiTest extends Api
{

    public function testCreateProduct(): void
    {
        $category = CategoryFactory::createOne();
        $response = $this->postAdmin( '/api/products', [
            "title" => "test@test.com",
            "artNum" => "12345",
            "description" => "description",
            "quantity" => 1,
            "price" => 44.5,
            "image" => "No Image",
            "category" => "/api/categories/" . $category->getId()
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateProductNotAdminUser(): void
    {
        $category = CategoryFactory::createOne();
        $response = $this->post( '/api/products', [
            "title" => "test@test.com",
            'artNum' => '12345',
            "description" => "description",
            "quantity" => 1,
            "price" => 44.5,
            "image" => "No Image",
            "category" => "/api/categories/" . $category->getId()
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateEmptyTitleProduct(): void
    {
        $category = CategoryFactory::createOne();
        $response = $this->postAdmin( '/api/products', [
            'artNum' => '12345',
            "description" => "description",
            "quantity" => 1,
            "price" => 44.5,
            "image" => "No Image",
            "category" => "/api/categories/" . $category->getId()
        ]);
 
        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains(['hydra:description' => 'title: This value should not be blank.']);
    }

    public function testGetProduct(): void
    {
        $category = CategoryFactory::createOne();
        ProductFactory::createOne([
            "title" => "product title",
            'artNum' => '12345',
            "description" => "desc",
            "quantity" => 1,
            "category" => $category
        ]);
        $response = $this->get( '/api/products/1');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetProducts(): void
    {
        $category = CategoryFactory::createOne();
        ProductFactory::createOne([
            "title" => "product title",
            'artNum' => '12345',
            "description" => "desc",
            "quantity" => 1,
            "category" => $category
        ]);
        $response = $this->get( '/api/products');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }
    public function testGetNotFoundProducts(): void
    {
        $response = $this->get( '/api/products/1');

        $this->assertResponseStatusCodeSame(404);
    }
    public function testDeleteProducts(): void
    {
        $category = CategoryFactory::createOne();
        ProductFactory::createOne([
            "title" => "product title",
            'artNum' => '12345',
            "description" => "desc",
            "quantity" => 1,
            "category" => $category
        ]);
        $response = $this->deleteAdmin( '/api/products/1');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteProductsNotAdmin(): void
    {
        $category = CategoryFactory::createOne();
        ProductFactory::createOne([
            "title" => "product title",
            'artNum' => '12345',
            "description" => "desc",
            "quantity" => 1,
            "category" => $category
        ]);
        $response = $this->delete( '/api/products/1');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateProduct(): void
    {
        $category = CategoryFactory::createOne();
        $product = ProductFactory::createOne(["title" => "update produt",  
        'artNum' => '12345',
        "description" => "description",
        "quantity" => 1,
        "price" => 44.5,
        "image" => "No Image",
        "category" => $category
        ]);
        
        $response = $this->patchAdmin( '/api/products/' . $product->getId(), [
            "title" => "after update product",
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }
}
