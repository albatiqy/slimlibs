<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions\Web;

use Albatiqy\Slimlibs\Actions\ViewAction;

final class GlobalsJsGet extends ViewAction {

    protected function getResponse(array $args) {
        $params = $this->request->getQueryParams();
        $settings = $this->container->get('settings');
        $this->data['module'] = isset($params['module']);
        $this->data['settings'] = $settings;
        $backend_path = $settings['backend_path'];
        if (isset($params['backendPath'])) {
            $backend_path = $params['backendPath'];
        }
        $this->data['backend_path'] = $backend_path;
        $this->response = $this->response->withHeader('Content-Type', 'text/javascript');
        return $this->render('libs::globals.js');
    }
}