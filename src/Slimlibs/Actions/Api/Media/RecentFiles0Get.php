<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Api\Media;

use Albatiqy\Slimlibs\Actions\ResultAction;
use Albatiqy\Slimlibs\Result\Results\Data;
use Albatiqy\Slimlibs\Support\Helper\Format;

final class RecentFiles0Get extends ResultAction { // cache count max files?

    protected function getResult(array $data, array $args) { // harus diganti!!!!!!!!!!
        $dir = \APP_DIR . '/var/resources/media';
        $basepos = \strlen($dir);

        $filter = [];

        $type = $data['filter'] ?? 'image';

        switch (\strtoupper($type)) {
            case 'IMAGE':
                $filter += ['JPG', 'JPEG', 'PNG'];
                break;
            default:
                $filter[] = \strtoupper($type);
        }

        $dir_iterator = new \RecursiveDirectoryIterator($dir);
        $dir_iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);
        $images = [];
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {

                $extension = \strtoupper($fileinfo->getExtension());
                $type = $extension;
                $filename = $fileinfo->getFilename();
                $props = [];
                switch ($extension) {
                    case 'JPG':
                    case 'JPEG':
                    case 'PNG':
                        if (\substr(\strtoupper($filename), -7)=='PDF.PNG') {
                            continue 2;
                        }
                        $type = 'IMAGE';
                    break;
                    case 'PDF':
                        $props['thumbnail'] = \file_exists($fileinfo->getPathname().'.png'); //$fileinfo->getPath().'/'.$filename
                }
                if (\in_array($extension, $filter)) {
                    $mtime = $fileinfo->getMTime();
                    if(isset($images[$mtime])) {
                        $images[$mtime][] = ['path'=>\substr($fileinfo->getPath(), $basepos), 'type'=>$type, 'name'=>$filename, 'mtime' => \date("Y m d", $mtime) , 'size' => Format::bytes($fileinfo->getSize()), 'props' => (object)$props];
                    } else {
                        $images[$mtime] = [['path'=>\substr($fileinfo->getPath(), $basepos), 'type'=>$type, 'name'=>$filename, 'mtime' => \date("Y m d", $mtime) , 'size' => Format::bytes($fileinfo->getSize()), 'props' => (object)$props]];
                    }
                }
            }
        }
        \krsort($images);
        $images = \call_user_func_array('array_merge', $images);
        $recent = \array_slice($images, 0, 5);
        return new Data($recent);
    }
}