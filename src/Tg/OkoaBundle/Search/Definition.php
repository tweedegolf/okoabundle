<?php

namespace Tg\OkoaBundle\Search;

class Definition
{
    protected $definitions;

    protected $metadata;

    public function __construct()
    {
        $this->definitions = [];
    }

    public function define($column, $type, array $options = [])
    {
        $this->definitions[$column] = [
            'type' => $type,
            'options' => $options
        ];
    }

    public function hasColumn($column)
    {
        return in_array($column, $this->getDefinedColumns());
    }

    public function getDefinedColumns()
    {
        return array_keys($this->definitions);
    }

    public function getColumnsOfType($type)
    {
        $defs = [];
        foreach ($this->definitions as $key => $value) {
            if ($value['type'] === $type) {
                $defs[] = $key;
            }
        }
        return $defs;
    }

    public function getColumnOptions($column)
    {
        return $this->definitions[$column]['options'];
    }

    public function setColumnOption($column, $option, $value)
    {
        $this->definitions[$column]['options'][$option] = $value;
    }

    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function getEntityClassname()
    {
        return $this->metadata->getName();
    }
}
