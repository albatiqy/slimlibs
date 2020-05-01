<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;

final class Session implements MiddlewareInterface {

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

        if (\session_status() !== \PHP_SESSION_ACTIVE) {
            \session_cache_expire(30);
            \session_set_cookie_params(0, \BASE_PATH, $request->getUri()->getHost());
            \session_start();
        }

        $callable = $request->getAttribute('__route__')->getCallable();



        

        $response = $handler->handle($request);
        \session_write_close();

        return $response;
    }

    private function throwUnauthorizedException($request, $callable) {
        throw new HttpUnauthorizedException($request);
    }
}