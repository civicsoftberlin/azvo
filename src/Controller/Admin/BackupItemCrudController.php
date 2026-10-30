<?php

namespace App\Controller\Admin;

use App\Entity\BackupItem;
use App\Enum\GpuSubstitutionType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BackupItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BackupItem::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            //IntegerField::new('substitutionId'),
            DateField::new('incidentDate'),
            IntegerField::new('hour'),
            IntegerField::new('absenceId'),
            IntegerField::new('classId'),
            TextField::new('absentTeacher'),
            TextField::new('substitutionTeacher'),
            TextField::new('absentCourse'),
            //TextField::new('absentCourseStatistics'),
            TextField::new('substitutionCourse'),
            //TextField::new('substitutionCourseStatistics'),
            TextField::new('absentRoom'),
            TextField::new('substitutionRoom'),
            //TextField::new('statistics'),
            TextField::new('absentClasses'),
            TextField::new('absenceReason'),
            TextField::new('absenceComment'),
            IntegerField::new('bit'),
            TextField::new('substitutionClasses'),
            TextField::new('substitutionType')
                ->formatValue(fn($item) => $item ? GpuSubstitutionType::tryFromChar($item)->getGermanLabel() : ' '),
            DateTimeField::new('updatedAt'),
            TextField::new('absenceCount'),

        ];
    }

}
