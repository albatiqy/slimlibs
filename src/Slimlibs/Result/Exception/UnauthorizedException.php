<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Result\Exception;

use Albatiqy\Slimlibs\Result\ResultException;

class UnauthorizedException extends ResultException {

    const CODE = 401;
    const MESSAGE = 'Unauthorized Exception';
    const TITLE = '401 Unauthorized Exception';
    const DESCRIPTION = '';

    protected function init() {
        $this->errType = self::T_UNAUTHORIZED;
    }

}