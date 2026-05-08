<?php

declare(strict_types=1);

namespace App\Controller;

use App\Catalog\Application\Port\Persistence\IProductRepository;
use App\Catalog\Infrastructure\Cache\CatalogCache;
use App\Catalog\Infrastructure\Persistence\Doctrine\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    public function __construct(
        readonly private IProductRepository $productRepository,
        readonly private CatalogCache $catalogCache
    ) {
    }

    #[Route(path: '/category/{id}/products', name: 'category_products')]
    public function categoryProducts(Category $category): Response
    {
        $categoryId = (int) $category->getId();
        //$products =  $this->productRepository->findByCategoryForList($categoryId);
        
        $products = $this->catalogCache->getCategoryProducts(
            $categoryId,
            fn (): array => $this->productRepository->findByCategoryForList($categoryId)
        );

        return $this->render('products.html.twig', [
            'category' => $category,
            'products' => $products,
        ]);
    }
}
