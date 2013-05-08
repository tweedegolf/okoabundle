<?php

namespace Tg\OkoaBundle\Util;

class PathUtil
{
    public static function join()
    {
        $paths = array();
        foreach (func_get_args() as $arg) {
            if ($arg !== '') {
                $paths[] = $arg;
            }
        }
        return preg_replace('#/+#', '/', join('/', $paths));
    }
}
