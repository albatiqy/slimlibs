<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;

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

        $request = $request->withAttribute('payload', $payload);
        $request = $request->withAttribute('uid', $payload['uid']);

        $this->container->set('payload', $payload);

        return $handler->handle($request);
    }

    private function throwUnauthorizedException($request, $callable) {
        throw new HttpUnauthorizedException($request);
    }
}