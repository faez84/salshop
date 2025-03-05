<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class ProductController extends AbstractController
{
    #[Route(path:'/product/{id}', name: "product_details")]

    public function detail(int $id, ProductRepository $productRepository): Response
    {
        return $this->render('products/details.html.twig', [
            'product' => $productRepository->find($id),
        ])->setMaxAge(3600);
    }
}
