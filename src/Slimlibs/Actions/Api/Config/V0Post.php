<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Api\Config;

use Albatiqy\Slimlibs\Actions\ResultAction;
use Albatiqy\Slimlibs\Providers\Validation\ValidationException;
use Albatiqy\Slimlibs\Result\Results\Data;
use Albatiqy\Slimlibs\Services\Configs;

final class V0Post extends ResultAction {

    protected function getResult(array $data, array $args) {
        try {
            $da = Configs::getInstance();
            $record = $da->create($data);
            if (\is_object($record)) {
                return new Data(['id' => $record->id], Data::STATUS_CREATED);
            }
        } catch (ValidationException $ve) {
            $this->sendValidationError($ve->getErrors());
        }
    }
}