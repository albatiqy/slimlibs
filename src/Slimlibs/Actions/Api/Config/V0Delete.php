<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Api\Config;

use Albatiqy\Slimlibs\Actions\ResultAction;
use Albatiqy\Slimlibs\Providers\Database\DbServiceException;
use Albatiqy\Slimlibs\Result\Results\Data;
use Albatiqy\Slimlibs\Services\Configs;

final class V0Delete extends ResultAction {

    protected function getResult(array $data, array $args) {
        try {
            $da = Configs::getInstance();
            if ($da->delete($args['id'])) {
                return new Data();
            }
        } catch (DbServiceException $dbe) {
            if ($dbe->getCode() == DbServiceException::E_NO_RESULT) {
                $this->sendNotExist();
            }
        }
    }
}