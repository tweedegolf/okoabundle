<?php

namespace Tg\OkoaBundle\Util;

/**
 * String utility functions.
 */
class StringUtil
{
    /**
     * Very basic function that makes some english word plural.
     * @param  string $str
     * @return string
     */
    public static function pluralize($str)
    {
        if (strlen($str) > 0 && $str[strlen($str) - 1] === 's') {
            return $str . 'es';
        }

        return $str . 's';
    }

    /**
     * Very basic function that makes an english plural singular.
     * @param  string $str
     * @return string
     */
    public static function singular($str)
    {
        if (strlen($str) > 0 && $str[strlen($str) - 1] === 's') {
            if (strlen($str) > 2 && substr($str, -3) == 'ses') {
                return substr($str, 0, -2);
            }

            return substr($str, 0, -1);
        }

        return $str;
    }
}
