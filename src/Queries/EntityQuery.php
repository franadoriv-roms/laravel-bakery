<?php

namespace Bakery\Queries;

use Bakery\Concerns\ModelAware;
use Bakery\Traits\JoinsRelationships;
use Illuminate\Database\Eloquent\Builder;
use Bakery\Queries\Concerns\EagerLoadRelationships;

abstract class EntityQuery extends Query
{
    use ModelAware;
    use JoinsRelationships;
    use EagerLoadRelationships;

    /**
     * Scope the query.
     * This can be overwritten to make your own collection queries.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function scopeQuery(Builder $query): Builder
    {
        return $query;
    }
}
