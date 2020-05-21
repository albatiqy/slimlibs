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

    public static function get_client_ip($trust_proxy_headers = false)
    {
        if (!$trust_proxy_headers) {
            return $_SERVER['REMOTE_ADDR'];
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}