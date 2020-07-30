<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

final class Str {

    public static function stripSpace($string) {
        return \preg_replace('/\s+/', '', $string);
    }

    public static function zeroPad($number, $length) {
        return \str_pad($number, $length, '0', \STR_PAD_LEFT);
    }

    public static function startsWith($string, $starts_with) {
        return \strpos($string, $starts_with) === 0;
    }

    public static function endsWith($string, $ends_with) {
        return \substr($string, -\strlen($ends_with)) === $ends_with;
    }

    public static function contains($haystack, $needle, $case_sensitive = false) {
        if ($case_sensitive) {
            return \strpos($haystack, $needle) !== false;
        }
        return \stripos($haystack, $needle) !== false;
    }

    public static function safeTruncate($string, $length, $append = '...') {
        $ret = \substr($string, 0, $length);
        $last_space = \strrpos($ret, ' ');

        if ($last_space !== false && $string != $ret) {
            $ret = \substr($ret, 0, $last_space);
        }

        if ($ret != $string) {
            $ret .= $append;
        }

        return $ret;
    }

    public static function urlSafeBase64_encode($string) {
        return \rtrim(\strtr(\base64_encode($string), '+/', '-_'), '=');
    }

    public static function urlSafeBase64_decode($string) {
        return \base64_decode(\str_pad(\strtr($string, '-_', '+/'), \strlen($string) % 4, '=', \STR_PAD_RIGHT));
    }
}