<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Api\Config;

use Albatiqy\Slimlibs\Actions\ResultAction;
use Albatiqy\Slimlibs\Providers\Database\DbServiceException;
use Albatiqy\Slimlibs\Result\Results\Data;
use Albatiqy\Slimlibs\Result\Results\Table;
use Albatiqy\Slimlibs\Services\Configs;

final class V0Get extends ResultAction { // perbaiki

    protected function getResult(array $data, array $args) {
        try {
            $da = Configs::getInstance();
            if (isset($args['id'])) {
                return new Data($da->getById($args['id']));
            } else {
                $query = $da->findAll($data);
                return new Table($query->data, $query->recordsFiltered, $query->recordsTotal);
            }
        } catch (DbServiceException $dbe) {
            if ($dbe->getCode() == DbServiceException::E_NO_RESULT) {
                $this->sendNotExist();
            }
        }
    }
}