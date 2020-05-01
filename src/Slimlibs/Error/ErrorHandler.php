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
                        $uri = $this->request->getUri();
                        $response = $this->responseFactory->createResponse(302);
                        return $response->withHeader('Location', \BASE_PATH . '/login?return=' . \urlencode($uri->getPath()));
                    }
                }
            }
        }
        return parent::respond();
    }

}
