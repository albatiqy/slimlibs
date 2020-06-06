<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Api\Media;

use Albatiqy\Slimlibs\Actions\ResultAction;
use Albatiqy\Slimlibs\Result\Results\Data;
use Albatiqy\Slimlibs\Support\Helper\Format;

final class Browse0Get extends ResultAction { // cache

    protected function getResult(array $data, array $args) {
        $base_dir = \APP_DIR . '/var/resources/media';
        $path = $data['path'] ?? '/uploads';

        if (\substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
            $path = \rtrim($path, '/');
        }
        $path = \preg_replace('#/+#', '/', $path);
        $path = \str_replace('../', '', $path);

        $dir = \rtrim($base_dir . $path, '/');

        $filter = [];

        $type = $data['filter'] ?? 'image';

        switch (\strtoupper($type)) {
            case 'IMAGE':
                $filter += ['JPG', 'JPEG', 'PNG'];
                break;
            default:
                $filter[] = \strtoupper($type);
        }

        $results = [];

        if (\is_dir($dir)) {
            $iterator = new \DirectoryIterator($dir);
            foreach ($iterator as $fileinfo) {
                if (!$fileinfo->isDot()) {
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
                            $results[] = ['isdir' => false, 'type'=>$type, 'name' => $filename, 'mtime' => \date("Y m d", $fileinfo->getMTime()) , 'size' => Format::bytes($fileinfo->getSize()), 'props' => (object)$props];
                        }
                    } elseif ($fileinfo->isDir()) {
                        $results[] = ['isdir' => true, 'name' => $fileinfo->getFilename()];
                    }
                }
            }
        }

        return new Data(['files'=>$results, 'path'=>$path]);
    }
}