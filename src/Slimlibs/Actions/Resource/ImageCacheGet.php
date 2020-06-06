<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Resource;

use Albatiqy\Slimlibs\Actions\ResourceAction;

final class ImageCacheGet extends ResourceAction {

    protected function getResponse(array $data, array $args) {
        if (!isset($args['id'])) {
            return $this->renderImg('image/png', \LIBS_DIR.'/web/resources/blank.png');
        }
        $srckey = \APP_DIR . '/var/resources/imgcache/keys/'.$args['id'];
        if (!\file_exists($srckey)) {
            return $this->renderImg('image/png', \LIBS_DIR.'/web/resources/blank.png');
        }
        $filein = \file_get_contents($srckey);
        if (\file_exists($filein)) {
            $extension = \pathinfo($filein, \PATHINFO_EXTENSION);
            switch (\strtoupper($extension)) {
                case 'JPG':
                case 'JPEG':
                    return $this->renderImg('image/jpg', $filein);
                case 'PNG':
                    return $this->renderImg('image/png', $filein);
            }
        } else {
            return $this->renderImg('image/png', \LIBS_DIR.'/web/resources/blank.png');
        }
    }
}