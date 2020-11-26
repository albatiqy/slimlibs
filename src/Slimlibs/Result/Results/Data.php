<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Result\Results;

use Albatiqy\Slimlibs\Result\AbstractResult;

final class Data extends AbstractResult {

    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const STATUS_ACCEPTED = 202;

    public $data = [];

    function __construct($data = [], $status = self::STATUS_OK) {
        $this->status = $status;
        $this->resType = self::RES_TYPES[self::T_DATA];
        $this->data = $data;
    }
}