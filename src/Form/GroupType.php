<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Group Name',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
                    'placeholder' => 'Enter group name'
                ]
            ])
            ->add('contacts', EntityType::class, [
                'class' => Contact::class,
                'choice_label' => function(Contact $contact) {
                    return $contact->getFirstName() . ' ' . $contact->getLastName();
                },
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'attr' => ['class' => 'mt-1 block w-full'],
                'label' => 'Select Contacts'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
        ]);
    }
}