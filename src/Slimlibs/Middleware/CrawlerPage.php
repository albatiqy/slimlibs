<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Albatiqy\Slimlibs\Support\Helper\Env;
use Albatiqy\Slimlibs\Container\Container;
use Slim\App;

final class CrawlerPage implements MiddlewareInterface {

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $profile = 'crawler';
        $useragent = $request->getHeaderLine('User-Agent');
        if (\strpos($useragent, 'Google') !== false) {
            //$route = $request->getAttribute('__route__');
            //$callable = $route->getCallable();
            //if ('\\'.$callable::isCacheApplied())
            if ($this->validateGoogleBotIP(Env::getClientIp())) {
                $container = Container::getInstance();
                $settings = $container->get('settings');
                $pathuri = \substr($request->getUri()->getPath(), \strlen(\BASE_PATH));
                $cacheFile = $settings['cache']['base_dir'] . '/'. $profile.'-pages' . ($pathuri != '/' ? $pathuri : '/index') . '.php';
                if (\file_exists($cacheFile)) {
                    if (\time()-\Albatiqy\Slimlibs\Actions\ViewAction::getCacheExpires() < \filemtime($cacheFile)) {
                        $app = $container->get(App::class);
                        $responseFactory = $app->getResponseFactory();
                        $response = $responseFactory->createResponse(200);
                        $response->getBody()->write(\file_get_contents($cacheFile));
                        return $response;
                    }
                }
                $request = $request->withAttribute('crawler-page', $profile);
            }
        }
        return $handler->handle($request);
    }

    private function validateGoogleBotIP($ip) {
        $hostname = \gethostbyaddr($ip); //"crawl-66-249-66-1.googlebot.com"
        return \preg_match('/\.googlebot\.com$/i', $hostname);
    }

}