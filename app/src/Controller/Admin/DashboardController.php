<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Catalog\Infrastructure\Persistence\Doctrine\Category;
use App\Checkout\Infrastructure\Persistence\Doctrine\Order;
use App\Checkout\Infrastructure\Persistence\Doctrine\OrderProduct;
use App\Checkout\Application\Port\Messaging\QueryBus;
use App\Checkout\Infrastructure\Messaging\Query\GetOrderStatsLastThreeMonthsQuery;
use App\Checkout\Infrastructure\Messaging\Query\OrderStatsPointView;
use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
use App\User\Infrastructure\Persistence\Doctrine\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * This will suppress all the PMD warnings in
 * this class.
 *
 * @SuppressWarnings(PHPMD)
 */
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly QueryBus $queryBus
    ) {
    }

    // ... you'll also need to load some CSS/JavaScript assets to render
    // the charts; this is explained later in the chapter about Design

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $stats = $this->queryBus->askNullable(new GetOrderStatsLastThreeMonthsQuery());
        $result = is_array($stats) ? $stats : [];

        $labels = [];
        $counts = [];
        foreach ($result as $point) {
            if (!$point instanceof OrderStatsPointView) {
                continue;
            }

            $labels[] = $point->getDateAsDay();
            $counts[] = $point->getOrderCount();
        }

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Created Orders',
                    'backgroundColor' => 'rgb(0, 0, 0)',
                    'borderColor' => 'rgb(0, 0, 0)',
                    'data' => $counts,
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 50,
                ],
            ],
        ]);
        return $this->render('admin/dashboard.html.twig', [
            'chart' => $chart,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Backend')
            ->renderContentMaximized()
        ;
    }

//     public function configureCrud(): Crud
//     {
//         return Crud::new()->overrideTemplates([
//             'crud/index' => 'admin/pages/list.html.twig',
// //            'crud/field/textarea' => 'admin/fields/dynamic_textarea.html.twig',
//         ]);
//     }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Blog');
        yield MenuItem::linkToCrud('Categories', 'fas fa-list', Category::class);
        yield MenuItem::linkToCrud('Products', 'fas fa-list', Product::class);
        yield MenuItem::linkToCrud('Orders', 'fas fa-list', Order::class);
        yield MenuItem::linkToCrud('Orders Products', 'fas fa-list', OrderProduct::class);
        yield MenuItem::linkToCrud('Users', 'fas fa-list', User::class);
    }
    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addAssetMapperEntry(Asset::new('app')->preload());
    }
}
