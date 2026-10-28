<?php

namespace App\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use DoctrineExtensions\Query\Mysql\Day;

final class DayFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        // Array von 1 bis 31 aufbauen: [1 => 1, 2 => 2, ..., 31 => 31]
        $days = [];
        for ($i = 1; $i <= 31; $i++) {
            $days[$i] = $i;
        }

        return new self()
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ChoiceType::class)
            ->setFormTypeOptions([
                'choices' => $days,
                'placeholder' => 'Alle Tage',
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $day = $filterDataDto->getValue();
        if (!$day) {
            return;
        }

        $alias = $filterDataDto->getEntityAlias();
        $dayPadded = str_pad((string)$day, 2, '0', STR_PAD_LEFT);
        // DAY() ist eine eingebaute DQL-Funktion in Doctrine.
        // Sie extrahiert den Tag (1-31) aus dem Datum.
        $queryBuilder->andWhere("{$alias}.incidentDate LIKE :dayPattern")
            ->setParameter('dayPattern', '%-' . $dayPadded);
    }
}
