<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('auto_import_enable', CheckboxType::class, [
                'label' => 'active',
                'help' => 'Automatischen Import aktivieren',
            ])
            ->add('auto_import_url', TextType::class, [
                'label' => 'url',
                'required' => false,
            ]);
    }

}
