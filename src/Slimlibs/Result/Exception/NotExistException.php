<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Result\Exception;

use Albatiqy\Slimlibs\Result\ResultException;

class NotExistException extends ResultException {

    const CODE = 404;
    const MESSAGE = 'Resource Not Exist';
    const TITLE = '';
    const DESCRIPTION = '';

    protected function init() {
        $this->errType = self::T_NOT_EXIST;
    }

}