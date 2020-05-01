<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions;

use Psr\Container\ContainerInterface;

abstract class ResourceAction {

    protected $container;
    protected $request = null;
    protected $response = null;

    abstract protected function getResponse(array $data, array $args);

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args) {
        $this->request = $request;
        $this->response = $response;
        $data = $request->getParsedBody() ?? [];
        $data += $request->getQueryParams();
        return $this->getResponse($data, $args);
    }

    protected function renderJpg($path) {
        $imgSrc = \APP_DIR.'/var/resources'.$path;
        if (!\file_exists($imgSrc)) {
            $imgSrc = \LIBS_DIR.'/web/resources/blank.jpg';
        }
        return $this->renderImg('image/jpg', $imgSrc);
    }

    protected function renderPng($path) {
        $imgSrc = \APP_DIR.'/var/resources'.$path;
        if (!\file_exists($imgSrc)) {
            $imgSrc = \LIBS_DIR.'/web/resources/blank.png';
        }
        return $this->renderImg('image/png', $imgSrc);
    }

    protected function renderImg($contentType, $imgSrc) {
        $this->response->getBody()->write(\file_get_contents($imgSrc));
        return $this->response
            ->withHeader('Content-Type', $contentType)
            ->withStatus(200)
            ->withHeader('Cache-Control', 'public, max-age=86400');
    }

    public function __get($key) {
        return $this->container->get($key);
    }
}