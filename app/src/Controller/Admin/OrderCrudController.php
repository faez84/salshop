<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Field\MapField;
use App\Entity\Order;
use App\Entity\OrderProduct;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This will suppress all the PMD warnings in
 * this class.
 *
 * @SuppressWarnings(PHPMD)
 */
class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route(path: '/admin/oorder/{id}', name: 'admin_order_products')]
    public function dispalyOrderProducts(int $id): Response
    {
        $orderProds = $this->em->getRepository(OrderProduct::class)->findBy(["oorder" => $id]);
        return $this->render('admin/order_products.html.twig', [
            "orderProds" => $orderProds
        ]);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            MapField::new('id', 'orderProducts'),
            MoneyField::new('cost')->setCurrency('EUR'),
            TextField::new('payment'),
            TextField::new('status'),
            DateTimeField::new('createdAt'),

            // AssociationField::new('orderProducts', 'orderProducts')
            // ->setTemplatePath('admin/field/orderproducts.html.twig')
        ];
    }

    // public function configureActions(Actions $actions): Actions
    // {
    //     return $actions
    //     ->add(Crud::PAGE_INDEX, Action::DETAIL)
    //     ->add(Crud::PAGE_INDEX, Action::new('anewpprove', 'Approve Users')->linkToCrudAction('approveUsers')
    //     ->addCssClass('btn btn-primary')
    //     ->setIcon('fa fa-user-check'))
    //     // ...
    //         ->addBatchAction(Action::new('approve', 'Approve Users')
    //             ->linkToCrudAction('approveUsers')
    //             ->addCssClass('btn btn-primary')
    //             ->setIcon('fa fa-user-check'))
    //     ;
    // }
}
