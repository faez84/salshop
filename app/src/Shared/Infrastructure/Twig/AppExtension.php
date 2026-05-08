<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Twig;

use App\Catalog\Application\Port\Persistence\ICategoryRepository;
use App\Catalog\Infrastructure\Cache\CatalogCache;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private ICategoryRepository $categoriesRepository,
        private \Twig\Environment $twig,
        private CatalogCache $catalogCache
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('categoryList', [$this, 'categoryList']),
        ];
    }

    public function categoryList(): string
    {
//        $categories = $this->categoriesRepository->findRootCategorySummaries();

        $categories = $this->catalogCache->getRootCategories(
            fn (): array => $this->categoriesRepository->findRootCategorySummaries()
        );

        return $this->twig->render('layout/cats.html.twig', [
            'categories' => $categories,
        ]);
    }
}
