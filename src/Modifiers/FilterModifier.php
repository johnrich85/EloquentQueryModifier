<?php namespace Johnrich85\EloquentQueryModifier\Modifiers;

use Johnrich85\EloquentQueryModifier\FilterQuery;
use Johnrich85\EloquentQueryModifier\InputConfig;
use Johnrich85\EloquentQueryModifier\InputDecoders\JsonDecoder;
use Mockery\CountValidator\Exception;

class FilterModifier extends BaseModifier
{
    /**
     * The type of filter that will be applied.
     * @var string
     */
    protected $filterType = 'where';

    /**
     * @var bool
     */
    protected $first = true;

    /**
     * FilterModifier constructor.
     * @param array $data
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param InputConfig $config
     */
    public function __construct(array $data, \Illuminate\Database\Eloquent\Builder $builder, InputConfig $config)
    {
        parent::__construct($data, $builder, $config);

        $this->filterType = $this->config->getFilterType();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function modify()
    {
        $fields = $this->getFilterableFields();

        if ($fields == false) {
            return $this->builder;
        };

        $this->addWheres($fields);

        return $this->builder;
    }

    /**
     * Loops data, adding where filters
     * to columns.
     *
     * @param $fields
     */
    protected function addWheres($fields)
    {
        foreach ($fields as $field) {
            if (empty($this->data[$field])) {
                continue;
            }

            $data = $this->data[$field];

            if ($this->detectWhereInFilter($data)) {
                $this->addWhereInFilter($field, $data);
                continue;
            }

            $this->addWhereFilter($field, $data);
        }
    }

    /**
     * @param $data
     * @return bool
     */
    protected function detectWhereInFilter($data)
    {
        if (!is_array($data) || (array_key_exists('value', $data) ||
                array_key_exists('operator', $data))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns an array of filterable fields,
     * or false if none are found.
     *
     * @return array|bool
     */
    protected function getFilterableFields()
    {
        $fields = $this->config->getFilterableFields();

        if (count($fields) == 0) {
            return false;
        }

        return $fields;
    }

    /**
     * @param $field
     * @param $value
     */
    protected function addWhereFilter($field, $value)
    {
        $query = $this->parseQuery($value);

        if ($query->value !== null) {
            $this->addWhereType($field, $query->operator, $query->value);
        }
    }

    /**
     * Returns FilterQuery object.
     *
     * @param $value
     * @return FilterQuery|null
     */
    protected function parseQuery($value)
    {
        $payload = null;

        if($value instanceof FilterQuery) {
            $payload =  $value;
        } elseif  (is_array($value)) {
            $payload = new FilterQuery($value);
        } else {
            $json = $this->jsonDecode($value);

            if ($json) {
                $values = [
                    'value' => $this->getJsonValue($json),
                    'operator' => $this->getJsonOperator($json)
                ];
            } else {
                $values = [
                    'value' => $value
                ];
            }

            $payload = new FilterQuery($values);
        }

        return $payload;
    }

    /**
     * @param $field
     * @param $operator
     * @param $value
     */
    protected function addWhereType($field, $operator, $value)
    {
        if ($this->isInclude($operator)) {
            $this->builder = $this->builder->whereIn($field, $value);

            return;
        } elseif ($this->isExclude($operator)) {
            $this->builder = $this->builder->whereNotIn($field, $value);
        } else {
            $this->addStandardWhere($field, $operator, $value);
        }
    }

    /**
     * Adds standard where filter.
     *
     * @param $field
     * @param $operator
     * @param $value
     */
    protected function addStandardWhere($field, $operator, $value)
    {
        if ($operator == '==') {
            $operator = '=';
        }

        if ($this->filterType == 'orWhere' && !$this->first) {
            $this->builder = $this->builder->orWhere($field, $operator, $value);
        } else {
            $this->builder = $this->builder->where($field, $operator, $value);
            $this->first = false;
        }
    }

    /**
     * @param $operator
     * @return bool
     */
    protected function isInclude($operator)
    {
        return $operator == 'include';
    }

    /**
     * @param $operator
     * @return bool
     */
    protected function isExclude($operator)
    {
        return $operator == 'exclude';
    }

    /**
     * @param $value
     * @return bool
     */
    protected function jsonDecode($value)
    {
        $decoder = new JsonDecoder();

        $decoder->decode($value);

        if (!$decoder->success()) {
            return false;
        }

        return $decoder->getData();
    }

    /**
     * @param array $decoded
     * @return mixed|null
     */
    protected function getJsonValue(array $decoded)
    {
        if (isset($decoded['value'])) {
            return $decoded['value'];
        }

        return null;
    }

    /**
     * @param array $decoded
     * @return string
     */
    protected function getJsonOperator(array $decoded)
    {
        if (isset($decoded['operator'])) {
            return $decoded['operator'];
        }

        return '=';
    }

    /**
     * Loops over an array, adding a where
     * filter on each iteration.
     *
     * @param $field
     * @param array $data
     */
    protected function addWhereInFilter($field, array $data)
    {
        if (array_key_exists('operator', $data) || array_key_exists('value', $data)) {
            $error = 'Field value must be an object, not an array. Arrays are supported but only for WhereIn queries.';
            $error .= 'Please refer to documentation for further info.';

            throw new Exception($error);
        }

        $queryOb = new FilterQuery();
        $queryOb->operator = 'include';
        $queryOb->value = $data;

        $this->addWhereFilter($field, $queryOb);
    }

    /**
     * @return string
     */
    public function getFilterType()
    {
        return $this->filterType;
    }

    /**
     * @param string $filterType
     */
    public function setFilterType($filterType)
    {
        $this->filterType = $filterType;
    }

}
