<?php

declare(strict_types=1);

namespace App\Controller;


use App\Catalog\Application\Port\Persistence\IProductRepository;
use App\Catalog\Infrastructure\Cache\CatalogCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    public function __construct(
        private readonly IProductRepository$productRepository,
        private readonly CatalogCache $catalogCache
    ) {
    }

    #[Route(path: '/product/{id}', name: 'product_details')]
    public function detail(int $id): Response
    {
        $product = $this->catalogCache->getProductDetail(
            $id,
            fn (): ?array => $this->productRepository->findDetailSummaryById($id)
        );

        if (null === $product) {
            throw $this->createNotFoundException(sprintf('Product with ID %d was not found.', $id));
        }

        return $this->render('products/details.html.twig', [
            'product' => $product,
        ]);
    }
}
