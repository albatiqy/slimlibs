<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Result\Exception;

use Albatiqy\Slimlibs\Result\ResultException;

class ValidationException extends ResultException {

    const CODE = 400;
    const MESSAGE = 'Validation Exception';
    const TITLE = 'Validation Exception';
    const DESCRIPTION = '';


    protected function init() {
        $this->errType = self::T_VALIDATION;
    }
}