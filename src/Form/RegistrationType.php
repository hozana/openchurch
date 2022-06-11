<?php

namespace App\Form;

use FOS\UserBundle\Form\Type\RegistrationFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('fullname');
    }

    public function getParent(): string
    {
        return RegistrationFormType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'app_user_registration';
    }
}
