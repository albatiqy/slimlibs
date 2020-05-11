<?php declare (strict_types = 1);

namespace Albatiqy\Slimlibs\Error;

use Albatiqy\Slimlibs\Error\HtmlErrorRenderer;
use Albatiqy\Slimlibs\Error\JsonResultErrorRenderer;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;

class ErrorHandler extends SlimErrorHandler {

    private $container;

    public function __construct($callableResolver, $responseFactory, $container) {
        $this->container = $container;
        parent::__construct($callableResolver, $responseFactory);
        $this->registerErrorRenderer('application/json', JsonResultErrorRenderer::class);
        $this->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
    }

    protected function logError(string $error): void {
        $logger = $this->container->get('monolog');
        $logger->error($error);
    }

    protected function respond(): ResponseInterface {
        if ($this->exception instanceof HttpException) {
            if ($this->contentType == 'text/html') {
                $accepts = \explode(',', $this->request->getHeader('Accept')[0]);
                if (\count($accepts) > 1) {
                    if ($this->exception->getCode() == 401) {
                        $callable = $this->request->getAttribute('__route__')->getCallable();
                        $uri = $this->request->getUri();
                        $loginuri = '/login?return=' . \urlencode($uri->getPath());
                        if (\strpos($callable, 'App\\Actions\\Web\Modules')===0) {
                            $loginuri = '/mlogin?return=' . \urlencode($uri->getPath());
                        }
                        $response = $this->responseFactory->createResponse(302);
                        return $response->withHeader('Location', \BASE_PATH . $loginuri);
                    }
                }
            }
        }
        return parent::respond();
    }

}
