<?php

namespace Tg\OkoaBundle\Doctrine;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use LogicException;

class EntityListenerResolver extends DefaultEntityListenerResolver
{
    protected $listeners;

    public function __construct()
    {
        $this->listeners = [];
    }
    public function resolve($name)
    {
        foreach ($this->listeners as $listener) {
            if ($listener instanceof $name) {
                return $listener;
            }
        }
        return parent::resolve($name);
    }

    public function addListener($listener)
    {
        if (!is_object($listener)) {
            throw new LogicException("Listeners need to be objects");
        }
        $this->listeners[] = $listener;
    }
}
