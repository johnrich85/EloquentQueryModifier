<?php namespace Johnrich85\EloquentQueryModifier\Modifiers;

use Johnrich85\EloquentQueryModifier\InputConfig;

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
    public function __construct(array $data, \Illuminate\Database\Eloquent\Builder $builder, InputConfig $config) {
        parent::__construct($data, $builder, $config);

        $this->filterType = $this->config->getFilterType();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function modify()
    {
        $fields = $this->getFilterableFields();

        if ($fields === false) {
            return $this->builder;
        } else {
            if ($fields == '') {
                $this->throwNoDataException();
            }
        }

        foreach ($fields as $field) {
            if (empty($this->data[$field])) {
                continue;
            }

            $data = $this->data[$field];

            if (is_array($data)) {
                $this->addWhereFilters($field, $data);
                continue;
            }

            $this->addWhereFilter($field, $data);
        }

        return $this->builder;
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
        if ($this->filterType == 'orWhere' && !$this->first) {
            $this->builder = $this->builder->orWhere($field, $value);
        } else {
            $this->builder = $this->builder->where($field, $value);
            $this->first = false;
        }
    }

    /**
     * Loops over an array, adding a where
     * filter on each iteration.
     *
     * @param $field
     * @param array $data
     */
    protected function addWhereFilters($field, array $data)
    {
        foreach ($data as $fieldValue) {
            $this->addWhereFilter($field, $fieldValue);
        }
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
