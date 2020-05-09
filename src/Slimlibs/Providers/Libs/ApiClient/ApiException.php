<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs\ApiClient;

class ApiException extends \Exception {

    private $res_code;

    public function __construct($message, $res_code) {
        $this->res_code = $res_code;
        parent::__construct($message);
    }

    public function getResCode() {
        return $this->res_code;
    }

}