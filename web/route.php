<?php declare(strict_types=1);

use Slim\App as SlimApp;
use Slim\Routing\RouteCollectorProxy;
use Albatiqy\Slimlibs\Middleware\Jwt;

return static function (SlimApp $app) use ($settings) {
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->group('/v0', function (RouteCollectorProxy $group) {
            $group->post('/users/login', Albatiqy\Slimlibs\Actions\Api\Login0Post::class); // <== jgn di jwt
            $group->post('/users/token', Albatiqy\Slimlibs\Actions\Api\Token0Post::class); // <== jgn di jwt
            $group->group('', function (RouteCollectorProxy $group) {
                $group->group('/sys', function (RouteCollectorProxy $group) {
                    $group->group('/configs', function (RouteCollectorProxy $group) {
                        $group->put('', Albatiqy\Slimlibs\Actions\Api\Config\V0Put::class);
                        $group->group('/{key}', function (RouteCollectorProxy $group) {
                            $group->get('', Albatiqy\Slimlibs\Actions\Api\Config\V0Get::class);
                            $group->delete('', Albatiqy\Slimlibs\Actions\Api\Config\V0Delete::class);
                        });
                    });
                });
                $group->group('/module', function (RouteCollectorProxy $group) {
                    $group->group('/media', function (RouteCollectorProxy $group) {
                        $group->get('/browse', Albatiqy\Slimlibs\Actions\Api\Media\Browse0Get::class);
                        $group->get('/recentfiles', Albatiqy\Slimlibs\Actions\Api\Media\RecentFiles0Get::class);
                    });
                });
                $group->group('/auth', function (RouteCollectorProxy $group) {
                    $group->group('/users', function (RouteCollectorProxy $group) {
                        $group->get('', App\Actions\Api\Auth\User\V0Get::class);
                        $group->group('/{id:U\d{9}}', function (RouteCollectorProxy $group) {
                            $group->get('', App\Actions\Api\Auth\User\V0Get::class);
                            $group->put('', App\Actions\Api\Auth\User\V0Put::class);
                            $group->delete('', App\Actions\Api\Auth\User\V0Delete::class);
                        });
                    });
                });
            })->add(Jwt::class);
        });
        require APP_DIR.'/routes/api.php';
    });
    $app->get('/js/modules/globals.js', Albatiqy\Slimlibs\Actions\Web\GlobalsJsGet::class); // create api global json
    $app->get($settings['login_path'], App\Actions\Web\LoginGet::class);
    $app->get('/mlogin', App\Actions\Web\Modules\LoginGet::class);
    $app->group('/resources', function (RouteCollectorProxy $group) {
        $group->get('/imgcache/{id}', Albatiqy\Slimlibs\Actions\Resource\ImageCacheGet::class);
        $group->group('/media', function (RouteCollectorProxy $group) {
            $group->get('/{path:.*}', Albatiqy\Slimlibs\Actions\Resource\MediaGet::class);
        });
    });
    require APP_DIR.'/routes/main.php';
};