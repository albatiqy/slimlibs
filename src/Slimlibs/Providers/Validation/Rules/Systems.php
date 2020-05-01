<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Validation\Rules;

final class Systems {

    public static function dapodik_kode_wilayah($value) {
        return Rules::regexp($value, '/^[0-9 ]{6,8}$/');
    }

    public static function dapodik_npsn($value) {
        return Rules::regexp($value, '/^[0-9]{8}$/');
    }

    public static function dapodik_nuptk($value) {
        return Rules::regexp($value, '/^[0-9]{16}$/');
    }
}