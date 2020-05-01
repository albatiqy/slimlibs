<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Result;

abstract class AbstractResult {

    const T_NONE = 'NONE';
    const T_DATA = 'OBJECT';
    const T_TABLE = 'OBJECTS';

    const RES_TYPES = [
        self::T_NONE => 1,
        self::T_DATA => 2,
        self::T_TABLE => 3,
    ];

    protected $status = 200;

    public $resType = 0;

    public function getStatus() {
        return $this->status;
    }
}