<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs\ApiClient;

class ApiException extends \Exception {

    private $res_code;
    private $object;

    public function __construct($object) {
        $this->res_code = $object->errType;
        $this->object = $object;
        parent::__construct($object->message);
    }

    public function getObject() {
        return $this->object;
    }

    public function getResCode() {
        return $this->res_code;
    }

}