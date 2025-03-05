<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\OrderProduct;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

/**
 * This will suppress all the PMD warnings in
 * this class.
 *
 * @SuppressWarnings(PHPMD)
 */

class OrderProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrderProduct::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            NumberField::new('oorder_id'),
            NumberField::new(propertyName: 'pproduct_id'),
            NumberField::new('amount'),
            MoneyField::new('cost')->setCurrency("EUR"),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('oorder')

            // most of the times there is no need to define the
            // filter type because EasyAdmin can guess it automatically
           
        ;
    }
    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
