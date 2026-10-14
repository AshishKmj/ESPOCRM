<?php
namespace Espo\Custom\Classes\Select\Lead\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\Part\Condition as Cond;

class FollowUpDateMissing implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where(
            Cond::and(
                Cond::or(
                    Cond::equal(Cond::column('reminderStatus'), 'missing_date'),
                    Cond::equal(Cond::column('nextFollowUpDate'), null)
                ),
                Cond::notIn(Cond::column('status'), ['Dead', 'Converted', 'Recycled'])
            )
        );
    }
}
