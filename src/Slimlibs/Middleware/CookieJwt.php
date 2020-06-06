<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;
use Albatiqy\Slimlibs\Error\Exception\InactiveUserException;
use Albatiqy\Slimlibs\Providers\Auth\AuthInterface;

final class CookieJwt implements MiddlewareInterface {

    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $callable = $request->getAttribute('__route__')->getCallable();

        $jwt = $this->container->get('jwt');
        $token = $_COOKIE['access_token'] ?? null;

        if (!$token) {
            $this->throwUnauthorizedException($request, $callable);
        }

        $payload = [];
        try {
            $payload = $jwt->decode($token);
        } catch (\Exception $e) {
            $this->throwUnauthorizedException($request, $callable);
        }

        $auth = $this->container->get(AuthInterface::class);

        if (!$auth->isUserActive($payload['uid'])) {
            if (!$auth->isSuperUser($payload['uid'])) {
                throw new InactiveUserException($request, $payload['uid']);
            }
        }

        $request = $request->withAttribute('payload', $payload);
        $request = $request->withAttribute('uid', $payload['uid']);

        $this->container->set('payload', $payload);

        return $handler->handle($request);
    }

    private function throwUnauthorizedException($request, $callable) {
        throw new HttpUnauthorizedException($request);
    }
}