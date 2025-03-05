<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\HttpKernel\Attribute\Cache;

class CategoryController extends AbstractController
{
    public function __construct(
        readonly private ProductRepository $productRepository
    ) {
    }

    #[Route(path: '/category/{id}/products', name: "category_products")]
    #[Cache(public: true, maxage: 360, mustRevalidate: true)]

    public function categoryProducts(Category $category,): Response
    {
        $response = $this->render('products.html.twig', [
            'products' => $this->productRepository->findBy(['category' => $category->getId()]),
        ]);


        return $response;
    }
}
