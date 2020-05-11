<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Api\Config;

use Albatiqy\Slimlibs\Actions\ResultAction;
use Albatiqy\Slimlibs\Providers\Database\DbServiceException;
use Albatiqy\Slimlibs\Result\Results\Data;
use Albatiqy\Slimlibs\Providers\Libs\Configs;

final class V0Get extends ResultAction {

    protected function getResult(array $data, array $args) {
        try {
            $da = $this->container->get(Configs::class);
            return new Data([$args['key'] => $da->get($args['key'])]);
        } catch (DbServiceException $dbe) {
            if ($dbe->getCode() == DbServiceException::E_NO_RESULT) {
                $this->sendNotExist();
            }
        }
    }
}