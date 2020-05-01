<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Web;

use Albatiqy\Slimlibs\Actions\ViewAction;

final class GlobalsJsGet extends ViewAction {

    protected function getResponse(array $args) {
        $this->data['settings'] = $this->container->get('settings');
        $this->response = $this->response->withHeader('Content-Type', 'text/javascript');
        return $this->render('libs::globals.js');
    }
}