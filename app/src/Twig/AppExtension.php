<?php

declare(strict_types=1);

namespace App\Twig;

use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Response;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(private CategoryRepository $categoriesRepository, private \Twig\Environment $twig)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('calculateArea', [$this, 'calculateArea']),
        ];
    }

    public function calculateArea(): string
    {
        return $this->twig->render('layout/cats.html.twig', [
            'categories' => $this->categoriesRepository->findBy(['parent' => null])]);
    }
}
