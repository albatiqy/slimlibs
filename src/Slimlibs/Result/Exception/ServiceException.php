<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Result\Exception;

use Albatiqy\Slimlibs\Result\ResultException;

class ServiceException extends ResultException {

    const CODE = 500;
    const MESSAGE = 'Service Exception';
    const TITLE = '500 Service Exception';
    const DESCRIPTION = '';


    protected function init() {
        $this->errType = self::T_SERVICE;
    }
}