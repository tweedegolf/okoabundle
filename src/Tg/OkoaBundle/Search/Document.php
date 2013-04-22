<?php

namespace Tg\OkoaBundle\Search;

use ArrayIterator;
use IteratorAggregate;
use Countable;

class Document implements IteratorAggregate, Countable
{
    private $options;

    private $fields;

    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->fields = [];
    }

    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    public function getOption($option, $default = null)
    {
        if ($this->hasOption($option)) {
            return $this->options[$option];
        }
        return $default;
    }

    public function hasOption($option)
    {
        return isset($this->options[$option]);
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function set($name, $value, array $options = [])
    {
        $this->fields[$name] = [
            'value' => $value,
            'options' => $options
        ];
    }

    public function has($name)
    {
        return isset($this->fields[$name]);
    }

    public function get($name, $default = null)
    {
        if ($this->has($name)) {
            return $this->fields[$name];
        }
        return $default;
    }

    public function getValue($name, $default = null)
    {
        if ($this->has($name)) {
            return $this->fields[$name]['value'];
        }
        return $default;
    }

    public function getFieldNames()
    {
        return array_keys($this->fields);
    }

    public function count()
    {
        return count($this->fields);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->fields);
    }
}
