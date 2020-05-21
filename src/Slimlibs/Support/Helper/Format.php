<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

final class Format {

    public static function bytes($bytes, $decimals = 0) {
        $bytes = \floatval($bytes);

        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < \pow(1024, 2)) {
            return \number_format($bytes / 1024, $decimals, '.', '') . ' KB';
        } elseif ($bytes < \pow(1024, 3)) {
            return \number_format($bytes / \pow(1024, 2), $decimals, '.', '') . ' MB';
        } elseif ($bytes < \pow(1024, 4)) {
            return \number_format($bytes / \pow(1024, 3), $decimals, '.', '') . ' GB';
        } elseif ($bytes < \pow(1024, 5)) {
            return \number_format($bytes / \pow(1024, 4), $decimals, '.', '') . ' TB';
        } elseif ($bytes < \pow(1024, 6)) {
            return \number_format($bytes / \pow(1024, 5), $decimals, '.', '') . ' PB';
        } else {
            return \number_format($bytes / \pow(1024, 5), $decimals, '.', '') . ' PB';
        }
    }
}