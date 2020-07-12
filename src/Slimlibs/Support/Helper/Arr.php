<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

final class Arr {

    public static function unique($array) {
        $array = \array_keys(\array_flip($array));

        return $array;
    }

    public static function get(&$var, $default = null) {
        if (isset($var)) {
            return $var;
        }

        return $default;
    }

    public static function is_assoc($array) {
        if (!\is_array($array)) {
            return false;
        }

        // $array = array() is not associative
        if (\sizeof($array) === 0) {
            return false;
        }

        return \array_keys($array) !== \range(0, \count($array) - 1);
    }

    public static function first(array $array) {
        return \reset($array);
    }

    public static function last(array $array) {
        return \end($array);
    }

    public static function firstKey(array $array) {
        \reset($array);

        return \key($array);
    }

    public static function lastKey(array $array) {
        \end($array);

        return \key($array);
    }

    public static function flatten(array $array, $preserve_keys = true) {
        $flattened = [];

        \array_walk_recursive($array, function ($value, $key) use (&$flattened, $preserve_keys) {
            if ($preserve_keys && !\is_int($key)) {
                $flattened[$key] = $value;
            } else {
                $flattened[] = $value;
            }
        });

        return $flattened;
    }

    public static function pluck(array $array, $field, $preserve_keys = true, $remove_nomatches = true) {
        $new_list = [];

        foreach ($array as $key => $value) {
            if (\is_object($value)) {
                if (isset($value->{$field})) {
                    if ($preserve_keys) {
                        $new_list[$key] = $value->{$field};
                    } else {
                        $new_list[] = $value->{$field};
                    }
                } elseif (!$remove_nomatches) {
                    $new_list[$key] = $value;
                }
            } else {
                if (isset($value[$field])) {
                    if ($preserve_keys) {
                        $new_list[$key] = $value[$field];
                    } else {
                        $new_list[] = $value[$field];
                    }
                } elseif (!$remove_nomatches) {
                    $new_list[$key] = $value;
                }
            }
        }

        return $new_list;
    }

    public static function array_search_deep(array $array, $search, $field = false) {
        // *grumbles* stupid PHP type system
        $search = (string) $search;

        foreach ($array as $key => $elem) {
            // *grumbles* stupid PHP type system
            $key = (string) $key;

            if ($field) {
                if (\is_object($elem) && $elem->{$field} === $search) {
                    return $key;
                } elseif (\is_array($elem) && $elem[$field] === $search) {
                    return $key;
                } elseif (\is_scalar($elem) && $elem === $search) {
                    return $key;
                }
            } else {
                if (\is_object($elem)) {
                    $elem = (array) $elem;

                    if (\in_array($search, $elem)) {
                        return $key;
                    }
                } elseif (\is_array($elem) && \in_array($search, $elem)) {
                    return $key;
                } elseif (\is_scalar($elem) && $elem === $search) {
                    return $key;
                }
            }
        }

        return false;
    }

    public static function array_map_deep(array $array, $callback, $on_nonscalar = false) {
        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                $args = [$value, $callback, $on_nonscalar];
                $array[$key] = \call_user_func_array(array(__CLASS__, __FUNCTION__), $args);
            } elseif (\is_scalar($value) || $on_nonscalar) {
                $array[$key] = \call_user_func($callback, $value);
            }
        }

        return $array;
    }

    public static function array_merge_deep(array $dest, array $src, $appendIntegerKeys = true) {
        foreach ($src as $key => $value) {
            if (\is_int($key) and $appendIntegerKeys) {
                $dest[] = $value;
            } elseif (isset($dest[$key]) and \is_array($dest[$key]) and \is_array($value)) {
                $dest[$key] = static::array_merge_deep($dest[$key], $value, $appendIntegerKeys);
            } else {
                $dest[$key] = $value;
            }
        }
        return $dest;
    }

    public static function array_clean(array $array) {
        return \array_filter($array);
    }
}