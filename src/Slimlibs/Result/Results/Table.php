<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Result\Results;

use Albatiqy\Slimlibs\Result\AbstractResult;

final class Table extends AbstractResult {

    const STATUS_OK = 200;

    public $data = [];
    public $recordsFiltered = 0;
    public $recordsTotal = 0;

    function __construct($data, $recordsFiltered, $recordsTotal, $status = self::STATUS_OK) {
        $this->status = $status;
        $this->resType = self::RES_TYPES[self::T_TABLE];
        $this->data = $data;
        $this->recordsFiltered = $recordsFiltered;
        $this->recordsTotal = $recordsTotal;
    }
}