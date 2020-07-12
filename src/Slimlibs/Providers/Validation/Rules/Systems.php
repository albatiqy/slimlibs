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

    public static function no_hp($value) {
        return Rules::regexp($value, '/^(08)[0-9]{8,20}/');
    }

    public static function nik($value) {
        return Rules::regexp($value, '/^[0-9]{16}$/');
        /*
        //330613050983
        $wilayah = \substr($value,0,6);
        $d = (int)\substr($value,6,2);
        $m = (int)\substr($value,8,2);
        $y = 2000;
        if (!\checkdate($m, $d, $y)) {
            return false;
        }
        $y = (int)\substr($value,8,4);
        $m = (int)\substr($value,12,2);
        $d = 1;
        if (!\checkdate($m, $d, $y)) {
            return false;
        }
        $jk = (int)\substr($value,14,1);
        if (!\in_array($jk, [1,2])) {
            return false;
        }
        */
    }

    public static function npwp($value) {
        return Rules::regexp($value, '/^[0-9]{15}$/');
        /*
        //330613050983
        $wilayah = \substr($value,0,6);
        $d = (int)\substr($value,6,2);
        $m = (int)\substr($value,8,2);
        $y = 2000;
        if (!\checkdate($m, $d, $y)) {
            return false;
        }
        $y = (int)\substr($value,8,4);
        $m = (int)\substr($value,12,2);
        $d = 1;
        if (!\checkdate($m, $d, $y)) {
            return false;
        }
        $jk = (int)\substr($value,14,1);
        if (!\in_array($jk, [1,2])) {
            return false;
        }
        */
    }

    public static function nip($value) {
        if (!Rules::regexp($value, '/^[0-9]{18}$/')) {
            return false;
        }
        $y = (int)\substr($value,0,4);
        $m = (int)\substr($value,4,2);
        $d = (int)\substr($value,6,2);
        if (!\checkdate($m, $d, $y)) {
            return false;
        }
        $y = (int)\substr($value,8,4);
        $m = (int)\substr($value,12,2);
        $d = 1;
        if (!\checkdate($m, $d, $y)) {
            return false;
        }
        $jk = (int)\substr($value,14,1);
        if (!\in_array($jk, [1,2])) {
            return false;
        }
        return true;
    }

    public static function nama($value) {
        return Rules::regexp($value, '/^[A-Za-z ,.\']{3,255}$/');
    }
}