<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Albatiqy\Slimlibs\Support\Helper\Env;

final class GoogleCrawler implements MiddlewareInterface {

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        /*
        $useragent = $request->getHeaderLine('User-Agent');
        if (\strpos($useragent, 'Google') !== false) {
            if ($this->validateGoogleBotIP(Env::getClientIp())) {
                $request = $request->withAttribute('google-crawler', 'google');
            }
        }
        */
        $request = $request->withAttribute('google-crawler', 'google');
        return $handler->handle($request);
    }

    private function validateGoogleBotIP($ip) {
        $hostname = \gethostbyaddr($ip); //"crawl-66-249-66-1.googlebot.com"
        return \preg_match('/\.googlebot\.com$/i', $hostname);
    }

}