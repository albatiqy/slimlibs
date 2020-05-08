<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Api\Config;

use Albatiqy\Slimlibs\Actions\ResultAction;
use Albatiqy\Slimlibs\Providers\Validation\ValidationException;
use Albatiqy\Slimlibs\Result\Results\Data;
use Albatiqy\Slimlibs\Services\Configs;

final class V0Put extends ResultAction { // perbaiki

    protected function getResult(array $data, array $args) {
        try {
            $da = Configs::getInstance();
            $record = $da->update($data, $args['id']);
            if (\is_object($record)) {
                return new Data();
            }
        } catch (ValidationException $ve) {
            $this->sendValidationError($ve->getErrors());
        }
    }
}