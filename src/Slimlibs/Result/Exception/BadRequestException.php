<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Result\Exception;

use Albatiqy\Slimlibs\Result\ResultException;

class BadRequestException extends ResultException {

    const CODE = 400;
    const MESSAGE = 'Bad Request Exception';
    const TITLE = 'Bad Request Exception';
    const DESCRIPTION = '';


    protected function init() {
        $this->errType = self::T_BAD_REQUEST;
    }
}