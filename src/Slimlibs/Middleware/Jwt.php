<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Albatiqy\Slimlibs\Result\Exception\UnauthorizedException;
use Albatiqy\Slimlibs\Providers\Auth\AuthInterface;

final class Jwt implements MiddlewareInterface {

    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

        $jwt = $this->container->get('jwt');
        $authorization = \explode(' ', (string) $request->getHeaderLine('Authorization'));
        $token = $authorization[1] ?? '';

        $callable = $request->getAttribute('__route__')->getCallable();

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
                throw new UnauthorizedException($request, [], 'Inactive User');
            }
        }

        // Append valid token
        $request = $request->withAttribute('payload', $payload);

        // Append the user id as request attribute
        $request = $request->withAttribute('uid', $payload['uid']);

        $this->container->set('payload', $payload);

        return $handler->handle($request);
    }

    private function throwUnauthorizedException($request, $callable) {
        if (\is_subclass_of($callable, \Albatiqy\Slimlibs\Actions\ResultAction::class)) {
            $request = $request->withHeader('Accept', 'application/json'); //==================untk skrg hanya menerima json
        }
        throw new UnauthorizedException($request, [], 'Token invalid');
    }

    private function checkRole($callable) {

    }
}