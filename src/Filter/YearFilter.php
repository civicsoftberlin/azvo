<?php

namespace App\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class YearFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        $currentYear = (int) date('Y');
        $years = [];

        // Die letzten 5 Jahre + aktuelles Jahr + kommende 2 Jahre
        for ($y = $currentYear - 5; $y <= $currentYear + 2; $y++) {
            $years[$y] = $y;
        }

        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ChoiceType::class)
            ->setFormTypeOptions([
                'choices' => $years,
                'placeholder' => 'Alle Jahre',
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $year = $filterDataDto->getValue();
        if (!$year) {
            return;
        }

        $alias = $filterDataDto->getEntityAlias();
        $start = sprintf('%04d-01-01', $year);
        $end = sprintf('%04d-12-31', $year);

        $queryBuilder->andWhere("{$alias}.incidentDate BETWEEN :ys AND :ye")
            ->setParameter('ys', $start)
            ->setParameter('ye', $end);
    }
}
