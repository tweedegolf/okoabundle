<?php

namespace Tg\OkoaBundle\Util;

use ArrayObject;

class TemplateBag extends ArrayObject
{
    public function __construct(array $data = array())
    {
        parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
    }

    public function data()
    {
        return $this->getArrayCopy();
    }

    public function __invoke()
    {
        return $this->data();
    }

    public function toArray()
    {
        return $this->data();
    }

    public function __toArray()
    {
        return $this->data();
    }
}
