<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

final class Convert {

    public static function strToBool($string, $default = false)
    {
        $yes_words = 'affirmative|all right|aye|indubitably|most assuredly|ok|of course|okay|sure thing|y|yes+|yea|yep|sure|yeah|true|t|on|1|oui|vrai';
        $no_words = 'no*|no way|nope|nah|na|never|absolutely not|by no means|negative|never ever|false|f|off|0|non|faux';

        if (\preg_match('/^(' . $yes_words . ')$/i', $string)) {
            return true;
        } elseif (\preg_match('/^(' . $no_words . ')$/i', $string)) {
            return false;
        }

        return $default;
    }

}