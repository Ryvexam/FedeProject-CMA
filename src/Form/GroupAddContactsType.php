<?php

namespace App\Form;

use App\Entity\Group;
use App\Entity\Contact;
use App\Repository\ContactRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
                'query_builder' => function (ContactRepository $contactRepository) {
                    return $contactRepository->createQueryBuilder('c')
                        ->orderBy('c.firstName', 'ASC');
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
        ]);
    }
}