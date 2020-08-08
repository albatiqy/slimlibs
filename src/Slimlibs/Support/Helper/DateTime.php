<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

final class DateTime {

    private const INPUT_FORMAT = 'Y-m-d H:i:s';

    public static $bulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
    public static $hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jum'at", "Sabtu"];

    public static function rangeFormat($date1, $date2, $separator = null, $input_format = null) {
        if ($separator == null) {
            $separator = ' s.d. ';
        }
        if ($input_format == null) {
            $input_format = self::INPUT_FORMAT;
        }
        if (!$date1||!$date2) {
            if (!$date2) {
                $date2 = $date1;
            } else {
                $date1 = $date2;
            }
        }
        $src_mulai = \DateTime::createFromFormat($input_format, $date1);
        $src_selesai = \DateTime::createFromFormat($input_format, $date2);
        if ($src_mulai->getTimestamp() > $src_selesai->getTimestamp()) {
            $tmp = $src_selesai;
            $src_selesai = $src_mulai;
            $src_mulai = $tmp;
        }
        $arr_mulai = [$src_mulai->format("j"), $src_mulai->format("n"), $src_mulai->format("Y")];
        $arr_selesai = [$src_selesai->format("j"), $src_selesai->format("n"), $src_selesai->format("Y")];
        $rentang_tanggal = '';
        if ($arr_mulai[2] != $arr_selesai[2])
            $rentang_tanggal = $arr_mulai[0].' '.self::$bulan[$arr_mulai[1]].' '.$arr_mulai[2].$separator.$arr_selesai[0].' '.self::$bulan[$arr_selesai[1]].' '.$arr_selesai[2];
        elseif ($arr_mulai[1] != $arr_selesai[1])
            $rentang_tanggal = $arr_mulai[0].' '.self::$bulan[$arr_mulai[1]].$separator.$arr_selesai[0].' '.self::$bulan[$arr_selesai[1]].' '.$arr_selesai[2];
        elseif ($arr_mulai[0] != $arr_selesai[0])
            $rentang_tanggal = $arr_mulai[0].$separator.$arr_selesai[0].' '.self::$bulan[$arr_selesai[1]].' '.$arr_selesai[2];
        else
            $rentang_tanggal = $arr_selesai[0].' '.self::$bulan[$arr_selesai[1]].' '.$arr_selesai[2];
        return $rentang_tanggal;
    }

    public static function rangeTimeFormat($date1, $date2, $input_format = null) {
        if ($input_format == null) {
            $input_format = self::INPUT_FORMAT;
        }
        if (!$date1||!$date2) {
            if (!$date2) {
                $date2 = $date1;
            } else {
                $date1 = $date2;
            }
        }
        $src_mulai = \DateTime::createFromFormat($input_format, $date1);
        $src_selesai = \DateTime::createFromFormat($input_format, $date2);
        if ($src_selesai->getTimestamp() < $src_mulai->getTimestamp()) {
            $tmp = $src_mulai;
            $src_mulai = $src_selesai;
            $src_selesai = $tmp;
        }
        $arr_mulai = [$src_mulai->format("j"), $src_mulai->format("n"), $src_mulai->format("Y")];
        $arr_selesai = [$src_selesai->format("j"), $src_selesai->format("n"), $src_selesai->format("Y")];
        $rentang_tanggal = '';
        if ($src_mulai->format("Y-m-d")==$src_selesai->format("Y-m-d")) {
            if ($src_mulai->format("H:i")==$src_selesai->format("H:i")) {
                $rentang_tanggal = self::$hari[$src_mulai->format("w")].' '.$arr_mulai[0].' '.self::$bulan[$arr_mulai[1]].' '.$arr_mulai[2].', pukul '.$src_selesai->format("H:i").' WIB';
            } else {
                $rentang_tanggal = self::$hari[$src_mulai->format("w")].' '.$arr_mulai[0].' '.self::$bulan[$arr_mulai[1]].' '.$arr_mulai[2].', pukul '.$src_mulai->format("H:i").' s.d. '.$src_selesai->format("H:i").' WIB';
            }
        } else {
            $rentang_tanggal = self::$hari[$src_mulai->format("w")].' '.$arr_mulai[0].' '.self::$bulan[$arr_mulai[1]].' '.$arr_mulai[2].' pukul '.$src_mulai->format("H:i").' WIB s.d. '.self::$hari[$src_selesai->format("w")].' '.$arr_selesai[0].' '.self::$bulan[$arr_selesai[1]].' '.$arr_selesai[2].' pukul '.$src_selesai->format("H:i").' WIB';
        }
        return $rentang_tanggal;
    }

    public static function format($date, $hari = false, $input_format = null) {
        if ($input_format == null) {
            $input_format = self::INPUT_FORMAT;
        }
        $src = \DateTime::createFromFormat($input_format, $date);
        return ($hari?self::$hari[$src->format("w")].', ':'').$src->format("j").' '.self::$bulan[$src->format("n")].' '.$src->format("Y");
    }
}