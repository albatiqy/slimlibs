<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Result\Exception;

use Albatiqy\Slimlibs\Result\ResultException;

class NoAccessException extends ResultException {

    const CODE = 403;
    const MESSAGE = 'Access Denied Exception';
    const TITLE = '403 Access Denied Exception';
    const DESCRIPTION = '';

    protected function init() {
        $this->errType = self::T_NO_ACCESS;
    }

}