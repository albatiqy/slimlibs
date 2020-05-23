<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

final class Image {

    public static function strToBin($imageString) {
        $cleanedString = \str_replace(' ', '+', \preg_replace('#^data:image/[^;]+;base64,#', '', $imageString));
        $result = \base64_decode($cleanedString, true);

        if (!$result) {
            $result = $imageString;
        }

        return $result;
    }

    public static function resizeMax($image, $max_width, $max_height) {
        $w = \imagesx($image);
        $h = \imagesy($image);
        if ((!$w) || (!$h)) {
            die();
        }
        if (($w <= $max_width) && ($h <= $max_height)) {return $image;}
        $ratio = $max_width / $w;
        $new_w = (int)\round($max_width, 0);
        $new_h = (int)\round($h * $ratio, 0);
        if ($new_h > $max_height) {
            $ratio = $max_height / $h;
            $new_h = $max_height;
            $new_w = (int)\round($w * $ratio, 0);
        }
        $new_image = \imagecreatetruecolor($new_w, $new_h);
        \imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_w, $new_h, $w, $h);
        return $new_image;
    }

    public static function getImageLocation($image) {
        $exif = \exif_read_data($image, 0, true);
        if ($exif && isset($exif['GPS'])) {
            $GPSLatitudeRef = $exif['GPS']['GPSLatitudeRef'];
            $GPSLatitude = $exif['GPS']['GPSLatitude'];
            $GPSLongitudeRef = $exif['GPS']['GPSLongitudeRef'];
            $GPSLongitude = $exif['GPS']['GPSLongitude'];

            $lat_degrees = \count($GPSLatitude) > 0 ? self::gps2Num($GPSLatitude[0]) : 0;
            $lat_minutes = \count($GPSLatitude) > 1 ? self::gps2Num($GPSLatitude[1]) : 0;
            $lat_seconds = \count($GPSLatitude) > 2 ? self::gps2Num($GPSLatitude[2]) : 0;

            $lon_degrees = \count($GPSLongitude) > 0 ? self::gps2Num($GPSLongitude[0]) : 0;
            $lon_minutes = \count($GPSLongitude) > 1 ? self::gps2Num($GPSLongitude[1]) : 0;
            $lon_seconds = \count($GPSLongitude) > 2 ? self::gps2Num($GPSLongitude[2]) : 0;

            $lat_direction = ('W' == $GPSLatitudeRef || 'S' == $GPSLatitudeRef) ? -1 : 1;
            $lon_direction = ('W' == $GPSLongitudeRef || 'S' == $GPSLongitudeRef) ? -1 : 1;

            $latitude = $lat_direction * ($lat_degrees + ($lat_minutes / 60) + ($lat_seconds / (60 * 60)));
            $longitude = $lon_direction * ($lon_degrees + ($lon_minutes / 60) + ($lon_seconds / (60 * 60)));

            return ['latitude' => $latitude, 'longitude' => $longitude];
        } else {
            return false;
        }
    }

    public static function cache($url) {
        $parse_url = \parse_url($url);
        $base_dir = \APP_DIR . '/var/cache/imgcache/';
        $fcache = $base_dir.$parse_url['host'].$parse_url['path'];
        $key = null;
        if (\file_exists($fcache)) {
            $expires = (\filemtime($fcache) + (60*60*24*30));
            $now = \time() + 30;
            if ($expires <= $now) {
                $ch = \curl_init($url);
                $fp = \fopen($fcache, 'wb');
                \curl_setopt($ch, \CURLOPT_FILE, $fp);
                \curl_setopt($ch, \CURLOPT_HEADER, 0);
                \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
                \curl_exec($ch);
                \curl_close($ch);
                \fclose($fp);
                $key = \strtolower(\base_convert(\time().\rand(1,9),10,36));
                \file_put_contents($base_dir.$key, $fcache);
                \file_put_contents($fcache.'.map', $key);
            } else {
                $key = \file_get_contents($fcache.'.map');
            }
        } else {
            $dir = \dirname($fcache);
            if (!\is_dir($dir)) {
                \umask(2);
                \mkdir($dir, 0777, true);
            }
            $ch = \curl_init($url);
            $fp = \fopen($fcache, 'wb');
            \curl_setopt($ch, \CURLOPT_FILE, $fp);
            \curl_setopt($ch, \CURLOPT_HEADER, 0);
            \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
            \curl_exec($ch);
            \curl_close($ch);
            \fclose($fp);
            $key = \strtolower(\base_convert(\time().\rand(1,9),10,36));
            \file_put_contents($base_dir.$key, $fcache);
            \file_put_contents($fcache.'.map', $key);
        }
        return $key;
    }

    private static function gps2Num($coordPart){
        $parts = \explode('/', $coordPart);
        if(\count($parts) <= 0)
        return 0;
        if(\count($parts) == 1)
        return $parts[0];
        return \floatval($parts[0]) / \floatval($parts[1]);
    }
}