<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Validation\Rules;

final class Rules {

    public static function is_empty($value) {
        return $value === null || (\is_array($value) && empty($value)) || (\is_string($value) && \trim($value) === '');
    }

    /**
     * Test that the value is an array
     */
    public static function is_array($value) {
        return \is_array($value);
    }

    /**
     * Test that the value is a string
     */
    public static function is_string($value) {
        return \is_string($value);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // Type rules

    /**
     * Check that the input value is considered a boolean
     * and alter the value if necessary.
     *
     * The type of the value is preserved.
     * - strings (such as 'true' or 'y') will become '0' or '1'
     * - integers and bools are not modified
     *
     * This is made to accomodate PDO/MySQL that don't handle boolean directly.
     * A SELECT statement will return '0' or '1' as strings too, so this way
     * we're consistant accross the board. This is also made to avoid bugs with
     * in_array() and the like, due to PHP's type conversion.
     *
     * If you want to force the type to something else, use the second parameter.
     *
     * @param $value
     * @param $sanitize mixed - true  => alter value and keep type (default)
     *                        - 'bool', 'string' or 'int' => alter and cast to this type
     *                        - false => do not alter the value
     */
    public static function bool(&$value, $sanitize = true) {
        $true_values = array('true', 't', 'yes', 'y', 'on', '1', 1, true);
        $false_values = array('false', 'f', 'no', 'n', 'off', '0', 0, false);

        $ret = false;

        if ($sanitize === true) {
            $sanitize = \gettype($value);
        }

        // see http://stackoverflow.com/questions/13846769/php-in-array-0-value
        if (\in_array($value, $true_values, true)) {
            $value = $sanitize ? true : $value;
            $ret = true;
        } elseif (\in_array($value, $false_values, true)) {
            $value = $sanitize ? false : $value;
            $ret = true;
        }

        if ($ret && $sanitize) {
            switch ($sanitize) {
            case 'int':
            case 'integer':
                $value = (int) $value;
                break;

            case 'string':
                $value = $value ? '1' : '0';
                break;

            case 'bool':
            case 'boolean':
                $value = (bool) $value;
                break;

            default:
                throw new \InvalidArgumentException("Cannot cast the value to type $sanitize");
            }
        }

        return $ret;
    }

    /**
     * Check that the input is a valid date, optionally of a given format
     *
     * @see http://www.php.net/strtotime
     */
    public static function date($value, $format = 'Y-m-d') {
        if (!\is_string($value)) {
            return false;
        }

        if ($format) {
            $ret = \DateTime::createFromFormat($format, $value);
            if ($ret) {
                $errors = \DateTime::getLastErrors();
                if (!empty($errors['warning_count'])) {
                    $ret = false;
                }
            }
        } else {
            // validate anything, not really recommended
            try {
                $ret = new \DateTime($value);
            } catch (\Exception $e) {
                $ret = false;
            }
        }

        return $ret !== false;
    }

    public static function datetime($value) {
        return self::date($value, 'Y-m-d H:i:s');
    }

    public static function time($value) {
        return self::date($value, 'H:i');
    }

    public static function numeric($value) {
        return \is_numeric($value);
    }

    public static function integer($value) {
        return \filter_var($value, \FILTER_VALIDATE_INT) !== false;
    }

    public static function decimal($value) {
        return \filter_var($value, \FILTER_VALIDATE_FLOAT) !== false;
    }

    public static function intl_decimal(&$value, $locale = null) {
        if (!\class_exists('\Locale')) {
            throw new \RuntimeException('intl extension is not installed');
        }

        if (!\is_string($value) && !\is_int($value) && !\is_float($value)) {
            return false;
        }

        if ($locale === null) {
            $locale = \Locale::getDefault();
        }

        $fmt = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        $ret = $fmt->parse($value);

        if ($ret !== false) {
            $value = $ret;
            return true;
        }

        return false;
    }

    public static function intl_integer(&$value, $locale) {
        $original_value = $value;
        $ret = self::intl_decimal($value, $locale);
        if ($ret == (int) $ret) {
            $value = (int) $ret;
            return true;
        }
        return false;
    }

    /**
     * Check that the input is a valid email address.
     */
    public static function email($value) {
        return \filter_var($value, \FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function url(&$value, $protocols = null) {
        $ret = \filter_var($value, \FILTER_VALIDATE_URL);
        if ($ret === false) {
            return false;
        }

        if ($protocols === null) {
            return true;
        }

        if (!\is_array($protocols)) {
            $protocols = array($protocols);
        }

        foreach ($protocols as $proto) {
            $proto .= '://';
            if (\substr($value, 0, \strlen($proto)) == $proto) {
                return true;
            }
        }

        return false;
    }

    public static function ip($value, $flags = null) {
        return \filter_var($value, \FILTER_VALIDATE_IP, $flags) !== false;
    }

    public static function ipv4($value) {
        return self::ip($value, \FILTER_FLAG_IPV4);
    }

    public static function ipv6($value) {
        return self::ip($value, \FILTER_FLAG_IPV6);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // Value rules

    /**
     * Check that the value is in the param array
     * If value is an array, it'll compute array diff.
     */
    public static function in($value, array $param) {
        if (\is_array($value)) {
            $ret = \array_diff($value, $param);
            return empty($ret);
        }

        return \in_array($value, $param);
    }

    /**
     * Check that value is a key of the param array.
     * If value is an array, it'll compute array diff.
     */
    public static function in_keys($value, array $param) {
        if (\is_array($value)) {
            $ret = \array_diff($value, \array_keys($param));
            return empty($ret);
        }

        if (!\is_string($value) && !\is_int($value)) {
            return false;
        }

        return \array_key_exists($value, $param);
    }

    public static function between($value, $between) {
        if (!\is_array($between) || \count($between) != 2) {
            throw new \InvalidArgumentException("'between' rule takes an array of exactly two values");
        }

        list($min, $max) = $between;
        if ($min !== null) {
            if (!self::min($value, $min)) {
                return false;
            }
        }
        if ($max !== null) {
            if (!self::max($value, $max)) {
                return false;
            }
        }

        return true;
    }

    public static function max($value, $param) {
        return $value <= $param;
    }

    public static function min($value, $param) {
        return $value >= $param;
    }

    public static function length($value, $between) {
        if (!\is_array($between) || \count($between) != 2) {
            throw new \InvalidArgumentException("'length' rule takes an array of exactly two values");
        }

        list($min, $max) = $between;
        if ($min !== null) {
            if (!self::min_length($value, $min)) {
                return false;
            }
        }
        if ($max !== null) {
            if (!self::max_length($value, $max)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check that the value is a maximum length
     */
    public static function max_length($value, $length) {
        if (!\is_string($value) && !\is_int($value)) {
            return false;
        }
        if ((!\is_int($length) && !\ctype_digit($length)) || $length < 0) {
            throw new \InvalidArgumentException('The length must be an positive integer');
        }
        return \mb_strlen($value) <= $length;
    }

    /**
     * Check that the value is a minimum length
     */
    public static function min_length($value, $length) {
        if (!\is_string($value) && !\is_int($value)) {
            return false;
        }
        if ((!\is_int($length) && !\ctype_digit($length)) || $length < 0) {
            throw new \InvalidArgumentException('The length must be an positive integer');
        }
        return \mb_strlen($value) >= $length;
    }

    /**
     * Check the value against a regexp.
     *
     * @param $value mixed
     * @param $regexp string Regular expression
     * @return bool
     */
    public static function regexp($value, $regexp) {
        if (!\is_string($regexp)) {
            throw new \InvalidArgumentException('The regular expression must be a string');
        }
        if (!$regexp) {
            throw new \InvalidArgumentException('The regular expression cannot be empty');
        }

        return !!\filter_var($value, \FILTER_VALIDATE_REGEXP, array(
            'options' => array('regexp' => $regexp),
        ));
    }

    ///////////////////////////////////////////////////////////////////////////////
    // Special rules

    /**
     * Check that the value is a string and trim it of unwanted character.
     *
     * @param $value mixed
     * @param $character_mask string The list of characters to be trimmed.
     * @see http://www.php.net/trim
     * @return bool
     */
    public static function trim(&$value, $character_mask = null) {
        // trim will trigger an error if called with something else than a string or an int
        if (!\is_string($value) && !\is_int($value) && !\is_float($value)) {
            return true;
        }

        if ($character_mask === null) {
            $character_mask = " \t\n\r\0\x0B";
        } elseif (!\is_string($character_mask)) {
            throw new \InvalidArgumentException("Character mask for 'trim' must be a string");
        }

        $value = \trim($value, $character_mask);
        return true;
    }

    // public static function date_max($value, $param)
    // {
    //     return strtotime($value) <= $param;
    // }

    // public static function date_min($value, $param)
    // {
    //     return strtotime($value) >= $param;
    // }

    public static function alpha_num($value) {
        return self::regexp($value, '/^[\pL\pM\pN]+$/u');
    }

    public static function uploaded($value, $type) {
        if ($value instanceof \Psr\Http\Message\UploadedFileInterface) {
            $mediatype = $value->getClientMediaType();
            $ext = \pathinfo($value->getClientFilename(), \PATHINFO_EXTENSION);
            $chkmedia = function ($t) use ($mediatype, $ext) {
                $types = \explode('/', $mediatype);
                switch (\strtoupper($t)) {
                case 'IMAGE':
                case 'JPEG':
                case 'JFIF':
                case 'JPG':
                case 'PNG':
                case 'GIF':
                    if (\strtoupper($types[0]) == 'IMAGE') {
                        return true;
                    }
                    break;
                default:
                    if (\strpos($t, '/') !== false) {
                        if (\strtoupper($t) == \strtoupper($mediatype)) {
                            return true;
                        }
                    } else {
                        if (\strtoupper($ext) == \strtoupper($t)) {
                            return true;
                        }
                    }
                }
                return false;
            };
            if (\is_array($type)) {
                foreach ($type as $t) {
                    if ($chkmedia($t)) {
                        return true;
                    }
                }
            } else {
                return $chkmedia($type);
            }
        }
        return false;
    }

    public static function tmp_file($value) {
        $container = \Albatiqy\Slimlibs\Container\Container::getInstance();
        $tmp_dir = \APP_DIR.'/var/tmp';
        if (\file_exists($tmp_dir.'/'.$value)) {
            return true;
        }
        return false;
    }

    public static function image_base64($value) {
        return self::regexp($value, '#^data:image/[^;]+;base64,#');
    }

    public static function alpha_num_space($value) {
        return self::regexp($value, '/^[\pL\pM\pN ]+$/u');
    }
}