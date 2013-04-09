<?php

namespace Tg\OkoaBundle\Util;

/**
 * Array utilities class.
 */
class ArrayUtil
{
    /**
     * Returns true if at least one element in the array matches
     * according to the given callback.
     * @param array|Iterator $array
     * @param callback $func First argument given is the key, second argument is the value.
     * @return boolean
     */
    public static function any($array, $func)
    {
        foreach ($array as $key => $item) {
            if ($func($key, $item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if all elements in the array match according
     * to the given callback.
     * @param array|Iterator $array
     * @param callback $func First argument given is the key, second argument is the value.
     * @return boolean
     */
    public static function all($array, $func)
    {
        foreach ($array as $key => $item) {
            if (!$func($key, $item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns the array of elements that matches according to the given callback.
     * The callback is given the array key as a first argument and the array value as the
     * second argument.
     * @param array|Iterator $array
     * @param callback $func First argument given is the key, second argument is the value.
     * @return array
     */
    public static function filter($array, $func)
    {
        $results = array();
        foreach ($array as $key => $item) {
            if ($func($key, $item)) {
                $results[$key] = $item;
            }
        }
        return $results;
    }

    /**
     * Find an item in an array where a callback returns true.
     * @param array|Iterator $array
     * @param callback $func The search function.
     * @param mixed $default The default value.
     * @return mixed
     */
    public static function find($array, $func, $default = null)
    {
        foreach ($array as $key => $value) {
            if ($func($key, $value)) {
                return $value;
            }
        }
        return $default;
    }

    /**
     * Return true if the given array is an associative array.
     * @param array|Iterator $arr
     * @return boolean
     */
    public static function isAssociative($arr)
    {
        if (is_array($arr)) {
            $keys = array_keys($arr);
            return count(array_filter($keys, 'is_string')) > 0 || min($keys) !== 0 || max($keys) !== count($keys) - 1;
        }
        return false;
    }

    /**
     * Return true if the given array is an indexed array.
     * @param array|Iterator $arr
     * @return boolean
     */
    public static function isIndexed($arr)
    {
        return !self::isAssociative($arr);
    }

    /**
     * Returns true if at least one of the values of the search array exists
     * as a value in the haystack array.
     * @param array|Iterator $search
     * @param array $haystack
     * @return boolean
     */
    public static function anyExists($search, array $haystack)
    {
        foreach ($search as $item) {
            if (in_array($item, $haystack)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if all of the values of the search array exist
     * as a value in the haystack array.
     * @param array|Iterator $search
     * @param array $haystack
     * @return boolean
     */
    public static function allExist($search, array $haystack)
    {
        foreach ($search as $s) {
            if (!in_array($s, $haystack)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Fold an array (from the start of the array moving to the end).
     * @param  array|Iterator    $arr
     * @param  mixed    $start
     * @param  callback $callback
     * @return mixed
     */
    public static function fold($array, $start, $callback)
    {
        $result = $start;
        foreach ($array as $key => $value) {
            $result = $callback($result, $key, $value);
        }
        return $result;
    }


    /**
     * Append values to an array
     * @param  array    $arr
     * @param  array    $values
     * @return array
     */
    public static function append(array &$arr, array $values)
    {
        $arr = array_merge($arr, $values);
        return $arr;
    }
}
