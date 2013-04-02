<?php

namespace Tg\OkoaBundle\Util;

use ArrayObject;

class TemplateBag extends ArrayObject
{
    public function __construct(array $data = array())
    {
        parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
    }

    public function toArray()
    {
        return $this->getArrayCopy();
    }
}
