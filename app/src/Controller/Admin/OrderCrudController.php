<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Checkout\Application\Port\IOrderStateManager;
use App\Checkout\Domain\Entity\Order as OrderAggregate;
use App\Checkout\Infrastructure\Persistence\Doctrine\Order;
use App\Checkout\Infrastructure\Persistence\Doctrine\OrderProduct;
use App\Controller\Admin\Field\MapField;
 
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

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

    public function __construct(
        private EntityManagerInterface $em,
        private readonly IOrderStateManager $orderStateManager
    )
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

    public function configureActions(Actions $actions): Actions
    {
        $refundAction = Action::new('refund', 'Refund')
            ->linkToCrudAction('refund')
            ->displayIf(static fn (Order $order): bool => Order::STATUS_FINISHED === $order->getStatus())
            ->addCssClass('btn btn-warning');

        $chargebackAction = Action::new('chargeback', 'Chargeback')
            ->linkToCrudAction('chargeback')
            ->displayIf(
                static fn (Order $order): bool => in_array(
                    (string) $order->getStatus(),
                    [Order::STATUS_FINISHED, Order::STATUS_REFUNDED],
                    true
                )
            )
            ->addCssClass('btn btn-danger');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $refundAction)
            ->add(Crud::PAGE_INDEX, $chargebackAction)
            ->add(Crud::PAGE_DETAIL, $refundAction)
            ->add(Crud::PAGE_DETAIL, $chargebackAction);
    }

    public function refund(AdminContext $context): Response
    {
        return $this->applyOrderStateChange($context, fn (OrderAggregate $order): bool => $this->orderStateManager->markAsRefunded($order), 'Order marked as refunded.');
    }

    public function chargeback(AdminContext $context): Response
    {
        return $this->applyOrderStateChange($context, fn (OrderAggregate $order): bool => $this->orderStateManager->markAsChargeback($order), 'Order marked as chargeback.');
    }

    /**
     * @param callable(OrderAggregate): bool $stateTransition
     */
    private function applyOrderStateChange(AdminContext $context, callable $stateTransition, string $successMessage): Response
    {
        $orderEntity = $context->getEntity()->getInstance();
        if (!$orderEntity instanceof Order) {
            throw new \RuntimeException('Order entity was not found.');
        }

        $order = OrderAggregate::fromPersistence($orderEntity);

        try {
            $transitionApplied = $stateTransition($order);
        } catch (Throwable) {
            $this->addFlash('error', 'Could not update the order state. Please retry.');

            return $this->redirect($this->getBackUrl($context));
        }

        if (!$transitionApplied) {
            $this->addFlash('warning', 'Order status transition is not allowed from the current state.');

            return $this->redirect($this->getBackUrl($context));
        }

        $this->addFlash('success', $successMessage);

        return $this->redirect($this->getBackUrl($context));
    }

    private function getBackUrl(AdminContext $context): string
    {
        $referrer = (string) $context->getReferrer();

        return '' !== $referrer ? $referrer : $this->generateUrl('admin');
    }
}
