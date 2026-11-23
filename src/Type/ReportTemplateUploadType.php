<?php

namespace App\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ReportTemplateUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('until', DateType::class, [
                'label' => 'Letzter Schultag laut Vorlage',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control-sm rounded-5 '
                ],
            ])
            ->add('templateFile', FileType::class, [
                'label' => 'Excel-Vorlage hochladen',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => '',
                    'accept' => '.xlsx',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
