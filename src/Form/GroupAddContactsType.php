<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupAddContactsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contacts', EntityType::class, [
                'class' => Contact::class,
                'choice_label' => function(Contact $contact) {
                    return $contact->getFirstName() . ' ' . $contact->getLastName();
                },
                'multiple' => true,
                'expanded' => true,
                'label' => 'Select Contacts to Add',
                'attr' => ['class' => 'mt-1 block w-full'],
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