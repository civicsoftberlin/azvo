<?php

namespace App\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class MonthFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        $months = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
        ];

        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            // EA 5: Formular-Typ und Optionen direkt hier setzen
            ->setFormType(ChoiceType::class)
            ->setFormTypeOptions([
                'choices' => array_flip($months),
                'placeholder' => 'Alle Monate',
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $month = $filterDataDto->getValue();
        if (!$month) {
            return;
        }

        $alias = $filterDataDto->getEntityAlias();
        $currentYear = (int) date('Y');

        $orX = $queryBuilder->expr()->orX();

        for ($y = $currentYear - 5; $y <= $currentYear + 2; $y++) {
            $start = sprintf('%04d-%02d-01', $y, $month);
            $end = date('Y-m-t', strtotime($start));

            $orX->add(
                $queryBuilder->expr()->between("{$alias}.incidentDate", ":ms_{$y}", ":me_{$y}")
            );
            $queryBuilder->setParameter("ms_{$y}", $start);
            $queryBuilder->setParameter("me_{$y}", $end);
        }

        $queryBuilder->andWhere($orX);
    }
}
