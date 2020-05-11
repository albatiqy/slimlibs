<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Api\Config;

use Albatiqy\Slimlibs\Actions\ResultAction;
use Albatiqy\Slimlibs\Providers\Validation\ValidationException;
use Albatiqy\Slimlibs\Result\Results\Data;
use Albatiqy\Slimlibs\Providers\Libs\Configs;

final class V0Put extends ResultAction {

    protected function getResult(array $data, array $args) {
        try {
            $da = $this->container->get(Configs::class);
            if ($da->set($data['key'], $data['value'])) {
                return new Data();
            }
        } catch (ValidationException $ve) {
            $this->sendValidationError($ve->getErrors());
        }
    }
}