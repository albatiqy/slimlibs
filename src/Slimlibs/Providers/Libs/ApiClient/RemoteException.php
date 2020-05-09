<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs\ApiClient;

class RemoteException extends \Exception {

    private $object;

    public function __construct($object) {
        $this->object = $object;
        parent::__construct("remote exception");
    }

    public function getObject() {
        return $this->object;
    }
}