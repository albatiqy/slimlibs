<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

final class Hash {

    public static function genUuid4($separator = '-') {
        return \sprintf("%04x%04x{$separator}%04x{$separator}%04x{$separator}%04x{$separator}%04x%04x%04x",
            \mt_rand(0, 0xffff), \mt_rand(0, 0xffff),
            \mt_rand(0, 0xffff),
            \mt_rand(0, 0x0fff) | 0x4000,
            \mt_rand(0, 0x3fff) | 0x8000,
            \mt_rand(0, 0xffff), \mt_rand(0, 0xffff), \mt_rand(0, 0xffff)
        );
    }

    public static function genCouponCode($j=4) {
        $alpha = \str_split('ABCDEFGHJKLMNPQRSTUVWXYZ');
        $alpha_cnt = \count($alpha)-1;
        $digit = \str_split('23456789');
        $digit_cnt = \count($digit)-1;
        $string = '';
        for ($i=0;$i<$j;$i++) {
            \srand(\intval((double)\microtime()*1234567));
            $x = \mt_rand(0, 1);
            switch ($x) {
                case 0:
                    $string .= $alpha[\mt_rand(0,$alpha_cnt)];
                break;
                case 1:
                    $string .= $digit[\mt_rand(0,$digit_cnt)];
                break;
            }
        }
        return $string;
    }

    public static function genTimeRandomString($j=8) {
        $string = '';
        for ($i=0;$i<$j;$i++) {
            \srand(\intval((double)\microtime()*1234567));
            $x = \mt_rand(0, 1);
            switch ($x) {
                case 0:
                    $string .= \chr(\mt_rand(65,90));
                break;
                case 1:
                    $string .= \chr(\mt_rand(48,57));
                break;
            }
        }
        return $string;
    }
}