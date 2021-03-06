<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Actions;

use Albatiqy\Slimlibs\Result\Exception;
use Psr\Container\ContainerInterface;
use Slim\Exception\HttpInternalServerErrorException;

abstract class ResultAction {

    protected $container;
    protected $request = null;
    protected $response = null;

    abstract protected function getResult(array $data, array $args);

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args) { // maintenance mode???
        $this->request = $request;
        $this->response = $response;
        $data = $request->getParsedBody() ?? [];
        $data += $request->getQueryParams();

        $result = $this->getResult($data, $args);

        if ($result==null) {
            $this->sendBadRequestError("Empty result");
        }

        $status = $result->getStatus();
        $this->response = $this->response->withStatus($status);
        if ($status != 204) {
            $result = \json_encode($result);
            $this->response->getBody()->write($result);
            $this->response = $this->response
                ->withHeader('Content-Type', 'application/json');
        }
        return $this->response;
    }

    public function __get($key) {
        return $this->container->get($key);
    }

    protected function determineContentType(ServerRequestInterface $request) {
        $acceptHeader = $request->getHeaderLine('Accept');
        $selectedContentTypes = \array_intersect(
            \explode(',', $acceptHeader),
            \array_keys($this->errorRenderers)
        );
        $count = \count($selectedContentTypes);
        if ($count) {
            $current = \current($selectedContentTypes);
            if ($current === 'text/plain' && $count > 1) {
                return \next($selectedContentTypes);
            }
            return $current;
        }
        if (\preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/' . $matches[1];
            if (\array_key_exists($mediaType, $this->errorRenderers)) {
                return $mediaType;
            }
        }
        return null;
    }

    protected function sendNotAuthorized($message = '') {
        throw new Exception\UnauthorizedException($this->request, [], $message);
    }

    protected function sendValidationError(array $errors) {
        throw new Exception\ValidationException($this->request, $errors);
    }

    protected function sendNotExist($message = '') {
        throw new Exception\NotExistException($this->request, [], $message);
    }

    protected function sendServiceError($message = '') {
        throw new Exception\ServiceException($this->request, [], $message);
    }

    protected function sendBadRequestError($message = '') {
        throw new Exception\BadRequestException($this->request, [], $message);
    }

    protected function sendServerError($message = '') {
        throw new HttpInternalServerErrorException($this->request, $message);
    }
}
