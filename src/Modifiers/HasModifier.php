<?php namespace Johnrich85\EloquentQueryModifier\Modifiers;

use Johnrich85\EloquentQueryModifier\FilterCountQuery;
use Johnrich85\EloquentQueryModifier\FilterSubQuery;

/**
 * Class WithModifier
 * @package Johnrich85\EloquentQueryModifier\Modifiers
 */
class HasModifier extends BaseModifier
{
    /**
     * Adds has filters if valid.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws \Exception
     */
    public function modify()
    {
        $hasQueries = $this->fetchValuesFromData();

        $isEmptyArray = is_array($hasQueries) && count($hasQueries) == 0;

        if ($hasQueries == null || $hasQueries === false || $isEmptyArray) {
            return $this->builder;
        }

        $this->addHasFilters($hasQueries);

        return $this->builder;
    }

    /**
     * Adds has filters, if all relations
     * exist. Throws exception if not.
     *
     * @param $hasQueries
     * @throws \Exception
     */
    protected function addHasFilters($hasQueries)
    {
        foreach ($hasQueries as $name => $query) {
            try {
                $this->builder->getRelation($name);
            } catch (\BadMethodCallException $e) {
                $this->throwInvalidRelationException($name);
            }

            $this->addHasFilter($name, $query);
        }
    }

    /**
     * Adds individual 'has' filter. Supports
     * basic, or a filter with a sub query.
     *
     * @param $name
     * @param array|closure $query
     */
    protected function addHasFilter($name, $query)
    {
        $countQuery = $this->pluckCountQuery($query);

        if (count($query) == 0 || $name == $query) {
            $this->builder->has($name, $countQuery->operator, (int)$countQuery->value);
        } else {
            $query = $this->buildSubQuery($name, $query);

            $this->builder->whereHas($name, $query, $countQuery->operator, (int)$countQuery->value);
        }
    }

    /**
     * Returns new FilterCountQuery object
     * and removes count from query params (since
     * any subsequent usage of params will have
     * no use for it).
     *
     * @param $query
     * @return FilterCountQuery
     */
    protected function pluckCountQuery(&$query)
    {
        if (empty($query['count'])) {
            $values = [];
        } else {
            $values = $query['count'];
            unset($query['count']);
        }

        $countQuery = new FilterCountQuery($values);

        return $countQuery;
    }

    /**
     * @return array|bool
     */
    public function fetchValuesFromData()
    {
        $withParameter = $this->config->getHas();

        if (empty($this->data[$withParameter])) {
            return false;
        }

        $fields = $this->data[$withParameter];

        if (is_array($fields)) {
            return $fields;
        }

        $fields = $this->parseString($fields);

        return $fields;
    }
}
