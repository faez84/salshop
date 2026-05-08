<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('paymentMethod', ChoiceType::class, options: [
                'choices' => $options['methods'],
            ])
            ->add('addressId', HiddenType::class, options: [
                'data' => $options['addressId'],
            ])
            ->add('idempotencyKey', HiddenType::class, options: [
                'data' => $options['idempotencyKey'],
            ])
            ->add('promoCode', TextType::class, [
                'required' => false,
                'empty_data' => '',
                'data' => $options['promoCode'],
                'label' => 'Promotion code',
            ])
            ->add('save', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'methods' => [],
            'addressId' => '',
            'idempotencyKey' => '',
            'promoCode' => '',
        ]);
    }
}
