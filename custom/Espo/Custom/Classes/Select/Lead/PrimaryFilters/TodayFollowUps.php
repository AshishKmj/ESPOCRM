<?php
namespace Espo\Custom\Classes\Select\Lead\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\Part\Condition as Cond;

class TodayFollowUps implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where(
            Cond::and(
                Cond::equal(Cond::column('reminderStatus'), 'due_today'),
                Cond::notIn(Cond::column('status'), ['Dead', 'Converted', 'Recycled'])
            )
        );
    }
}
