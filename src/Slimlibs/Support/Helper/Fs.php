<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Support\Helper;
final class Fs {

    public static function rmDir($path, $rmdir = true) {
        $iterator = new \DirectoryIterator($path);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isDir()) {
                continue;
            }
            self::rmDir($fileInfo->getPathname(), true);
        }
        $files = new \FilesystemIterator($path);

        foreach ($files as $file) {
            \unlink($file->getPathname());
        }
        if ($rmdir) {
            \rmdir($path);
        }
    }

    public static function copy($src, $dst) {
        $dir = \opendir($src);
        @\mkdir($dst);
        while (($file = \readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (\is_dir($src . '/' . $file)) {
                    self::copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    \copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        \closedir($dir);
    }

    public static function mkDir($path) {
        \umask(2);
        \mkdir($path, 0777, true);
    }

    public static function unZip($file, $dest) {
        \umask(2);
        $zip = \zip_open($file);
        if (\is_resource($zip)) {
            $tree = '';
            while (($zip_entry = \zip_read($zip)) !== false) {
                if (\strpos(\zip_entry_name($zip_entry), \DIRECTORY_SEPARATOR) !== false) {
                    $last = \strrpos(\zip_entry_name($zip_entry), \DIRECTORY_SEPARATOR);
                    $dir = \substr(\zip_entry_name($zip_entry), 0, $last);
                    $file = \substr(\zip_entry_name($zip_entry), \strrpos(\zip_entry_name($zip_entry), \DIRECTORY_SEPARATOR) + 1);
                    if (!\is_dir($dest . '/' . $dir)) {
                        if (@!\mkdir($dest . '/' . $dir, 0777, true)) {
                            throw new \Exception();
                        }
                    }
                    if (\strlen(\trim($file)) > 0) {
                        $return = @\file_put_contents($dest . '/' . $dir . '/' . $file, \zip_entry_read($zip_entry, \zip_entry_filesize($zip_entry)));
                        if (false === $return) {
                            throw new \Exception();
                        }
                    }
                } else {
                    \file_put_contents($dest . '/' . $file, \zip_entry_read($zip_entry, \zip_entry_filesize($zip_entry)));
                }
            }
        } else {
            throw new \Exception();
        }
    }

    public static function zip($path, $file) {
        $zip = function ($folder, &$zipFile, $exclusiveLength) {
            $handle = \opendir($folder);
            while (false !== $f = \readdir($handle)) {
                if ($f != '.' && $f != '..') {
                    $filePath = "$folder/$f";
                    $localPath = \substr($filePath, $exclusiveLength);
                    if (\is_file($filePath)) {
                        $zipFile->addFile($filePath, $localPath);
                    } elseif (\is_dir($filePath)) {
                        $zipFile->addEmptyDir($localPath);
                        $zip($filePath, $zipFile, $exclusiveLength);
                    }
                }
            }
            \closedir($handle);
        };
        $pathInfo = \pathInfo($path);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename'];
        $z = new \ZipArchive();
        $z->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $z->addEmptyDir($dirName);
        $zip($path, $z, \strlen("$parentPath/"));
        $z->close();
    }

    public static function list($path, $callback) {
        $directory = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
        foreach (new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST) as $item) {
            if (!$item->isFile()) {
                continue;
            }
            $callback($item->getRealPath());
        }
    }

    public static function directorySize($dir) {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS)) as $file => $key) {
            if ($key->isFile()) {
                $size += $key->getSize();
            }
        }
        return $size;
    }

    public static function directoryContents($dir) {
        $contents = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS)) as $pathname => $fi) {
            $contents[] = $pathname;
        }
        \natsort($contents);
        return $contents;
    }
}