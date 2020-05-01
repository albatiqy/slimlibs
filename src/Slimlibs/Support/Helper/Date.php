<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;

final class Date {

    //$tgl_mulai = \DateTime::createFromFormat('Y-m-d', $kegiatan['tgl_mulai']);

    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public static $bulan = ["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
    public static $hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jum'at", "Sabtu"];

    public static function rangeFormat($date1, $date2, $separator=' s.d. ') {
        $src_mulai = \date_create_from_format(self::DATE_FORMAT, $date1);
        $src_selesai = \date_create_from_format(self::DATE_FORMAT, $date2);
        if ($src_mulai > $src_selesai)
            $src_selesai = $src_mulai;
        $arr_mulai = [\date_format($src_mulai, "j"), \date_format($src_mulai, "n"), \date_format($src_mulai, "Y")];
        $arr_selesai = [\date_format($src_selesai, "j"), \date_format($src_selesai, "n"), \date_format($src_selesai, "Y")];
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

    public static function format($date, $hari = false) {
        $src = \date_create_from_format(self::DATE_FORMAT, $date);
        return ($hari?self::$hari[\date_format($src, "w")].', ':'').\date_format($src, "j").' '.self::$bulan[\date_format($src, "n")].' '.\date_format($src, "Y");
    }
}