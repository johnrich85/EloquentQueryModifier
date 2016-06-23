<?php namespace Johnrich85\EloquentQueryModifier\Modifiers;

use Johnrich85\EloquentQueryModifier\InputConfig;

abstract class BaseModifier implements QBModifier
{

    /**
     * @var InputConfig
     */
    protected $config;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $builder;

    public function __construct(array $data, \Illuminate\Database\Eloquent\Builder $builder, InputConfig $config)
    {
        $this->data = $data;
        $this->builder = $builder;
        $this->config = $config;
    }

    /**
     * Given a comma delimited string, explodes
     * to array.
     *
     * @param $list
     * @return array
     */
    protected function listToArray($list)
    {
        $payload = array_map(
            'trim',
            explode(',', $list)
        );

        return $payload;
    }

    /**
     * @return bool
     */
    protected function hasEagerLoad()
    {
        return $hasEagerLoad = (boolean)count($this->builder->getEagerLoads());
    }

    /**
     * @throws \Exception
     */
    protected function throwNoDataException()
    {
        throw new \Exception('Query parameter provided, but contains no data.');
    }

    /**
     * @param $field
     * @throws \Exception
     */
    protected function throwInvalidFieldException($field)
    {
        throw new \Exception('Query string parameter contains an invalid field: ' . $field);
    }

    /**
     * @param $class
     * @throws \Exception
     */
    protected function throwSearchNotSupportedException($class)
    {
        $message = $class;
        $message .= ' does not support search. ';
        $message .= 'To enable search for this model implement the Sofa\Eloquence\Eloquence trait';

        throw new \Exception($message);
    }
}

interface QBModifier
{
    public function modify();
}