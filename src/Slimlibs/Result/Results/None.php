<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Result\Results;

use Albatiqy\Slimlibs\Result\AbstractResult;

final class None extends AbstractResult {

    const STATUS_NO_CONTENT = 204;

    function __construct() {
        $this->status = self::STATUS_NO_CONTENT;
        $this->resType = self::RES_TYPES[self::T_NONE];
    }
}