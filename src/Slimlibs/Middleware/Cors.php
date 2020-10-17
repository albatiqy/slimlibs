<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final class Cors implements MiddlewareInterface {

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();
        $methods = $routingResults->getAllowedMethods();
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        $requestOrigin = $request->getHeaderLine('Origin');

        $response = $handler->handle($request);

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $requestOrigin ?: '*') // *
            ->withHeader('Access-Control-Allow-Methods', \implode(', ', $methods))
            ->withHeader('Access-Control-Allow-Headers', $requestHeaders ?: '*')
            ->withHeader('Access-Control-Expose-Headers', '*');

        // Optional: Allow Ajax CORS requests with Authorization header
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        //$response = $response->withHeader('Set-Cookie', 'fdsfsd=fsdfsfd; SameSite=Strict');

        return $response;
    }
}