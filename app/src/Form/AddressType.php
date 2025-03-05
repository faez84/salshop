<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Address;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('street', options: ['attr' =>
                [
                    'class' => 'border-1 border-[#1d1b1b]'
                ]
            ])
            ->add('city', options: ['attr' => [
                'class' => 'border-1 border-blue-700'
            ]])
            ->add('zip')
            ->add('defualt')
            ->add('save', SubmitType::class, [
                    'attr' =>
                        [
                            'class' => ' bg-indigo-600 flex gap-2 items-center text-white px-6 py-2 rounded-md 
                                        hover:bg-indigo-700 focus:outline-none focus:ring-2 
                                        focus:ring-indigo-500 focus:ring-offset-2'
                        ]
                ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
