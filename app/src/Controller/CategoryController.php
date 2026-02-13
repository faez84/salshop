<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        $products = $this->productRepository->findBy(['category' => $category->getId()]);

        return $this->render('products.html.twig', [
            'category' => $category,
            'products' => $products,
        ]);
    }
}
