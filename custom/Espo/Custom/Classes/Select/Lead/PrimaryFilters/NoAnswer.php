<?php
namespace Espo\Custom\Classes\Select\Lead\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\Part\Condition as Cond;

class NoAnswer implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where(
            Cond::in(Cond::column('lastFollowUpOutcome'), ['no_answer', 'busy_call_later'])
        );
    }
}
