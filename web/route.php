<?php declare(strict_types=1);

use Slim\App as SlimApp;
use Slim\Routing\RouteCollectorProxy;
use Albatiqy\Slimlibs\Middleware\Jwt;

return static function (SlimApp $app) use ($settings) {
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->group('/v0', function (RouteCollectorProxy $group) {
            $group->post('/users/login', Albatiqy\Slimlibs\Actions\Api\Login0Post::class); // <== jgn di jwt
            $group->post('/users/token', Albatiqy\Slimlibs\Actions\Api\Token0Post::class); // <== jgn di jwt
            $group->group('/sys', function (RouteCollectorProxy $group) {
                $group->group('/configs', function (RouteCollectorProxy $group) {
                    $group->put('', Albatiqy\Slimlibs\Actions\Api\Config\V0Put::class);
                    $group->group('/{key}', function (RouteCollectorProxy $group) {
                        $group->get('', Albatiqy\Slimlibs\Actions\Api\Config\V0Get::class);
                        $group->delete('', Albatiqy\Slimlibs\Actions\Api\Config\V0Delete::class);
                    });
                });
            })->add(Jwt::class);
        });
        require APP_DIR.'/routes/api.php';
    });
    $app->get('/js/modules/globals.js', Albatiqy\Slimlibs\Actions\Web\GlobalsJsGet::class); // create api global json
    $app->get($settings['login_path'], App\Actions\Web\LoginGet::class);
    $app->get('/mlogin', App\Actions\Web\Modules\LoginGet::class);
    require APP_DIR.'/routes/main.php';
};