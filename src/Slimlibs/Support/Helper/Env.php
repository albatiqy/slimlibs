<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

final class Env {

    public static function getClientIp() {
        $ipaddress = null;
        if (\getenv('HTTP_CLIENT_IP'))
            $ipaddress = \getenv('HTTP_CLIENT_IP');
        else if(\getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = \getenv('HTTP_X_FORWARDED_FOR');
        else if(\getenv('HTTP_X_FORWARDED'))
            $ipaddress = \getenv('HTTP_X_FORWARDED');
        else if(\getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = \getenv('HTTP_FORWARDED_FOR');
        else if(\getenv('HTTP_FORWARDED'))
            $ipaddress = \getenv('HTTP_FORWARDED');
        else if(\getenv('REMOTE_ADDR'))
            $ipaddress = \getenv('REMOTE_ADDR');

        if ($ipaddress) {
            if (\strpos($ipaddress, ',')!==false) {
                $addr = \explode(',', $ipaddress);
                $ipaddress = \trim($addr[\count($addr)-1]);
            }
        }

        return $ipaddress;
    }
}